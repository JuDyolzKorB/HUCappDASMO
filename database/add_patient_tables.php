<?php
// database/add_patient_tables.php
require_once __DIR__ . '/../includes/db.php';

global $db;

// This script should run on each health center database.
// In this system, health centers are managed as separate schemas or handled via getHCConnection.
// We'll iterate through all Health Centers and apply the migration.

$healthCenters = $db->fetchAll("SELECT * FROM HealthCenters");

foreach ($healthCenters as $hc) {
    echo "Updating database for Health Center: {$hc['Name']} (ID: {$hc['HealthCenterID']})...\n";
    
    try {
        $hcConn = Database::getHCConnection($hc['HealthCenterID']);
        if (!$hcConn) {
            echo "  - Connection failed, skipping.\n";
            continue;
        }

        // 1. Rename and Add Columns to PatientRequisition (formerly HC_Requisition or HC_PatientRequisition)
        // We'll try to handle various legacy states
        
        // Ensure PatientRequisition exists and has new columns
        $hcConn->exec("CREATE TABLE IF NOT EXISTS PatientRequisition (
            PatientRequisitionID INT AUTO_INCREMENT PRIMARY KEY,
            PatientName VARCHAR(255),
            PatientAddress TEXT,
            ContactNumber VARCHAR(20),
            IDProofPath VARCHAR(255),
            OtherInfo TEXT,
            StaffID INT,
            RequestDate DATETIME,
            StatusType VARCHAR(50) DEFAULT 'Pending',
            Remarks TEXT,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Try to migrate from HC_Requisition if it exists
        try {
            $hcConn->exec("INSERT INTO PatientRequisition (StaffID, RequestDate, StatusType, Remarks, CreatedAt) 
                           SELECT StaffID, RequestDate, StatusType, Remarks, CreatedAt FROM HC_Requisition");
            $hcConn->exec("DROP TABLE HC_Requisition");
            echo "  - Migrated from HC_Requisition.\n";
        } catch (Exception $e) { /* Table might not exist, skip */ }

        // Try to migrate from HC_PatientRequisition if it exists
        try {
            $hcConn->exec("INSERT INTO PatientRequisition (PatientName, StaffID, RequestDate, StatusType, Remarks, CreatedAt) 
                           SELECT CONCAT(p.FirstName, ' ', p.LastName), pr.StaffID, pr.RequestDate, pr.StatusType, pr.Remarks, pr.CreatedAt 
                           FROM HC_PatientRequisition pr 
                           JOIN HC_Patient p ON pr.PatientID = p.PatientID");
            $hcConn->exec("DROP TABLE HC_PatientRequisition");
            $hcConn->exec("DROP TABLE HC_Patient");
            echo "  - Migrated from HC_PatientRequisition and HC_Patient.\n";
        } catch (Exception $e) { /* Tables might not exist, skip */ }

        // 2. Create PatientRequisitionItem Table
        $hcConn->exec("CREATE TABLE IF NOT EXISTS PatientRequisitionItem (
            HCPRIID INT AUTO_INCREMENT PRIMARY KEY,
            PatientRequisitionID INT NOT NULL,
            ItemID INT NOT NULL,
            BatchID INT,
            QuantityRequested INT NOT NULL,
            QuantityIssued INT DEFAULT 0,
            FOREIGN KEY (PatientRequisitionID) REFERENCES PatientRequisition(PatientRequisitionID) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        echo "  - Created PatientRequisitionItem table.\n";

        echo "  - Successfully updated.\n";
    } catch (Exception $e) {
        echo "  - Error: " . $e->getMessage() . "\n";
    }
}
echo "Migration complete.\n";
