<?php
// database/create_inventory_adjustment_table.php
require_once __DIR__ . '/../includes/db.php';

global $db;

$sql = "
CREATE TABLE IF NOT EXISTS InventoryAdjustment (
    AdjustmentID INT AUTO_INCREMENT PRIMARY KEY,
    BatchID INT NOT NULL,
    UserID INT NOT NULL,
    AdjustmentQuantity INT NOT NULL,
    Reason VARCHAR(255),
    AdjustmentDate DATETIME,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $db->execute($sql);
    echo "Successfully created InventoryAdjustment table.\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
