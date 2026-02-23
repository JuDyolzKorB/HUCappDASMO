-- Migration script for Patient Requisitions
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS HCPatient (
    PatientID INT AUTO_INCREMENT PRIMARY KEY,
    HealthCenterID INT NOT NULL,
    FName VARCHAR(100) NOT NULL,
    MName VARCHAR(100),
    LName VARCHAR(100) NOT NULL,
    Age INT,
    Gender ENUM('Male', 'Female', 'Other'),
    Address TEXT,
    ContactNumber VARCHAR(20),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS HCPatientRequisition (
    PatientReqID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT NOT NULL,
    UserID INT NOT NULL,
    HealthCenterID INT NOT NULL,
    RequisitionNumber VARCHAR(100) UNIQUE,
    RequestDate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Pending',
    Diagnosis TEXT,
    Notes TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PatientID) REFERENCES HCPatient(PatientID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS HCPatientRequisitionItem (
    PRItemID INT AUTO_INCREMENT PRIMARY KEY,
    PatientReqID INT NOT NULL,
    ItemID INT NOT NULL,
    QuantityRequested INT NOT NULL,
    FOREIGN KEY (PatientReqID) REFERENCES HCPatientRequisition(PatientReqID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Indexes if they don't exist (using a procedure or just raw statements if supported)
-- For simplicity, just the tables first.

SET FOREIGN_KEY_CHECKS = 1;
