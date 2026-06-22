<?php
// FILE: actions/crm-add-lead.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$name       = trim($_POST['name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$source     = $_POST['source'] ?? 'Walk-in';
$pType      = $_POST['property_type'] ?? null;
$budget     = trim($_POST['budget'] ?? '');
$agentId    = (int)($_POST['assigned_to'] ?? 0);
$propertyId = (int)($_POST['property_id'] ?? 0);

if (empty($name) || (empty($email) && empty($phone))) {
    recordFlash('error', 'Name and Contact info are required.');
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

try {
    require_once '../includes/crm-utils.php';
    
    // 1. Manually capture lead (bypassing round-robin if agent is forced)
    $emailVal = $email ?: null;
    $stmt = $pdo->prepare("INSERT INTO crm_contacts (name, email, phone, source, assigned_to, property_type, budget) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $emailVal, $phone, $source, $agentId ?: null, $pType, $budget]);
    $contactId = $pdo->lastInsertId();

    // 2. Create initial deal in "Initial Inquiry" stage
    $stmtStage = $pdo->query("SELECT id FROM crm_stages ORDER BY display_order ASC LIMIT 1");
    $stageId = $stmtStage->fetchColumn();

    $stmtDeal = $pdo->prepare("INSERT INTO crm_deals (contact_id, property_id, stage_id) VALUES (?, ?, ?)");
    $stmtDeal->execute([$contactId, $propertyId ?: null, $stageId]);
    
    // 3. Log initial activity
    crmLogActivity($pdo, $contactId, 'note', "Lead manually added via admin portal. Source: $source.");

    recordFlash('success', 'Lead added successfully.');
} catch (Exception $e) {
    recordFlash('error', 'Error: ' . $e->getMessage());
}

header('Location: ' . BASE . 'admin/crm-leads.php');
exit;
