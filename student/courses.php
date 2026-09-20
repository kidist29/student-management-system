<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = currentStudentId();
$pageTitle = 'My Courses';
$activeNav = 'courses';

$stmt = $db->prepare('
    SELECT e.*, c.course_code, c.course_name, c.credit_hours, t.first_name AS t_first, t.last_name AS t_last, g.grade_letter
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN teachers t ON t.id = c.teacher_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE e.student_id = ?
    ORDER BY e.academic_year DESC, e.semester DESC
');
$stmt->execute([$studentId]);
$courses = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/student-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header"><div><h1>My Courses</h1></div></div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Course</th><th>Credits</th><th>Teacher</th><th>Term</th><th>Status</th><th>Grade</th></tr></thead>
                        <tbody>
                        <?php if (!$courses): ?>
                            <?= renderEmptyState(7, 'fa-book', 'Not enrolled in any courses yet', 'Once the registrar enrolls you in a course, it will show up here.') ?>
                        <?php endif; ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><strong><?= h($c['course_code']) ?></strong></td>
                                <td><?= h($c['course_name']) ?></td>
                                <td><?= h(rtrim(rtrim(number_format((float) $c['credit_hours'], 1), '0'), '.')) ?></td>
                                <td><?= $c['t_first'] ? h($c['t_first'] . ' ' . $c['t_last']) : '—' ?></td>
                                <td><?= h($c['semester']) ?> <?= h($c['academic_year']) ?></td>
                                <td><?= enrollmentStatusBadge($c['status']) ?></td>
                                <td><?= $c['grade_letter'] ? '<span class="badge badge-graduated">' . h($c['grade_letter']) . '</span>' : '<span style="color:var(--ink-400);font-size:0.85rem;">Pending</span>' ?></td>
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
