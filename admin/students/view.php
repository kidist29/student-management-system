<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('
    SELECT s.*, d.department_name
    FROM students s
    LEFT JOIN departments d ON d.id = s.department_id
    WHERE s.id = ?
');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('danger', 'Student not found.');
    redirect('admin/students/index.php');
}

$fullName = $student['first_name'] . ' ' . $student['last_name'];
$pageTitle = $fullName;
$activeNav = 'students';

$fields = [
    'Student ID'     => $student['student_id'],
    'Gender'         => $student['gender'],
    'Date of Birth'  => $student['date_of_birth'] ? date('F j, Y', strtotime($student['date_of_birth'])) : '—',
    'Email'          => $student['email'],
    'Phone'          => $student['phone'] ?: '—',
    'Address'        => $student['address'] ?: '—',
    'Department'     => $student['department_name'] ?: '—',
    'Program'        => $student['program'] ?: '—',
    'Year'           => 'Year ' . (int) $student['year_level'],
    'Semester'       => $student['semester'],
    'Admission Date' => $student['admission_date'] ? date('F j, Y', strtotime($student['admission_date'])) : '—',
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
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / <a href="<?= BASE_URL ?>admin/students/index.php">Students</a> / <?= h($fullName) ?></div>
                    <h1><?= h($fullName) ?></h1>
                </div>
                <div style="display:flex;gap:10px;">
                    <a href="<?= BASE_URL ?>admin/students/edit.php?id=<?= (int) $student['id'] ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
                    <a href="<?= BASE_URL ?>admin/enrollments/create.php?student_id=<?= (int) $student['id'] ?>" class="btn btn-outline"><i class="fa-solid fa-clipboard-list"></i> Enroll</a>
                    <a href="<?= BASE_URL ?>admin/transcript/index.php?student_id=<?= (int) $student['id'] ?>" class="btn btn-outline"><i class="fa-solid fa-file-lines"></i> Transcript</a>
                    <a href="<?= BASE_URL ?>admin/students/index.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <div class="grid grid-2">
                <div class="card">
                    <div class="card-header"><h3>Student Details</h3><?= statusBadge($student['status']) ?></div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">
                            <?php foreach ($fields as $label => $value): ?>
                                <div>
                                    <div style="font-size:0.75rem;font-weight:700;color:var(--ink-400);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:3px;"><?= h($label) ?></div>
                                    <div style="font-size:0.95rem;color:var(--ink-900);"><?= h($value) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align:center;">
                        <img src="<?= h(photoUrl($student['photo'], $fullName)) ?>" alt="<?= h($fullName) ?>"
                             style="width:160px;height:160px;border-radius:50%;object-fit:cover;border:4px solid var(--paper-100);margin-bottom:16px;">
                        <h3 style="margin-bottom:2px;"><?= h($fullName) ?></h3>
                        <p style="margin:0;"><?= h($student['student_id']) ?></p>
                        <div style="margin-top:14px;"><?= statusBadge($student['status']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
