<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$db = getDB();
$studentId = currentStudentId();

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

$courseCountStmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id = ?');
$courseCountStmt->execute([$studentId]);
$courseCount = (int) $courseCountStmt->fetchColumn();

$gpaRows = $db->prepare('
    SELECT c.credit_hours, g.grade_point
    FROM grades g
    JOIN enrollments e ON e.id = g.enrollment_id
    JOIN courses c ON c.id = e.course_id
    WHERE e.student_id = ?
');
$gpaRows->execute([$studentId]);
$gpa = calculateGpa($gpaRows->fetchAll());

$recentCoursesStmt = $db->prepare('
    SELECT c.course_code, c.course_name, e.semester, e.academic_year, e.status, g.grade_letter
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE e.student_id = ?
    ORDER BY e.enrollment_date DESC
    LIMIT 5
');
$recentCoursesStmt->execute([$studentId]);
$recentCourses = $recentCoursesStmt->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/student-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div><h1>Welcome back, <?= h(explode(' ', $student['first_name'])[0]) ?></h1></div>
            </div>

            <div class="grid grid-3" style="margin-bottom:24px;">
                <div class="stat-card">
                    <div class="stat-icon navy"><i class="fa-solid fa-book"></i></div>
                    <div><div class="stat-value"><?= $courseCount ?></div><div class="stat-label">COURSES ENROLLED</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fa-solid fa-award"></i></div>
                    <div><div class="stat-value"><?= number_format($gpa['gpa'], 2) ?></div><div class="stat-label">CUMULATIVE GPA</div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                    <div><div class="stat-value"><?= h($student['status']) ?></div><div class="stat-label">STATUS</div></div>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header"><h3>Recent Courses</h3><a href="<?= BASE_URL ?>student/courses.php" class="btn btn-ghost btn-sm">View all</a></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Course</th><th>Term</th><th>Grade</th></tr></thead>
                            <tbody>
                            <?php if (!$recentCourses): ?>
                                <?= renderEmptyState(3, 'fa-book', 'Not enrolled in any courses yet', 'Once the registrar enrolls you in a course, it will show up here.') ?>
                            <?php endif; ?>
                            <?php foreach ($recentCourses as $c): ?>
                                <tr>
                                    <td><strong><?= h($c['course_code']) ?></strong><br><span style="font-size:0.8rem;color:var(--ink-400);"><?= h($c['course_name']) ?></span></td>
                                    <td><?= h($c['semester']) ?><br><span style="font-size:0.78rem;color:var(--ink-400);"><?= h($c['academic_year']) ?></span></td>
                                    <td><?= $c['grade_letter'] ? '<span class="badge badge-graduated">' . h($c['grade_letter']) . '</span>' : '<span style="color:var(--ink-400);font-size:0.85rem;">Pending</span>' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>My Profile</h3></div>
                    <div class="card-body">
                        <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                            <img class="avatar-sm" style="width:56px;height:56px;" src="<?= h(photoUrl($student['photo'], $student['first_name'] . ' ' . $student['last_name'])) ?>" alt="">
                            <div>
                                <div style="font-weight:600;"><?= h($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                <div style="font-size:0.82rem;color:var(--ink-400);"><?= h($student['student_id']) ?></div>
                            </div>
                        </div>
                        <div style="display:grid;gap:10px;font-size:0.88rem;">
                            <div><strong>Department:</strong> <?= h($student['department_name'] ?: '—') ?></div>
                            <div><strong>Program:</strong> <?= h($student['program'] ?: '—') ?></div>
                            <div><strong>Year:</strong> Year <?= (int) $student['year_level'] ?></div>
                            <div><strong>Email:</strong> <?= h($student['email']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
