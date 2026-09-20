<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/students/index.php');
}

verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT first_name, last_name, photo FROM students WHERE id = ?');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('danger', 'Student not found.');
    redirect('admin/students/index.php');
}

// Safe delete: a student with enrollment/grade history is never silently
// deleted (the database's ON DELETE CASCADE would otherwise wipe that
// academic record along with them). Mark them Inactive or Graduated instead.
$historyStmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id = ?');
$historyStmt->execute([$id]);
$historyCount = (int) $historyStmt->fetchColumn();

if ($historyCount > 0) {
    setFlash('danger', 'Cannot delete "' . $student['first_name'] . ' ' . $student['last_name'] . '" — they have '
        . $historyCount . ' enrollment record(s) with academic history. Set their status to Inactive or Graduated instead, '
        . 'or remove their enrollments first if you\'re certain.');
    redirect('admin/students/index.php');
}

$del = $db->prepare('DELETE FROM students WHERE id = ?');
$del->execute([$id]);
deletePhotoFile($student['photo']);
setFlash('success', 'Student "' . $student['first_name'] . ' ' . $student['last_name'] . '" was deleted.');

redirect('admin/students/index.php');
