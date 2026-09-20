<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('teacher');

$db = getDB();
$teacherId = currentTeacherId();
$enrollmentId = (int) ($_GET['enrollment_id'] ?? 0);

// Ownership check: the enrollment's course must belong to this teacher.
// This is the server-side enforcement — a teacher cannot grade a student
// in a course they don't teach, no matter what enrollment_id is put in the URL.
$stmt = $db->prepare("
    SELECT e.id AS enrollment_id, e.academic_year, e.semester,
           s.first_name, s.last_name, s.student_id AS student_code,
           c.id AS course_id, c.course_code, c.course_name, c.teacher_id,
           g.id AS grade_id, g.score, g.remarks
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE e.id = ? AND c.teacher_id = ?
");
$stmt->execute([$enrollmentId, $teacherId]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    setFlash('danger', 'That enrollment was not found in one of your courses.');
    redirect('teacher/courses.php');
}

$pageTitle = ($enrollment['grade_id'] ? 'Edit' : 'Add') . ' Grade';
$activeNav = 'courses';
$errors = [];
$values = ['score' => $enrollment['score'] ?? '', 'remarks' => $enrollment['remarks'] ?? ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $values['score'] = trim($_POST['score'] ?? '');
    $values['remarks'] = trim($_POST['remarks'] ?? '');

    if ($values['score'] === '' || !is_numeric($values['score']) || (float) $values['score'] < 0 || (float) $values['score'] > 100) {
        $errors['score'] = 'Enter a score between 0 and 100.';
    }

    if (!$errors) {
        $result = scoreToGrade((float) $values['score']);
        if ($enrollment['grade_id']) {
            $upd = $db->prepare('UPDATE grades SET score = ?, grade_letter = ?, grade_point = ?, remarks = ? WHERE id = ?');
            $upd->execute([$values['score'], $result['letter'], $result['point'], $values['remarks'] ?: null, $enrollment['grade_id']]);
        } else {
            $ins = $db->prepare('INSERT INTO grades (enrollment_id, score, grade_letter, grade_point, remarks) VALUES (?, ?, ?, ?, ?)');
            $ins->execute([$enrollmentId, $values['score'], $result['letter'], $result['point'], $values['remarks'] ?: null]);
        }
        setFlash('success', 'Grade saved for ' . $enrollment['first_name'] . ' ' . $enrollment['last_name'] . ': ' . $result['letter'] . '.');
        redirect('teacher/course-students.php?course_id=' . $enrollment['course_id']);
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/teacher-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>teacher/courses.php">My Courses</a> / <a href="<?= BASE_URL ?>teacher/course-students.php?course_id=<?= (int) $enrollment['course_id'] ?>"><?= h($enrollment['course_code']) ?></a> / Grade</div>
                    <h1><?= h($pageTitle) ?></h1>
                </div>
                <a href="<?= BASE_URL ?>teacher/course-students.php?course_id=<?= (int) $enrollment['course_id'] ?>" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to Roster</a>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <p style="margin:0 0 20px;color:var(--ink-600);">
                        <strong><?= h($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></strong> (<?= h($enrollment['student_code']) ?>)
                        — <?= h($enrollment['course_code']) ?>: <?= h($enrollment['course_name']) ?>
                        &middot; <?= h($enrollment['semester']) ?> <?= h($enrollment['academic_year']) ?>
                    </p>
                    <form method="post" action="" data-validate novalidate style="max-width:520px;">
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
                            <input type="text" id="remarks" name="remarks" class="form-control" value="<?= h($values['remarks']) ?>" placeholder="Optional note">
                        </div>
                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Grade</button>
                            <a href="<?= BASE_URL ?>teacher/course-students.php?course_id=<?= (int) $enrollment['course_id'] ?>" class="btn btn-ghost">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
