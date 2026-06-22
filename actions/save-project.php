<?php
// FILE: actions/save-project.php
session_start();
require_once '../config/db.php';
$pdo = getPDO();

// --- SELF-HEALING DATABASE BLOCK ---
try {
    $check = $pdo->query("SHOW COLUMNS FROM projects LIKE 'agent_phone'");
    if ($check->rowCount() === 0) {
        $pdo->exec("ALTER TABLE projects ADD agent_phone VARCHAR(50) DEFAULT NULL AFTER status");
        $pdo->exec("ALTER TABLE projects ADD agent_whatsapp VARCHAR(50) DEFAULT NULL AFTER agent_phone");
    }
} catch (Exception $e) { /* Fail silently if table doesn't exist yet */ }
// ------------------------------------

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'admin/projects.php'); exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

$title             = trim($_POST['title'] ?? '');
$project_type      = $_POST['project_type'] ?? 'Apartment';
$possession_status = $_POST['possession_status'] ?? 'Under Construction';
$location          = trim($_POST['location'] ?? '');
$price_min         = trim($_POST['price_min'] ?? '');
$price_max         = trim($_POST['price_max'] ?? '');
$area_min          = trim($_POST['area_min'] ?? '');
$area_max          = trim($_POST['area_max'] ?? '');
$description       = trim($_POST['description'] ?? '');
$meta_title        = trim($_POST['meta_title'] ?? '');
$meta_description  = trim($_POST['meta_description'] ?? '');
$agent_phone       = trim($_POST['agent_phone'] ?? '');
$agent_whatsapp    = trim($_POST['agent_whatsapp'] ?? '');
$status            = ($_POST['save_action'] ?? '') === 'publish' ? 'active' : 'draft';

if (!$title || !$location) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Title and location are required.'];
    header('Location: ' . BASE . ($id ? "admin/add-project.php?id=$id" : 'admin/add-project.php'));
    exit;
}

// Slug Logic
function uniqueProjectSlug(PDO $pdo, string $base, ?int $excludeId = null): string {
    $slug = $base; $i = 2;
    while (true) {
        $sql  = 'SELECT COUNT(*) FROM projects WHERE slug = ?';
        $args = [$slug];
        if ($excludeId) { $sql .= ' AND id != ?'; $args[] = $excludeId; }
        $stmt = $pdo->prepare($sql); $stmt->execute($args);
        if ((int)$stmt->fetchColumn() === 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

$slug = uniqueProjectSlug($pdo, slugify($title), $id);

// Handle Cover Image
$cover_image = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT cover_image FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $cover_image = $stmt->fetchColumn();
}

// Async upload check
if (!empty($_POST['async_cover_image'])) {
    $cover_image = $_POST['async_cover_image'];
} elseif (!empty($_FILES['cover_image']['name'])) {
    $res = uploadPropertyImage($_FILES['cover_image'], UPLOAD_DIR);
    if ($res['success']) $cover_image = $res['path'];
}

// 1. PROJECT UPSERT
if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE projects SET title=?, slug=?, project_type=?, location=?, description=?, possession_status=?, price_min=?, price_max=?, area_min=?, area_max=?, cover_image=?, status=?, agent_phone=?, agent_whatsapp=?, meta_title=?, meta_description=? WHERE id=?");
    $stmt->execute([$title, $slug, $project_type, $location, $description, $possession_status, $price_min, $price_max, $area_min, $area_max, $cover_image, $status, $agent_phone, $agent_whatsapp, $meta_title, $meta_description, $id]);
    $projectId = $id;
} else {
    $stmt = $pdo->prepare("INSERT INTO projects (title, slug, project_type, location, description, possession_status, price_min, price_max, area_min, area_max, cover_image, status, agent_phone, agent_whatsapp, meta_title, meta_description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$title, $slug, $project_type, $location, $description, $possession_status, $price_min, $price_max, $area_min, $area_max, $cover_image, $status, $agent_phone, $agent_whatsapp, $meta_title, $meta_description]);
    $projectId = $pdo->lastInsertId();
}

// 2. UNIT CONFIGURATIONS (Delete then re-insert for simplicity)
$pdo->prepare("DELETE FROM project_units WHERE project_id = ?")->execute([$projectId]);
if (!empty($_POST['unit_type'])) {
    foreach ($_POST['unit_type'] as $i => $type) {
        if (!trim($type)) continue;
        $size = $_POST['unit_size'][$i] ?? '';
        $price = $_POST['unit_price'][$i] ?? '';
        $avail = $_POST['unit_availability'][$i] ?? 'Available';
        
        $stmt = $pdo->prepare("INSERT INTO project_units (project_id, unit_type, size, price, availability, display_order) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$projectId, $type, $size, $price, $avail, $i]);
    }
}

// 3. GALLERY IMAGES
// Remove selected images
if (!empty($_POST['remove_images'])) {
    foreach ($_POST['remove_images'] as $imgId) {
        $stmt = $pdo->prepare("DELETE FROM project_images WHERE id = ? AND project_id = ?");
        $stmt->execute([(int)$imgId, $projectId]);
    }
}

// Async gallery
if (!empty($_POST['async_gallery_images'])) {
    foreach ($_POST['async_gallery_images'] as $path) {
        $stmt = $pdo->prepare("INSERT INTO project_images (project_id, image_path) VALUES (?,?)");
        $stmt->execute([$projectId, $path]);
    }
}

// Standard upload fallback
if (!empty($_FILES['gallery_images']['name'][0])) {
    foreach ($_FILES['gallery_images']['tmp_name'] as $i => $tmp) {
        if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $res = uploadPropertyImage([
            'tmp_name' => $tmp,
            'name'     => $_FILES['gallery_images']['name'][$i],
            'type'     => $_FILES['gallery_images']['type'][$i],
            'size'     => $_FILES['gallery_images']['size'][$i],
            'error'    => $_FILES['gallery_images']['error'][$i]
        ], UPLOAD_DIR);
        if ($res['success']) {
            $stmt = $pdo->prepare("INSERT INTO project_images (project_id, image_path) VALUES (?,?)");
            $stmt->execute([$projectId, $res['path']]);
        }
    }
}

$_SESSION['flash'] = ['type' => 'success', 'msg' => "Project '$title' saved successfully."];
header('Location: ' . BASE . 'admin/projects.php');
exit;
