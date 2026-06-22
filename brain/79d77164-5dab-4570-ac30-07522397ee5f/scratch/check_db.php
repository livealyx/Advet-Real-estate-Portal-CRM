<?php
require_once 'config/db.php';
$pdo = getPDO();
$res = $pdo->query('SELECT id, title, category, status FROM properties')->fetchAll();
print_r($res);
