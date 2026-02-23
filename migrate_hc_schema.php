<?php
require_once 'includes/db.php';

$db = Database::getInstance();
$hcs = $db->fetchAll("SELECT HealthCenterID, Name, DatabaseName FROM HealthCenters WHERE DatabaseName IS NOT NULL AND DatabaseName != ''");

$migration = "
CREATE TABLE IF NOT EXISTS PatientRequisition (
    PatientRequisitionID INT AUTO_INCREMENT PRIMARY KEY,
    PatientName VARCHAR(255) NOT NULL,
    PatientAddress TEXT,
    ContactNumber VARCHAR(20),
    IDProofPath VARCHAR(255),
    OtherInfo TEXT,
    StaffID INT,
    RequestDate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Pending',
    Remarks TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StaffID) REFERENCES HC_Staff(StaffID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS PatientRequisitionItem (
    HCPRIID INT AUTO_INCREMENT PRIMARY KEY,
    PatientRequisitionID INT NOT NULL,
    ItemID INT NOT NULL,
    BatchID INT,
    QuantityRequested INT NOT NULL,
    QuantityIssued INT DEFAULT 0,
    FOREIGN KEY (PatientRequisitionID) REFERENCES PatientRequisition(PatientRequisitionID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

foreach ($hcs as $hc) {
    $dbName = $hc['DatabaseName'];
    echo "Migrating: {$hc['Name']} ({$dbName})\n";
    
    try {
        $hcConn = Database::getHCConnection($hc['HealthCenterID']);
        if (!$hcConn) {
            echo "  SKIP: Could not connect\n";
            continue;
        }
        
        foreach (array_filter(array_map('trim', explode(';', $migration))) as $sql) {
            if (!empty($sql)) {
                $hcConn->exec($sql);
            }
        }
        
        echo "  OK\n";
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration complete.\n";
