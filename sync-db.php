<?php
// Root sync script
require_once 'config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Critical Database Sync</h1>";

$cols = [
    'agent_phone'    => "VARCHAR(50) DEFAULT NULL",
    'agent_whatsapp' => "VARCHAR(50) DEFAULT NULL"
];

foreach ($cols as $name => $def) {
    try {
        echo "Processing <b>$name</b>... ";
        $exists = $pdo->query("SHOW COLUMNS FROM projects LIKE '$name'")->rowCount();
        
        if (!$exists) {
            $pdo->exec("ALTER TABLE projects ADD $name $def");
            echo "<span style='color:green'>SUCCESSfully added column.</span><br>";
        } else {
            echo "<span style='color:orange'>Already exists.</span><br>";
        }
    } catch (PDOException $e) {
        echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
    }
}

echo "<hr><p>If both say SUCCESS or Already Exists, please go back and save your project.</p>";
?>
