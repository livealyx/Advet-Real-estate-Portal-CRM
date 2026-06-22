<?php
// FILE: actions/crm-delete-lead.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    die('Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $pdo = getPDO();

    try {
        // Start transaction to clean up related data
        $pdo->beginTransaction();

        // 1. Delete associated tasks
        $pdo->prepare("DELETE FROM crm_tasks WHERE contact_id = ?")->execute([$id]);

        // 2. Delete associated deals (and their transactions/activities if any, though usually we cascaded or linked)
        // Fetch deals to delete their child activities/transactions
        $deals = $pdo->prepare("SELECT id FROM crm_deals WHERE contact_id = ?");
        $deals->execute([$id]);
        while($deal = $deals->fetch()){
             $pdo->prepare("DELETE FROM crm_transactions WHERE deal_id = ?")->execute([$deal['id']]);
        }
        $pdo->prepare("DELETE FROM crm_deals WHERE contact_id = ?")->execute([$id]);

        // 3. Delete associated documents
        $docs = $pdo->prepare("SELECT file_path FROM crm_documents WHERE contact_id = ?");
        $docs->execute([$id]);
        while($doc = $docs->fetch()){
            $path = '../' . $doc['file_path'];
            if(file_exists($path)) @unlink($path);
        }
        $pdo->prepare("DELETE FROM crm_documents WHERE contact_id = ?")->execute([$id]);

        // 4. Delete associated activities (standalone)
        $pdo->prepare("DELETE FROM crm_activities WHERE contact_id = ?")->execute([$id]);

        // 5. Finally delete the contact
        $stmt = $pdo->prepare("DELETE FROM crm_contacts WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Lead and all associated records deleted successfully.'];
    } catch (\Exception $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete lead: ' . $e->getMessage()];
    }
}

header('Location: ' . BASE . 'admin/crm-leads.php');
exit;
