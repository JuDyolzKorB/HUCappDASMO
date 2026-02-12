-- Migration file to populate database with initial data from constants.ts
-- This file mirrors the data structures previously held in TypeScript constants

-- 1. Users
INSERT INTO Users (UserID, Username, FName, LName, Role, Password) VALUES
('U001', 'admin', 'Admin', 'User', 'Administrator', 'password'),
('U002', 'hstaff1', 'Health', 'Staff', 'Health Center Staff', 'password'),
('U007', 'hstaff2', 'Center', 'User', 'Health Center Staff', 'password'),
('U004', 'hpharm', 'Head', 'Pharmacist', 'Head Pharmacist', 'password'),
('U006', 'wstaff', 'Warehouse', 'Staff', 'Warehouse Staff', 'password'),
('U009', 'frank', 'Frank', 'Gold', 'Accounting Office User', 'password'),
('U010', 'grace', 'Grace', 'Silver', 'CMO/GSO/COA User', 'password')
ON DUPLICATE KEY UPDATE Username=Username;

-- 2. Items
INSERT INTO Item (ItemID, ItemName, ItemType, UnitOfMeasure) VALUES
('I001', 'Paracetamol 500mg', 'Analgesic', 'Tablet'),
('I002', 'Amoxicillin 250mg', 'Antibiotic', 'Capsule'),
('I003', 'Gauze Pads 4x4', 'Medical Supply', 'Pack'),
('I004', 'Salbutamol Nebule', 'Respiratory', 'Nebule'),
('I005', 'Losartan 50mg', 'Cardiovascular', 'Tablet'),
('I006', 'Antiseptic Solution 500ml', 'Antiseptic', 'Bottle')
ON DUPLICATE KEY UPDATE ItemName=ItemName;

-- 3. HealthCenters
INSERT INTO HealthCenters (HealthCenterID, Name, Address) VALUES
('HC01', 'Central Health Unit', '123 Health St, Central City'),
('HC02', 'North District Clinic', '456 North Ave, Northtown'),
('HC03', 'Southside Medical Center', '789 South Blvd, Southville')
ON DUPLICATE KEY UPDATE Name=Name;

-- 4. Warehouses
INSERT INTO Warehouse (WarehouseID, WarehouseName, Location, WarehouseType) VALUES
('W01', 'Main Warehouse', 'Central City', 'Central')
ON DUPLICATE KEY UPDATE WarehouseName=WarehouseName;

-- 5. Suppliers
INSERT INTO Supplier (SupplierID, Name, Address, ContactInfo) VALUES
('S01', 'MedSupply Inc.', '123 Pharma Lane', '555-1234'),
('S02', 'Global Health Distributors', '456 Wellness Ave', '555-5678')
ON DUPLICATE KEY UPDATE Name=Name;

-- 6. CentralInventoryBatch
INSERT INTO CentralInventoryBatch (BatchID, ItemID, ExpiryDate, QuantityOnHand, UnitCost, WarehouseID, QuantityReleased, DateReceived) VALUES
('B001', 'I001', '2025-12-31', 4500, 0.10, 'W01', 0, '2023-01-01'),
('B002', 'I001', '2025-06-30', 3000, 0.11, 'W01', 0, '2023-01-01'),
('B003', 'I002', '2026-02-28', 1200, 0.25, 'W01', 0, '2023-01-01'),
('B004', 'I003', '2027-01-31', 8000, 1.50, 'W01', 0, '2023-01-01'),
('B005', 'I004', '2025-05-31', 150, 2.10, 'W01', 0, '2023-01-01'),
('B006', 'I005', '2026-08-31', 2500, 0.50, 'W01', 0, '2023-01-01'),
('B007', 'I006', '2028-01-01', 500, 3.00, 'W01', 0, '2023-01-01'),
('B008', 'I002', '2026-09-30', 1500, 0.22, 'W01', 0, '2023-09-20')
ON DUPLICATE KEY UPDATE QuantityOnHand=QuantityOnHand;

