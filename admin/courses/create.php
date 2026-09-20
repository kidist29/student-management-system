<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Add Course';
$activeNav = 'courses';

$errors = [];
$values = [
    'course_code' => '', 'course_name' => '', 'credit_hours' => '3.0', 'department_id' => '',
    'semester' => '1st Semester', 'teacher_id' => '', 'status' => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['course_code'] === '') $errors['course_code'] = 'Course code is required.';
    if ($values['course_name'] === '') $errors['course_name'] = 'Course name is required.';
    if (!is_numeric($values['credit_hours']) || (float) $values['credit_hours'] <= 0) {
        $errors['credit_hours'] = 'Enter a valid number of credit hours.';
    }
    if (!in_array($values['semester'], ['1st Semester', '2nd Semester', 'Summer'], true)) $errors['semester'] = 'Please select a semester.';
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) $errors['status'] = 'Please select a status.';

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM courses WHERE course_code = ?');
        $dup->execute([$values['course_code']]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['course_code'] = 'That course code is already in use.';
        }
    }

    if (!$errors) {
        $stmt = $db->prepare('
            INSERT INTO courses (course_code, course_name, credit_hours, department_id, semester, teacher_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $values['course_code'], $values['course_name'], (float) $values['credit_hours'],
            $values['department_id'] ?: null, $values['semester'], $values['teacher_id'] ?: null, $values['status'],
        ]);
        setFlash('success', 'Course "' . $values['course_name'] . '" was added.');
        redirect('admin/courses/index.php');
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/courses/index.php">Courses</a> / Add</div>
                    <h1>Add Course</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/courses/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" action="" data-validate novalidate>
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_code">Course Code <span class="req">*</span></label>
                                <input type="text" id="course_code" name="course_code" class="form-control <?= isset($errors['course_code']) ? 'is-invalid' : '' ?>" value="<?= h($values['course_code']) ?>" placeholder="e.g. CS301" required>
                                <?php if (isset($errors['course_code'])): ?><div class="field-error"><?= h($errors['course_code']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="course_name">Course Name <span class="req">*</span></label>
                                <input type="text" id="course_name" name="course_name" class="form-control <?= isset($errors['course_name']) ? 'is-invalid' : '' ?>" value="<?= h($values['course_name']) ?>" required>
                                <?php if (isset($errors['course_name'])): ?><div class="field-error"><?= h($errors['course_name']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="credit_hours">Credit Hours <span class="req">*</span></label>
                                <input type="number" step="0.5" min="0.5" id="credit_hours" name="credit_hours" class="form-control <?= isset($errors['credit_hours']) ? 'is-invalid' : '' ?>" value="<?= h($values['credit_hours']) ?>" required>
                                <?php if (isset($errors['credit_hours'])): ?><div class="field-error"><?= h($errors['credit_hours']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="semester">Semester <span class="req">*</span></label>
                                <select id="semester" name="semester" class="form-control" required>
                                    <?php foreach (['1st Semester', '2nd Semester', 'Summer'] as $sem): ?>
                                        <option value="<?= $sem ?>" <?= $values['semester'] === $sem ? 'selected' : '' ?>><?= $sem ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
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
                                <label for="teacher_id">Teacher</label>
                                <select id="teacher_id" name="teacher_id" class="form-control">
                                    <option value="">— Select —</option>
                                    <?= teacherOptions($db, $values['teacher_id']) ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" style="max-width:260px;">
                            <label for="status">Status <span class="req">*</span></label>
                            <select id="status" name="status" class="form-control" required>
                                <?php foreach (['Active', 'Inactive'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $values['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Course</button>
                            <a href="<?= BASE_URL ?>admin/courses/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
