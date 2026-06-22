<?php
// FILE: actions/manage-testimonial.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Unauthorized');
}

$pdo = getPDO();
$id     = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id > 0) {
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE testimonials SET status='approved' WHERE id=?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Feedback approved and published.'];
        } elseif ($action === 'decline') {
            $stmt = $pdo->prepare("UPDATE testimonials SET status='declined' WHERE id=?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Feedback declined.'];
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id=?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Feedback deleted permanently.'];
        }
    } catch (\PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'An error occurred.'];
    }
}

header('Location: ' . BASE . 'admin/testimonials.php');
exit;
