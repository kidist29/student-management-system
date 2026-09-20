<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM departments WHERE id = ?');
$stmt->execute([$id]);
$department = $stmt->fetch();

if (!$department) {
    setFlash('danger', 'Department not found.');
    redirect('admin/departments/index.php');
}

$studentsStmt = $db->prepare('SELECT id, first_name, last_name, student_id, status FROM students WHERE department_id = ? ORDER BY first_name LIMIT 6');
$studentsStmt->execute([$id]);
$deptStudents = $studentsStmt->fetchAll();

$teachersStmt = $db->prepare('SELECT id, first_name, last_name, teacher_code, status FROM teachers WHERE department_id = ? ORDER BY first_name LIMIT 6');
$teachersStmt->execute([$id]);
$deptTeachers = $teachersStmt->fetchAll();

$coursesStmt = $db->prepare('SELECT id, course_code, course_name, status FROM courses WHERE department_id = ? ORDER BY course_code LIMIT 6');
$coursesStmt->execute([$id]);
$deptCourses = $coursesStmt->fetchAll();

$pageTitle = $department['department_name'];
$activeNav = 'departments';

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/departments/index.php">Departments</a> / <?= h($department['department_name']) ?></div>
                    <h1><?= h($department['department_name']) ?></h1>
                </div>
                <div style="display:flex;gap:10px;">
                    <a href="<?= BASE_URL ?>admin/departments/edit.php?id=<?= (int) $department['id'] ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
                    <a href="<?= BASE_URL ?>admin/departments/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <div class="card" style="margin-bottom:22px;">
                <div class="card-header"><h3>Department Details</h3><?= statusBadge($department['status']) ?></div>
                <div class="card-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;">
                    <div><div style="font-size:0.75rem;font-weight:700;color:var(--ink-400);text-transform:uppercase;">Code</div><div><?= h($department['department_code']) ?></div></div>
                    <div><div style="font-size:0.75rem;font-weight:700;color:var(--ink-400);text-transform:uppercase;">Head</div><div><?= h($department['department_head'] ?: '—') ?></div></div>
                    <div><div style="font-size:0.75rem;font-weight:700;color:var(--ink-400);text-transform:uppercase;">Status</div><div><?= h($department['status']) ?></div></div>
                    <div style="grid-column:1/-1;"><div style="font-size:0.75rem;font-weight:700;color:var(--ink-400);text-transform:uppercase;">Description</div><div><?= h($department['description'] ?: '—') ?></div></div>
                </div>
            </div>

            <div class="grid grid-3">
                <div class="card">
                    <div class="card-header"><h3>Students</h3></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Name</th><th>ID</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (!$deptStudents): ?><tr><td colspan="3" style="text-align:center;color:var(--ink-400);">No students in this department yet.</td></tr><?php endif; ?>
                            <?php foreach ($deptStudents as $s): ?>
                                <tr>
                                    <td><a href="<?= BASE_URL ?>admin/students/view.php?id=<?= (int)$s['id'] ?>"><?= h($s['first_name'] . ' ' . $s['last_name']) ?></a></td>
                                    <td><?= h($s['student_id']) ?></td>
                                    <td><?= statusBadge($s['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3>Teachers</h3></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Name</th><th>ID</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (!$deptTeachers): ?><tr><td colspan="3" style="text-align:center;color:var(--ink-400);">No teachers in this department yet.</td></tr><?php endif; ?>
                            <?php foreach ($deptTeachers as $t): ?>
                                <tr>
                                    <td><a href="<?= BASE_URL ?>admin/teachers/view.php?id=<?= (int)$t['id'] ?>"><?= h($t['first_name'] . ' ' . $t['last_name']) ?></a></td>
                                    <td><?= h($t['teacher_code']) ?></td>
                                    <td><?= statusBadge($t['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3>Courses</h3></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Code</th><th>Course</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php if (!$deptCourses): ?><tr><td colspan="3" style="text-align:center;color:var(--ink-400);">No courses in this department yet.</td></tr><?php endif; ?>
                            <?php foreach ($deptCourses as $c): ?>
                                <tr>
                                    <td><strong><?= h($c['course_code']) ?></strong></td>
                                    <td><?= h($c['course_name']) ?></td>
                                    <td><?= statusBadge($c['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
