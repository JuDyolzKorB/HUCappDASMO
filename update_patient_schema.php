<?php
require_once 'includes/db.php';
global $db;

$sql = "
-- Update HCPatient table
ALTER TABLE HCPatient ADD COLUMN IF NOT EXISTS IDProof VARCHAR(255) AFTER ContactNumber;

-- Update HCPatientRequisition table
ALTER TABLE HCPatientRequisition ADD COLUMN IF NOT EXISTS Diagnosis VARCHAR(255) AFTER StatusType;
ALTER TABLE HCPatientRequisition ADD COLUMN IF NOT EXISTS Notes TEXT AFTER Diagnosis;
ALTER TABLE HCPatientRequisition ADD COLUMN IF NOT EXISTS ContactInfo VARCHAR(255) AFTER Notes;
ALTER TABLE HCPatientRequisition ADD COLUMN IF NOT EXISTS IDProof VARCHAR(255) AFTER ContactInfo;

-- Ensure the UserID column exists if not already present
ALTER TABLE HCPatientRequisition ADD COLUMN IF NOT EXISTS UserID INT AFTER PatientID;
";

try {
    $db->getConnection()->exec($sql);
    echo "Database schema updated successfully.\n";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
