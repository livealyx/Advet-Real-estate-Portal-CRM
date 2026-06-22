<?php
require_once 'config/db.php';
$pdo = getPDO();
$pdo->exec("UPDATE properties SET category = 'Flat/Apartment' WHERE category = '' OR category IS NULL OR category = 'Home'");
echo "Categories Repaired\n";
