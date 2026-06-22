<?php
require_once 'd:/wamp/www/advet/config/db.php';
$pdo = getPDO();

function checkTable($pdo, $tableName) {
    echo "--- Table: $tableName ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE `$tableName`");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Field: {$row['Field']}, Type: {$row['Type']}\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

checkTable($pdo, 'crm_activities');
checkTable($pdo, 'crm_transactions');
checkTable($pdo, 'crm_tasks');
checkTable($pdo, 'crm_documents');
