<?php
// FILE: actions/async-upload.php
session_start();
require_once '../config/db.php';

// Check authorization (admin or agent)
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No file provided.']);
    exit;
}

// Reuse existing upload logic from db.php (uploads to properties/ by default)
$result = uploadPropertyImage($_FILES['file'], UPLOAD_DIR);

header('Content-Type: application/json');
echo json_encode($result);
