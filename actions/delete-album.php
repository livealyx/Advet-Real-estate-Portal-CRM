<?php
// FILE: actions/delete-album.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: ' . BASE . 'admin/albums.php'); exit;
}

$pdo = getPDO();
$id  = (int)$_POST['id'];

try {
    // 1. Fetch all associated images to delete them from disk
    $stmt = $pdo->prepare("SELECT image_path FROM album_images WHERE album_id = ?");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();

    foreach ($images as $img) {
        $path = __DIR__ . '/../' . $img['image_path'];
        if ($img['image_path'] && file_exists($path)) {
            unlink($path);
        }
    }

    // 2. Delete the album (Cascade will handle album_images in DB)
    $stmt = $pdo->prepare("DELETE FROM albums WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Album and all photos deleted.'];
} catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error deleting album.'];
}

header('Location: ' . BASE . 'admin/albums.php');
exit;
