<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('
    SELECT g.*, s.first_name, s.last_name, s.student_id AS student_code, c.course_code, c.course_name, e.semester, e.academic_year
    FROM grades g
    JOIN enrollments e ON e.id = g.enrollment_id
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    WHERE g.id = ?
');
$stmt->execute([$id]);
$grade = $stmt->fetch();

if (!$grade) {
    setFlash('danger', 'Grade not found.');
    redirect('admin/grades/index.php');
}

$pageTitle = 'Edit Grade';
$activeNav = 'grades';
$errors = [];
$values = $grade;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $values['score'] = trim($_POST['score'] ?? '');
    $values['remarks'] = trim($_POST['remarks'] ?? '');

    if ($values['score'] === '' || !is_numeric($values['score']) || (float) $values['score'] < 0 || (float) $values['score'] > 100) {
        $errors['score'] = 'Enter a score between 0 and 100.';
    }

    if (!$errors) {
        $result = scoreToGrade((float) $values['score']);
        $stmt = $db->prepare('UPDATE grades SET score = ?, grade_letter = ?, grade_point = ?, remarks = ? WHERE id = ?');
        $stmt->execute([$values['score'], $result['letter'], $result['point'], $values['remarks'] ?: null, $id]);
        setFlash('success', 'Grade updated: ' . $result['letter'] . ' (' . number_format($result['point'], 2) . ').');
        redirect('admin/grades/index.php');
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/grades/index.php">Grades</a> / Edit</div>
                    <h1>Edit Grade</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/grades/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <p style="margin:0 0 20px;color:var(--ink-600);">
                        <strong><?= h($grade['first_name'] . ' ' . $grade['last_name']) ?></strong> (<?= h($grade['student_code']) ?>)
                        — <?= h($grade['course_code']) ?>: <?= h($grade['course_name']) ?>
                        &middot; <?= h($grade['semester']) ?> <?= h($grade['academic_year']) ?>
                    </p>
                    <form method="post" action="" data-validate novalidate style="max-width:640px;">
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="score">Score (0–100) <span class="req">*</span></label>
                                <input type="number" id="score" name="score" class="form-control <?= isset($errors['score']) ? 'is-invalid' : '' ?>" value="<?= h($values['score']) ?>" min="0" max="100" step="0.01" required data-grade-preview="gradePreview">
                                <?php if (isset($errors['score'])): ?><div class="field-error"><?= h($errors['score']) ?></div><?php else: ?><div class="form-hint">The letter grade and grade point are calculated automatically.</div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Calculated Grade</label>
                                <div id="gradePreview" class="grade-preview">— enter a score —</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <input type="text" id="remarks" name="remarks" class="form-control" value="<?= h($values['remarks']) ?>">
                        </div>
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
                            <a href="<?= BASE_URL ?>admin/grades/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
