<?php
// FILE: actions/delete-crm-doc.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'manager', 'agent'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit;
}

$pdo = getPDO();
$user = $_SESSION['user'];

try {
    // Check permission
    $stmt = $pdo->prepare("SELECT * FROM crm_documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        echo json_encode(['success' => false, 'message' => 'Document not found']); exit;
    }

    if ($user['role'] !== 'admin' && $doc['uploaded_by'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Forbidden']); exit;
    }

    // Delete file
    $filePath = __DIR__ . '/../assets/uploads/' . $doc['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete record
    $pdo->prepare("DELETE FROM crm_documents WHERE id = ?")->execute([$id]);

    // Log activity
    require_once '../includes/crm-utils.php';
    crmLogActivity($pdo, $doc['contact_id'], 'note', "Document deleted: " . $doc['title']);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
