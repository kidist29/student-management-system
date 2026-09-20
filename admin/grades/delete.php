<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/grades/index.php');
}
verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT id FROM grades WHERE id = ?');
$stmt->execute([$id]);

if ($stmt->fetch()) {
    $del = $db->prepare('DELETE FROM grades WHERE id = ?');
    $del->execute([$id]);
    setFlash('success', 'Grade deleted.');
} else {
    setFlash('danger', 'Grade not found.');
}

redirect('admin/grades/index.php');
