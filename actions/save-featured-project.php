<?php
// FILE: actions/save-featured-project.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'admin/featured-projects.php'); exit;
}

$pdo = getPDO();
$id = $_POST['id'] ?? null;
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$display_order = (int)($_POST['display_order'] ?? 0);

if (empty($title)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Title is required.'];
    header('Location: ' . BASE . ($id ? "admin/add-featured-project.php?id=$id" : 'admin/add-featured-project.php'));
    exit;
}

$imagePath = null;
if (isset($_FILES['image']) && $_FILES['image']['name'] !== '') {
    $res = uploadPropertyImage($_FILES['image'], UPLOAD_DIR);
    if ($res['success']) {
        $imagePath = $res['path'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Upload failed: ' . $res['message']];
        header('Location: ' . BASE . ($id ? "admin/add-featured-project.php?id=$id" : 'admin/add-featured-project.php'));
        exit;
    }
}

if ($id) {
    // Update
    if ($imagePath) {
        $stmt = $pdo->prepare("UPDATE featured_projects SET title = ?, description = ?, image_path = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$title, $description, $imagePath, $display_order, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE featured_projects SET title = ?, description = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$title, $description, $display_order, $id]);
    }
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Featured project updated successfully.'];
} else {
    // Insert
    $stmt = $pdo->prepare("INSERT INTO featured_projects (title, description, image_path, display_order) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $description, $imagePath, $display_order]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'New featured project published.'];
}

header('Location: ' . BASE . 'admin/featured-projects.php');
exit;
