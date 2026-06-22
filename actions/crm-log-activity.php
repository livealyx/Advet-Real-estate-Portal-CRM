<?php
// FILE: actions/crm-log-activity.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$contactId = (int)($_POST['contact_id'] ?? 0);
$type      = $_POST['type'] ?? 'note';
$details   = trim($_POST['details'] ?? '');
$agentId   = (int)$_SESSION['user']['id'];

if (!$contactId || empty($details)) {
    recordFlash('error', 'Details are required to log activity.');
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

try {
    require_once '../includes/crm-utils.php';
    crmLogActivity($pdo, $contactId, strtolower($type), $details);
    recordFlash('success', 'Activity logged successfully.');
} catch (Exception $e) {
    recordFlash('error', 'Error: ' . $e->getMessage());
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
