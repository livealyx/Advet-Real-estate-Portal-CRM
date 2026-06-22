<?php
// FILE: actions/delete-project.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $pdo = getPDO();
    $id = (int)$_POST['id'];

    // Optional: Security check for agents (if needed, but for now assuming admin/agent can delete if they can add)
    
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Project deleted successfully.'];
}

header('Location: ' . BASE . 'admin/projects.php');
exit;
