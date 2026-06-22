<?php
// FILE: actions/save-faq.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'admin/faq.php');
    exit;
}

$pdo = getPDO();
$id            = (int)($_POST['id'] ?? 0);
$question      = trim($_POST['question'] ?? '');
$answer        = trim($_POST['answer'] ?? '');
$display_order = (int)($_POST['display_order'] ?? 0);
$status        = $_POST['status'] ?? 'active';

if (!$question || !$answer) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Question and Answer are required.'];
    header('Location: ' . BASE . 'admin/add-faq.php' . ($id ? "?id=$id" : ''));
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE faqs SET question=?, answer=?, display_order=?, status=? WHERE id=?");
        $stmt->execute([$question, $answer, $display_order, $status, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'FAQ updated successfully.'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, display_order, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$question, $answer, $display_order, $status]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'FAQ created successfully.'];
    }
} catch (\PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error saving FAQ.'];
}

header('Location: ' . BASE . 'admin/faq.php');
exit;
