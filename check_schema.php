<?php
require_once 'includes/db.php';
$out = fopen('schema_output.txt', 'w');
function checkTable($db, $table, $out) {
    fwrite($out, "--- $table ---\n");
    try {
        $res = $db->fetchAll("DESCRIBE $table");
        fwrite($out, sprintf("%-20s | %-15s | %-5s | %-4s | %-15s | %-15s\n", "Field", "Type", "Null", "Key", "Default", "Extra"));
        fwrite($out, str_repeat("-", 85) . "\n");
        foreach ($res as $row) {
            $line = sprintf("%-20s | %-15s | %-5s | %-4s | %-15s | %-15s\n", 
                $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default'], $row['Extra']);
            fwrite($out, $line);
        }
    } catch (Exception $e) {
        fwrite($out, "Error: " . $e->getMessage() . "\n");
    }
    fwrite($out, "\n");
}

$db = Database::getInstance();
checkTable($db, 'Issuance', $out);
checkTable($db, 'IssuanceItem', $out);
checkTable($db, 'CentralInventoryBatch', $out);
fclose($out);
