<?php
// FILE: actions/save-team-member.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'admin/team.php'); exit;
}

$pdo = getPDO();
$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$designation = $_POST['designation'] ?? '';
$bio = $_POST['bio'] ?? '';
$display_order = (int)($_POST['display_order'] ?? 0);

// Helper to clean handles (strips @ and full URLs if pasted)
function cleanHandle($val) {
    if (empty($val)) return '';
    $val = trim($val, " @\t\n\r\0\x0B");
    if (str_contains($val, '/')) {
        $val = rtrim($val, '/');
        $parts = explode('/', $val);
        return end($parts);
    }
    return $val;
}

$facebook = cleanHandle($_POST['facebook_url'] ?? '');
$x = cleanHandle($_POST['x_url'] ?? '');
$instagram = cleanHandle($_POST['instagram_url'] ?? '');
$whatsapp = $_POST['whatsapp_number'] ?? '';
$threads = cleanHandle($_POST['threads_url'] ?? '');
$socialvynk = cleanHandle($_POST['socialvynk_url'] ?? '');

$imagePath = $_POST['async_image'] ?? null;
if (!$imagePath && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imageRes = uploadPropertyImage($_FILES['image'], UPLOAD_DIR);
    if ($imageRes['success']) {
        $imagePath = $imageRes['path'];
    }
}

$sqlParams = [
    $name, $email, $phone, $designation, $bio, $display_order,
    $facebook, $x, $instagram, $whatsapp, $threads, $socialvynk
];

if ($id) {
    $sql = "UPDATE team_members SET name=?, email=?, phone=?, designation=?, bio=?, display_order=?, 
            facebook_url=?, x_url=?, instagram_url=?, whatsapp_number=?, threads_url=?, socialvynk_url=?";
    
    if ($imagePath) {
        $sql .= ", image_path=?";
        $sqlParams[] = $imagePath;
    }
    
    $sql .= " WHERE id=?";
    $sqlParams[] = $id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($sqlParams);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Team member profile updated.'];
} else {
    $sql = "INSERT INTO team_members (name, email, phone, designation, bio, display_order, 
            facebook_url, x_url, instagram_url, whatsapp_number, threads_url, socialvynk_url, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $sqlParams[] = $imagePath;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($sqlParams);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'New team member added.'];
}

header('Location: ' . BASE . 'admin/team.php');
exit;