-- 7. Purchase Orders
INSERT INTO PurchaseOrder (POID, UserID, SupplierID, HealthCenterID, PONumber, PODate, StatusType) VALUES
('PO001', 'U002', 'S01', 'HC01', 'PO-240001', '2023-10-10 09:00:00', 'Approved'),
('PO002', 'U007', 'S02', 'HC02', 'PO-240002', '2023-10-12 14:30:00', 'Pending'),
('PO003', 'U006', 'S01', NULL, 'PO-230003', '2023-09-15 11:00:00', 'Completed')
ON DUPLICATE KEY UPDATE StatusType=StatusType;

-- 8. Purchase Order Items
INSERT INTO PurchaseOrderItem (POItemID, POID, ItemID, QuantityOrdered) VALUES
('POI001', 'PO001', 'I001', 5000),
('POI002', 'PO001', 'I003', 2000),
('POI003', 'PO002', 'I005', 3000),
('POI004', 'PO003', 'I002', 1500)
ON DUPLICATE KEY UPDATE QuantityOrdered=QuantityOrdered;

-- 9. Requisitions
INSERT INTO Requisition (RequisitionID, HealthCenterID, UserID, RequestDate, StatusType) VALUES
('R00001', 'HC01', 'U002', '2023-10-01 10:00:00', 'Approved'),
('R00002', 'HC02', 'U002', '2023-10-05 09:00:00', 'Pending'),
('R00003', 'HC03', 'U007', '2023-10-06 11:30:00', 'Rejected')
ON DUPLICATE KEY UPDATE StatusType=StatusType;

-- 10. Requisition Items
INSERT INTO RequisitionItem (RequisitionItemID, RequisitionID, ItemID, QuantityRequested) VALUES
('RI001', 'R00001', 'I001', 1000),
('RI002', 'R00001', 'I003', 500),
('RI003', 'R00002', 'I002', 500),
('RI004', 'R00002', 'I004', 200),
('RI005', 'R00002', 'I006', 100),
('RI006', 'R00003', 'I005', 2000)
ON DUPLICATE KEY UPDATE QuantityRequested=QuantityRequested;

-- 11. Approval Logs
INSERT INTO ApprovalLog (ApprovalLogID, RequisitionID, UserID, Decision, DecisionDate) VALUES
('AL001', 'R00001', 'U004', 'Approved', '2023-10-02 14:00:00'),
('AL002', 'R00003', 'U001', 'Rejected', '2023-10-07 16:00:00')
ON DUPLICATE KEY UPDATE Decision=Decision;

-- 12. Receivings
INSERT INTO Receiving (ReceivingID, UserID, POID, ReceivedDate) VALUES
('REC-001', 'U006', 'PO003', '2023-09-20 10:00:00')
ON DUPLICATE KEY UPDATE ReceivedDate=ReceivedDate;

-- 13. Receiving Items
INSERT INTO ReceivingItem (ReceivingItemID, ReceivingID, BatchID, QuantityReceived) VALUES
('RI-B008', 'REC-001', 'B008', 1500)
ON DUPLICATE KEY UPDATE QuantityReceived=QuantityReceived;

-- 14. Notifications (Creating table as it was missing from schema.sql)
CREATE TABLE IF NOT EXISTS Notifications (
    NotificationID VARCHAR(50) PRIMARY KEY,
    Title VARCHAR(200),
    Message TEXT,
    Timestamp DATETIME,
    IsRead BOOLEAN DEFAULT FALSE,
    Type VARCHAR(50),
    TargetRoles JSON,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Notifications (NotificationID, Title, Message, Timestamp, IsRead, Type, TargetRoles) VALUES
('N001', 'System Update', 'The system will be undergoing scheduled maintenance tonight at 11 PM.', NOW(), 1, 'system', NULL),
('N002', 'Low Stock Alert', 'Item "Paracetamol 500mg" is running low.', NOW() - INTERVAL 30 MINUTE, 1, 'alert', '["Administrator", "Warehouse Staff", "Head Pharmacist"]'),
('N003', 'PO Approved', 'Purchase Order PO-240001 has been approved.', NOW() - INTERVAL 120 MINUTE, 0, 'po', '["Warehouse Staff", "Accounting Office User"]')
ON DUPLICATE KEY UPDATE Title=Title;
