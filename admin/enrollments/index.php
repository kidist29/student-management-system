<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Enrollments';
$activeNav = 'enrollments';
$isAjax = isset($_GET['ajax']);

$search       = trim($_GET['q'] ?? '');
$courseId     = $_GET['course_id'] ?? '';
$semester     = $_GET['semester'] ?? '';
$academicYear = $_GET['academic_year'] ?? '';
$status       = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR c.course_code LIKE ? OR c.course_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($courseId !== '') { $where[] = 'e.course_id = ?'; $params[] = $courseId; }
if ($semester !== '') { $where[] = 'e.semester = ?'; $params[] = $semester; }
if ($academicYear !== '') { $where[] = 'e.academic_year = ?'; $params[] = $academicYear; }
if ($status !== '') { $where[] = 'e.status = ?'; $params[] = $status; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sortColumns = [
    'student'  => 's.first_name',
    'course'   => 'c.course_code',
    'semester' => 'e.semester',
    'year'     => 'e.academic_year',
    'enrolled' => 'e.enrollment_date',
    'status'   => 'e.status',
];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$orderBy = $sortKey ? safeOrderBy($sortColumns, $sortKey, 'enrolled', $sortDir) : 'e.enrollment_date DESC, e.id DESC';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$countStmt = $db->prepare("
    SELECT COUNT(*) FROM enrollments e
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
    SELECT e.*, s.first_name, s.last_name, s.student_id AS student_code,
           c.course_code, c.course_name,
           g.id AS grade_id, g.grade_letter
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

$courses = $db->query('SELECT id, course_code, course_name FROM courses ORDER BY course_code')->fetchAll();
$academicYears = $db->query('SELECT DISTINCT academic_year FROM enrollments ORDER BY academic_year DESC')->fetchAll(PDO::FETCH_COLUMN);

ob_start();
?>
<div class="card">
    <div class="card-body" style="padding-bottom:0;">
        <form method="get" class="toolbar" style="margin-bottom:16px;">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="form-control" placeholder="Search student or course…" value="<?= h($search) ?>">
            </div>
            <div class="filter-bar">
                <select name="course_id" class="form-control">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string) $courseId === (string) $c['id'] ? 'selected' : '' ?>><?= h($c['course_code']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="semester" class="form-control">
                    <option value="">All Semesters</option>
                    <?php foreach (['1st Semester', '2nd Semester', 'Summer'] as $sem): ?>
                        <option value="<?= $sem ?>" <?= $semester === $sem ? 'selected' : '' ?>><?= $sem ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="academic_year" class="form-control">
                    <option value="">All Years</option>
                    <?php foreach ($academicYears as $ay): ?>
                        <option value="<?= h($ay) ?>" <?= $academicYear === $ay ? 'selected' : '' ?>><?= h($ay) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach (['Enrolled', 'Completed', 'Dropped'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                <?php if ($search || $courseId !== '' || $semester !== '' || $academicYear !== '' || $status !== ''): ?>
                    <a href="<?= BASE_URL ?>admin/enrollments/index.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'enrollments') ?></div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= sortableHeader('Student', 'student', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Course', 'course', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Term', 'semester', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Enrolled On', 'enrolled', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Status', 'status', $sortKey, $sortDir) ?></th>
                    <th>Grade</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$enrollments): ?>
                <?= renderEmptyState(7, 'fa-clipboard-list', 'No enrollments found', 'Try a different search or filter, or enroll a student in a course.', '<a href="' . BASE_URL . 'admin/enrollments/create.php" class="btn btn-primary btn-sm">Enroll Student</a>') ?>
            <?php endif; ?>
            <?php foreach ($enrollments as $e): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>admin/students/view.php?id=<?= (int) $e['student_id'] ?>" style="color:inherit;">
                            <div style="font-weight:600;"><?= h($e['first_name'] . ' ' . $e['last_name']) ?></div>
                            <div style="font-size:0.78rem;color:var(--ink-400);"><?= h($e['student_code']) ?></div>
                        </a>
                    </td>
                    <td><strong><?= h($e['course_code']) ?></strong><br><span style="font-size:0.82rem;color:var(--ink-400);"><?= h($e['course_name']) ?></span></td>
                    <td><?= h($e['semester']) ?><br><span style="font-size:0.78rem;color:var(--ink-400);"><?= h($e['academic_year']) ?></span></td>
                    <td><?= h(date('M j, Y', strtotime($e['enrollment_date']))) ?></td>
                    <td><?= enrollmentStatusBadge($e['status']) ?></td>
                    <td>
                        <?php if ($e['grade_letter']): ?>
                            <span class="badge badge-graduated"><?= h($e['grade_letter']) ?></span>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>admin/grades/create.php?enrollment_id=<?= (int) $e['id'] ?>" class="btn btn-ghost btn-sm">Add grade</a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/enrollments/edit.php?id=<?= (int) $e['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                    data-delete-url="<?= BASE_URL ?>admin/enrollments/delete.php?id=<?= (int) $e['id'] ?>"
                                    data-delete-name="<?= h($e['first_name'] . ' ' . $e['last_name'] . ' — ' . $e['course_code']) ?>">
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
<?php
$fragment = ob_get_clean();

if ($isAjax) {
    echo $fragment;
    exit;
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Enrollments</div>
                    <h1>Enrollments</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/enrollments/create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Enroll Student</a>
            </div>

            <div id="enrollmentsLiveTable" data-live-table>
                <?= $fragment ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="deleteConfirmModal">
    <div class="modal">
        <div class="modal-header"><h3>Remove enrollment</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body"><p style="margin:0;">Remove <strong id="deleteConfirmName"></strong> from this course? Any grade recorded for it will also be removed.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Remove</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
