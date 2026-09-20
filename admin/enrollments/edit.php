<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('
    SELECT e.*, s.first_name, s.last_name, c.course_code, c.course_name
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    WHERE e.id = ?
');
$stmt->execute([$id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    setFlash('danger', 'Enrollment not found.');
    redirect('admin/enrollments/index.php');
}

$pageTitle = 'Edit Enrollment';
$activeNav = 'enrollments';
$errors = [];
$values = $enrollment;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach (['academic_year', 'semester', 'enrollment_date', 'status'] as $key) {
        $values[$key] = trim($_POST[$key] ?? '');
    }

    if ($values['academic_year'] === '' || !preg_match('/^\d{4}\/\d{4}$/', $values['academic_year'])) {
        $errors['academic_year'] = 'Use the format YYYY/YYYY, e.g. 2025/2026.';
    }
    if (!in_array($values['semester'], ['1st Semester', '2nd Semester', 'Summer'], true)) $errors['semester'] = 'Please select a semester.';
    if (!in_array($values['status'], ['Enrolled', 'Completed', 'Dropped'], true)) $errors['status'] = 'Please select a status.';
    if ($values['enrollment_date'] === '') $errors['enrollment_date'] = 'Please choose an enrollment date.';

    if (!$errors) {
        $dup = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ? AND academic_year = ? AND semester = ? AND id != ?');
        $dup->execute([$enrollment['student_id'], $enrollment['course_id'], $values['academic_year'], $values['semester'], $id]);
        if ((int) $dup->fetchColumn() > 0) {
            $errors['academic_year'] = 'This student already has an enrollment in this course for that term.';
        }
    }

    if (!$errors) {
        $stmt = $db->prepare('UPDATE enrollments SET academic_year = ?, semester = ?, enrollment_date = ?, status = ? WHERE id = ?');
        $stmt->execute([$values['academic_year'], $values['semester'], $values['enrollment_date'], $values['status'], $id]);
        setFlash('success', 'Enrollment was updated.');
        redirect('admin/enrollments/index.php');
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/enrollments/index.php">Enrollments</a> / Edit</div>
                    <h1>Edit Enrollment</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/enrollments/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <p style="margin:0 0 20px;color:var(--ink-600);">
                        <strong><?= h($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></strong>
                        — <?= h($enrollment['course_code']) ?>: <?= h($enrollment['course_name']) ?>
                    </p>
                    <form method="post" action="" data-validate novalidate style="max-width:640px;">
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="academic_year">Academic Year <span class="req">*</span></label>
                                <input type="text" id="academic_year" name="academic_year" class="form-control <?= isset($errors['academic_year']) ? 'is-invalid' : '' ?>" value="<?= h($values['academic_year']) ?>" required>
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
                                <input type="date" id="enrollment_date" name="enrollment_date" class="form-control" value="<?= h($values['enrollment_date']) ?>" required>
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
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                            <a href="<?= BASE_URL ?>admin/enrollments/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
