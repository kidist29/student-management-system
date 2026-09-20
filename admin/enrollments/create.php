<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Enroll Student';
$activeNav = 'enrollments';

$errors = [];
$values = [
    'student_id' => $_GET['student_id'] ?? '',
    'course_id' => $_GET['course_id'] ?? '',
    'academic_year' => currentAcademicYear(),
    'semester' => '1st Semester',
    'enrollment_date' => date('Y-m-d'),
    'status' => 'Enrolled',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['student_id'] === '') $errors['student_id'] = 'Please select a student.';
    if ($values['course_id'] === '') $errors['course_id'] = 'Please select a course.';
    if ($values['academic_year'] === '' || !preg_match('/^\d{4}\/\d{4}$/', $values['academic_year'])) {
        $errors['academic_year'] = 'Use the format YYYY/YYYY, e.g. 2025/2026.';
    }
    if (!in_array($values['semester'], ['1st Semester', '2nd Semester', 'Summer'], true)) $errors['semester'] = 'Please select a semester.';
    if (!in_array($values['status'], ['Enrolled', 'Completed', 'Dropped'], true)) $errors['status'] = 'Please select a status.';
    if ($values['enrollment_date'] === '') $errors['enrollment_date'] = 'Please choose an enrollment date.';

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ? AND academic_year = ? AND semester = ?');
        $dup->execute([$values['student_id'], $values['course_id'], $values['academic_year'], $values['semester']]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['course_id'] = 'This student is already enrolled in that course for this term.';
        }
    }

    if (!$errors) {
        try {
            $stmt = $db->prepare('
                INSERT INTO enrollments (student_id, course_id, academic_year, semester, enrollment_date, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $values['student_id'], $values['course_id'], $values['academic_year'],
                $values['semester'], $values['enrollment_date'], $values['status'],
            ]);
            setFlash('success', 'Student was enrolled in the course.');
            redirect('admin/enrollments/index.php');
        } catch (PDOException $e) {
            // Unique-key race condition safety net; the earlier check normally catches this first.
            if ($e->getCode() === '23000') {
                $errors['course_id'] = 'This student is already enrolled in that course for this term.';
            } else {
                error_log('Enrollment insert failed: ' . $e->getMessage());
                $errors['course_id'] = 'Could not save this enrollment. Please try again.';
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/enrollments/index.php">Enrollments</a> / Enroll</div>
                    <h1>Enroll Student</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/enrollments/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
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
                                <label for="student_id">Student <span class="req">*</span></label>
                                <select id="student_id" name="student_id" class="form-control <?= isset($errors['student_id']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">— Select a student —</option>
                                    <?= studentOptions($db, $values['student_id']) ?>
                                </select>
                                <?php if (isset($errors['student_id'])): ?><div class="field-error"><?= h($errors['student_id']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="course_id">Course <span class="req">*</span></label>
                                <select id="course_id" name="course_id" class="form-control <?= isset($errors['course_id']) ? 'is-invalid' : '' ?>" required>
                                    <option value="">— Select a course —</option>
                                    <?= courseOptions($db, $values['course_id']) ?>
                                </select>
                                <?php if (isset($errors['course_id'])): ?><div class="field-error"><?= h($errors['course_id']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="academic_year">Academic Year <span class="req">*</span></label>
                                <input type="text" id="academic_year" name="academic_year" class="form-control <?= isset($errors['academic_year']) ? 'is-invalid' : '' ?>" value="<?= h($values['academic_year']) ?>" placeholder="2025/2026" required>
                                <?php if (isset($errors['academic_year'])): ?><div class="field-error"><?= h($errors['academic_year']) ?></div><?php endif; ?>
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
                                <label for="enrollment_date">Enrollment Date <span class="req">*</span></label>
                                <input type="date" id="enrollment_date" name="enrollment_date" class="form-control <?= isset($errors['enrollment_date']) ? 'is-invalid' : '' ?>" value="<?= h($values['enrollment_date']) ?>" required>
                                <?php if (isset($errors['enrollment_date'])): ?><div class="field-error"><?= h($errors['enrollment_date']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="status">Status <span class="req">*</span></label>
                                <select id="status" name="status" class="form-control" required>
                                    <?php foreach (['Enrolled', 'Completed', 'Dropped'] as $st): ?>
                                        <option value="<?= $st ?>" <?= $values['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Enrollment</button>
                            <a href="<?= BASE_URL ?>admin/enrollments/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
