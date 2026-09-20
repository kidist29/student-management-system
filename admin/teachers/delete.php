<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/teachers/index.php');
}
verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT first_name, last_name, photo FROM teachers WHERE id = ?');
$stmt->execute([$id]);
$teacher = $stmt->fetch();

if ($teacher) {
    $del = $db->prepare('DELETE FROM teachers WHERE id = ?');
    $del->execute([$id]);
    deletePhotoFile($teacher['photo']);
    setFlash('success', 'Teacher "' . $teacher['first_name'] . ' ' . $teacher['last_name'] . '" was deleted.');
} else {
    setFlash('danger', 'Teacher not found.');
}

redirect('admin/teachers/index.php');
