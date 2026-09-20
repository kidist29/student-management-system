<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Grades';
$activeNav = 'grades';

$search       = trim($_GET['q'] ?? '');
$courseId     = $_GET['course_id'] ?? '';
$semester     = $_GET['semester'] ?? '';
$academicYear = $_GET['academic_year'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR c.course_code LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($courseId !== '') { $where[] = 'e.course_id = ?'; $params[] = $courseId; }
if ($semester !== '') { $where[] = 'e.semester = ?'; $params[] = $semester; }
if ($academicYear !== '') { $where[] = 'e.academic_year = ?'; $params[] = $academicYear; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sortColumns = [
    'student' => 's.first_name',
    'course'  => 'c.course_code',
    'score'   => 'g.score',
    'grade'   => 'g.grade_point',
];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$orderBy = $sortKey ? safeOrderBy($sortColumns, $sortKey, 'student', $sortDir) : 'g.created_at DESC';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$countStmt = $db->prepare("
    SELECT COUNT(*) FROM grades g
    JOIN enrollments e ON e.id = g.enrollment_id
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    $whereSql
");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT g.*, e.academic_year, e.semester, e.student_id AS student_pk, e.course_id AS course_pk,
           s.first_name, s.last_name, s.student_id AS student_code,
           c.course_code, c.course_name, c.credit_hours
    FROM grades g
    JOIN enrollments e ON e.id = g.enrollment_id
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$grades = $stmt->fetchAll();

$courses = $db->query('SELECT id, course_code FROM courses ORDER BY course_code')->fetchAll();
$academicYears = $db->query('SELECT DISTINCT academic_year FROM enrollments ORDER BY academic_year DESC')->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Grades</div>
                    <h1>Grades</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/grades/create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Record Grade</a>
            </div>

            <div class="card">
                <div class="card-body" style="padding-bottom:0;">
                    <form method="get" class="toolbar" style="margin-bottom:20px;">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="q" class="form-control" placeholder="Search student or course…" value="<?= h($search) ?>">
                        </div>
                        <div class="filter-bar">
                            <select name="course_id" class="form-control" onchange="this.form.submit()">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (string) $courseId === (string) $c['id'] ? 'selected' : '' ?>><?= h($c['course_code']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="semester" class="form-control" onchange="this.form.submit()">
                                <option value="">All Semesters</option>
                                <?php foreach (['1st Semester', '2nd Semester', 'Summer'] as $sem): ?>
                                    <option value="<?= $sem ?>" <?= $semester === $sem ? 'selected' : '' ?>><?= $sem ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="academic_year" class="form-control" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                <?php foreach ($academicYears as $ay): ?>
                                    <option value="<?= h($ay) ?>" <?= $academicYear === $ay ? 'selected' : '' ?>><?= h($ay) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                            <?php if ($search || $courseId !== '' || $semester !== '' || $academicYear !== ''): ?>
                                <a href="<?= BASE_URL ?>admin/grades/index.php" class="btn btn-ghost btn-sm">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'grades') ?></div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th><?= sortableHeader('Student', 'student', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Course', 'course', $sortKey, $sortDir) ?></th><th>Term</th><th>Credits</th><th><?= sortableHeader('Score', 'score', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Grade', 'grade', $sortKey, $sortDir) ?></th><th>Points</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php if (!$grades): ?>
                            <?= renderEmptyState(8, 'fa-award', 'No grades found', 'Try a different search or filter, or record a grade for an enrolled student.', '<a href="' . BASE_URL . 'admin/grades/create.php" class="btn btn-primary btn-sm">Record Grade</a>') ?>
                        <?php endif; ?>
                        <?php foreach ($grades as $g): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;"><?= h($g['first_name'] . ' ' . $g['last_name']) ?></div>
                                    <div style="font-size:0.78rem;color:var(--ink-400);"><?= h($g['student_code']) ?></div>
                                </td>
                                <td><strong><?= h($g['course_code']) ?></strong><br><span style="font-size:0.82rem;color:var(--ink-400);"><?= h($g['course_name']) ?></span></td>
                                <td><?= h($g['semester']) ?><br><span style="font-size:0.78rem;color:var(--ink-400);"><?= h($g['academic_year']) ?></span></td>
                                <td><?= h(rtrim(rtrim(number_format((float) $g['credit_hours'], 1), '0'), '.')) ?></td>
                                <td><?= $g['score'] !== null ? number_format((float) $g['score'], 1) : '—' ?></td>
                                <td><span class="badge badge-graduated"><?= h($g['grade_letter']) ?></span></td>
                                <td><?= number_format((float) $g['grade_point'], 2) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/grades/edit.php?id=<?= (int) $g['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                                data-delete-url="<?= BASE_URL ?>admin/grades/delete.php?id=<?= (int) $g['id'] ?>"
                                                data-delete-name="<?= h($g['first_name'] . ' ' . $g['last_name'] . ' — ' . $g['course_code']) ?>">
                                            <i class="fa-solid fa-trash" style="color:var(--danger);"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-body"><?= renderPagination($page, $totalPages) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="deleteConfirmModal">
    <div class="modal">
        <div class="modal-header"><h3>Delete grade</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body"><p style="margin:0;">Delete the grade for <strong id="deleteConfirmName"></strong>? This cannot be undone.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
