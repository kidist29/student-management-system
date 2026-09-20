<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'User not found.');
    redirect('admin/users/index.php');
}

$pageTitle = 'Edit User';
$activeNav = 'users';
$errors = [];
$values = $user;
$values['password'] = '';
$isSelf = (int) $user['id'] === (int) ($_SESSION['user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['full_name', 'email', 'password', 'role', 'teacher_id', 'student_id', 'status'] as $key) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['full_name'] === '') $errors['full_name'] = 'Full name is required.';
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email address is required.';
    }
    if ($values['password'] !== '' && strlen($values['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters (or leave blank to keep the current one).';
    }
    if (!in_array($values['role'], ['super_admin', 'admin', 'teacher', 'student'], true)) {
        $errors['role'] = 'Please select a role.';
    }
    if ($values['role'] === 'teacher' && $values['teacher_id'] === '') {
        $errors['teacher_id'] = 'Select which teacher this login belongs to.';
    }
    if ($values['role'] === 'student' && $values['student_id'] === '') {
        $errors['student_id'] = 'Select which student this login belongs to.';
    }
    if ($isSelf && $values['role'] !== 'super_admin') {
        $errors['role'] = 'You cannot remove your own Super Admin role.';
    }
    if ($isSelf && $values['status'] !== 'Active') {
        $errors['status'] = 'You cannot deactivate your own account.';
    }
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) $errors['status'] = 'Please select a status.';

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
        $dup->execute([$values['email'], $id]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['email'] = 'That email is already used by another account.';
        }
    }

    $teacherId = $values['role'] === 'teacher' ? ($values['teacher_id'] ?: null) : null;
    $studentId = $values['role'] === 'student' ? ($values['student_id'] ?: null) : null;

    if (!$errors) {
        try {
            if ($values['password'] !== '') {
                $stmt = $db->prepare('
                    UPDATE users SET full_name = ?, email = ?, password = ?, role = ?, teacher_id = ?, student_id = ?, status = ?
                    WHERE id = ?
                ');
                $stmt->execute([
                    $values['full_name'], $values['email'], password_hash($values['password'], PASSWORD_BCRYPT),
                    $values['role'], $teacherId, $studentId, $values['status'], $id,
                ]);
            } else {
                $stmt = $db->prepare('
                    UPDATE users SET full_name = ?, email = ?, role = ?, teacher_id = ?, student_id = ?, status = ?
                    WHERE id = ?
                ');
                $stmt->execute([
                    $values['full_name'], $values['email'], $values['role'], $teacherId, $studentId, $values['status'], $id,
                ]);
            }
            setFlash('success', 'User "' . $values['full_name'] . '" was updated.');
            redirect('admin/users/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['teacher_id'] = $errors['student_id'] = 'That teacher or student already has a login account.';
            } else {
                error_log('User update failed: ' . $e->getMessage());
                $errors['email'] = 'Could not save these changes. Please try again.';
            }
        }
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/users/index.php">Users &amp; Roles</a> / Edit</div>
                    <h1>Edit User</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>
            <?php if ($isSelf): ?>
                <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i><span>This is your own account — you can't change your own role away from Super Admin or deactivate yourself.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" action="" data-validate novalidate style="max-width:640px;">
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="req">*</span></label>
                                <input type="text" id="full_name" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" value="<?= h($values['full_name']) ?>" required>
                                <?php if (isset($errors['full_name'])): ?><div class="field-error"><?= h($errors['full_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= h($values['email']) ?>" required>
                                <?php if (isset($errors['email'])): ?><div class="field-error"><?= h($errors['email']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" minlength="8">
                                <?php if (isset($errors['password'])): ?><div class="field-error"><?= h($errors['password']) ?></div><?php else: ?><div class="form-hint">Leave blank to keep the current password.</div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span class="req">*</span></label>
                                <select id="status" name="status" class="form-control" required <?= $isSelf ? 'disabled' : '' ?>>
                                    <?php foreach (['Active', 'Inactive'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $values['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($isSelf): ?><input type="hidden" name="status" value="Active"><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="role">Role <span class="req">*</span></label>
                            <select id="role" name="role" class="form-control" required <?= $isSelf ? 'disabled' : '' ?>
                                    onchange="document.getElementById('teacherLinkGroup').style.display = this.value==='teacher' ? 'block' : 'none'; document.getElementById('studentLinkGroup').style.display = this.value==='student' ? 'block' : 'none';">
                                <option value="super_admin" <?= $values['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin — full system access</option>
                                <option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>Registrar — manage students, teachers, courses, academic records</option>
                                <option value="teacher" <?= $values['role'] === 'teacher' ? 'selected' : '' ?>>Teacher — view assigned courses, enter grades</option>
                                <option value="student" <?= $values['role'] === 'student' ? 'selected' : '' ?>>Student — view own profile, courses, grades, transcript</option>
                            </select>
                            <?php if ($isSelf): ?><input type="hidden" name="role" value="super_admin"><?php endif; ?>
                        </div>

                        <div class="form-group" id="teacherLinkGroup" style="display:<?= $values['role'] === 'teacher' ? 'block' : 'none' ?>;">
                            <label for="teacher_id">Linked Teacher Record <span class="req">*</span></label>
                            <select id="teacher_id" name="teacher_id" class="form-control <?= isset($errors['teacher_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— Select —</option>
                                <?= availableTeacherOptions($db, $values['teacher_id'], $id) ?>
                            </select>
                            <?php if (isset($errors['teacher_id'])): ?><div class="field-error"><?= h($errors['teacher_id']) ?></div><?php endif; ?>
                        </div>

                        <div class="form-group" id="studentLinkGroup" style="display:<?= $values['role'] === 'student' ? 'block' : 'none' ?>;">
                            <label for="student_id">Linked Student Record <span class="req">*</span></label>
                            <select id="student_id" name="student_id" class="form-control <?= isset($errors['student_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— Select —</option>
                                <?= availableStudentOptions($db, $values['student_id'], $id) ?>
                            </select>
                            <?php if (isset($errors['student_id'])): ?><div class="field-error"><?= h($errors['student_id']) ?></div><?php endif; ?>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                            <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
