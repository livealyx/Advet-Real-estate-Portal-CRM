<?php
// FILE: actions/clear-cache.php
session_start();
require_once '../config/db.php';

// Only admins can clear cache
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../includes/cache.php';

if (AdvetCache::clear()) {
    recordFlash('success', 'System cache has been cleared successfully.');
} else {
    recordFlash('error', 'Failed to clear system cache. Check folder permissions.');
}

// Redirect back to dashboard or settings
$return = $_SERVER['HTTP_REFERER'] ?? (BASE . 'admin/dashboard.php');
header('Location: ' . $return);
exit;
