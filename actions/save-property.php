<?php
// FILE: actions/save-property.php
session_start();
require_once '../config/db.php';
if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'admin/listings.php'); exit; }

$pdo = getPDO();
$userRole = $_SESSION['user']['role'];
$userId   = (int)$_SESSION['user']['id'];

// Determine if edit or insert
$id = isset($_POST['id']) && (int)$_POST['id'] > 0 ? (int)$_POST['id'] : null;

// Validate required fields
$title    = trim($_POST['title']    ?? '');
$location = trim($_POST['location'] ?? '');
$price    = (float)($_POST['price'] ?? 0);

if (!$title || !$location || $price <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Title, location and price are required.'];
    $returnUrl = $_POST['return_url'] ?? (BASE . ($id ? "admin/edit-property.php?id=$id" : 'admin/add-property.php'));
    header('Location: ' . $returnUrl);
    exit;
}

// Security: Check if agent owns the property they're editing
if ($id && $userRole === 'agent') {
    $ownCheck = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND agent_id = ?");
    $ownCheck->execute([$id, $userId]);
    if (!$ownCheck->fetch()) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Unauthorized.'];
        header('Location: ' . BASE . 'admin/listings.php'); exit;
    }
}

// Determine agent_id
$agentId = null;
if ($userRole === 'agent') {
    $agentId = $userId; // Agents always assign to themselves
} else {
    // Admin can specify agent
    $agentId = !empty($_POST['agent_id']) ? (int)$_POST['agent_id'] : null;
}

// Status override from submit button name, or from select
$status = in_array($_POST['status_override'] ?? '', ['active','draft','sold'])
    ? $_POST['status_override']
    : (in_array($_POST['status'] ?? '', ['active','draft','sold']) ? $_POST['status'] : 'draft');

$bedrooms          = (int)($_POST['bedrooms']          ?? 0);
$bathrooms         = (int)($_POST['bathrooms']         ?? 0);
$sqft              = (int)($_POST['sqft']              ?? 0);
$description       = trim($_POST['description']        ?? '');
$primaryMaterial   = trim($_POST['primary_material']   ?? '');
$secondaryMaterial = trim($_POST['secondary_material'] ?? '');
$acousticStillness = trim($_POST['acoustic_stillness'] ?? '');
$balcony           = (int)($_POST['balcony']           ?? 0);
$flatType          = trim($_POST['flat_type']          ?? 'Raw');

// Slug
$baseSlug      = slugify($title);
$slug          = uniqueSlug($pdo, $baseSlug, $id);

$category      = $_POST['category'] ?? 'Flat/Apartment';
$listingType   = $_POST['listing_type'] ?? 'Buy';
$projectId     = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

// Handle featured image
$featuredImage = null;
if ($id) {
    // Keep existing unless replaced or removed
    $existing = $pdo->prepare("SELECT featured_image, gallery_images FROM properties WHERE id = ?");
    $existing->execute([$id]);
    $existingRow  = $existing->fetch();
    $featuredImage = $existingRow['featured_image'];

    if (!empty($_POST['remove_featured'])) {
        $featuredImage = null;
    }
}

$asyncFeatured = $_POST['async_featured_image'] ?? null;
if ($asyncFeatured) {
    $featuredImage = $asyncFeatured;
} elseif (!empty($_FILES['featured_image']['name'])) {
    $res = uploadPropertyImage($_FILES['featured_image'], UPLOAD_DIR);
    if ($res['success']) {
        $featuredImage = $res['path'];
    }
}

// --- 1. Load Existing ---
$gallery = [];
if ($id && isset($existingRow['gallery_images'])) {
    $gallery = json_decode($existingRow['gallery_images'], true) ?: [];
}

// --- 2. Remove Checked Photos ---
$toRemove = array_map(fn($p) => str_replace('\\', '/', $p), (array)($_POST['remove_gallery'] ?? []));
if (!empty($toRemove)) {
    $gallery = array_values(array_filter($gallery, function($img) use ($toRemove) {
        $clean = str_replace('\\', '/', $img);
        return !in_array($clean, $toRemove);
    }));
}

// --- 3. Add New (Async handles this via hidden fields) ---
$asyncGallery = (array)($_POST['async_gallery_images'] ?? []);
foreach ($asyncGallery as $path) {
    if ($path && !in_array($path, $gallery)) $gallery[] = $path;
}

// --- 4. Add New (Standard FILES fallback) ---
// Only runs if the uploader wasn't used or standard fields were populated
if (empty($asyncGallery) && !empty($_FILES['gallery_images']['name'][0])) {
    foreach ($_FILES['gallery_images']['tmp_name'] as $i => $tmp) {
        if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $res = uploadPropertyImage([
            'tmp_name' => $tmp,
            'name'     => $_FILES['gallery_images']['name'][$i],
            'type'     => $_FILES['gallery_images']['type'][$i],
            'size'     => $_FILES['gallery_images']['size'][$i],
            'error'    => $_FILES['gallery_images']['error'][$i],
        ], UPLOAD_DIR);
        if ($res['success']) $gallery[] = $res['path'];
    }
}

// Final deduplication & encoding
$galleryJson = json_encode(array_values(array_unique($gallery)));

if ($id) {
    // UPDATE
    $stmt = $pdo->prepare(
        "UPDATE properties SET
            title=?, slug=?, location=?, price=?, bedrooms=?, bathrooms=?, sqft=?,
            status=?, category=?, listing_type=?, description=?, primary_material=?, secondary_material=?,
            acoustic_stillness=?, featured_image=?, gallery_images=?, agent_id=?, balcony=?, flat_type=?, project_id=?
         WHERE id=?"
    );
    $stmt->execute([
        $title, $slug, $location, $price, $bedrooms, $bathrooms, $sqft,
        $status, $category, $listingType, $description, $primaryMaterial, $secondaryMaterial,
        $acousticStillness, $featuredImage, $galleryJson, $agentId, $balcony, $flatType, $projectId, $id
    ]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => "\"$title\" updated successfully."];
} else {
    // INSERT
    $stmt = $pdo->prepare(
        "INSERT INTO properties
            (title, slug, location, price, bedrooms, bathrooms, sqft, status,
             category, listing_type, description, primary_material, secondary_material,
             acoustic_stillness, featured_image, gallery_images, agent_id, balcony, flat_type, project_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $title, $slug, $location, $price, $bedrooms, $bathrooms, $sqft, $status,
        $category, $listingType, $description, $primaryMaterial, $secondaryMaterial, $acousticStillness,
        $featuredImage, $galleryJson, $agentId, $balcony, $flatType, $projectId
    ]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => "\"$title\" published successfully."];
}

AdvetCache::invalidate(); // Purge cache on change

$successRedirect = $_POST['success_redirect'] ?? (BASE . 'admin/listings.php');
header('Location: ' . $successRedirect);
exit;
