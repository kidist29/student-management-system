<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Add Department';
$activeNav = 'departments';

$errors = [];
$values = ['department_code' => '', 'department_name' => '', 'department_head' => '', 'description' => '', 'status' => 'Active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['department_code'] === '') $errors['department_code'] = 'Department code is required.';
    if ($values['department_name'] === '') $errors['department_name'] = 'Department name is required.';
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) $errors['status'] = 'Please select a status.';

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM departments WHERE department_code = ?');
        $dup->execute([$values['department_code']]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['department_code'] = 'That department code is already in use.';
        }
    }

    if (!$errors) {
        $stmt = $db->prepare('INSERT INTO departments (department_code, department_name, department_head, description, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$values['department_code'], $values['department_name'], $values['department_head'] ?: null, $values['description'] ?: null, $values['status']]);
        setFlash('success', 'Department "' . $values['department_name'] . '" was added.');
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/departments/index.php">Departments</a> / Add</div>
                    <h1>Add Department</h1>
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
                                <input type="text" id="department_code" name="department_code" class="form-control <?= isset($errors['department_code']) ? 'is-invalid' : '' ?>" value="<?= h($values['department_code']) ?>" placeholder="e.g. CS" required>
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
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Department</button>
                            <a href="<?= BASE_URL ?>admin/departments/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
