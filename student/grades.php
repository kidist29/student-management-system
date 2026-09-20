<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = currentStudentId();
$pageTitle = 'My Grades';
$activeNav = 'grades';

$stmt = $db->prepare('
    SELECT c.course_code, c.course_name, c.credit_hours, e.semester, e.academic_year,
           g.score, g.grade_letter, g.grade_point, g.remarks
    FROM grades g
    JOIN enrollments e ON e.id = g.enrollment_id
    JOIN courses c ON c.id = e.course_id
    WHERE e.student_id = ?
    ORDER BY e.academic_year DESC, e.semester DESC
');
$stmt->execute([$studentId]);
$grades = $stmt->fetchAll();
$gpa = calculateGpa($grades);

require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/student-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header"><div><h1>My Grades</h1></div></div>

            <div class="grid grid-3" style="margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon navy"><i class="fa-solid fa-award"></i></div>
                    <div><div class="stat-value"><?= number_format($gpa['gpa'], 2) ?></div><div class="stat-label">CUMULATIVE GPA</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div><div class="stat-value"><?= number_format($gpa['total_credits'], 1) ?></div><div class="stat-label">TOTAL CREDITS EARNED</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-book"></i></div>
                    <div><div class="stat-value"><?= count($grades) ?></div><div class="stat-label">GRADED COURSES</div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Grade History</h3>
                    <a href="<?= BASE_URL ?>student/transcript.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-file-lines"></i> View Transcript</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Course</th><th>Credits</th><th>Term</th><th>Score</th><th>Grade</th><th>Points</th></tr></thead>
                        <tbody>
                        <?php if (!$grades): ?>
                            <?= renderEmptyState(7, 'fa-award', 'No grades recorded yet', 'Once a teacher records a grade for one of your courses, it will show up here.') ?>
                        <?php endif; ?>
                        <?php foreach ($grades as $g): ?>
                            <tr>
                                <td><strong><?= h($g['course_code']) ?></strong></td>
                                <td><?= h($g['course_name']) ?></td>
                                <td><?= h(rtrim(rtrim(number_format((float) $g['credit_hours'], 1), '0'), '.')) ?></td>
                                <td><?= h($g['semester']) ?> <?= h($g['academic_year']) ?></td>
                                <td><?= $g['score'] !== null ? number_format((float) $g['score'], 1) : '—' ?></td>
                                <td><span class="badge badge-graduated"><?= h($g['grade_letter']) ?></span></td>
                                <td><?= number_format((float) $g['grade_point'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
