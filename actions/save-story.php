<?php
// FILE: actions/save-story.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'admin/stories.php');
    exit;
}

$pdo = getPDO();
$id           = (int)($_POST['id'] ?? 0);
$title        = trim($_POST['title'] ?? '');
$excerpt      = trim($_POST['excerpt'] ?? '');
$content      = trim($_POST['content'] ?? '');
$published_at = $_POST['published_at'] ? date('Y-m-d H:i:s', strtotime($_POST['published_at'])) : null;
$remove_cover = (int)($_POST['remove_cover'] ?? 0);

// SEO Metadata
$meta_title       = trim($_POST['meta_title'] ?? '');
$meta_description = trim($_POST['meta_description'] ?? '');
$meta_keywords    = trim($_POST['meta_keywords'] ?? '');

if (!$title || !$content) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Title and Content are required.'];
    header('Location: ' . BASE . 'admin/add-story.php' . ($id ? "?id=$id" : ''));
    exit;
}

// Simple slug generator
$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

// Ensure slug is unique
$stmt = $pdo->prepare('SELECT COUNT(*) FROM stories WHERE slug = ? AND id != ?');
$stmt->execute([$slug, $id]);
if ($stmt->fetchColumn() > 0) {
    $slug .= '-' . time();
}

$cover_image = null;

// Handle existing cover
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT cover_image FROM stories WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetchColumn();
    if ($existing) $cover_image = $existing;
}

if ($remove_cover && $cover_image) {
    $path = __DIR__ . '/..' . (str_starts_with($cover_image, '/') ? '' : '/') . $cover_image;
    if (file_exists($path)) @unlink($path);
    $cover_image = null;
}

// Handle new cover (either async or traditional)
$async_cover = trim($_POST['async_cover_image'] ?? '');

if ($async_cover) {
    // If it was uploaded via AdvetUploader (async)
    // We expect a path like 'assets/uploads/properties/prop_...webp'
    // Ensure it starts with a slash for consistency if needed, but imgUrl handles both.
    // Let's keep it as is from the uploader.
    if ($cover_image) {
        $oldPath = __DIR__ . '/..' . (str_starts_with($cover_image, '/') ? '' : '/') . $cover_image;
        if (file_exists($oldPath)) @unlink($oldPath);
    }
    $cover_image = (str_starts_with($async_cover, '/') ? '' : '/') . $async_cover;
} elseif (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
    // Traditional upload fallback
    $tmp   = $_FILES['cover_image']['tmp_name'];
    $name  = preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['cover_image']['name']));
    $ext   = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
        $dir = __DIR__ . '/../uploads/stories/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        
        $fname = uniqid('story_') . '.' . $ext;
        if (move_uploaded_file($tmp, $dir . $fname)) {
            // Remove old
            if ($cover_image) {
                $oldPath = __DIR__ . '/..' . (str_starts_with($cover_image, '/') ? '' : '/') . $cover_image;
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $cover_image = '/uploads/stories/' . $fname;
        }
    }
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE stories SET title=?, slug=?, excerpt=?, content=?, cover_image=?, meta_title=?, meta_description=?, meta_keywords=?, published_at=? WHERE id=?");
        $stmt->execute([$title, $slug, $excerpt, $content, $cover_image, $meta_title, $meta_description, $meta_keywords, $published_at, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Story updated successfully.'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO stories (title, slug, excerpt, content, cover_image, meta_title, meta_description, meta_keywords, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $excerpt, $content, $cover_image, $meta_title, $meta_description, $meta_keywords, $published_at]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Story created successfully.'];
    }
} catch (\PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error saving story.'];
}

header('Location: ' . BASE . 'admin/stories.php');
exit;
