<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$pageTitle = 'Record Grade';
$activeNav = 'grades';

$errors = [];
$values = [
    'enrollment_id' => $_GET['enrollment_id'] ?? '',
    'score' => '',
    'remarks' => '',
];

// Enrollments that don't yet have a grade recorded.
$openEnrollments = $db->query("
    SELECT e.id, e.academic_year, e.semester, s.first_name, s.last_name, s.student_id AS student_code,
           c.course_code, c.course_name
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE g.id IS NULL
    ORDER BY e.enrollment_date DESC
")->fetchAll();

if (!$openEnrollments) {
    setFlash('warning', 'Every current enrollment already has a grade. Enroll a student in a course first.');
    redirect('admin/enrollments/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['enrollment_id'] === '') $errors['enrollment_id'] = 'Please select an enrollment.';
    if ($values['score'] === '' || !is_numeric($values['score']) || (float) $values['score'] < 0 || (float) $values['score'] > 100) {
        $errors['score'] = 'Enter a score between 0 and 100.';
    }

    if (!$errors) {
        // Re-verify server-side that this enrollment actually exists — never trust
        // that a submitted ID matches what was in the pre-rendered dropdown.
        $exists = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE id = ?');
        $exists->execute([$values['enrollment_id']]);
        if ((int) $exists->fetchColumn() === 0) {
            $errors['enrollment_id'] = 'That enrollment could not be found. It may have been removed.';
        }
    }

    if (!$errors) {
        $check = $db->prepare('SELECT COUNT(*) FROM grades WHERE enrollment_id = ?');
        $check->execute([$values['enrollment_id']]);
        if ((int) $check->fetchColumn() > 0) {
            $errors['enrollment_id'] = 'This enrollment already has a grade. Edit it from the Grades list instead.';
        }
    }

    if (!$errors) {
        $result = scoreToGrade((float) $values['score']);
        $stmt = $db->prepare('INSERT INTO grades (enrollment_id, score, grade_letter, grade_point, remarks) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$values['enrollment_id'], $values['score'], $result['letter'], $result['point'], $values['remarks'] ?: null]);
        setFlash('success', 'Grade recorded: ' . $result['letter'] . ' (' . number_format($result['point'], 2) . ').');
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/grades/index.php">Grades</a> / Record</div>
                    <h1>Record Grade</h1>
                </div>
                <a href="<?= BASE_URL ?>admin/grades/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to list</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" action="" data-validate novalidate style="max-width:640px;">
                        <?= csrfField() ?>
                        <div class="form-group">
                            <label for="enrollment_id">Enrollment <span class="req">*</span></label>
                            <select id="enrollment_id" name="enrollment_id" class="form-control <?= isset($errors['enrollment_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">— Select an enrollment —</option>
                                <?php foreach ($openEnrollments as $en): ?>
                                    <option value="<?= (int) $en['id'] ?>" <?= (string) $values['enrollment_id'] === (string) $en['id'] ? 'selected' : '' ?>>
                                        <?= h($en['first_name'] . ' ' . $en['last_name'] . ' (' . $en['student_code'] . ') — ' . $en['course_code'] . ' · ' . $en['semester'] . ' ' . $en['academic_year']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['enrollment_id'])): ?><div class="field-error"><?= h($errors['enrollment_id']) ?></div><?php endif; ?>
                            <div class="form-hint">Only enrollments without a grade yet are listed.</div>
                        </div>
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
                            <input type="text" id="remarks" name="remarks" class="form-control" value="<?= h($values['remarks']) ?>" placeholder="Optional note">
                        </div>
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Grade</button>
                            <a href="<?= BASE_URL ?>admin/grades/index.php" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
