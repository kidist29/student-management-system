<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Courses';
$activeNav = 'courses';
$isAjax = isset($_GET['ajax']);

$search       = trim($_GET['q'] ?? '');
$departmentId = $_GET['department_id'] ?? '';
$semester     = $_GET['semester'] ?? '';
$status       = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(c.course_code LIKE ? OR c.course_name LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like);
}
if ($departmentId !== '') {
    $where[] = 'c.department_id = ?';
    $params[] = $departmentId;
}
if ($semester !== '') {
    $where[] = 'c.semester = ?';
    $params[] = $semester;
}
if ($status !== '') {
    $where[] = 'c.status = ?';
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sortColumns = [
    'code'       => 'c.course_code',
    'name'       => 'c.course_name',
    'credits'    => 'c.credit_hours',
    'department' => 'd.department_name',
    'semester'   => 'c.semester',
    'status'     => 'c.status',
];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$orderBy = safeOrderBy($sortColumns, $sortKey, 'code', $sortDir);

$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$countStmt = $db->prepare("SELECT COUNT(*) FROM courses c LEFT JOIN departments d ON d.id = c.department_id $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT c.*, d.department_name, t.first_name AS teacher_first, t.last_name AS teacher_last
    FROM courses c
    LEFT JOIN departments d ON d.id = c.department_id
    LEFT JOIN teachers t ON t.id = c.teacher_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$courses = $stmt->fetchAll();

$departments = $db->query('SELECT id, department_name FROM departments ORDER BY department_name')->fetchAll();

ob_start();
?>
<div class="card">
    <div class="card-body" style="padding-bottom:0;">
        <form method="get" class="toolbar" style="margin-bottom:16px;">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="form-control" placeholder="Search code or name…" value="<?= h($search) ?>">
            </div>
            <div class="filter-bar">
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= (string) $departmentId === (string) $d['id'] ? 'selected' : '' ?>><?= h($d['department_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="semester" class="form-control">
                    <option value="">All Semesters</option>
                    <?php foreach (['1st Semester', '2nd Semester', 'Summer'] as $sem): ?>
                        <option value="<?= $sem ?>" <?= $semester === $sem ? 'selected' : '' ?>><?= $sem ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach (['Active', 'Inactive'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                <?php if ($search || $departmentId !== '' || $semester !== '' || $status !== ''): ?>
                    <a href="<?= BASE_URL ?>admin/courses/index.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'courses') ?></div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= sortableHeader('Code', 'code', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Course', 'name', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Credits', 'credits', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Department', 'department', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Semester', 'semester', $sortKey, $sortDir) ?></th>
                    <th>Teacher</th>
                    <th><?= sortableHeader('Status', 'status', $sortKey, $sortDir) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$courses): ?>
                <?= renderEmptyState(8, 'fa-book', 'No courses found', 'Try a different search or filter, or add a new course to get started.', '<a href="' . BASE_URL . 'admin/courses/create.php" class="btn btn-primary btn-sm">Add Course</a>') ?>
            <?php endif; ?>
            <?php foreach ($courses as $c): ?>
                <tr>
                    <td><strong><?= h($c['course_code']) ?></strong></td>
                    <td><?= h($c['course_name']) ?></td>
                    <td><?= h(rtrim(rtrim(number_format((float) $c['credit_hours'], 1), '0'), '.')) ?></td>
                    <td><?= h($c['department_name'] ?? '—') ?></td>
                    <td><?= h($c['semester']) ?></td>
                    <td><?= $c['teacher_first'] ? h($c['teacher_first'] . ' ' . $c['teacher_last']) : '—' ?></td>
                    <td><?= statusBadge($c['status']) ?></td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/courses/edit.php?id=<?= (int) $c['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                    data-delete-url="<?= BASE_URL ?>admin/courses/delete.php?id=<?= (int) $c['id'] ?>"
                                    data-delete-name="<?= h($c['course_name']) ?>">
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Courses</div>
                    <h1>Courses</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/courses/create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Course</a>
            </div>

            <div id="coursesLiveTable" data-live-table>
                <?= $fragment ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="deleteConfirmModal">
    <div class="modal">
        <div class="modal-header"><h3>Delete course</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body"><p style="margin:0;">Are you sure you want to delete <strong id="deleteConfirmName"></strong>? This cannot be undone.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
