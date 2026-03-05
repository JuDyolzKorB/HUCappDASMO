<?php
// Migration: Add Brand and DosageUnit to Item table
require_once __DIR__ . '/../includes/db.php';

$statements = [
    "ALTER TABLE Item ADD COLUMN IF NOT EXISTS Brand VARCHAR(150) NULL AFTER ItemName",
    "ALTER TABLE Item ADD COLUMN IF NOT EXISTS DosageUnit VARCHAR(100) NULL AFTER UnitOfMeasure",
];

foreach ($statements as $sql) {
    try {
        $db->execute($sql);
        echo "OK: $sql\n";
    } catch (Exception $e) {
        echo "Failed: " . $e->getMessage() . "\n";
    }
}
echo "Migration complete.\n";
