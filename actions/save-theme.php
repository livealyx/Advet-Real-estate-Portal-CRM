<?php
// FILE: actions/save-theme.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'admin/theme.php'); exit; }

$pdo      = getPDO();
$settings = $_POST['settings'] ?? [];

$allowed = ['theme_background','theme_foreground','theme_surface','theme_muted','theme_sand','accent_color','theme_accent_dark'];

$stmt = $pdo->prepare(
    "INSERT INTO settings (setting_key, setting_value)
         VALUES (?, ?)
     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
);

foreach ($allowed as $key) {
    if (isset($settings[$key])) {
        $value = trim((string)$settings[$key]);
        $stmt->execute([$key, $value]);
    }
}

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Theme colors updated successfully.'];
header('Location: ' . BASE . 'admin/theme.php');
exit;
