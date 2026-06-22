<?php
// FILE: actions/crm-update-deal-stage.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$id      = (int)($_POST['id'] ?? 0);
$stageId = (int)($_POST['stage_id'] ?? 0);

if (!$id || !$stageId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']); exit;
}

$pdo = getPDO();

try {
    // Audit check: can this user update this deal?
    if ($_SESSION['user']['role'] !== 'admin') {
        $stmt = $pdo->prepare("SELECT c.assigned_to FROM crm_deals d JOIN crm_contacts c ON c.id = d.contact_id WHERE d.id = ?");
        $stmt->execute([$id]);
        $assignedTo = $stmt->fetchColumn();
        if ($assignedTo != $_SESSION['user']['id']) {
             echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE crm_deals SET stage_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$stageId, $id]);

    // Log activity
    require_once '../includes/crm-utils.php';
    $contactId = $pdo->query("SELECT contact_id FROM crm_deals WHERE id = $id")->fetchColumn();
    $stageName = $pdo->query("SELECT name FROM crm_stages WHERE id = $stageId")->fetchColumn();
    crmLogActivity($pdo, $contactId, 'note', "Deal moved to stage: $stageName");

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
