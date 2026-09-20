<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('teacher');

$db = getDB();
$teacherId = currentTeacherId();

$stmt = $db->prepare('SELECT t.*, d.department_name FROM teachers t LEFT JOIN departments d ON d.id = t.department_id WHERE t.id = ?');
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch();

if (!$teacher) {
    // The linked teacher record is missing/was deleted — fail safely rather than showing empty/wrong data.
    setFlash('danger', 'Your teacher record could not be found. Contact a Super Admin.');
    redirect('auth/logout.php');
}

$courses = $db->prepare("
    SELECT c.*, d.department_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
    FROM courses c
    LEFT JOIN departments d ON d.id = c.department_id
    WHERE c.teacher_id = ?
    ORDER BY c.course_code
");
$courses->execute([$teacherId]);
$courses = $courses->fetchAll();

$stmt2 = $db->prepare('SELECT COUNT(DISTINCT e.student_id) FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE c.teacher_id = ?');
$stmt2->execute([$teacherId]);
$totalStudents = (int) $stmt2->fetchColumn();

$ungradedStmt = $db->prepare('
    SELECT COUNT(*) FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE c.teacher_id = ? AND g.id IS NULL
');
$ungradedStmt->execute([$teacherId]);
$ungradedCount = (int) $ungradedStmt->fetchColumn();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/teacher-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div><h1>Welcome back, <?= h(explode(' ', $teacher['first_name'])[0]) ?></h1></div>
            </div>

            <div class="grid grid-3" style="margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon navy"><i class="fa-solid fa-book"></i></div>
                    <div><div class="stat-value"><?= count($courses) ?></div><div class="stat-label">ASSIGNED COURSES</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-user-graduate"></i></div>
                    <div><div class="stat-value"><?= $totalStudents ?></div><div class="stat-label">STUDENTS TAUGHT</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fa-solid fa-award"></i></div>
                    <div><div class="stat-value"><?= $ungradedCount ?></div><div class="stat-label">GRADES PENDING</div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>My Courses</h3></div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Course</th><th>Department</th><th>Semester</th><th>Students</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$courses): ?>
                            <?= renderEmptyState(6, 'fa-book', 'No courses assigned yet', 'Once the registrar assigns you a course, it will show up here.') ?>
                        <?php endif; ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><strong><?= h($c['course_code']) ?></strong></td>
                                <td><?= h($c['course_name']) ?></td>
                                <td><?= h($c['department_name'] ?? '—') ?></td>
                                <td><?= h($c['semester']) ?></td>
                                <td><?= (int) $c['student_count'] ?></td>
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
