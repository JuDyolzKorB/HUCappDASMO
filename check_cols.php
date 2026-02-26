<?php
require_once 'includes/db.php';
global $db;
$tables = ['HCPatient', 'HCPatientRequisition'];
foreach ($tables as $t) {
    try {
        $cols = $db->fetchAll("DESCRIBE $t");
        echo "Table: $t\n";
        foreach ($cols as $c) {
            echo " - {$c['Field']} ({$c['Type']})\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Table $t error: " . $e->getMessage() . "\n";
    }
}
