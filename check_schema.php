<?php
require_once 'includes/db.php';
global $db;
$tables = ['Users', 'ProcurementOrder', 'CentralInventoryBatch', 'HCInventoryBatch'];
foreach ($tables as $table) {
    try {
        echo "Table: $table\n";
        $cols = $db->fetchAll("DESCRIBE $table");
        foreach ($cols as $col) {
            echo "  - {$col['Field']}\n";
        }
    } catch (Exception $e) {
        echo "Error on $table: " . $e->getMessage() . "\n";
    }
    echo "------------------\n";
}
