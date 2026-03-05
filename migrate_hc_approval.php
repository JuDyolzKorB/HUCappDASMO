<?php
require_once 'includes/db.php';

$db = Database::getInstance();
$hcs = $db->fetchAll("SELECT HealthCenterID, Name, DatabaseName FROM HealthCenters WHERE DatabaseName IS NOT NULL AND DatabaseName != ''");

$migration = "
ALTER TABLE PatientRequisition
    ADD COLUMN IF NOT EXISTS ApprovedBy INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ApprovedAt DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS ApproverName VARCHAR(255) DEFAULT NULL;
";

foreach ($hcs as $hc) {
    echo "Migrating: {$hc['Name']} ({$hc['DatabaseName']})\n";
    try {
        $hcConn = Database::getHCConnection($hc['HealthCenterID']);
        if (!$hcConn) { echo "  SKIP: No connection\n"; continue; }
        $hcConn->exec($migration);
        echo "  OK\n";
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}
echo "Done.\n";
