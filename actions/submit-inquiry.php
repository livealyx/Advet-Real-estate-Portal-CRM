<?php
// FILE: actions/submit-inquiry.php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE . 'contact.php'); exit;
}

$pdo = getPDO();

$name       = trim($_POST['name']        ?? '');
$email      = trim($_POST['email']       ?? '');
$phone      = trim($_POST['phone']       ?? '');
$message    = trim($_POST['message']     ?? '');
$propertyId = isset($_POST['property_id']) && (int)$_POST['property_id'] > 0
              ? (int)$_POST['property_id'] : null;
$projectId = isset($_POST['project_id']) && (int)$_POST['project_id'] > 0
              ? (int)$_POST['project_id'] : null;

// Determine redirect target
$referer = $_SERVER['HTTP_REFERER'] ?? '/public/contact.php';

// Validate
if (!$name || !$email || !$message) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please fill in your name, email, and message.'];
    header('Location: ' . $referer); exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Please enter a valid email address.'];
    header('Location: ' . $referer); exit;
}

$userId = $_SESSION['user']['id'] ?? null;

$stmt = $pdo->prepare(
    "INSERT INTO inquiries (name, email, phone, message, user_id, property_id, project_id) VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$name, $email, $phone ?: null, $message, $userId, $propertyId, $projectId]);

// CRM Integration: Capture as Lead
try {
    require_once __DIR__ . '/../includes/crm-utils.php';
    crmCaptureLead($pdo, [
        'name'        => $name,
        'email'       => $email,
        'phone'       => $phone,
        'message'     => $message,
        'property_id' => $propertyId,
        'source'      => 'Website Form'
    ]);
} catch (\Throwable $e) {
    // Silently fail CRM integration to not block user submission
}

// Send Email Notification if enabled
$settings = loadSettings($pdo);
if (($settings['inquiry_notifications'] ?? '0') === '1' && !empty($settings['contact_email'])) {
    $to      = $settings['contact_email'];
    $subject = "New Inquiry from " . $name . " | " . ($settings['site_name'] ?? 'Advet');
    $headers = "From: " . ($settings['site_name'] ?? 'Advet Studio') . " <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $mailBody = "
    <div style='font-family: serif; border: 1px solid #DFD8CC; padding: 40px; border-radius: 20px; color: #2A2925; max-width: 600px; margin: auto;'>
        <p style='text-transform: uppercase; letter-spacing: 0.2em; font-size: 10px; color: #899178; font-weight: bold; margin-bottom: 20px;'>New Studio Inquiry</p>
        <h2 style='font-weight: 300; margin-bottom: 30px;'>A conversation <span style='font-style: italic; color: #6D685C;'>has begun.</span></h2>
        
        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Phone:</strong> " . ($phone ?: 'Not provided') . "</p>
        <p><strong>Message:</strong><br>{$message}</p>
        
        <div style='margin-top: 40px; padding-top: 20px; border-top: 1px solid #DFD8CC; font-size: 11px; color: #6D685C;'>
            Sent from " . ($settings['site_name'] ?? 'Advet Buildwell') . " Studio Portal.
        </div>
    </div>";

    @mail($to, $subject, $mailBody, $headers);
}

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Thank you! Your inquiry has been received. We\'ll be in touch soon.'];
header('Location: ' . $referer);
exit;
