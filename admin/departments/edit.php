<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM departments WHERE id = ?');
$stmt->execute([$id]);
$department = $stmt->fetch();

if (!$department) {
    setFlash('danger', 'Department not found.');
    redirect('admin/departments/index.php');
}

$pageTitle = 'Edit Department';
$activeNav = 'departments';
$errors = [];
$values = $department;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['department_code', 'department_name', 'department_head', 'description', 'status'] as $key) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['department_code'] === '') $errors['department_code'] = 'Department code is required.';
    if ($values['department_name'] === '') $errors['department_name'] = 'Department name is required.';
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) $errors['status'] = 'Please select a status.';

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM departments WHERE department_code = ? AND id != ?');
        $dup->execute([$values['department_code'], $id]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['department_code'] = 'That department code is already used by another department.';
        }
    }

    if (!$errors) {
        $stmt = $db->prepare('UPDATE departments SET department_code = ?, department_name = ?, department_head = ?, description = ?, status = ? WHERE id = ?');
        $stmt->execute([$values['department_code'], $values['department_name'], $values['department_head'] ?: null, $values['description'] ?: null, $values['status'], $id]);
        setFlash('success', 'Department "' . $values['department_name'] . '" was updated.');
        redirect('admin/departments/index.php');
    }
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/departments/index.php">Departments</a> / Edit</div>
                    <h1>Edit Department</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/departments/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" action="" data-validate novalidate style="max-width:640px;">
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="department_code">Department Code <span class="req">*</span></label>
                                <input type="text" id="department_code" name="department_code" class="form-control <?= isset($errors['department_code']) ? 'is-invalid' : '' ?>" value="<?= h($values['department_code']) ?>" required>
                                <?php if (isset($errors['department_code'])): ?><div class="field-error"><?= h($errors['department_code']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span class="req">*</span></label>
                                <select id="status" name="status" class="form-control" required>
                                    <?php foreach (['Active', 'Inactive'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $values['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="department_name">Department Name <span class="req">*</span></label>
                            <input type="text" id="department_name" name="department_name" class="form-control <?= isset($errors['department_name']) ? 'is-invalid' : '' ?>" value="<?= h($values['department_name']) ?>" required>
                            <?php if (isset($errors['department_name'])): ?><div class="field-error"><?= h($errors['department_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="department_head">Department Head</label>
                            <input type="text" id="department_head" name="department_head" class="form-control" value="<?= h($values['department_head']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control"><?= h($values['description']) ?></textarea>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                            <a href="<?= BASE_URL ?>admin/departments/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
