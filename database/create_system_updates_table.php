<?php
require_once __DIR__ . '/../includes/db.php';

$sql = "CREATE TABLE IF NOT EXISTS SystemUpdates (
    UpdateID INT AUTO_INCREMENT PRIMARY KEY,
    EventType VARCHAR(100) NOT NULL,
    EventData JSON,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

global $db;
try {
    $db->execute($sql);
    echo "SystemUpdates table created successfully.\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
