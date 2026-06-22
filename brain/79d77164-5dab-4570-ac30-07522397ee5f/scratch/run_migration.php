<?php
require_once 'config/db.php';
$pdo = getPDO();
$sql = file_get_contents('migrate.sql');
try {
    $pdo->exec($sql);
    echo "Migration Successful\n";
} catch (Exception $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
