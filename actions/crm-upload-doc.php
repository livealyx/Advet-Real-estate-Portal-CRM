<?php
// FILE: actions/crm-upload-doc.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$pdo = getPDO();
$contactId = (int)($_POST['contact_id'] ?? 0);
$docType   = trim($_POST['doc_type'] ?? 'Document');
$title     = trim($_POST['title'] ?? 'Uploaded Document');

if (!$contactId || empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Lead and File are required.']); exit;
}

$file = $_FILES['file'];

// Validation
$allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts)) {
    echo json_encode(['success' => false, 'message' => 'Unsupported format. Use PDF, DOCX or JPG.']); exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']); exit;
}

$uploadDir = '../assets/uploads/crm/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = 'doc_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.]/', '', $file['name']);
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativePath = 'assets/uploads/crm/' . $fileName;
    $stmt = $pdo->prepare("INSERT INTO crm_documents (contact_id, title, file_path, doc_type, uploaded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $contactId, 
        $title, 
        $relativePath, 
        $docType, 
        $_SESSION['user']['id'] ?? null
    ]);
    
    // Log activity
    require_once '../includes/crm-utils.php';
    crmLogActivity($pdo, $contactId, 'note', "Document archived: $title ($docType)");
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Archive failure. Check server permissions.']);
}
