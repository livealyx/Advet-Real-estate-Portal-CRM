<?php
// FILE: actions/crm-record-payment.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$dealId      = (int)($_POST['deal_id'] ?? 0);
$amount      = (float)($_POST['amount'] ?? 0);
$type        = $_POST['payment_type'] ?? 'General';
$date        = $_POST['payment_date'] ?? date('Y-m-d');
$status      = $_POST['status'] ?? 'pending';
$ref         = trim($_POST['ref_number'] ?? '');

if (!$dealId || $amount <= 0) {
    recordFlash('error', 'Select a deal and enter a valid amount.');
    header('Location: ' . $_SERVER['HTTP_REFERER']); exit;
}

try {
    // Record Transaction
    $stmt = $pdo->prepare("INSERT INTO crm_transactions (deal_id, amount, payment_type, payment_date, status, ref_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$dealId, $amount, $type, $date, $status, $ref]);

    // Log activity in Contact Timeline
    $stmtDeal = $pdo->prepare("SELECT contact_id FROM crm_deals WHERE id = ?");
    $stmtDeal->execute([$dealId]);
    $contactId = $stmtDeal->fetchColumn();

    if ($contactId) {
        require_once '../includes/crm-utils.php';
        $fmtAmount = formatPrice($amount);
        crmLogActivity($pdo, $contactId, 'note', "Payment Recorded: $fmtAmount ($type) - Status: $status. Ref: $ref");
    }

    recordFlash('success', 'Payment recorded successfully.');
} catch (Exception $e) {
    recordFlash('error', 'Database error: ' . $e->getMessage());
}

header('Location: ' . BASE . 'admin/crm-transactions.php');
exit;
