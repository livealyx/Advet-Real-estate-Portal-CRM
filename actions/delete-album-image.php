<?php
// FILE: actions/delete-album-image.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['image_id']) || empty($_POST['album_id'])) {
    header('Location: ' . BASE . 'admin/albums.php'); exit;
}

$pdo = getPDO();
$id  = (int)$_POST['image_id'];
$album_id = (int)$_POST['album_id'];

try {
    // 1. Fetch image path to delete from disk
    $stmt = $pdo->prepare("SELECT image_path FROM album_images WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();

    if ($img && $img['image_path']) {
        $path = __DIR__ . '/../' . $img['image_path'];
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // 2. Delete from DB
    $stmt = $pdo->prepare("DELETE FROM album_images WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Image deleted.'];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error deleting image.'];
}

header('Location: ' . BASE . 'admin/add-album.php?id=' . $album_id);
exit;
