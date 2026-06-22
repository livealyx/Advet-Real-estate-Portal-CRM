<?php
// FILE: actions/auth-handler.php
session_start();
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$pdo    = getPDO();

// ── LOGIN ──────────────────────────────────────────────────────────────────
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'auth/login.php'); exit; }

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please enter your email and password.'];
        header('Location: ' . BASE . 'auth/login.php'); exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid email or password.'];
        header('Location: ' . BASE . 'auth/login.php'); exit;
    }

    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Welcome back, ' . $user['name'] . '!'];

    $redirect = $_POST['redirect'] ?? '';
    if (!empty($redirect) && str_starts_with($redirect, BASE)) {
        header('Location: ' . $redirect);
    } elseif (in_array($user['role'], ['admin', 'agent'])) {
        header('Location: ' . BASE . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE . 'index.php');
    }
    exit;
}

// ── REGISTER ───────────────────────────────────────────────────────────────
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'auth/register.php'); exit; }

    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password = trim($_POST['password']         ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if (!$name || !$email || !$password || !$confirm) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'All fields are required.'];
        header('Location: ' . BASE . 'auth/register.php'); exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please enter a valid email address.'];
        header('Location: ' . BASE . 'auth/register.php'); exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        header('Location: ' . BASE . 'auth/register.php'); exit;
    }

    if ($password !== $confirm) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Passwords do not match.'];
        header('Location: ' . BASE . 'auth/register.php'); exit;
    }

    // Check email uniqueness
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'An account with this email already exists.'];
        header('Location: ' . BASE . 'auth/register.php'); exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $hash, 'member']);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Account created. Please sign in.'];
    header('Location: ' . BASE . 'auth/login.php');
    exit;
}

// ── FORGOT PASSWORD REQUEST ────────────────────────────────────────────────
if ($action === 'forgot_request') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'auth/forgot-password.php'); exit; }

    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please enter a valid email address.'];
        header('Location: ' . BASE . 'auth/forgot-password.php'); exit;
    }

    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        
        // Delete any existing tokens for this email
        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
        $stmt->execute([$email]);

        // Insert new token
        $stmt = $pdo->prepare('INSERT INTO password_resets (email, token) VALUES (?, ?)');
        $stmt->execute([$email, $token]);

        // Send Email
        $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . BASE . "auth/reset-password.php?token=" . $token;
        
        $settings = loadSettings($pdo);
        $siteName = $settings['site_name'] ?? 'Advet Buildwell';
        
        $to      = $email;
        $subject = "Password Restoration | " . $siteName;
        $headers = "From: " . $siteName . " <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $mailBody = "
        <div style='font-family: serif; border: 1px solid #DFD8CC; padding: 40px; border-radius: 20px; color: #2A2925; max-width: 600px; margin: auto;'>
            <p style='text-transform: uppercase; letter-spacing: 0.2em; font-size: 10px; color: #899178; font-weight: bold; margin-bottom: 20px;'>Security Notification</p>
            <h2 style='font-weight: 300; margin-bottom: 30px;'>Restore your <span style='font-style: italic; color: #6D685C;'>sanctuary access.</span></h2>
            
            <p>Greetings {$user['name']},</p>
            <p>A request was made to reset your password. If this was you, please click the button below to proceed:</p>
            
            <div style='text-align: center; margin: 40px 0;'>
                <a href='{$resetLink}' style='background: #2A2925; color: #FDFCF9; padding: 18px 30px; text-decoration: none; border-radius: 12px; font-family: sans-serif; font-size: 13px; font-weight: bold; letter-spacing: 0.05em;'>RESET PASSWORD</a>
            </div>
            
            <p style='font-size: 12px; color: #6D685C;'>Or copy this link: <br> <a href='{$resetLink}' style='color: #899178;'>{$resetLink}</a></p>
            <p style='font-size: 11px; color: #6D685C; margin-top: 20px;'>Note: This link will expire in 1 hour. If you didn't request this, you can safely ignore this email.</p>
        </div>";

        @mail($to, $subject, $mailBody, $headers);
    }

    // Always show success to prevent email enumeration
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'If an account exists with that email, a reset link has been sent.'];
    header('Location: ' . BASE . 'auth/login.php');
    exit;
}

// ── RESET PASSWORD ACTION ───────────────────────────────────────────────────
if ($action === 'reset_password') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE . 'auth/login.php'); exit; }

    $token    = $_POST['token']            ?? '';
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$token || !$password || !$confirm) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'All fields are required.'];
        header('Location: ' . BASE . 'auth/reset-password.php?token=' . $token); exit;
    }

    if ($password !== $confirm) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Passwords do not match.'];
        header('Location: ' . BASE . 'auth/reset-password.php?token=' . $token); exit;
    }

    if (strlen($password) < 8) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Password must be at least 8 characters.'];
        header('Location: ' . BASE . 'auth/reset-password.php?token=' . $token); exit;
    }

    // Verify token
    $stmt = $pdo->prepare('SELECT email FROM password_resets WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) LIMIT 1');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Invalid or expired token.'];
        header('Location: ' . BASE . 'auth/forgot-password.php'); exit;
    }

    $email = $reset['email'];
    $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Update password
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $stmt->execute([$hash, $email]);

    // Delete token
    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = ?');
    $stmt->execute([$email]);

    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Password reset successful. You can now log in.'];
    header('Location: ' . BASE . 'auth/login.php');
    exit;
}

// ── FALLBACK ───────────────────────────────────────────────────────────────
header('Location: ' . BASE . 'index.php');
exit;
