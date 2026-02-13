<?php
require_once 'includes/db.php';
$db = Database::getInstance();
$tables = $db->fetchAll("SHOW TABLES");
echo "--- TABLES IN hucappdb ---\n";
foreach ($tables as $t) {
    echo array_values($t)[0] . "\n";
}
