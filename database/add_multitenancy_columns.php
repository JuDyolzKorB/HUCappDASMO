<?php
// database/add_multitenancy_columns.php
require_once __DIR__ . '/../includes/db.php';

try {
    global $db;
    
    echo "Starting Multi-tenancy migration...\n";

    // 1. Add DatabaseName to HealthCenters
    try {
        $db->execute("ALTER TABLE HealthCenters ADD COLUMN IF NOT EXISTS DatabaseName VARCHAR(100) AFTER Name");
        echo "Added DatabaseName column to HealthCenters.\n";
    } catch (Exception $e) {
        echo "DatabaseName column already exists or error: " . $e->getMessage() . "\n";
    }

    // 2. Add HealthCenterID to Users
    try {
        $db->execute("ALTER TABLE Users ADD COLUMN IF NOT EXISTS HealthCenterID INT AFTER UserID");
        $db->execute("ALTER TABLE Users ADD FOREIGN KEY IF NOT EXISTS (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE SET NULL");
        echo "Added HealthCenterID column to Users.\n";
    } catch (Exception $e) {
        echo "HealthCenterID column already exists or error: " . $e->getMessage() . "\n";
    }

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
