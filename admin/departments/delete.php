<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/departments/index.php');
}
verifyCsrf();

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT department_name FROM departments WHERE id = ?');
$stmt->execute([$id]);
$department = $stmt->fetch();

if ($department) {
    // Students/teachers/courses referencing this department have ON DELETE SET NULL,
    // so they're kept and simply become "unassigned" rather than being deleted.
    $del = $db->prepare('DELETE FROM departments WHERE id = ?');
    $del->execute([$id]);
    setFlash('success', 'Department "' . $department['department_name'] . '" was deleted.');
} else {
    setFlash('danger', 'Department not found.');
}

redirect('admin/departments/index.php');
