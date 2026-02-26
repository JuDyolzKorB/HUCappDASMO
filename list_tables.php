<?php
require_once 'includes/db.php';
global $db;
$tables = $db->fetchAll("SHOW TABLES");
foreach ($tables as $row) {
    $tableName = array_values($row)[0];
    try {
        $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM $tableName")['cnt'];
        echo "$tableName ($count rows)\n";
    } catch (Exception $e) {
        echo "$tableName (ERROR: " . $e->getMessage() . ")\n";
    }
}
