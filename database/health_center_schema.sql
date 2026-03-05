-- Health Center Local Database Schema
-- This schema is intended to be used for separate databases for each health center.
-- It tracks local inventory, requisitions, and links back to central warehouse batches.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS HC_RequisitionItem;
DROP TABLE IF EXISTS HC_Requisition;
DROP TABLE IF EXISTS HC_Inventory;
DROP TABLE IF EXISTS HC_Staff;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Local Staff Table
CREATE TABLE HC_Staff (
    StaffID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Role VARCHAR(50) NOT NULL, -- e.g., 'Staff', 'Nurse', 'Doctor'
    Username VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Local Inventory Table
-- Tracks items received from the central warehouse
CREATE TABLE HC_Inventory (
    InventoryID INT AUTO_INCREMENT PRIMARY KEY,
    ItemID INT NOT NULL, -- Central Item ID
    BatchID INT NOT NULL, -- Reference to CentralInventoryBatch.BatchID
    QuantityOnHand INT NOT NULL DEFAULT 0,
    ExpiryDate DATE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Local Patient Requisition Table
-- Requisitions made for patients within the health center
CREATE TABLE PatientRequisition (
    PatientRequisitionID INT AUTO_INCREMENT PRIMARY KEY,
    PatientName VARCHAR(255) NOT NULL,
    PatientAddress TEXT,
    ContactNumber VARCHAR(20),
    IDProofPath VARCHAR(255), -- Path to uploaded photo
    OtherInfo TEXT,
    StaffID INT,
    RequestDate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Pending',
    Remarks TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StaffID) REFERENCES HC_Staff(StaffID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Local Patient Requisition Item Table
CREATE TABLE PatientRequisitionItem (
    HCPRIID INT AUTO_INCREMENT PRIMARY KEY,
    PatientRequisitionID INT NOT NULL,
    ItemID INT NOT NULL, -- Central Item ID
    BatchID INT, -- Which batch it was fulfilled from (links to central BatchID)
    QuantityRequested INT NOT NULL,
    QuantityIssued INT DEFAULT 0,
    FOREIGN KEY (PatientRequisitionID) REFERENCES PatientRequisition(PatientRequisitionID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for performance
CREATE INDEX idx_hc_inv_item ON HC_Inventory(ItemID);
CREATE INDEX idx_hc_inv_batch ON HC_Inventory(BatchID);
CREATE INDEX idx_pc_req_status ON PatientRequisition(StatusType);
