<?php
// FILE: actions/delete-featured-project.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: ' . BASE . 'admin/featured-projects.php'); exit;
}

$pdo = getPDO();
$id = (int)$_POST['id'];

// Ideally we'd delete the file too, but for now we just remove the db entry
$stmt = $pdo->prepare("DELETE FROM featured_projects WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Project removed from highlights.'];
header('Location: ' . BASE . 'admin/featured-projects.php');
exit;
