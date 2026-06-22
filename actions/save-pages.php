<?php
// FILE: actions/save-pages.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: ' . BASE . 'admin/pages.php'); exit; 
}

$pdo      = getPDO();
$settings = $_POST['settings'] ?? [];

$allowed = ['privacy_policy', 'terms_of_use'];

$stmt = $pdo->prepare(
    "INSERT INTO settings (setting_key, setting_value)
         VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);

foreach ($allowed as $key) {
    if (isset($settings[$key])) {
        // Allowing HTML, so no strip_tags, just trim
        $value = trim((string)$settings[$key]);
        $stmt->execute([$key, $value]);
    }
}

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Legal pages automatically updated successfully.'];
header('Location: ' . BASE . 'admin/pages.php');
exit;
