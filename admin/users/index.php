<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin');

$db = getDB();
$pageTitle = 'Users & Roles';
$activeNav = 'users';

$search = trim($_GET['q'] ?? '');
$role   = $_GET['role'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like);
}
if ($role !== '') {
    $where[] = 'u.role = ?';
    $params[] = $role;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sortColumns = ['name' => 'u.full_name', 'email' => 'u.email', 'role' => 'u.role', 'status' => 'u.status'];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$orderBy = $sortKey ? safeOrderBy($sortColumns, $sortKey, 'name', $sortDir) : 'u.created_at DESC';

$perPage = 10;
$page = max(1, (int) ($_GET['page'] ?? 1));
$countStmt = $db->prepare("SELECT COUNT(*) FROM users u $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT u.*, t.first_name AS t_first, t.last_name AS t_last,
           s.first_name AS s_first, s.last_name AS s_last
    FROM users u
    LEFT JOIN teachers t ON t.id = u.teacher_id
    LEFT JOIN students s ON s.id = u.student_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Users &amp; Roles</div>
                    <h1>Users &amp; Roles</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/users/create.php" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Add User</a>
            </div>

            <div class="card">
                <div class="card-body" style="padding-bottom:0;">
                    <form method="get" class="toolbar" style="margin-bottom:20px;">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="q" class="form-control" placeholder="Search name or email…" value="<?= h($search) ?>">
                        </div>
                        <div class="filter-bar">
                            <select name="role" class="form-control" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <option value="super_admin" <?= $role === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Registrar</option>
                                <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                            <?php if ($search || $role !== ''): ?>
                                <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-ghost btn-sm">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                    <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'users') ?></div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th><?= sortableHeader('Name', 'name', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Email', 'email', $sortKey, $sortDir) ?></th><th><?= sortableHeader('Role', 'role', $sortKey, $sortDir) ?></th><th>Linked To</th><th><?= sortableHeader('Status', 'status', $sortKey, $sortDir) ?></th><th>Last Login</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$users): ?>
                            <?= renderEmptyState(7, 'fa-user-shield', 'No users found', 'Try a different search or filter, or add a new user account.', '<a href="' . BASE_URL . 'admin/users/create.php" class="btn btn-primary btn-sm">Add User</a>') ?>
                        <?php endif; ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td style="font-weight:600;"><?= h($u['full_name']) ?><?php if ((int) $u['id'] === (int) ($_SESSION['user_id'] ?? 0)): ?> <span style="font-size:0.72rem;color:var(--ink-400);">(you)</span><?php endif; ?></td>
                                <td><?= h($u['email']) ?></td>
                                <td><?= roleBadge($u['role']) ?></td>
                                <td>
                                    <?php if ($u['t_first']): ?><?= h($u['t_first'] . ' ' . $u['t_last']) ?>
                                    <?php elseif ($u['s_first']): ?><?= h($u['s_first'] . ' ' . $u['s_last']) ?>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><?= statusBadge($u['status']) ?></td>
                                <td><?= $u['last_login'] ? h(date('M j, Y g:i A', strtotime($u['last_login']))) : 'Never' ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/users/edit.php?id=<?= (int) $u['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <?php if ((int) $u['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                        <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                                data-delete-url="<?= BASE_URL ?>admin/users/delete.php?id=<?= (int) $u['id'] ?>"
                                                data-delete-name="<?= h($u['full_name']) ?>">
                                            <i class="fa-solid fa-trash" style="color:var(--danger);"></i>
                                        </button>
                                        <?php endif; ?>
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
        <div class="modal-header"><h3>Delete user</h3><button type="button" class="modal-close" data-modal-close>&times;</button></div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body"><p style="margin:0;">Delete the login for <strong id="deleteConfirmName"></strong>? Their student/teacher record (if any) is kept — only the login is removed.</p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
