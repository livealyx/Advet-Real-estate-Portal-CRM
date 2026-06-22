<?php
// FILE: actions/generate-sitemap.php
session_start();
require_once '../config/db.php';

// Allow if admin OR if coming from a secret cron key (optional security)
$isCron = (isset($_GET['key']) && $_GET['key'] === 'studiocron123');
if (!$isCron && (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin')) {
    die('Unauthorized');
}

$pdo = getPDO();
$base = 'http://' . $_SERVER['HTTP_HOST'] . BASE; 

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

// 1. Static Pages
$static = ['', 'about.php', 'philosophy.php', 'properties.php', 'commercial.php', 'stories.php', 'contact.php', 'faq.php'];
foreach ($static as $page) {
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $base . $page . '</loc>' . PHP_EOL;
    $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
    $xml .= '    <priority>' . ($page === '' ? '1.0' : '0.8') . '</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
}

// 2. Dynamic Properties
$props = $pdo->query("SELECT id, updated_at FROM properties WHERE status = 'active'")->fetchAll();
foreach ($props as $p) {
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $base . 'property-detail.php?id=' . $p['id'] . '</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . date('Y-m-d', strtotime($p['updated_at'])) . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
    $xml .= '    <priority>0.7</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
}

// 3. Dynamic Stories
$stories = $pdo->query("SELECT slug, published_at FROM stories WHERE published_at IS NOT NULL")->fetchAll();
foreach ($stories as $s) {
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $base . 'story-detail.php?slug=' . $s['slug'] . '</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . date('Y-m-d', strtotime($s['published_at'])) . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
    $xml .= '    <priority>0.6</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
}

$xml .= '</urlset>';

// Write to root
$filePath = __DIR__ . '/../sitemap.xml';
if (file_put_contents($filePath, $xml)) {
    // Log success
    $pdo->prepare("INSERT INTO cron_logs (task_name, message) VALUES (?, ?)")
        ->execute(['Sitemap Generation', 'Successfully generated sitemap.xml with ' . (count($static) + count($props) + count($stories)) . ' URLs.']);
    
    if (!$isCron) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Sitemap has been successfully regenerated.'];
        header('Location: ' . BASE . 'admin/system.php');
    }
} else {
    if (!$isCron) {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Failed to write sitemap.xml. Check file permissions.'];
        header('Location: ' . BASE . 'admin/system.php');
    }
}
