<?php
// FILE: actions/delete-property.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'admin/listings.php'); exit; }

$pdo = getPDO();
$id  = (int)($_POST['id'] ?? 0);
$redirect = $_POST['redirect'] ?? (BASE . 'admin/listings.php');

if (!$id) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid property ID.'];
    header('Location: ' . $redirect); exit;
}

$userRole = $_SESSION['user']['role'];
$userId   = (int)$_SESSION['user']['id'];

// Authorization check for agents
if ($userRole === 'agent') {
    $check = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND agent_id = ?");
    $check->execute([$id, $userId]);
    if (!$check->fetch()) {
         $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unauthorized to delete this property.'];
         header('Location: ' . $redirect); exit;
    }
}

$stmt = $pdo->prepare("SELECT title FROM properties WHERE id = ?");
$stmt->execute([$id]);
$prop = $stmt->fetch();

if (!$prop) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Property not found.'];
    header('Location: ' . $redirect); exit;
}

// Also set property_id = NULL on related inquiries (FK handles this via ON DELETE SET NULL, but explicit is safer)
$pdo->prepare("DELETE FROM properties WHERE id = ?")->execute([$id]);

$_SESSION['flash'] = ['type' => 'success', 'msg' => "\"" . $prop['title'] . "\" has been deleted."];
header('Location: ' . $redirect);
exit;
