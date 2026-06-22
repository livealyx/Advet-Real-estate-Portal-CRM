<?php
// FILE: actions/get-related-properties.php
header('Content-Type: application/json');
require_once '../config/db.php';

$pdo = getPDO();

$type = $_GET['type'] ?? 'property'; // 'property' or 'project'
$currentId = (int)($_GET['id'] ?? 0);
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';
$price = (float)($_GET['price'] ?? 0);

// Extract city from location if possible (assuming "Area, City" format)
$city = '';
if (!empty($location)) {
    $parts = explode(',', $location);
    $city = trim(end($parts));
}

try {
    ob_start();
    
    // Build recommendation query
    $sql = "SELECT p.id, p.title, p.slug, p.location, p.price, p.category, p.featured_image, p.description, p.bedrooms, p.bathrooms, p.sqft, p.flat_type, p.listing_type, u.is_verified, p.project_id
            FROM properties p
            LEFT JOIN users u ON u.id = p.agent_id
            WHERE p.status = 'active'";
    
    $params = [];
    if ($type === 'property' && $currentId > 0) {
        $sql .= " AND p.id != ?";
        $params[] = $currentId;
    }
    
    // Simple relevance scoring in SQL
    // Priority: Same Project > Same Location > Verified Agent > Same Category > Price Proximity
    $sql .= " ORDER BY 
                (p.project_id = ?) DESC,
                (p.location LIKE ?) DESC, 
                (u.is_verified = 1) DESC,
                (p.category = ?) DESC,
                ABS(p.price - ?) ASC
              LIMIT 8";
    
    $params[] = ($type === 'project' ? $currentId : 0);
    $params[] = "%$city%";
    $params[] = $category;
    $params[] = $price;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    // Format results
    $formatted = [];
    foreach ($results as $row) {
        $desc = strip_tags($row['description'] ?? '');
        if (function_exists('mb_strimwidth')) {
            $shortDesc = mb_strimwidth($desc, 0, 100, "...");
        } else {
            $shortDesc = strlen($desc) > 100 ? substr($desc, 0, 97) . "..." : $desc;
        }

        $formatted[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'location' => $row['location'],
            'price' => formatPrice((float)$row['price']),
            'raw_price' => $row['price'],
            'category' => $row['category'],
            'image' => imgUrl($row['featured_image']),
            'description' => $shortDesc,
            'bedrooms' => $row['bedrooms'],
            'bathrooms' => $row['bathrooms'],
            'sqft' => $row['sqft'],
            'flat_type' => $row['flat_type'],
            'listing_type' => $row['listing_type'],
            'url' => BASE . 'public/property-detail.php?slug=' . $row['slug']
        ];
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => $formatted,
        'count' => count($formatted)
    ]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
