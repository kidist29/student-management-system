<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin');

$db = getDB();
$pageTitle = 'Add User';
$activeNav = 'users';

$errors = [];
$values = [
    'full_name' => '', 'email' => '', 'password' => '', 'role' => 'admin',
    'teacher_id' => '', 'student_id' => '', 'status' => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['full_name'] === '') $errors['full_name'] = 'Full name is required.';
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email address is required.';
    }
    if (strlen($values['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
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
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) $errors['status'] = 'Please select a status.';

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $dup->execute([$values['email']]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['email'] = 'That email is already in use by another account.';
        }
    }

    // Values that don't apply to the chosen role are simply not stored.
    $teacherId = $values['role'] === 'teacher' ? ($values['teacher_id'] ?: null) : null;
    $studentId = $values['role'] === 'student' ? ($values['student_id'] ?: null) : null;

    if (!$errors) {
        try {
            $stmt = $db->prepare('
                INSERT INTO users (full_name, email, password, role, teacher_id, student_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $values['full_name'], $values['email'], password_hash($values['password'], PASSWORD_BCRYPT),
                $values['role'], $teacherId, $studentId, $values['status'],
            ]);
            setFlash('success', 'User "' . $values['full_name'] . '" was created.');
            redirect('admin/users/index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['teacher_id'] = $errors['student_id'] = 'That teacher or student already has a login account.';
            } else {
                error_log('User creation failed: ' . $e->getMessage());
                $errors['email'] = 'Could not create this user. Please try again.';
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/users/index.php">Users &amp; Roles</a> / Add</div>
                    <h1>Add User</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
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
                                <label for="password">Password <span class="req">*</span></label>
                                <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" required minlength="8">
                                <?php if (isset($errors['password'])): ?><div class="field-error"><?= h($errors['password']) ?></div><?php else: ?><div class="form-hint">At least 8 characters.</div><?php endif; ?>
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
                            <label for="role">Role <span class="req">*</span></label>
                            <select id="role" name="role" class="form-control" required onchange="document.getElementById('teacherLinkGroup').style.display = this.value==='teacher' ? 'block' : 'none'; document.getElementById('studentLinkGroup').style.display = this.value==='student' ? 'block' : 'none';">
                                <option value="super_admin" <?= $values['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin — full system access</option>
                                <option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>Registrar — manage students, teachers, courses, academic records</option>
                                <option value="teacher" <?= $values['role'] === 'teacher' ? 'selected' : '' ?>>Teacher — view assigned courses, enter grades</option>
                                <option value="student" <?= $values['role'] === 'student' ? 'selected' : '' ?>>Student — view own profile, courses, grades, transcript</option>
                            </select>
                        </div>

                        <div class="form-group" id="teacherLinkGroup" style="display:<?= $values['role'] === 'teacher' ? 'block' : 'none' ?>;">
                            <label for="teacher_id">Linked Teacher Record <span class="req">*</span></label>
                            <select id="teacher_id" name="teacher_id" class="form-control <?= isset($errors['teacher_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— Select —</option>
                                <?= availableTeacherOptions($db, $values['teacher_id']) ?>
                            </select>
                            <?php if (isset($errors['teacher_id'])): ?><div class="field-error"><?= h($errors['teacher_id']) ?></div><?php else: ?><div class="form-hint">Only teachers without an existing login are listed.</div><?php endif; ?>
                        </div>

                        <div class="form-group" id="studentLinkGroup" style="display:<?= $values['role'] === 'student' ? 'block' : 'none' ?>;">
                            <label for="student_id">Linked Student Record <span class="req">*</span></label>
                            <select id="student_id" name="student_id" class="form-control <?= isset($errors['student_id']) ? 'is-invalid' : '' ?>">
                                <option value="">— Select —</option>
                                <?= availableStudentOptions($db, $values['student_id']) ?>
                            </select>
                            <?php if (isset($errors['student_id'])): ?><div class="field-error"><?= h($errors['student_id']) ?></div><?php else: ?><div class="form-hint">Only students without an existing login are listed.</div><?php endif; ?>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Create User</button>
                            <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
