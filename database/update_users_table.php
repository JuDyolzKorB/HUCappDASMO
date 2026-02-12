<?php
// database/update_users_table.php
require_once __DIR__ . '/../includes/db.php';

try {
    global $db;
    
    echo "Starting Users table migration...\n";

    // Add EmailNotifications
    $db->execute("ALTER TABLE Users ADD COLUMN IF NOT EXISTS EmailNotifications TINYINT(1) DEFAULT 1 AFTER Password");
    echo "Added EmailNotifications column.\n";

    // Add InAppNotifications
    $db->execute("ALTER TABLE Users ADD COLUMN IF NOT EXISTS InAppNotifications TINYINT(1) DEFAULT 1 AFTER EmailNotifications");
    echo "Added InAppNotifications column.\n";

    // Add ThemePreference
    $db->execute("ALTER TABLE Users ADD COLUMN IF NOT EXISTS ThemePreference VARCHAR(50) DEFAULT 'system' AFTER InAppNotifications");
    echo "Added ThemePreference column.\n";

    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
