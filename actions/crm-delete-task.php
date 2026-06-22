<?php
// FILE: actions/crm-delete-task.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM crm_tasks WHERE id = ?");
    $stmt->execute([$id]);
    recordFlash('success', 'Task archived successfully.');
} catch (Exception $e) {
    recordFlash('error', 'Error: ' . $e->getMessage());
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
