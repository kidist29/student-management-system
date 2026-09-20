<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Students';
$activeNav = 'students';
$isAjax = isset($_GET['ajax']);

// ---- Filters ----
$search       = trim($_GET['q'] ?? '');
$departmentId = $_GET['department_id'] ?? '';
$status       = $_GET['status'] ?? '';
$year         = $_GET['year'] ?? '';

$where  = [];
$params = [];

if ($search !== '') {
    $where[] = '(s.student_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($departmentId !== '') {
    $where[] = 's.department_id = ?';
    $params[] = $departmentId;
}
if ($status !== '') {
    $where[] = 's.status = ?';
    $params[] = $status;
}
if ($year !== '') {
    $where[] = 's.year_level = ?';
    $params[] = $year;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- Sorting ----
$sortColumns = [
    'name'       => 's.first_name',
    'student_id' => 's.student_id',
    'department' => 'd.department_name',
    'year'       => 's.year_level',
    'status'     => 's.status',
    'admitted'   => 's.admission_date',
];
$sortKey = $_GET['sort'] ?? null;
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$orderBy = $sortKey ? safeOrderBy($sortColumns, $sortKey, 'name', $sortDir) : 's.created_at DESC, s.id DESC';

// ---- Pagination ----
$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));

$countStmt = $db->prepare("SELECT COUNT(*) FROM students s LEFT JOIN departments d ON d.id = s.department_id $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT s.*, d.department_name
    FROM students s
    LEFT JOIN departments d ON d.id = s.department_id
    $whereSql
    ORDER BY $orderBy
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$departments = $db->query('SELECT id, department_name FROM departments ORDER BY department_name')->fetchAll();

// ---- Render the list fragment (search/filter toolbar + table + pagination).
// Rendered once, then either echoed standalone (AJAX) or embedded in the
// full page layout below — the page works identically with JS disabled.
ob_start();
?>
<div class="card">
    <div class="card-body" style="padding-bottom:0;">
        <form method="get" class="toolbar" style="margin-bottom:16px;">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="form-control" placeholder="Search name, ID, or email…" value="<?= h($search) ?>">
            </div>
            <div class="filter-bar">
                <select name="department_id" class="form-control" >
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= (string) $departmentId === (string) $d['id'] ? 'selected' : '' ?>>
                            <?= h($d['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="form-control" >
                    <option value="">All Statuses</option>
                    <?php foreach (['Active', 'Inactive', 'Graduated'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $status === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="form-control" >
                    <option value="">All Years</option>
                    <?php for ($y = 1; $y <= 6; $y++): ?>
                        <option value="<?= $y ?>" <?= (string) $year === (string) $y ? 'selected' : '' ?>>Year <?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-outline btn-sm">Filter</button>
                <?php if ($search || $departmentId !== '' || $status !== '' || $year !== ''): ?>
                    <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </div>
        </form>
        <div class="record-count" style="margin-bottom:14px;"><?= renderRecordCount($page, $perPage, $totalRows, 'students') ?></div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?= sortableHeader('Student', 'name', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Department', 'department', $sortKey, $sortDir) ?></th>
                    <th>Program</th>
                    <th><?= sortableHeader('Year', 'year', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Status', 'status', $sortKey, $sortDir) ?></th>
                    <th><?= sortableHeader('Admitted', 'admitted', $sortKey, $sortDir) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$students): ?>
                <?= renderEmptyState(7, 'fa-user-graduate', 'No students found', 'Try a different search or filter, or add a new student to get started.', '<a href="' . BASE_URL . 'admin/students/create.php" class="btn btn-primary btn-sm">Add Student</a>') ?>
            <?php endif; ?>
            <?php foreach ($students as $s): $fullName = $s['first_name'] . ' ' . $s['last_name']; ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img class="avatar-sm" src="<?= h(photoUrl($s['photo'], $fullName)) ?>" alt="">
                            <div>
                                <div style="font-weight:600;"><?= h($fullName) ?></div>
                                <div style="font-size:0.78rem;color:var(--ink-400);"><?= h($s['student_id']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= h($s['department_name'] ?? '—') ?></td>
                    <td><?= h($s['program'] ?? '—') ?></td>
                    <td>Year <?= (int) $s['year_level'] ?></td>
                    <td><?= statusBadge($s['status']) ?></td>
                    <td><?= $s['admission_date'] ? h(date('M j, Y', strtotime($s['admission_date']))) : '—' ?></td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/students/view.php?id=<?= (int) $s['id'] ?>" title="View"><i class="fa-solid fa-eye"></i></a>
                            <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>admin/students/edit.php?id=<?= (int) $s['id'] ?>" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <button type="button" class="btn btn-ghost btn-sm" title="Delete"
                                    data-delete-url="<?= BASE_URL ?>admin/students/delete.php?id=<?= (int) $s['id'] ?>"
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
    <div class="card-body">
        <?= renderPagination($page, $totalPages) ?>
    </div>
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Students</div>
                    <h1>Students</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/students/create.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add Student
                </a>
            </div>

            <div id="studentsLiveTable" data-live-table>
                <?= $fragment ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal-backdrop" id="deleteConfirmModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete student</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>
        <form id="deleteConfirmForm" method="post" action="">
            <?= csrfField() ?>
            <div class="modal-body">
                <p style="margin:0;">Are you sure you want to delete <strong id="deleteConfirmName"></strong>? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
