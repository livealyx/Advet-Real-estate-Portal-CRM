<?php
// FILE: actions/search-projects.php
header('Content-Type: application/json');
require_once '../config/db.php';

$pdo = getPDO();
$q   = trim($_GET['q'] ?? '');

try {
    if (strlen($q) < 2) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id, title, location, cover_image 
        FROM projects 
        WHERE (title LIKE ? OR location LIKE ?) 
        AND status = 'active' 
        LIMIT 10
    ");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
    $projects = $stmt->fetchAll();

    $results = [];
    foreach ($projects as $p) {
        $results[] = [
            'id' => $p['id'],
            'text' => $p['title'],
            'subtext' => $p['location'],
            'image' => imgUrl($p['cover_image'])
        ];
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
