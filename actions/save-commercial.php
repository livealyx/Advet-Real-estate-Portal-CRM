<?php
// FILE: actions/save-commercial.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'admin/featured-commercial.php'); exit;
}

$pdo = getPDO();
$id = $_POST['id'] ?? null;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$display_order = (int)($_POST['display_order'] ?? 0);

if (empty($title)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Title is required.'];
    header('Location: ' . BASE . ($id ? "admin/add-featured-commercial.php?id=$id" : 'admin/add-featured-commercial.php'));
    exit;
}

$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['name'] !== '') {
    $res = uploadPropertyImage($_FILES['image'], UPLOAD_DIR);
    if ($res['success']) {
        $imagePath = $res['path'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Upload failed: ' . $res['message']];
        header('Location: ' . BASE . ($id ? "admin/add-featured-commercial.php?id=$id" : 'admin/add-featured-commercial.php'));
        exit;
    }
}

if ($id) {
    if ($imagePath) {
        $stmt = $pdo->prepare("UPDATE space_archetypes SET title = ?, description = ?, image_path = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$title, $description, $imagePath, $display_order, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE space_archetypes SET title = ?, description = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$title, $description, $display_order, $id]);
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Category updated successfully.'];
} else {
    $stmt = $pdo->prepare("INSERT INTO space_archetypes (title, description, image_path, display_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $imagePath, $display_order]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'New Category published.'];
}

AdvetCache::clearAll();

header('Location: ' . BASE . 'admin/featured-commercial.php');
exit;
