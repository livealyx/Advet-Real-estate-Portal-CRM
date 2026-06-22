<?php
// FILE: actions/subscribe-newsletter.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'index.php'); exit;
}

$pdo     = getPDO();
$email   = trim($_POST['email'] ?? '');
$referer = $_SERVER['HTTP_REFERER'] ?? '/index.php';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please enter a valid email address.'];
    header('Location: ' . $referer); exit;
}

// Check if newsletter is enabled
$enabled = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='newsletter_enabled'")->fetchColumn();
if ($enabled === '0') {
    $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Newsletter subscriptions are currently paused.'];
    header('Location: ' . $referer); exit;
}

// INSERT IGNORE — silently succeeds even if already subscribed
$stmt = $pdo->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Welcome to the field notes. We\'ll be in touch.'];
} else {
    $_SESSION['flash'] = ['type' => 'info', 'msg' => 'You\'re already on our list — thank you.'];
}

header('Location: ' . $referer);
exit;
