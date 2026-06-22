<?php
// FILE: actions/update-inquiry-status.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'admin/inquiries.php'); exit; }

$pdo      = getPDO();
$userRole = $_SESSION['user']['role'];
$userId   = (int)$_SESSION['user']['id'];

$id       = (int)($_POST['id']     ?? 0);
$status   = $_POST['status']       ?? '';
$redirect = $_POST['redirect']     ?? (BASE . 'admin/inquiries.php');

$allowed = ['new', 'read', 'replied'];
if (!$id || !in_array($status, $allowed, true)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid request.'];
    header('Location: ' . $redirect); exit;
}

// Authorization check for agents
if ($userRole === 'agent') {
    $check = $pdo->prepare("SELECT i.id FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE i.id = ? AND p.agent_id = ?");
    $check->execute([$id, $userId]);
    if (!$check->fetch()) {
         $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unauthorized.'];
         header('Location: ' . $redirect); exit;
    }
}

$stmt = $pdo->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Inquiry marked as ' . $status . '.'];
header('Location: ' . $redirect);
exit;
