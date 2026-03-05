<?php
// Migration: Add LotNumber to CentralInventoryBatch
require_once __DIR__ . '/../includes/db.php';

$sql = "ALTER TABLE CentralInventoryBatch ADD COLUMN IF NOT EXISTS LotNumber VARCHAR(100) NULL AFTER BatchID";
try {
    $db->execute($sql);
    echo "Migration successful: LotNumber column added to CentralInventoryBatch.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
