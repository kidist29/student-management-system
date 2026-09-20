<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM teachers WHERE id = ?');
$stmt->execute([$id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    setFlash('danger', 'Teacher not found.');
    redirect('admin/teachers/index.php');
}

$pageTitle = 'Edit Teacher';
$activeNav = 'teachers';
$errors = [];
$values = $teacher;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['teacher_code', 'first_name', 'last_name', 'gender', 'email', 'phone', 'department_id', 'qualification', 'status'] as $key) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['teacher_code'] === '') $errors['teacher_code'] = 'Teacher ID is required.';
    if ($values['first_name'] === '') $errors['first_name'] = 'First name is required.';
    if ($values['last_name'] === '') $errors['last_name'] = 'Last name is required.';
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email address is required.';
    }
    if (!in_array($values['gender'], ['Male', 'Female', 'Other'], true)) $errors['gender'] = 'Please select a gender.';
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) $errors['status'] = 'Please select a status.';
    if ($values['phone'] !== '' && !isValidPhone($values['phone'])) {
        $errors['phone'] = 'Enter a valid phone number (digits, spaces, +, -, and () only).';
    }

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM teachers WHERE (teacher_code = ? OR email = ?) AND id != ?');
        $dup->execute([$values['teacher_code'], $values['email'], $id]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['teacher_code'] = 'That Teacher ID or email is already used by another teacher.';
        }
    }

    $photoPath = $teacher['photo'];
    if (!$errors && !empty($_FILES['photo']['name'])) {
        try {
            $newPhoto = handlePhotoUpload($_FILES['photo'], 'teachers');
            if ($newPhoto) {
                deletePhotoFile($teacher['photo']);
                $photoPath = $newPhoto;
            }
        } catch (RuntimeException $e) {
            $errors['photo'] = $e->getMessage();
        }
    } elseif (!empty($_POST['remove_photo'])) {
        deletePhotoFile($teacher['photo']);
        $photoPath = null;
    }

    if (!$errors) {
        $stmt = $db->prepare('
            UPDATE teachers SET teacher_code = ?, first_name = ?, last_name = ?, gender = ?, email = ?,
                phone = ?, department_id = ?, qualification = ?, status = ?, photo = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $values['teacher_code'], $values['first_name'], $values['last_name'], $values['gender'],
            $values['email'], $values['phone'] ?: null, $values['department_id'] ?: null,
            $values['qualification'] ?: null, $values['status'], $photoPath, $id,
        ]);
        setFlash('success', 'Teacher "' . $values['first_name'] . ' ' . $values['last_name'] . '" was updated.');
        redirect('admin/teachers/index.php');
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/teachers/index.php">Teachers</a> / Edit</div>
                    <h1>Edit Teacher</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" action="" enctype="multipart/form-data" data-validate novalidate>
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="teacher_code">Teacher ID <span class="req">*</span></label>
                                <input type="text" id="teacher_code" name="teacher_code" class="form-control <?= isset($errors['teacher_code']) ? 'is-invalid' : '' ?>" value="<?= h($values['teacher_code']) ?>" required>
                                <?php if (isset($errors['teacher_code'])): ?><div class="field-error"><?= h($errors['teacher_code']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="photo">Photo</label>
                                <div class="input-file-wrap" style="display:flex;align-items:center;gap:14px;">
                                    <img class="avatar-sm" style="width:48px;height:48px;" src="<?= h(photoUrl($teacher['photo'], $teacher['first_name'] . ' ' . $teacher['last_name'])) ?>" alt="">
                                    <div style="flex:1;">
                                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                                        <?php if ($teacher['photo']): ?>
                                            <label style="display:flex;align-items:center;gap:6px;font-weight:400;margin-top:8px;font-size:0.82rem;">
                                                <input type="checkbox" name="remove_photo" value="1"> Remove current photo
                                            </label>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (isset($errors['photo'])): ?><div class="field-error"><?= h($errors['photo']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name <span class="req">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control <?= isset($errors['first_name']) ? 'is-invalid' : '' ?>" value="<?= h($values['first_name']) ?>" required>
                                <?php if (isset($errors['first_name'])): ?><div class="field-error"><?= h($errors['first_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name <span class="req">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control <?= isset($errors['last_name']) ? 'is-invalid' : '' ?>" value="<?= h($values['last_name']) ?>" required>
                                <?php if (isset($errors['last_name'])): ?><div class="field-error"><?= h($errors['last_name']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender">Gender <span class="req">*</span></label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                        <option value="<?= $g ?>" <?= $values['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= h($values['email']) ?>" required>
                                <?php if (isset($errors['email'])): ?><div class="field-error"><?= h($errors['email']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= h($values['phone']) ?>" placeholder="+251 9XX XXX XXX">
                                <?php if (isset($errors['phone'])): ?><div class="field-error"><?= h($errors['phone']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="department_id">Department</label>
                                <select id="department_id" name="department_id" class="form-control">
                                    <option value="">— Select —</option>
                                    <?= departmentOptions($db, $values['department_id']) ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="qualification">Qualification</label>
                                <input type="text" id="qualification" name="qualification" class="form-control" value="<?= h($values['qualification']) ?>">
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
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                            <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
