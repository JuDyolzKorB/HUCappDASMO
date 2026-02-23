-- HUCappDASMO Database Schema
-- Refactored for Auto-Increment IDs and Correct Relationships

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS Report;
DROP TABLE IF EXISTS ApprovalLog;
DROP TABLE IF EXISTS SecurityLog;
DROP TABLE IF EXISTS TransactionAuditLog;
DROP TABLE IF EXISTS NoticeOfIssue;
DROP TABLE IF EXISTS RequisitionAdjustmentDetail;
DROP TABLE IF EXISTS RequisitionAdjustment;
DROP TABLE IF EXISTS IssuanceItem;
DROP TABLE IF EXISTS Issuance;
DROP TABLE IF EXISTS RequisitionItem;
DROP TABLE IF EXISTS Requisition;
DROP TABLE IF EXISTS ReceivingItem;
DROP TABLE IF EXISTS Receiving;
DROP TABLE IF EXISTS PurchaseOrderItem;
DROP TABLE IF EXISTS PurchaseOrder;
DROP TABLE IF EXISTS CentralInventoryBatch;
DROP TABLE IF EXISTS Inventory; -- Legacy check
DROP TABLE IF EXISTS Contract;
DROP TABLE IF EXISTS Warehouse;
DROP TABLE IF EXISTS Item;
DROP TABLE IF EXISTS Supplier;
DROP TABLE IF EXISTS HealthCenters;
DROP TABLE IF EXISTS Users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. User Table
CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FName VARCHAR(100) NOT NULL,
    MName VARCHAR(100),
    LName VARCHAR(100) NOT NULL,
    Role VARCHAR(50) NOT NULL,
    Username VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. HealthCenters Table
