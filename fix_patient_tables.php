<?php
require_once 'includes/db.php';
global $db;

$sql = "
CREATE TABLE IF NOT EXISTS HCPatient (
    PatientID INT AUTO_INCREMENT PRIMARY KEY,
    HealthCenterID INT NOT NULL,
    FName VARCHAR(100) NOT NULL,
    MName VARCHAR(100),
    LName VARCHAR(100) NOT NULL,
    Age INT,
    Gender VARCHAR(20),
    ContactNumber VARCHAR(20),
    Address TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID)
);

CREATE TABLE IF NOT EXISTS HCPatientRequisition (
    PatientReqID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT NOT NULL,
    HealthCenterID INT NOT NULL,
    RequisitionNumber VARCHAR(50) UNIQUE NOT NULL,
    RequestDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    StatusType ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
    Remarks TEXT,
    FOREIGN KEY (PatientID) REFERENCES HCPatient(PatientID),
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID)
);

CREATE TABLE IF NOT EXISTS HCPatientRequisitionItem (
    PatientReqItemID INT AUTO_INCREMENT PRIMARY KEY,
    PatientReqID INT NOT NULL,
    ItemID INT NOT NULL,
    QuantityRequested INT NOT NULL,
    QuantityIssued INT DEFAULT 0,
    UnitCost DECIMAL(10, 2) DEFAULT 0.00,
    FOREIGN KEY (PatientReqID) REFERENCES HCPatientRequisition(PatientReqID),
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID)
);
";

try {
    $db->getConnection()->exec($sql);
    echo "Tables created or already exist.\n";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
}
