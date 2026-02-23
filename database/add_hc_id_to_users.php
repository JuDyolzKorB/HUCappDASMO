<?php
// database/add_hc_id_to_users.php
require_once __DIR__ . '/../includes/db.php';

try {
    global $db;
    echo "Adding HealthCenterID to Users table...\n";
    
    // Add HealthCenterID column
    $db->execute("ALTER TABLE Users ADD COLUMN IF NOT EXISTS HealthCenterID INT AFTER Role");
    
    // Add foreign key
    $db->execute("ALTER TABLE Users ADD FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE SET NULL");
    
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
