<?php
/**
 * Shared helper functions used by the admin CRUD modules.
 * Requires auth_check.php (for h(), getDB()) to already be loaded.
 */

/** Render a status pill for students/teachers/departments/courses. */
function statusBadge(string $status): string
{
    $map = [
        'Active'    => 'badge-active',
        'Inactive'  => 'badge-inactive',
        'Graduated' => 'badge-graduated',
    ];
    $cls = $map[$status] ?? 'badge-inactive';
    return '<span class="badge ' . $cls . '">' . h($status) . '</span>';
}

/** Build a <select> options list of departments. */
function departmentOptions(PDO $db, $selectedId = null): string
{
    $rows = $db->query('SELECT id, department_name FROM departments ORDER BY department_name')->fetchAll();
    $html = '';
    foreach ($rows as $row) {
        $sel = ((string) $selectedId === (string) $row['id']) ? 'selected' : '';
        $html .= '<option value="' . (int) $row['id'] . '" ' . $sel . '>' . h($row['department_name']) . '</option>';
    }
    return $html;
}

/** Build a <select> options list of teachers. */
function teacherOptions(PDO $db, $selectedId = null): string
{
    $rows = $db->query('SELECT id, first_name, last_name FROM teachers ORDER BY first_name')->fetchAll();
    $html = '';
    foreach ($rows as $row) {
        $sel = ((string) $selectedId === (string) $row['id']) ? 'selected' : '';
        $html .= '<option value="' . (int) $row['id'] . '" ' . $sel . '>' . h($row['first_name'] . ' ' . $row['last_name']) . '</option>';
    }
    return $html;
}

/**
 * Render pagination links, preserving the current query string (minus "page").
 * $totalPages must be >= 1.
 */
function renderPagination(int $currentPage, int $totalPages): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $params = $_GET;
    $urlFor = function (int $page) use ($params) {
        $params['page'] = $page;
        return '?' . http_build_query($params);
    };

    $html = '<div class="pagination">';

    $prevCls = $currentPage <= 1 ? 'disabled' : '';
    $html .= '<a class="' . $prevCls . '" href="' . h($urlFor(max(1, $currentPage - 1))) . '"><i class="fa-solid fa-chevron-left"></i></a>';

    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    if ($start > 1) {
        $html .= '<a href="' . h($urlFor(1)) . '">1</a>';
        if ($start > 2) $html .= '<span>&hellip;</span>';
    }
    for ($p = $start; $p <= $end; $p++) {
        $activeCls = $p === $currentPage ? 'active' : '';
        $html .= '<a class="' . $activeCls . '" href="' . h($urlFor($p)) . '">' . $p . '</a>';
    }
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span>&hellip;</span>';
        $html .= '<a href="' . h($urlFor($totalPages)) . '">' . $totalPages . '</a>';
    }

    $nextCls = $currentPage >= $totalPages ? 'disabled' : '';
    $html .= '<a class="' . $nextCls . '" href="' . h($urlFor(min($totalPages, $currentPage + 1))) . '"><i class="fa-solid fa-chevron-right"></i></a>';

    $html .= '</div>';
    return $html;
}

/**
 * Validate and store an uploaded photo. Returns the stored relative filename
 * (e.g. "students/abc123.jpg") on success, null if no file was uploaded,
 * or throws a RuntimeException with a user-facing message on invalid input.
 */
function handlePhotoUpload(array $file, string $subfolder): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The photo failed to upload. Please try again.');
    }

    $maxBytes = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('The photo must be smaller than 2MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, or WEBP photos are allowed.');
    }

    $dir = __DIR__ . '/../assets/uploads/' . $subfolder;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $destination = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded photo.');
    }

    return $subfolder . '/' . $filename;
}

/** Delete a previously uploaded photo file, if it exists. */
function deletePhotoFile(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }
    $full = __DIR__ . '/../assets/uploads/' . $relativePath;
    if (is_file($full)) {
        @unlink($full);
    }
}

