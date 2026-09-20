<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('
    SELECT t.*, d.department_name
    FROM teachers t
    LEFT JOIN departments d ON d.id = t.department_id
    WHERE t.id = ?
');
$stmt->execute([$id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    setFlash('danger', 'Teacher not found.');
    redirect('admin/teachers/index.php');
}

$coursesStmt = $db->prepare('SELECT id, course_code, course_name, semester FROM courses WHERE teacher_id = ? ORDER BY course_name');
$coursesStmt->execute([$id]);
$teacherCourses = $coursesStmt->fetchAll();

$fullName = $teacher['first_name'] . ' ' . $teacher['last_name'];
$pageTitle = $fullName;
$activeNav = 'teachers';

$fields = [
    'Teacher ID'    => $teacher['teacher_code'],
    'Gender'        => $teacher['gender'],
    'Email'         => $teacher['email'],
    'Phone'         => $teacher['phone'] ?: '—',
    'Department'    => $teacher['department_name'] ?: '—',
    'Qualification' => $teacher['qualification'] ?: '—',
];

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/teachers/index.php">Teachers</a> / <?= h($fullName) ?></div>
                    <h1><?= h($fullName) ?></h1>
                </div>
                <div style="display:flex;gap:10px;">
                    <a href="<?= BASE_URL ?>admin/teachers/edit.php?id=<?= (int) $teacher['id'] ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
                    <a href="<?= BASE_URL ?>admin/teachers/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <div class="grid grid-2" style="margin-bottom:22px;">
                <div class="card">
                    <div class="card-header"><h3>Teacher Details</h3><?= statusBadge($teacher['status']) ?></div>
                    <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                        <?php foreach ($fields as $label => $value): ?>
                            <div>
                                <div style="font-size:0.75rem;font-weight:700;color:var(--ink-400);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:3px;"><?= h($label) ?></div>
                                <div style="font-size:0.95rem;color:var(--ink-900);"><?= h($value) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="text-align:center;">
                        <img src="<?= h(photoUrl($teacher['photo'], $fullName)) ?>" alt="<?= h($fullName) ?>"
                             style="width:140px;height:140px;border-radius:50%;object-fit:cover;border:4px solid var(--paper-100);margin-bottom:14px;">
                        <h3 style="margin-bottom:2px;"><?= h($fullName) ?></h3>
                        <p style="margin:0;"><?= h($teacher['teacher_code']) ?></p>
                        <div style="margin-top:12px;"><?= statusBadge($teacher['status']) ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Courses Taught</h3></div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Code</th><th>Course</th><th>Semester</th></tr></thead>
                        <tbody>
                        <?php if (!$teacherCourses): ?><tr><td colspan="3" style="text-align:center;color:var(--ink-400);padding:20px;">No courses assigned yet.</td></tr><?php endif; ?>
                        <?php foreach ($teacherCourses as $c): ?>
                            <tr>
                                <td><strong><?= h($c['course_code']) ?></strong></td>
                                <td><?= h($c['course_name']) ?></td>
                                <td><?= h($c['semester']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
