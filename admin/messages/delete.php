<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/messages/index.php');
}
verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);
$db->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
setFlash('success', 'Message deleted.');
redirect('admin/messages/index.php');
