<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/enrollments/index.php');
}
verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT id FROM enrollments WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->fetch()) {
    // The "grades" table has ON DELETE CASCADE on enrollment_id, so any
    // recorded grade for this enrollment is removed automatically.
    $del = $db->prepare('DELETE FROM enrollments WHERE id = ?');
    $del->execute([$id]);
    setFlash('success', 'The enrollment was removed.');
} else {
    setFlash('danger', 'Enrollment not found.');
}

redirect('admin/enrollments/index.php');
