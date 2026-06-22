<?php
// FILE: actions/crm-create-deal.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$contactId = (int)($_POST['contact_id'] ?? 0);
$propId    = (int)($_POST['property_id'] ?? 0);
$value     = (float)($_POST['value'] ?? 0);
$stageId   = (int)($_POST['stage_id'] ?? 0);

if (!$contactId || !$stageId) {
    recordFlash('error', 'Contact and Pipeline Stage are required.');
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

try {
    // Get property title for the deal name if available
    $title = "Deal";
    if ($propId) {
        $stmtP = $pdo->prepare("SELECT title FROM properties WHERE id = ?");
        $stmtP->execute([$propId]);
        $title = $stmtP->fetchColumn() ?: "Deal";
    } else {
        $stmtC = $pdo->prepare("SELECT name FROM crm_contacts WHERE id = ?");
        $stmtC->execute([$contactId]);
        $title = "Opportunity: " . $stmtC->fetchColumn();
    }

    $stmt = $pdo->prepare("INSERT INTO crm_deals (contact_id, property_id, stage_id, deal_value, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->execute([$contactId, $propId ?: null, $stageId, $value]);

    require_once '../includes/crm-utils.php';
    $fmtValue = formatPrice($value);
    crmLogActivity($pdo, $contactId, 'note', "New Deal opened: $title with projected value of $fmtValue.");

    recordFlash('success', 'New deal created successfully.');
} catch (Exception $e) {
    recordFlash('error', 'Error: ' . $e->getMessage());
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
