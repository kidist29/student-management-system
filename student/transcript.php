<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = currentStudentId();
$pageTitle = 'My Transcript';
$activeNav = 'transcript';

$stmt = $db->prepare('
    SELECT s.*, d.department_name
    FROM students s
    LEFT JOIN departments d ON d.id = s.department_id
    WHERE s.id = ?
');
$stmt->execute([$studentId]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('danger', 'Your student record could not be found. Contact a Super Admin.');
    redirect('auth/logout.php');
}

$rows = $db->prepare('
    SELECT e.academic_year, e.semester, c.course_code, c.course_name, c.credit_hours,
           g.grade_letter, g.grade_point
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    JOIN grades g ON g.enrollment_id = e.id
    WHERE e.student_id = ?
    ORDER BY e.academic_year ASC, e.semester ASC
');
$rows->execute([$studentId]);
$allRows = $rows->fetchAll();

$termGroups = [];
foreach ($allRows as $row) {
    $key = $row['academic_year'] . ' — ' . $row['semester'];
    $termGroups[$key]['label'] = $row['semester'] . ', ' . $row['academic_year'];
    $termGroups[$key]['rows'][] = $row;
}
foreach ($termGroups as $key => $group) {
    $termGroups[$key]['gpa'] = calculateGpa($group['rows']);
}
$overall = calculateGpa($allRows);

require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/student-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header no-print">
                <div><h1>My Transcript</h1></div>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Transcript</button>
            </div>

            <div class="transcript-sheet">
                <div class="transcript-head">
                    <div class="institution">
                        <div class="seal"><?= h(INSTITUTION_INITIAL) ?></div>
                        <div>
                            <h2><?= h(INSTITUTION_NAME) ?></h2>
                            <p>Office of the Registrar &middot; Addis Ababa, Ethiopia</p>
                        </div>
                    </div>
                    <div class="doc-label">
                        <div class="tag">OFFICIAL ACADEMIC TRANSCRIPT</div>
                        <div class="date">Generated <?= h(date('F j, Y \a\t g:i A')) ?></div>
                    </div>
                </div>

                <div class="transcript-student">
                    <img src="<?= h(photoUrl($student['photo'], $student['first_name'] . ' ' . $student['last_name'])) ?>" alt="">
                    <div class="grid-fields">
                        <div><div class="lbl">Student Name</div><div class="val"><?= h($student['first_name'] . ' ' . $student['last_name']) ?></div></div>
                        <div><div class="lbl">Student ID</div><div class="val"><?= h($student['student_id']) ?></div></div>
                        <div><div class="lbl">Status</div><div class="val"><?= h($student['status']) ?></div></div>
                        <div><div class="lbl">Department</div><div class="val"><?= h($student['department_name'] ?: '—') ?></div></div>
                        <div><div class="lbl">Program</div><div class="val"><?= h($student['program'] ?: '—') ?></div></div>
                        <div><div class="lbl">Admission Date</div><div class="val"><?= $student['admission_date'] ? h(date('M j, Y', strtotime($student['admission_date']))) : '—' ?></div></div>
                    </div>
                </div>

                <?php if (!$termGroups): ?>
                    <p style="text-align:center;color:var(--ink-400);padding:24px 0;">No graded coursework on record yet.</p>
                <?php endif; ?>

                <?php foreach ($termGroups as $group): ?>
                    <div class="transcript-term">
                        <h4><?= h($group['label']) ?></h4>
                        <table>
                            <thead><tr><th>Code</th><th>Course</th><th>Credits</th><th>Grade</th><th>Points</th></tr></thead>
                            <tbody>
                            <?php foreach ($group['rows'] as $row): ?>
                                <tr>
                                    <td><?= h($row['course_code']) ?></td>
                                    <td><?= h($row['course_name']) ?></td>
                                    <td><?= h(rtrim(rtrim(number_format((float) $row['credit_hours'], 1), '0'), '.')) ?></td>
                                    <td><?= h($row['grade_letter']) ?></td>
                                    <td><?= number_format((float) $row['grade_point'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Semester GPA</td>
                                    <td><?= number_format($group['gpa']['total_credits'], 1) ?></td>
                                    <td colspan="2"><?= number_format($group['gpa']['gpa'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endforeach; ?>

                <?php if ($termGroups): ?>
                    <div class="transcript-summary">
                        <div class="box"><div class="num"><?= number_format($overall['total_credits'], 1) ?></div><div class="lbl">TOTAL CREDITS EARNED</div></div>
                        <div class="box"><div class="num"><?= number_format($overall['gpa'], 2) ?></div><div class="lbl">CUMULATIVE GPA</div></div>
                        <div class="box"><div class="num"><?= count($termGroups) ?></div><div class="lbl">TERMS ON RECORD</div></div>
                    </div>
                <?php endif; ?>

                <div class="transcript-signatures">
                    <div class="sig-block"><div class="sig-line"></div><div class="sig-label">Registrar's Signature</div></div>
                    <div class="sig-block"><div class="sig-line"></div><div class="sig-label">Official Seal</div></div>
                    <div class="sig-block"><div class="sig-line"></div><div class="sig-label">Date Issued</div></div>
                </div>

                <div class="transcript-footer">
                    <span><?= h(INSTITUTION_NAME) ?> &middot; <?= h(INSTITUTION_ADDRESS) ?></span>
                    <span>This is an official document generated electronically on <?= h(date('F j, Y \a\t g:i A')) ?>.</span>
                </div>
                <div class="transcript-print-footer">
                    <?= h(INSTITUTION_NAME) ?> — Official Academic Transcript for <?= h($student['first_name'] . ' ' . $student['last_name']) ?> (<?= h($student['student_id']) ?>) — Generated <?= h(date('F j, Y')) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
