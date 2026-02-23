<?php
// database/add_hc_to_users.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = Database::getInstance();
    
    echo "Adding HealthCenterID column to Users table...\n";
    
    // Check if column exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM Users");
    $exists = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'HealthCenterID') {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        $db->execute("ALTER TABLE Users ADD COLUMN HealthCenterID INT NULL AFTER Role");
        $db->execute("ALTER TABLE Users ADD CONSTRAINT fk_user_hc FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE SET NULL");
        echo "Column added successfully.\n";
    } else {
        echo "Column already exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
