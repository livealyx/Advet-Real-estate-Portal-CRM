<?php
// FILE: actions/delete-archetype.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

$id = $_POST['id'] ?? null;
if ($id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM space_archetypes WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Archetype removed from archive.'];
}

header('Location: ' . BASE . 'admin/featured-commercial.php');
exit;
