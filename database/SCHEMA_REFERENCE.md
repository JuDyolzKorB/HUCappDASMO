# Final Database Schema - HUCappDASMO

## Overview
Complete MySQL database schema with 21 tables for the Health Unit Central Warehouse Application.

**Database Name:** `hucappdb`  
**Server:** `127.0.0.1`  
**Tables:** 21

---

## Table Structure

### 1. Users (User)
System users with authentication
- **UserID** (PK)
- FName, MName, LName
- Role
- Username (Unique)
- Password (Hashed)

### 2. HealthCenters
Health center locations
- **HealthCenterID** (PK)
- Name
- Address

### 3. Supplier
Supplier information
- **SupplierID** (PK)
- Name
- Address
- ContactInfo

### 4. Item
Inventory items (ItemType stored as VARCHAR, no separate table)
- **ItemID** (PK)
- ItemName
- ItemType
- UnitOfMeasure

### 5. Warehouse
Warehouse locations
- **WarehouseID** (PK)
- WarehouseName
- Location
- WarehouseType

### 6. CentralInventoryBatch
Inventory batches with FIFO tracking
- **BatchID** (PK)
- ItemID (FK → Item)
- WarehouseID (FK → Warehouse)
- ExpiryDate
- QuantityOnHand
- QuantityReleased
- UnitCost
- DateReceived

### 7. PurchaseOrder
Purchase order headers
- **POID** (PK)
- UserID (FK → Users)
- SupplierID (FK → Supplier)
- HealthCenterID (FK → HealthCenters)
- PONumber (Unique)
- PODate
- StatusType

### 8. PurchaseOrderItem
Purchase order line items
- **POItemID** (PK)
- POID (FK → PurchaseOrder)
- ItemID (FK → Item)
- QuantityOrdered
- UnitCost

### 9. Receiving
Receiving records
- **ReceivingID** (PK)
- UserID (FK → Users)
- POID (FK → PurchaseOrder)
- ReceivedDate

### 10. ReceivingItem
Received item details
- **ReceivingItemID** (PK)
- ReceivingID (FK → Receiving)
- BatchID (FK → CentralInventoryBatch)
- QuantityReceived

### 11. Requisition
Requisition headers
- **RequisitionID** (PK)
- HealthCenterID (FK → HealthCenters)
- UserID (FK → Users)
- RequestDate
- StatusType

### 12. RequisitionItem
Requisition line items
- **RequisitionItemID** (PK)
- RequisitionID (FK → Requisition)
- ItemID (FK → Item)
- QuantityRequested

### 13. Issuance
Issuance records
- **IssuanceID** (PK)
- RequisitionID (FK → Requisition)
- UserID (FK → Users)
- IssueDate
- StatusType

### 14. IssuanceItem
Issued item details
- **IssuanceItemID** (PK)
- IssuanceID (FK → Issuance)
- BatchID (FK → CentralInventoryBatch)
- RequisitionItemID (FK → RequisitionItem)
- QuantityIssued

### 15. RequisitionAdjustment
Adjustment records
- **RequisitionAdjustmentID** (PK)
- IssuanceID (FK → Issuance)
- UserID (FK → Users)
- AdjustmentType
- AdjustmentDate
- Reason

### 16. RequisitionAdjustmentDetail
Adjustment details
- **RADID** (PK) - Changed from RAD
- RequisitionAdjustmentID (FK → RequisitionAdjustment)
- BatchID (FK → CentralInventoryBatch)
- QuantityAdjusted

### 17. NoticeOfIssue
Issue reporting
- **IssueID** (PK)
- BatchID (FK → CentralInventoryBatch)
- UserID (FK → Users)
- ReportDate
- IssueType
- QuantityAffected
- PhotoPath
- StatusType
- Remarks

### 18. TransactionAuditLog
Transaction audit trail
- **AuditLogID** (PK)
- UserID (FK → Users)
- ReferenceType
- ActionType
- ActionDate

### 19. SecurityLog
Security event logging
- **SecurityLogID** (PK)
- UserID (FK → Users)
- ActionType
- ActionDescription
- IPAddress
- ModuleAffected
- ActionDate

### 20. ApprovalLog
Approval workflow tracking
- **ApprovalLogID** (PK)
- RequisitionID (FK → Requisition)
- UserID (FK → Users)
- Decision
- DecisionDate

### 21. Report
Generated reports
- **ReportID** (PK)
- UserID (FK → Users)
- ReportType
- GeneratedDate
- GeneratedForOffice

---

## Key Changes from Original Design

1. **Users Table**: Named `Users` instead of `User` to avoid MySQL reserved keyword conflicts
2. **ItemType**: Removed separate table, now stored as VARCHAR in Item table
3. **RADID**: Changed from `RAD` to `RADID` for clarity
4. **Report Table**: Added as table #21 for report generation tracking

## Indexes

Performance indexes created on:
- PurchaseOrder: StatusType, PODate
- Requisition: StatusType, RequestDate
- CentralInventoryBatch: ItemID, ExpiryDate
- Users: Username
- SecurityLog: ActionDate
- TransactionAuditLog: ActionDate
- Report: GeneratedDate

## Foreign Key Relationships

All foreign keys use:
- `ON DELETE CASCADE` for child records (e.g., OrderItems, RequisitionItems)
- `ON DELETE SET NULL` for optional references (e.g., UserID in logs)

This ensures data integrity while allowing flexible record management.