/** The grading scale used across Grades and Transcript. Change here to retune it everywhere. */
function gradeScale(): array
{
    return [
        'A' => 4.00, 'A-' => 3.75, 'B+' => 3.50, 'B' => 3.00, 'B-' => 2.75,
        'C+' => 2.50, 'C' => 2.00, 'C-' => 1.75, 'D+' => 1.50, 'D' => 1.00, 'F' => 0.00,
    ];
}

/**
 * Automatically derive a letter grade and grade point from a numeric score
 * (0-100). This is the single place that defines the score bands — change
 * the thresholds here to retune grading for the whole system.
 * Returns ['letter' => string, 'point' => float].
 */
function scoreToGrade(float $score): array
{
    $bands = [
        ['min' => 90, 'letter' => 'A'],
        ['min' => 85, 'letter' => 'A-'],
        ['min' => 80, 'letter' => 'B+'],
        ['min' => 75, 'letter' => 'B'],
        ['min' => 70, 'letter' => 'B-'],
        ['min' => 65, 'letter' => 'C+'],
        ['min' => 60, 'letter' => 'C'],
        ['min' => 55, 'letter' => 'C-'],
        ['min' => 50, 'letter' => 'D+'],
        ['min' => 45, 'letter' => 'D'],
        ['min' => 0,  'letter' => 'F'],
    ];
    $scale = gradeScale();
    foreach ($bands as $band) {
        if ($score >= $band['min']) {
            return ['letter' => $band['letter'], 'point' => $scale[$band['letter']]];
        }
    }
    return ['letter' => 'F', 'point' => $scale['F']];
}

/** Given rows with 'credit_hours' and 'grade_point', compute total credits, points, and GPA. */
function calculateGpa(array $rows): array
{
    $totalCredits = 0.0;
    $totalPoints = 0.0;
    foreach ($rows as $row) {
        $credits = (float) $row['credit_hours'];
        $totalCredits += $credits;
        $totalPoints += $credits * (float) $row['grade_point'];
    }
    $gpa = $totalCredits > 0 ? $totalPoints / $totalCredits : 0.0;
    return ['total_credits' => $totalCredits, 'total_points' => $totalPoints, 'gpa' => round($gpa, 2)];
}

/** A reasonable default academic year string, e.g. "2026/2027", based on today's date. */
function currentAcademicYear(): string
{
    $month = (int) date('n');
    $year = (int) date('Y');
    // Academic year rolls over in September in this system's calendar.
    return $month >= 9 ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
}

/** Build a <select> options list of students, labeled "Name (STU-ID)". */
function studentOptions(PDO $db, $selectedId = null): string
{
    $rows = $db->query('SELECT id, student_id, first_name, last_name FROM students ORDER BY first_name')->fetchAll();
    $html = '';
    foreach ($rows as $row) {
        $sel = ((string) $selectedId === (string) $row['id']) ? 'selected' : '';
        $label = $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['student_id'] . ')';
        $html .= '<option value="' . (int) $row['id'] . '" ' . $sel . '>' . h($label) . '</option>';
    }
    return $html;
}

/** Build a <select> options list of courses, labeled "CODE — Name". */
function courseOptions(PDO $db, $selectedId = null): string
{
    $rows = $db->query('SELECT id, course_code, course_name FROM courses ORDER BY course_code')->fetchAll();
    $html = '';
    foreach ($rows as $row) {
        $sel = ((string) $selectedId === (string) $row['id']) ? 'selected' : '';
        $label = $row['course_code'] . ' — ' . $row['course_name'];
        $html .= '<option value="' . (int) $row['id'] . '" ' . $sel . '>' . h($label) . '</option>';
    }
    return $html;
}

/** Render a fully-formed, performance-optimized <img> tag from the public image registry. */
function renderPublicImage(string $key, string $extraAttrs = ''): string
{
    static $registry = null;
    if ($registry === null) {
        $registry = require __DIR__ . '/images.php';
    }
    if (!isset($registry[$key])) {
        return '';
    }
    $img = $registry[$key];
    $loading = !empty($img['eager']) ? 'eager' : 'lazy';
    return '<img src="' . h($img['src']) . '" alt="' . h($img['alt']) . '" '
        . 'width="' . (int) $img['width'] . '" height="' . (int) $img['height'] . '" '
        . 'loading="' . $loading . '" decoding="async" ' . $extraAttrs . '>';
}

