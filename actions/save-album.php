<?php
// FILE: actions/save-album.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'admin/albums.php');
    exit;
}

$pdo = getPDO();
$id  = (int)($_POST['id'] ?? 0);
$album_id = $id;

// Handle Album Info Save
if (isset($_POST['title'])) {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $status      = $_POST['status'] ?? 'active';

    if (!$title) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Title is required.'];
        header('Location: ' . BASE . ($id ? "admin/add-album.php?id=$id" : "admin/add-album.php"));
        exit;
    }

    $baseSlug = slugify($title);
    // Custom unique slug for albums
    $slug = $baseSlug; $idx = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM albums WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if (!$stmt->fetch()) break;
        $slug = $baseSlug . '-' . (++$idx);
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE albums SET title=?, slug=?, description=?, display_order=?, status=? WHERE id=?");
            $stmt->execute([$title, $slug, $description, $display_order, $status, $id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Album updated successfully.'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO albums (title, slug, description, display_order, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $description, $display_order, $status]);
            $album_id = (int)$pdo->lastInsertId();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Album created successfully.'];
        }
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Database error: ' . $e->getMessage()];
        header('Location: ' . BASE . 'admin/albums.php');
        exit;
    }
}

// Handle Set Cover Photo
if (isset($_POST['set_cover']) && $album_id > 0) {
    $cover = $_POST['set_cover'];
    $stmt = $pdo->prepare("UPDATE albums SET cover_image = ? WHERE id = ?");
    $stmt->execute([$cover, $album_id]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Cover image updated.'];
}

// Handle Multiple Image Uploads (Support both Legacy and Async)
$allPaths = [];

// Handle Multiple Image Uploads (Support both Legacy and Async)
$allPaths = [];

// 1. Process Legacy $_FILES
if (!empty($_FILES['photos']['name'][0]) && $album_id > 0) {
    $uploadDir = __DIR__ . '/../assets/uploads/gallery/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    foreach ($_FILES['photos']['name'] as $i => $name) {
        if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts)) {
                $filename = 'img_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $uploadDir . $filename)) {
                    $allPaths[] = 'assets/uploads/gallery/' . $filename;
                }
            }
        }
    }
}

// 2. Process Async Uploads (Flexible Naming)
$asyncSources = ['async_photos', 'photos_async', 'gallery_paths', 'async_gallery_images'];
foreach ($asyncSources as $key) {
    if (!empty($_POST[$key]) && $album_id > 0) {
        $val = $_POST[$key];
        if (is_array($val)) {
            foreach ($val as $p) $allPaths[] = trim($p);
        } else {
            // Check if it's a JSON array (from older GalleryManager logic)
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                foreach ($decoded as $p) $allPaths[] = trim($p);
            } else if (trim($val)) {
                // Single path
                $allPaths[] = trim($val);
            }
        }
    }
}

// 3. Persist to Database
if (!empty($allPaths) && $album_id > 0) {
    $insertedCount = 0;
    foreach (array_unique($allPaths) as $path) {
        if (empty($path)) continue;
        
        $stmt = $pdo->prepare("INSERT INTO album_images (album_id, image_path, display_order) VALUES (?, ?, ?)");
        $stmt->execute([$album_id, $path, 0]);
        
        // If album has no cover, set the first uploaded one as cover
        $checkCover = $pdo->prepare("SELECT cover_image FROM albums WHERE id = ?");
        $checkCover->execute([$album_id]);
        if (!$checkCover->fetchColumn()) {
            $upd = $pdo->prepare("UPDATE albums SET cover_image = ? WHERE id = ?");
            $upd->execute([$path, $album_id]);
        }
        $insertedCount++;
    }

    if ($insertedCount > 0) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Successfully integrated $insertedCount images."];
    }
}

header('Location: ' . BASE . 'admin/add-album.php?id=' . $album_id);
exit;
