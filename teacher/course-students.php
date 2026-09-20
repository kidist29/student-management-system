<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('teacher');

$db = getDB();
$teacherId = currentTeacherId();
$courseId = (int) ($_GET['course_id'] ?? 0);

// Ownership check: this course must belong to the logged-in teacher.
// A teacher trying another course's ID in the URL gets treated as not-found,
// not shown someone else's roster.
$courseStmt = $db->prepare("
    SELECT c.*, d.department_name
    FROM courses c
    LEFT JOIN departments d ON d.id = c.department_id
    WHERE c.id = ? AND c.teacher_id = ?
");
$courseStmt->execute([$courseId, $teacherId]);
$course = $courseStmt->fetch();

if (!$course) {
    setFlash('danger', 'That course was not found in your assigned courses.');
    redirect('teacher/courses.php');
}

$pageTitle = $course['course_code'] . ' Roster';
$activeNav = 'courses';

$studentsStmt = $db->prepare("
    SELECT e.id AS enrollment_id, e.status AS enrollment_status, e.enrollment_date,
           s.id AS student_pk, s.first_name, s.last_name, s.student_id, s.photo,
           g.id AS grade_id, g.grade_letter
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    LEFT JOIN grades g ON g.enrollment_id = e.id
    WHERE e.course_id = ?
    ORDER BY s.first_name
");
$studentsStmt->execute([$courseId]);
$students = $studentsStmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../includes/teacher-nav.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>teacher/courses.php">My Courses</a> / <?= h($course['course_code']) ?></div>
                    <h1><?= h($course['course_code']) ?> — <?= h($course['course_name']) ?></h1>
                </div>
                <a href="<?= BASE_URL ?>teacher/courses.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back to My Courses</a>
            </div>

            <div class="card">
                <div class="card-header"><h3>Enrolled Students</h3></div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Student</th><th>Status</th><th>Enrolled On</th><th>Grade</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$students): ?>
                            <?= renderEmptyState(5, 'fa-user-graduate', 'No students enrolled yet', 'Once a student is enrolled in this course, they will show up here.') ?>
                        <?php endif; ?>
                        <?php foreach ($students as $s): $fullName = $s['first_name'] . ' ' . $s['last_name']; ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <img class="avatar-sm" src="<?= h(photoUrl($s['photo'], $fullName)) ?>" alt="">
                                        <div>
                                            <div style="font-weight:600;"><?= h($fullName) ?></div>
                                            <div style="font-size:0.78rem;color:var(--ink-400);"><?= h($s['student_id']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= enrollmentStatusBadge($s['enrollment_status']) ?></td>
                                <td><?= h(date('M j, Y', strtotime($s['enrollment_date']))) ?></td>
                                <td>
                                    <?php if ($s['grade_letter']): ?>
                                        <span class="badge badge-graduated"><?= h($s['grade_letter']) ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--ink-400);font-size:0.85rem;">Not graded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>teacher/grade.php?enrollment_id=<?= (int) $s['enrollment_id'] ?>" class="btn btn-ghost btn-sm">
                                        <?= $s['grade_letter'] ? 'Edit Grade' : 'Add Grade' ?>
                                    </a>
                                </td>
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
