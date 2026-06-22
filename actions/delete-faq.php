<?php
// FILE: actions/delete-faq.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Unauthorized');
}

$pdo = getPDO();
$id  = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $pdo->prepare('DELETE FROM faqs WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'FAQ deleted successfully.'];
    } catch (\PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error deleting FAQ.'];
    }
}

header('Location: ' . BASE . 'admin/faq.php');
exit;
