<?php
// FILE: actions/submit-comment.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Unauthorized');
}

$pdo = getPDO();
$story_id     = (int)($_POST['story_id'] ?? 0);
$user_name    = trim($_POST['user_name'] ?? '');
$user_email   = trim($_POST['user_email'] ?? '');
$comment_text = trim($_POST['comment_text'] ?? '');
$parent_id    = (int)($_POST['parent_id'] ?? 0) ?: null;

// If logged in, prioritize user context
$user_id = null;
if (!empty($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $user_name = $_SESSION['user']['name'];
    $user_email = $_SESSION['user']['email'];
}

if (!$story_id || !$user_name || !$user_email || !$comment_text) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please fill all required fields.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// Basic SPAM protection (can be expanded)
if (strlen($comment_text) < 2) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Comment is too short.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO story_comments (story_id, user_id, user_name, user_email, comment_text, status, parent_id) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->execute([$story_id, $user_id, $user_name, $user_email, $comment_text, $parent_id]);
    
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Comment submitted and awaiting moderation. Thank you!'];
} catch (\PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error submitting comment.'];
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
