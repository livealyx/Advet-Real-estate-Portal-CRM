<?php
// FILE: actions/delete-inquiry.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agent'])) {
    header('Location: ' . BASE . 'auth/login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $redirect = $_POST['redirect'] ?? (BASE . 'admin/inquiries.php');

    if ($id > 0) {
        try {
            $pdo = getPDO();
            $userRole = $_SESSION['user']['role'];
            $userId   = (int)$_SESSION['user']['id'];

            if ($userRole === 'admin') {
                $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
                $stmt->execute([$id]);
            } else {
                // Agent can only delete inquiries for their own properties
                $stmt = $pdo->prepare("DELETE i FROM inquiries i JOIN properties p ON i.property_id = p.id WHERE i.id = ? AND p.agent_id = ?");
                $stmt->execute([$id, $userId]);
            }

            if ($stmt->rowCount() > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => "Inquiry deleted successfully."];
            } else {
                $_SESSION['flash'] = ['type' => 'error', 'msg' => "Inquiry not found or access denied."];
            }
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => "Error deleting inquiry: " . $e->getMessage()];
        }
    }
    header('Location: ' . $redirect);
    exit;
}
header('Location: ' . BASE . 'admin/inquiries.php');
exit;
