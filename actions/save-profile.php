<?php
// FILE: actions/save-profile.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

$pdo = getPDO();
$userId = (int)$_SESSION['user']['id'];

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$name || !$email) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Name and email are strictly required.'];
    header('Location: ' . BASE . 'admin/profile.php'); exit;
}

// Handle Profile Picture
$profilePicPath = $_POST['async_profile_picture'] ?? null;
if (!$profilePicPath && !empty($_FILES['profile_picture']['tmp_name'])) {
    $upload = uploadProfilePicture($_FILES['profile_picture']);
    if (!$upload['success']) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Avatar error: ' . $upload['error']];
        header('Location: ' . BASE . 'admin/profile.php'); exit;
    }
    $profilePicPath = $upload['path'];
}

// Determine if we are updating password
if ($password) {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    if ($profilePicPath) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$name, $email, $hash, $profilePicPath, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$name, $email, $hash, $userId]);
    }
} else {
    // No password update
    if ($profilePicPath) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$name, $email, $profilePicPath, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $userId]);
    }
}

// Refresh Session Data to immediately reflect structural UI changes globally
$stmt = $pdo->prepare("SELECT id, name, email, role, COALESCE(profile_picture, '') as profile_picture FROM users WHERE id = ?");
$stmt->execute([$userId]);
$_SESSION['user'] = $stmt->fetch(\PDO::FETCH_ASSOC);

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Profile updated successfully.'];
header('Location: ' . BASE . 'admin/profile.php'); exit;
