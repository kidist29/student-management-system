<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/courses/index.php');
}
verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT course_name FROM courses WHERE id = ?');
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    setFlash('danger', 'Course not found.');
    redirect('admin/courses/index.php');
}

// Safe delete: a course with enrollment/grade history is never silently
// deleted (the database's ON DELETE CASCADE would otherwise wipe that
// history along with it). Set it to Inactive instead.
$historyStmt = $db->prepare('SELECT COUNT(*) FROM enrollments WHERE course_id = ?');
$historyStmt->execute([$id]);
$historyCount = (int) $historyStmt->fetchColumn();

if ($historyCount > 0) {
    setFlash('danger', 'Cannot delete "' . $course['course_name'] . '" — it has ' . $historyCount
        . ' enrollment record(s) with academic history. Set its status to Inactive instead, '
        . 'or remove its enrollments first if you\'re certain.');
    redirect('admin/courses/index.php');
}

$del = $db->prepare('DELETE FROM courses WHERE id = ?');
$del->execute([$id]);
setFlash('success', 'Course "' . $course['course_name'] . '" was deleted.');

redirect('admin/courses/index.php');
