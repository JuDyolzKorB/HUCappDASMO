<?php
require_once __DIR__ . '/../includes/db.php';

try {
    global $db;
    
    // 1. Create Contract Table
    $db->execute("
        CREATE TABLE IF NOT EXISTS Contract (
            ContractID INT AUTO_INCREMENT PRIMARY KEY,
            SupplierID INT,
            ContractNumber VARCHAR(100) UNIQUE NOT NULL,
            StartDate DATE,
            EndDate DATE,
            ContractAmount DECIMAL(15, 2),
            StatusType VARCHAR(50) DEFAULT 'Active',
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (SupplierID) REFERENCES Supplier(SupplierID) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "✓ Contract table ensured.\n";
    
    // 2. Add ContractID column to ProcurementOrder if it doesn't exist
    // Check if column exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM ProcurementOrder LIKE 'ContractID'");
    if (empty($columns)) {
        $db->execute("ALTER TABLE ProcurementOrder ADD COLUMN ContractID INT AFTER StatusType");
        $db->execute("ALTER TABLE ProcurementOrder ADD FOREIGN KEY (ContractID) REFERENCES Contract(ContractID) ON DELETE SET NULL");
        echo "✓ ContractID column added to ProcurementOrder.\n";
    } else {
        echo "i ContractID column already exists in ProcurementOrder.\n";
    }
    
    echo "Summary: Database updated successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
