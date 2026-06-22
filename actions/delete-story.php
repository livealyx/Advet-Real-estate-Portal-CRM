<?php
// FILE: actions/delete-story.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Unauthorized');
}

$pdo = getPDO();
$id  = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT cover_image FROM stories WHERE id = ?');
        $stmt->execute([$id]);
        $cover = $stmt->fetchColumn();
        
        if ($cover) {
            $path = __DIR__ . '/..' . $cover;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        
        $pdo->prepare('DELETE FROM stories WHERE id = ?')->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Story deleted forever.'];
    } catch (\PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error deleting story.'];
    }
}

header('Location: ' . BASE . 'admin/stories.php');
exit;
