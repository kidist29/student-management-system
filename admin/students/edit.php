<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('danger', 'Student not found.');
    redirect('admin/students/index.php');
}

$pageTitle = 'Edit Student';
$activeNav = 'students';
$errors = [];
$values = $student;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['student_id','first_name','last_name','gender','date_of_birth','email','phone','address','department_id','program','year_level','semester','admission_date','status'] as $key) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['student_id'] === '') $errors['student_id'] = 'Student ID is required.';
    if ($values['first_name'] === '') $errors['first_name'] = 'First name is required.';
    if ($values['last_name'] === '') $errors['last_name'] = 'Last name is required.';
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid email address is required.';
    }
    if (!in_array($values['gender'], ['Male', 'Female', 'Other'], true)) $errors['gender'] = 'Please select a gender.';
    if (!in_array($values['status'], ['Active', 'Inactive', 'Graduated'], true)) $errors['status'] = 'Please select a status.';
    if ($values['year_level'] === '' || (int) $values['year_level'] < 1) $errors['year_level'] = 'Please enter a valid year.';
    if ($values['phone'] !== '' && !isValidPhone($values['phone'])) {
        $errors['phone'] = 'Enter a valid phone number (digits, spaces, +, -, and () only).';
    }
    if ($values['date_of_birth'] !== '' && strtotime($values['date_of_birth']) > time()) {
        $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
    }
    if ($values['admission_date'] !== '' && $values['date_of_birth'] !== ''
        && strtotime($values['admission_date']) < strtotime($values['date_of_birth'])) {
        $errors['admission_date'] = 'Admission date cannot be before the date of birth.';
    }

    if (!$errors) {
        $dupStmt = $db->prepare('SELECT COUNT(*) FROM students WHERE (student_id = ? OR email = ?) AND id != ?');
        $dupStmt->execute([$values['student_id'], $values['email'], $id]);
        if ((int) $dupStmt->fetchColumn() > 0) {
            $errors['student_id'] = 'That Student ID or email is already used by another student.';
        }
    }

    $photoPath = $student['photo'];
    if (!$errors && !empty($_FILES['photo']['name'])) {
        try {
            $newPhoto = handlePhotoUpload($_FILES['photo'], 'students');
            if ($newPhoto) {
                deletePhotoFile($student['photo']);
                $photoPath = $newPhoto;
            }
        } catch (RuntimeException $e) {
            $errors['photo'] = $e->getMessage();
        }
    } elseif (!empty($_POST['remove_photo'])) {
        deletePhotoFile($student['photo']);
        $photoPath = null;
    }

    if (!$errors) {
        $stmt = $db->prepare("
            UPDATE students SET
                student_id = ?, first_name = ?, last_name = ?, gender = ?, date_of_birth = ?,
                email = ?, phone = ?, address = ?, department_id = ?, program = ?,
                year_level = ?, semester = ?, admission_date = ?, photo = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $values['student_id'], $values['first_name'], $values['last_name'], $values['gender'],
            $values['date_of_birth'] ?: null, $values['email'], $values['phone'] ?: null, $values['address'] ?: null,
            $values['department_id'] ?: null, $values['program'] ?: null, (int) $values['year_level'],
            $values['semester'], $values['admission_date'] ?: null, $photoPath, $values['status'], $id,
        ]);

        setFlash('success', 'Student "' . $values['first_name'] . ' ' . $values['last_name'] . '" was updated.');
        redirect('admin/students/index.php');
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/students/index.php">Students</a> / Edit</div>
                    <h1>Edit Student</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
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
                                <label for="student_id">Student ID <span class="req">*</span></label>
                                <input type="text" id="student_id" name="student_id" class="form-control <?= isset($errors['student_id']) ? 'is-invalid' : '' ?>" value="<?= h($values['student_id']) ?>" required>
                                <?php if (isset($errors['student_id'])): ?><div class="field-error"><?= h($errors['student_id']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="photo">Photo</label>
                                <div class="input-file-wrap" style="display:flex;align-items:center;gap:14px;">
                                    <img class="avatar-sm" style="width:48px;height:48px;" src="<?= h(photoUrl($student['photo'], $student['first_name'] . ' ' . $student['last_name'])) ?>" alt="">
                                    <div style="flex:1;">
                                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                                        <?php if ($student['photo']): ?>
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
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control <?= isset($errors['date_of_birth']) ? 'is-invalid' : '' ?>" value="<?= h($values['date_of_birth']) ?>" max="<?= date('Y-m-d') ?>">
                                <?php if (isset($errors['date_of_birth'])): ?><div class="field-error"><?= h($errors['date_of_birth']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= h($values['email']) ?>" required>
                                <?php if (isset($errors['email'])): ?><div class="field-error"><?= h($errors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" value="<?= h($values['phone']) ?>" placeholder="+251 9XX XXX XXX">
                                <?php if (isset($errors['phone'])): ?><div class="field-error"><?= h($errors['phone']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" class="form-control" value="<?= h($values['address']) ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="department_id">Department</label>
                                <select id="department_id" name="department_id" class="form-control">
                                    <option value="">— Select —</option>
                                    <?= departmentOptions($db, $values['department_id']) ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="program">Program</label>
                                <input type="text" id="program" name="program" class="form-control" value="<?= h($values['program']) ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="year_level">Year <span class="req">*</span></label>
                                <select id="year_level" name="year_level" class="form-control <?= isset($errors['year_level']) ? 'is-invalid' : '' ?>" required>
                                    <?php for ($y = 1; $y <= 6; $y++): ?>
                                        <option value="<?= $y ?>" <?= (int) $values['year_level'] === $y ? 'selected' : '' ?>>Year <?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="semester">Semester</label>
                                <select id="semester" name="semester" class="form-control">
                                    <?php foreach (['1st Semester', '2nd Semester', 'Summer'] as $sem): ?>
                                        <option value="<?= $sem ?>" <?= $values['semester'] === $sem ? 'selected' : '' ?>><?= $sem ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="admission_date">Admission Date</label>
                                <input type="date" id="admission_date" name="admission_date" class="form-control <?= isset($errors['admission_date']) ? 'is-invalid' : '' ?>" value="<?= h($values['admission_date']) ?>">
                                <?php if (isset($errors['admission_date'])): ?><div class="field-error"><?= h($errors['admission_date']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span class="req">*</span></label>
                                <select id="status" name="status" class="form-control" required>
                                    <?php foreach (['Active', 'Inactive', 'Graduated'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $values['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                            <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
