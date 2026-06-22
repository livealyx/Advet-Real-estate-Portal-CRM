<?php
// FILE: actions/crm-add-task.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$agentId   = (int)$_SESSION['user']['id'];
$contactId = (int)($_POST['contact_id'] ?? 0);
$title     = trim($_POST['title'] ?? '');
$dueDate   = $_POST['due_date'] ?? '';
$priority  = $_POST['priority'] ?? 'medium';

if (empty($title) || empty($dueDate)) {
    recordFlash('error', 'Title and Due Date are required.');
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO crm_tasks (agent_id, contact_id, title, due_date, priority, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$agentId, $contactId ?: null, $title, $dueDate, $priority]);

    recordFlash('success', 'Task created successfully.');
} catch (Exception $e) {
    recordFlash('error', 'Error: ' . $e->getMessage());
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
