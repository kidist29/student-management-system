<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/users/index.php');
}
verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
    setFlash('danger', "You can't delete your own account.");
    redirect('admin/users/index.php');
}

$stmt = $db->prepare('SELECT full_name FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if ($user) {
    $del = $db->prepare('DELETE FROM users WHERE id = ?');
    $del->execute([$id]);
    setFlash('success', 'User "' . $user['full_name'] . '" was deleted.');
} else {
    setFlash('danger', 'User not found.');
}

redirect('admin/users/index.php');
