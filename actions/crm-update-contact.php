<?php
// FILE: actions/crm-update-contact.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$id     = (int)($_POST['id'] ?? 0);
$name   = trim($_POST['name'] ?? '');
$email  = trim($_POST['email'] ?? '');
$phone  = trim($_POST['phone'] ?? '');
$pType  = $_POST['property_type'] ?? null;
$budget = trim($_POST['budget'] ?? '');

if (!$id || empty($name)) {
    recordFlash('error', 'Valid ID and Name are required.');
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

try {
    $stmt = $pdo->prepare("UPDATE crm_contacts SET name = ?, email = ?, phone = ?, property_type = ?, budget = ? WHERE id = ?");
    $stmt->execute([$name, $email, $phone, $pType, $budget, $id]);

    require_once '../includes/crm-utils.php';
    crmLogActivity($pdo, $id, 'note', "Contact profile updated: Name, contact info, or preferences changed.");

    recordFlash('success', 'Contact updated successfully.');
} catch (Exception $e) {
    recordFlash('error', 'Error: ' . $e->getMessage());
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
