<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Teachers';
$activeNav = 'teachers';

$search       = trim($_GET['q'] ?? '');
$departmentId = $_GET['department_id'] ?? '';
$status       = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(t.teacher_code LIKE ? OR t.first_name LIKE ? OR t.last_name LIKE ? OR t.email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($departmentId !== '') {
    $where[] = 't.department_id = ?';
    $params[] = $departmentId;
}
if ($status !== '') {
    $where[] = 't.status = ?';
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sortColumns = [
    'name'          => 't.first_name',
    'department'    => 'd.department_name',
    'qualification' => 't.qualification',
    'status'        => 't.status',
];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$orderBy = $sortKey ? safeOrderBy($sortColumns, $sortKey, 'name', $sortDir) : 't.created_at DESC, t.id DESC';

$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$countStmt = $db->prepare("SELECT COUNT(*) FROM teachers t $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT t.*, d.department_name
    FROM teachers t
    LEFT JOIN departments d ON d.id = t.department_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$departments = $db->query('SELECT id, department_name FROM departments ORDER BY department_name')->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Teachers</div>
                    <h1>Teachers</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/teachers/create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Teacher</a>
            </div>

            <div class="card">
                <div class="card-body" style="padding-bottom:0;">
                    <form method="get" class="toolbar" style="margin-bottom:20px;">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="q" class="form-control" placeholder="Search name, ID, or email…" value="<?= h($search) ?>">
                        </div>
                        <div class="filter-bar">
                            <select name="department_id" class="form-control" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= (int) $d['id'] ?>" <?= (string) $departmentId === (string) $d['id'] ? 'selected' : '' ?>><?= h($d['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <?php foreach (['Active', 'Inactive'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                            <?php if ($search || $departmentId !== '' || $status !== ''): ?>
                                <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-ghost btn-sm">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'teachers') ?></div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr><th><?= sortableHeader('Teacher', 'name', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Department', 'department', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Qualification', 'qualification', $sortKey, $sortDir) ?></th><th>Phone</th><th><?= sortableHeader('Status', 'status', $sortKey, $sortDir) ?></th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php if (!$teachers): ?>
                            <?= renderEmptyState(6, 'fa-chalkboard-user', 'No teachers found', 'Try a different search or filter, or add a new teacher to get started.', '<a href="' . BASE_URL . 'admin/teachers/create.php" class="btn btn-primary btn-sm">Add Teacher</a>') ?>
                        <?php endif; ?>
                        <?php foreach ($teachers as $t): $fullName = $t['first_name'] . ' ' . $t['last_name']; ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <img class="avatar-sm" src="<?= h(photoUrl($t['photo'], $fullName)) ?>" alt="">
                                        <div>
                                            <div style="font-weight:600;"><?= h($fullName) ?></div>
                                            <div style="font-size:0.78rem;color:var(--ink-400);"><?= h($t['teacher_code']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= h($t['department_name'] ?? '—') ?></td>
                                <td><?= h($t['qualification'] ?: '—') ?></td>
                                <td><?= h($t['phone'] ?: '—') ?></td>
                                <td><?= statusBadge($t['status']) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/teachers/view.php?id=<?= (int) $t['id'] ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                                        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/teachers/edit.php?id=<?= (int) $t['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                                data-delete-url="<?= BASE_URL ?>admin/teachers/delete.php?id=<?= (int) $t['id'] ?>"
                                                data-delete-name="<?= h($fullName) ?>">
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
        <div class="modal-header"><h3>Delete teacher</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body"><p style="margin:0;">Are you sure you want to delete <strong id="deleteConfirmName"></strong>? Courses assigned to them will be kept, but unassigned.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
