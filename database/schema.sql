-- HUCappDASMO Database Schema
-- Database: hucappdb
-- Final Schema with 21 Tables

-- Drop tables if they exist (in reverse order of dependencies)
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
DROP TABLE IF EXISTS Warehouse;
DROP TABLE IF EXISTS Item;
DROP TABLE IF EXISTS Supplier;
DROP TABLE IF EXISTS HealthCenters;
DROP TABLE IF EXISTS Users;

-- 1. User Table (named Users to avoid MySQL reserved keyword)
CREATE TABLE Users (
    UserID VARCHAR(50) PRIMARY KEY,
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
    HealthCenterID VARCHAR(50) PRIMARY KEY,
    Name VARCHAR(200) NOT NULL,
    Address TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Supplier Table
CREATE TABLE Supplier (
    SupplierID VARCHAR(50) PRIMARY KEY,
    Name VARCHAR(200) NOT NULL,
    Address TEXT,
    ContactInfo VARCHAR(200),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Item Table
CREATE TABLE Item (
    ItemID VARCHAR(50) PRIMARY KEY,
    ItemName VARCHAR(200) NOT NULL,
    ItemType VARCHAR(50),
    UnitOfMeasure VARCHAR(50),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Warehouse Table
CREATE TABLE Warehouse (
    WarehouseID VARCHAR(50) PRIMARY KEY,
    WarehouseName VARCHAR(200) NOT NULL,
    Location TEXT,
    WarehouseType VARCHAR(100),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CentralInventoryBatch Table
CREATE TABLE CentralInventoryBatch (
    BatchID VARCHAR(50) PRIMARY KEY,
    ItemID VARCHAR(50) NOT NULL,
    WarehouseID VARCHAR(50),
    ExpiryDate DATE,
    QuantityOnHand INT NOT NULL DEFAULT 0,
    QuantityReleased INT DEFAULT 0,
    UnitCost DECIMAL(10, 2),
    DateReceived DATE,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE,
    FOREIGN KEY (WarehouseID) REFERENCES Warehouse(WarehouseID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. PurchaseOrder Table
CREATE TABLE PurchaseOrder (
    POID VARCHAR(50) PRIMARY KEY,
    UserID VARCHAR(50),
    SupplierID VARCHAR(50),
    HealthCenterID VARCHAR(50),
    PONumber VARCHAR(100) UNIQUE NOT NULL,
    PODate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL,
    FOREIGN KEY (SupplierID) REFERENCES Supplier(SupplierID) ON DELETE SET NULL,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. PurchaseOrderItem Table
CREATE TABLE PurchaseOrderItem (
    POItemID VARCHAR(50) PRIMARY KEY,
    POID VARCHAR(50) NOT NULL,
    ItemID VARCHAR(50) NOT NULL,
    QuantityOrdered INT NOT NULL,
    UnitCost DECIMAL(10, 2),
    FOREIGN KEY (POID) REFERENCES PurchaseOrder(POID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Receiving Table
CREATE TABLE Receiving (
    ReceivingID VARCHAR(50) PRIMARY KEY,
    UserID VARCHAR(50),
    POID VARCHAR(50),
    ReceivedDate DATETIME NOT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL,
    FOREIGN KEY (POID) REFERENCES PurchaseOrder(POID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. ReceivingItem Table
CREATE TABLE ReceivingItem (
    ReceivingItemID VARCHAR(50) PRIMARY KEY,
    ReceivingID VARCHAR(50) NOT NULL,
    BatchID VARCHAR(50) NOT NULL,
    QuantityReceived INT NOT NULL,
    FOREIGN KEY (ReceivingID) REFERENCES Receiving(ReceivingID) ON DELETE CASCADE,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Requisition Table
CREATE TABLE Requisition (
    RequisitionID VARCHAR(50) PRIMARY KEY,
    HealthCenterID VARCHAR(50),
    UserID VARCHAR(50),
    RequestDate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Pending',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (HealthCenterID) REFERENCES HealthCenters(HealthCenterID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. RequisitionItem Table
CREATE TABLE RequisitionItem (
    RequisitionItemID VARCHAR(50) PRIMARY KEY,
    RequisitionID VARCHAR(50) NOT NULL,
    ItemID VARCHAR(50) NOT NULL,
    QuantityRequested INT NOT NULL,
    FOREIGN KEY (RequisitionID) REFERENCES Requisition(RequisitionID) ON DELETE CASCADE,
    FOREIGN KEY (ItemID) REFERENCES Item(ItemID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Issuance Table
CREATE TABLE Issuance (
    IssuanceID VARCHAR(50) PRIMARY KEY,
    RequisitionID VARCHAR(50),
    UserID VARCHAR(50),
    IssueDate DATETIME NOT NULL,
    StatusType VARCHAR(50) DEFAULT 'Issued',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (RequisitionID) REFERENCES Requisition(RequisitionID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. IssuanceItem Table
CREATE TABLE IssuanceItem (
    IssuanceItemID VARCHAR(50) PRIMARY KEY,
    IssuanceID VARCHAR(50) NOT NULL,
    BatchID VARCHAR(50) NOT NULL,
    RequisitionItemID VARCHAR(50),
    QuantityIssued INT NOT NULL,
    FOREIGN KEY (IssuanceID) REFERENCES Issuance(IssuanceID) ON DELETE CASCADE,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE CASCADE,
    FOREIGN KEY (RequisitionItemID) REFERENCES RequisitionItem(RequisitionItemID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. RequisitionAdjustment Table
CREATE TABLE RequisitionAdjustment (
    RequisitionAdjustmentID VARCHAR(50) PRIMARY KEY,
    IssuanceID VARCHAR(50),
    UserID VARCHAR(50),
    AdjustmentType VARCHAR(100),
    AdjustmentDate DATETIME,
    Reason TEXT,
    FOREIGN KEY (IssuanceID) REFERENCES Issuance(IssuanceID) ON DELETE SET NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. RequisitionAdjustmentDetail Table
CREATE TABLE RequisitionAdjustmentDetail (
    RADID VARCHAR(50) PRIMARY KEY,
    RequisitionAdjustmentID VARCHAR(50) NOT NULL,
    BatchID VARCHAR(50),
    QuantityAdjusted INT,
    FOREIGN KEY (RequisitionAdjustmentID) REFERENCES RequisitionAdjustment(RequisitionAdjustmentID) ON DELETE CASCADE,
    FOREIGN KEY (BatchID) REFERENCES CentralInventoryBatch(BatchID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. NoticeOfIssue Table
CREATE TABLE NoticeOfIssue (
    IssueID VARCHAR(50) PRIMARY KEY,
    BatchID VARCHAR(50),
    UserID VARCHAR(50),
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
    AuditLogID VARCHAR(50) PRIMARY KEY,
    UserID VARCHAR(50),
    ReferenceType VARCHAR(100),
    ActionType VARCHAR(100),
    ActionDate DATETIME,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. SecurityLog Table
CREATE TABLE SecurityLog (
    SecurityLogID VARCHAR(50) PRIMARY KEY,
    UserID VARCHAR(50),
    ActionType VARCHAR(100),
    ActionDescription TEXT,
    IPAddress VARCHAR(50),
    ModuleAffected VARCHAR(100),
    ActionDate DATETIME,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. ApprovalLog Table
CREATE TABLE ApprovalLog (
    ApprovalLogID VARCHAR(50) PRIMARY KEY,
    RequisitionID VARCHAR(50) NOT NULL,
    UserID VARCHAR(50),
    Decision VARCHAR(50),
    DecisionDate DATETIME,
    FOREIGN KEY (RequisitionID) REFERENCES Requisition(RequisitionID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. Report Table
CREATE TABLE Report (
    ReportID VARCHAR(50) PRIMARY KEY,
    UserID VARCHAR(50),
    ReportType VARCHAR(100),
    GeneratedDate DATETIME,
    GeneratedForOffice VARCHAR(200),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create indexes for better performance
CREATE INDEX idx_po_status ON PurchaseOrder(StatusType);
CREATE INDEX idx_po_date ON PurchaseOrder(PODate);
CREATE INDEX idx_requisition_status ON Requisition(StatusType);
CREATE INDEX idx_requisition_date ON Requisition(RequestDate);
CREATE INDEX idx_inventory_item ON CentralInventoryBatch(ItemID);
CREATE INDEX idx_inventory_expiry ON CentralInventoryBatch(ExpiryDate);
CREATE INDEX idx_user_username ON Users(Username);
CREATE INDEX idx_security_log_date ON SecurityLog(ActionDate);
CREATE INDEX idx_audit_log_date ON TransactionAuditLog(ActionDate);
CREATE INDEX idx_report_date ON Report(GeneratedDate);
