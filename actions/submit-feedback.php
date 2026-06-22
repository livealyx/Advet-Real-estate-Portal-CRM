<?php
// FILE: actions/submit-feedback.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'reviews.php');
    exit;
}

$pdo = getPDO();

$name     = trim($_POST['name'] ?? '');
$aff      = trim($_POST['affiliation'] ?? '');
$extype   = trim($_POST['experience_type'] ?? 'other');
$content  = trim($_POST['content'] ?? '');
$rating   = (int)($_POST['rating'] ?? 5);

// Input validation
if (!$name || !$extype || !$content) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please fill in all required fields.'];
    header('Location: ' . BASE . 'reviews.php');
    exit;
}

$rating = max(1, min(5, $rating));

// Basic sanitization
$name    = strip_tags($name);
$aff     = strip_tags($aff);
$content = strip_tags($content); // Don't allow HTML in testimonials to keep them pure text

// Valid experience types
if (!in_array($extype, ['buying', 'selling', 'lease', 'other'])) {
    $extype = 'other';
}

try {
    $stmt = $pdo->prepare("INSERT INTO testimonials (name, affiliation, experience_type, content, rating, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$name, $aff, $extype, $content, $rating]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Thank you. Your perspective has been securely submitted for review.'];
} catch (\PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'An error occurred while saving your feedback. Please try again.'];
}

header('Location: ' . BASE . 'reviews.php');
exit;
