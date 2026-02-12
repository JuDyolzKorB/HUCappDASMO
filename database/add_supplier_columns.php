<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Starting schema update...\n";
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Add SupplierName column if not exists
    try {
        $conn->exec("ALTER TABLE ProcurementOrder ADD COLUMN SupplierName VARCHAR(200) AFTER SupplierID");
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
        $conn->exec("ALTER TABLE ProcurementOrder ADD COLUMN SupplierAddress TEXT AFTER SupplierName");
        echo "Added SupplierAddress column.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "SupplierAddress column already exists.\n";
        } else {
            throw $e;
        }
    }
    
    // Modify SupplierID to be nullable
    $conn->exec("ALTER TABLE ProcurementOrder MODIFY SupplierID INT NULL");
    echo "Ensured SupplierID is nullable.\n";

    echo "Schema update completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