CREATE TABLE HealthCenters (
    HealthCenterID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(200) NOT NULL,
    Address TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Supplier Table
CREATE TABLE Supplier (
    SupplierID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(200) NOT NULL,
    Address TEXT,
    ContactInfo VARCHAR(200),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Item Table
CREATE TABLE Item (
    ItemID INT AUTO_INCREMENT PRIMARY KEY,
    ItemName VARCHAR(200) NOT NULL,
    ItemType VARCHAR(50),
    UnitOfMeasure VARCHAR(50),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Warehouse Table
CREATE TABLE Warehouse (
    WarehouseID INT AUTO_INCREMENT PRIMARY KEY,
    WarehouseName VARCHAR(200) NOT NULL,
    Location TEXT,
    WarehouseType VARCHAR(100),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CentralInventoryBatch Table
CREATE TABLE CentralInventoryBatch (
    BatchID INT AUTO_INCREMENT PRIMARY KEY,
    ItemID INT NOT NULL,
    WarehouseID INT DEFAULT 1, -- Default to Main Warehouse
    ExpiryDate DATE,
    QuantityOnHand INT NOT NULL DEFAULT 0,
    QuantityReleased INT DEFAULT 0,
    UnitCost DECIMAL(10, 2),
    DateReceived DATE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE,
    FOREIGN KEY (WarehouseID) REFERENCES Warehouse(WarehouseID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Contract Table
CREATE TABLE Contract (
    ContractID INT AUTO_INCREMENT PRIMARY KEY,
    SupplierID INT,
    ContractNumber VARCHAR(100) UNIQUE NOT NULL,
    StartDate DATE,
    EndDate DATE,
    ContractAmount DECIMAL(15, 2),
    StatusType VARCHAR(50) DEFAULT 'Active',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SupplierID) REFERENCES Supplier(SupplierID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ProcurementOrder Table
CREATE TABLE ProcurementOrder (
    POID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    SupplierID INT,
    HealthCenterID INT,
    PONumber VARCHAR(100) UNIQUE, -- Generated e.g. PO-2026-0001
    PODate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Pending',
    ContractID INT, -- Added per previousTurn
    DocumentType VARCHAR(100), -- Added per previousTurn
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL,
    FOREIGN KEY (SupplierID) REFERENCES Supplier(SupplierID) ON DELETE SET NULL,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE SET NULL,
    FOREIGN KEY (ContractID) REFERENCES Contract(ContractID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. ProcurementOrderItem Table
CREATE TABLE ProcurementOrderItem (
    POItemID INT AUTO_INCREMENT PRIMARY KEY,
    POID INT NOT NULL,
    ItemID INT NOT NULL,
    QuantityOrdered INT NOT NULL,
    UnitCost DECIMAL(10, 2),
    ExpiryDate DATE, -- Added back ExpiryDate per requirements
    FOREIGN KEY (POID) REFERENCES ProcurementOrder(POID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Receiving Table
CREATE TABLE Receiving (
    ReceivingID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    POID INT,
    ReceivedDate DATETIME NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL,
    FOREIGN KEY (POID) REFERENCES ProcurementOrder(POID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. ReceivingItem Table
CREATE TABLE ReceivingItem (
    ReceivingItemID INT AUTO_INCREMENT PRIMARY KEY,
    ReceivingID INT NOT NULL,
    BatchID INT NOT NULL,
    QuantityReceived INT NOT NULL,
    FOREIGN KEY (ReceivingID) REFERENCES Receiving(ReceivingID) ON DELETE CASCADE,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Requisition Table
CREATE TABLE Requisition (
    RequisitionID INT AUTO_INCREMENT PRIMARY KEY,
    RequisitionNumber VARCHAR(100) UNIQUE, -- Generated e.g. REQ-2026-0001
    HealthCenterID INT,
    UserID INT,
    RequestDate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. RequisitionItem Table
CREATE TABLE RequisitionItem (
    RequisitionItemID INT AUTO_INCREMENT PRIMARY KEY,
    RequisitionID INT NOT NULL,
    ItemID INT NOT NULL,
    QuantityRequested INT NOT NULL,
    FOREIGN KEY (RequisitionID) REFERENCES Requisition(RequisitionID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Issuance Table
CREATE TABLE Issuance (
    IssuanceID INT AUTO_INCREMENT PRIMARY KEY,
    RequisitionID INT,
    UserID INT,
    IssueDate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Issued',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequisitionID) REFERENCES Requisition(RequisitionID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. IssuanceItem Table
CREATE TABLE IssuanceItem (
    IssuanceItemID INT AUTO_INCREMENT PRIMARY KEY,
    IssuanceID INT NOT NULL,
    BatchID INT NOT NULL,
    RequisitionItemID INT,
    QuantityIssued INT NOT NULL,
    FOREIGN KEY (IssuanceID) REFERENCES Issuance(IssuanceID) ON DELETE CASCADE,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE CASCADE,
    FOREIGN KEY (RequisitionItemID) REFERENCES RequisitionItem(RequisitionItemID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. RequisitionAdjustment Table
CREATE TABLE RequisitionAdjustment (
    RequisitionAdjustmentID INT AUTO_INCREMENT PRIMARY KEY,
    IssuanceID INT,
    UserID INT,
    AdjustmentType VARCHAR(100),
    AdjustmentDate DATETIME,
    Reason TEXT,
    FOREIGN KEY (IssuanceID) REFERENCES Issuance(IssuanceID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. RequisitionAdjustmentDetail Table
CREATE TABLE RequisitionAdjustmentDetail (
    RADID INT AUTO_INCREMENT PRIMARY KEY,
    RequisitionAdjustmentID INT NOT NULL,
    BatchID INT,
    QuantityAdjusted INT,
    FOREIGN KEY (RequisitionAdjustmentID) REFERENCES RequisitionAdjustment(RequisitionAdjustmentID) ON DELETE CASCADE,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. NoticeOfIssue Table
CREATE TABLE NoticeOfIssue (
    IssueID INT AUTO_INCREMENT PRIMARY KEY,
    BatchID INT,
    UserID INT,
    ReportDate DATETIME,
    IssueType VARCHAR(100),
    QuantityAffected INT,
    PhotoPath VARCHAR(500),
    StatusType VARCHAR(50),
    Remarks TEXT,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. TransactionAuditLog Table
CREATE TABLE TransactionAuditLog (
    AuditLogID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    ReferenceType VARCHAR(100),
    ReferenceID INT,
    ActionType VARCHAR(100),
    ActionDate DATETIME,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. SecurityLog Table
CREATE TABLE SecurityLog (
    SecurityLogID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT, -- Nullable for failed logins
    ActionType VARCHAR(100),
    ActionDescription TEXT,
    IPAddress VARCHAR(50),
    ModuleAffected VARCHAR(100),
    ActionDate DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. ApprovalLog Table
CREATE TABLE ApprovalLog (
    ApprovalLogID INT AUTO_INCREMENT PRIMARY KEY,
    RequisitionID INT NOT NULL,
    UserID INT,
    Decision VARCHAR(50),
    DecisionDate DATETIME,
    FOREIGN KEY (RequisitionID) REFERENCES Requisition(RequisitionID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. Report Table
CREATE TABLE Report (
    ReportID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT,
    ReportType VARCHAR(100),
    GeneratedDate DATETIME,
    GeneratedForOffice VARCHAR(200),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. Notifications Table
CREATE TABLE Notifications (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    timestamp DATETIME NOT NULL,
    isRead BOOLEAN DEFAULT FALSE,
    type VARCHAR(50),
    targetRoles TEXT -- Comma separated roles or null for all
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. Patient Table
CREATE TABLE HCPatient (
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

-- 24. Patient Requisition Table
CREATE TABLE HCPatientRequisition (
    PatientReqID INT AUTO_INCREMENT PRIMARY KEY,
    PatientID INT NOT NULL,
    UserID INT NOT NULL, -- The HC Staff who created it
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

-- 25. Patient Requisition Item Table
CREATE TABLE HCPatientRequisitionItem (
    PRItemID INT AUTO_INCREMENT PRIMARY KEY,
    PatientReqID INT NOT NULL,
    ItemID INT NOT NULL,
    QuantityQuantityrequested INT NOT NULL,
    FOREIGN KEY (PatientReqID) REFERENCES HCPatientRequisition(PatientReqID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes
CREATE INDEX idx_po_status ON ProcurementOrder(StatusType);
CREATE INDEX idx_req_status ON Requisition(StatusType);
CREATE INDEX idx_inv_item ON CentralInventoryBatch(ItemID);
CREATE INDEX idx_patient_name ON HCPatient(LName, FName);
CREATE INDEX idx_patient_req_status ON HCPatientRequisition(StatusType);