/** Render a small colored pill for an enrollment status. */
function enrollmentStatusBadge(string $status): string
{
    $map = ['Enrolled' => 'badge-active', 'Completed' => 'badge-graduated', 'Dropped' => 'badge-inactive'];
    $cls = $map[$status] ?? 'badge-inactive';
    return '<span class="badge ' . $cls . '">' . h($status) . '</span>';
}

/** Resolve a stored photo path (or null) to a displayable URL, with a fallback avatar. */
function photoUrl(?string $relativePath, string $displayName): string
{
    if ($relativePath) {
        return BASE_URL . 'assets/uploads/' . $relativePath;
    }
    return initialsAvatar($displayName, '#e4e1d8', '#1b2a4a');
}

/**
 * Generate a colored-initials avatar entirely locally as an inline SVG data
 * URI — no network request, so it can never show as a broken image the way
 * a remote avatar-generation service can.
 */
function initialsAvatar(string $displayName, string $bg = '#e4e1d8', string $fg = '#1b2a4a'): string
{
    $words = preg_split('/\s+/', trim($displayName));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        if ($word !== '') {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
    }
    if ($initials === '') {
        $initials = '?';
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
        . '<rect width="100" height="100" rx="50" fill="' . rawurlencode($bg) . '"/>'
        . '<text x="50" y="52" font-family="Inter,Arial,sans-serif" font-size="38" font-weight="600" '
        . 'fill="' . rawurlencode($fg) . '" text-anchor="middle" dominant-baseline="middle">'
        . rawurlencode($initials) . '</text></svg>';
    return 'data:image/svg+xml,' . $svg;
}

/**
 * Render an <img> tag from the central public-image registry (includes/images.php).
 * Always includes width/height (prevents layout shift) and lazy-loads unless
 * the entry is marked 'eager' (above-the-fold images).
 */
function publicImage(string $key, string $class = ''): string
{
    static $registry = null;
    if ($registry === null) {
        $registry = require __DIR__ . '/images.php';
    }
    if (!isset($registry[$key])) {
        return '';
    }
    $img = $registry[$key];
    $loading = !empty($img['eager']) ? 'eager' : 'lazy';
    $classAttr = $class !== '' ? ' class="' . h($class) . '"' : '';
    return '<img src="' . h($img['src']) . '" alt="' . h($img['alt']) . '" '
        . 'width="' . (int) $img['width'] . '" height="' . (int) $img['height'] . '" '
        . 'loading="' . $loading . '" decoding="async"' . $classAttr . '>';
}

/**
 * Validate a user-supplied sort column against a whitelist, returning a safe
 * ORDER BY fragment. Never interpolate $_GET['sort'] into SQL directly —
 * this is the one place that's allowed to happen, because it's checked
 * against $allowedColumns first.
 *
 * $allowedColumns maps the public sort key (used in the URL) to the real
 * SQL column expression, e.g. ['name' => 's.first_name', 'id' => 's.student_id'].
 */
function safeOrderBy(array $allowedColumns, ?string $sort, string $defaultKey, string $dir): string
{
    $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
    $column = ($sort !== null && isset($allowedColumns[$sort])) ? $allowedColumns[$sort] : $allowedColumns[$defaultKey];
    return $column . ' ' . $dir;
}

/**
 * Render a clickable, sortable column header. Preserves every other query
 * parameter (search, filters, page) and toggles direction when the same
 * column is clicked again.
 */
function sortableHeader(string $label, string $sortKey, ?string $currentSort, string $currentDir): string
{
    $isActive = $currentSort === $sortKey;
    $nextDir = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';

    $params = $_GET;
    $params['sort'] = $sortKey;
    $params['dir'] = $nextDir;
    unset($params['page']); // changing sort resets to page 1

    $icon = 'fa-sort';
    if ($isActive) {
        $icon = $currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    }

    return '<a href="?' . h(http_build_query($params)) . '" class="sortable-th' . ($isActive ? ' active' : '') . '">'
        . h($label) . ' <i class="fa-solid ' . $icon . '"></i></a>';
}

/** "Showing 1–8 of 23 students" — always call after pagination math is done. */
function renderRecordCount(int $page, int $perPage, int $totalRows, string $label): string
{
    if ($totalRows === 0) {
        return 'No ' . h($label) . ' found';
    }
    $start = ($page - 1) * $perPage + 1;
    $end = min($page * $perPage, $totalRows);
    return 'Showing <strong>' . $start . '&ndash;' . $end . '</strong> of <strong>' . number_format($totalRows) . '</strong> ' . h($label);
}

/** Render a proper empty-state block (icon + title + subtitle + optional CTA) for a table's colspan row. */
function renderEmptyState(int $colspan, string $icon, string $title, string $subtitle, string $ctaHtml = ''): string
{
    return '<tr><td colspan="' . $colspan . '">'
        . '<div class="empty-state">'
        . '<div class="icon"><i class="fa-solid ' . h($icon) . '"></i></div>'
        . '<div class="title">' . h($title) . '</div>'
        . '<div class="subtitle">' . h($subtitle) . '</div>'
        . $ctaHtml
        . '</div></td></tr>';
}

/** Render a colored pill for a user's role. */
function roleBadge(string $role): string
{
    $map = [
        'super_admin' => ['cls' => 'badge-graduated', 'label' => 'Super Admin'],
        'admin'       => ['cls' => 'badge-active',    'label' => 'Registrar'],
        'teacher'     => ['cls' => 'badge-active',    'label' => 'Teacher'],
        'student'     => ['cls' => 'badge-inactive',  'label' => 'Student'],
    ];
    $r = $map[$role] ?? ['cls' => 'badge-inactive', 'label' => $role];
    return '<span class="badge ' . $r['cls'] . '">' . h($r['label']) . '</span>';
}

/** Teachers not yet linked to a login account (plus the currently-linked one, when editing). */
function availableTeacherOptions(PDO $db, $selectedId = null, ?int $excludeUserId = null): string
{
    $sql = "SELECT id, first_name, last_name, teacher_code FROM teachers
            WHERE id NOT IN (SELECT teacher_id FROM users WHERE teacher_id IS NOT NULL" .
            ($excludeUserId ? ' AND id != ' . (int) $excludeUserId : '') . ")
            ORDER BY first_name";
    $rows = $db->query($sql)->fetchAll();
    $html = '';
    foreach ($rows as $row) {
        $sel = ((string) $selectedId === (string) $row['id']) ? 'selected' : '';
        $label = $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['teacher_code'] . ')';
        $html .= '<option value="' . (int) $row['id'] . '" ' . $sel . '>' . h($label) . '</option>';
    }
    return $html;
}

/** Students not yet linked to a login account (plus the currently-linked one, when editing). */
function availableStudentOptions(PDO $db, $selectedId = null, ?int $excludeUserId = null): string
{
    $sql = "SELECT id, first_name, last_name, student_id FROM students
            WHERE id NOT IN (SELECT student_id FROM users WHERE student_id IS NOT NULL" .
            ($excludeUserId ? ' AND id != ' . (int) $excludeUserId : '') . ")
            ORDER BY first_name";
    $rows = $db->query($sql)->fetchAll();
    $html = '';
    foreach ($rows as $row) {
        $sel = ((string) $selectedId === (string) $row['id']) ? 'selected' : '';
        $label = $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['student_id'] . ')';
        $html .= '<option value="' . (int) $row['id'] . '" ' . $sel . '>' . h($label) . '</option>';
    }
    return $html;
}

/** Loose but real phone validation: digits, spaces, +, -, (), 7-20 chars total. */
function isValidPhone(string $phone): bool
{
    return (bool) preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $phone);
}

