<?php
// database/create_hc_inventory_table.php
require_once __DIR__ . '/../includes/db.php';

global $db;

$sql = "
CREATE TABLE IF NOT EXISTS HCInventoryBatch (
    HCBatchID INT AUTO_INCREMENT PRIMARY KEY,
    HealthCenterID INT NOT NULL,
    ItemID INT NOT NULL,
    BatchID INT, -- Reference to the original central batch
    ExpiryDate DATE,
    QuantityOnHand INT NOT NULL DEFAULT 0,
    UnitCost DECIMAL(10, 2),
    DateReceivedAtHC DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $db->getConnection()->exec($sql);
    echo "Table 'HCInventoryBatch' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
