<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Departments';
$activeNav = 'departments';

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(department_code LIKE ? OR department_name LIKE ? OR department_head LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sortColumns = [
    'code'     => 'd.department_code',
    'name'     => 'd.department_name',
    'students' => 'student_count',
    'teachers' => 'teacher_count',
    'courses'  => 'course_count',
    'status'   => 'd.status',
];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$orderBy = safeOrderBy($sortColumns, $sortKey, 'name', $sortDir);

$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$countStmt = $db->prepare("SELECT COUNT(*) FROM departments $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT d.*,
        (SELECT COUNT(*) FROM students s WHERE s.department_id = d.id) AS student_count,
        (SELECT COUNT(*) FROM teachers t WHERE t.department_id = d.id) AS teacher_count,
        (SELECT COUNT(*) FROM courses c WHERE c.department_id = d.id) AS course_count
    FROM departments d
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$departments = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Departments</div>
                    <h1>Departments</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/departments/create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Department</a>
            </div>

            <div class="card">
                <div class="card-body" style="padding-bottom:0;">
                    <form method="get" class="toolbar" style="margin-bottom:20px;">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="q" class="form-control" placeholder="Search code, name, or head…" value="<?= h($search) ?>">
                        </div>
                        <div class="filter-bar">
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <?php foreach (['Active', 'Inactive'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                            <?php if ($search || $status !== ''): ?>
                                <a href="<?= BASE_URL ?>admin/departments/index.php" class="btn btn-ghost btn-sm">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'departments') ?></div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th><?= sortableHeader('Code', 'code', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Department', 'name', $sortKey, $sortDir) ?></th><th>Head</th><th><?= sortableHeader('Students', 'students', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Teachers', 'teachers', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Courses', 'courses', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Status', 'status', $sortKey, $sortDir) ?></th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php if (!$departments): ?>
                            <?= renderEmptyState(8, 'fa-building-columns', 'No departments found', 'Try a different search or filter, or add a new department to get started.', '<a href="' . BASE_URL . 'admin/departments/create.php" class="btn btn-primary btn-sm">Add Department</a>') ?>
                        <?php endif; ?>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td><strong><?= h($d['department_code']) ?></strong></td>
                                <td><?= h($d['department_name']) ?></td>
                                <td><?= h($d['department_head'] ?: '—') ?></td>
                                <td><?= (int) $d['student_count'] ?></td>
                                <td><?= (int) $d['teacher_count'] ?></td>
                                <td><?= (int) $d['course_count'] ?></td>
                                <td><?= statusBadge($d['status']) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/departments/view.php?id=<?= (int) $d['id'] ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                                        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/departments/edit.php?id=<?= (int) $d['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                                data-delete-url="<?= BASE_URL ?>admin/departments/delete.php?id=<?= (int) $d['id'] ?>"
                                                data-delete-name="<?= h($d['department_name']) ?>">
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
        <div class="modal-header"><h3>Delete department</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body"><p style="margin:0;">Are you sure you want to delete <strong id="deleteConfirmName"></strong>? Students and teachers in this department will be kept, but unassigned.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
