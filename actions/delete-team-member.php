<?php
// FILE: actions/delete-team-member.php
session_start();
require_once '../config/db.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: ' . BASE . 'admin/team.php'); exit;
}

$pdo = getPDO();
$id = (int)$_POST['id'];

$stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['flash'] = ['type' => 'success', 'msg' => 'Team member removed.'];
header('Location: ' . BASE . 'admin/team.php');
exit;
