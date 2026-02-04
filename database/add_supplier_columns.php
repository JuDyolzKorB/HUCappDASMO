<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Starting schema update...\n";
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Add SupplierName column if not exists
    try {
        $conn->exec("ALTER TABLE PurchaseOrder ADD COLUMN SupplierName VARCHAR(200) AFTER SupplierID");
        echo "Added SupplierName column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "SupplierName column already exists.\n";
        } else {
            throw $e;
        }
    }

    // Add SupplierAddress column if not exists
    try {
        $conn->exec("ALTER TABLE PurchaseOrder ADD COLUMN SupplierAddress TEXT AFTER SupplierName");
        echo "Added SupplierAddress column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "SupplierAddress column already exists.\n";
        } else {
            throw $e;
        }
    }
    
    // Modify SupplierID to be nullable (ALTER TABLE PurchaseOrder MODIFY SupplierID INT NULL)
    // It is already nullable in schema.sql (FOREIGN KEY ... ON DELETE SET NULL implies it)
    // But let's be sure.
    $conn->exec("ALTER TABLE PurchaseOrder MODIFY SupplierID INT NULL");
    echo "Ensured SupplierID is nullable.\n";

    echo "Schema update completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
