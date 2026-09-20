<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('teacher');

$db = getDB();
$teacherId = currentTeacherId();
$pageTitle = 'My Courses';
$activeNav = 'courses';

$stmt = $db->prepare("
    SELECT c.*, d.department_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
    FROM courses c
    LEFT JOIN departments d ON d.id = c.department_id
    WHERE c.teacher_id = ?
    ORDER BY c.course_code
");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/teacher-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div><h1>My Courses</h1></div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Course</th><th>Department</th><th>Credit Hours</th><th>Semester</th><th>Students</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$courses): ?>
                            <?= renderEmptyState(8, 'fa-book', 'No courses assigned yet', 'Once the registrar assigns you a course, it will show up here.') ?>
                        <?php endif; ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><strong><?= h($c['course_code']) ?></strong></td>
                                <td><?= h($c['course_name']) ?></td>
                                <td><?= h($c['department_name'] ?? '—') ?></td>
                                <td><?= h(rtrim(rtrim(number_format((float) $c['credit_hours'], 1), '0'), '.')) ?></td>
                                <td><?= h($c['semester']) ?></td>
                                <td><?= (int) $c['student_count'] ?></td>
                                <td><?= statusBadge($c['status']) ?></td>
                                <td><a href="<?= BASE_URL ?>teacher/course-students.php?course_id=<?= (int) $c['id'] ?>" class="btn btn-ghost btn-sm">View Students</a></td>
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
