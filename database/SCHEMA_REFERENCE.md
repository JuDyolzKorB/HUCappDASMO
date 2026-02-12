# Final Database Schema - HUCappDASMO
Updated: 2026-02-09

## Overview
Complete MySQL database schema with 24 tables for the Health Unit Central Warehouse Application.

**Database Name:** `hucappdb`  
**Server:** `127.0.0.1`  
**Tables:** 24

---

## Table Structure

### 1. Users
System users with authentication
- **UserID** (PK)
- FName, MName, LName
- Role
- Username (Unique)
- Password (Hashed)
- CreatedAt, UpdatedAt

### 2. SecurityLog
Security event logging
- **SecurityLogID** (PK)
- UserID (FK → Users)
- ActionType
- ActionDescription
- IPAddress
- ModuleAffected
- ActionDate

### 3. TransactionAuditLog
Transaction audit trail
- **AuditLogID** (PK)
- UserID (FK → Users)
- ReferenceType
- ReferenceID
- ActionType
- ActionDate

### 4. HealthCenters
Health center locations
- **HealthCenterID** (PK)
- Name
- Address
- CreatedAt

### 5. Warehouse
Warehouse locations
- **WarehouseID** (PK)
- WarehouseName
- Location
- WarehouseType

### 6. Supplier
Supplier information
- **SupplierID** (PK)
- Name
- Address
- ContactInfo

### 7. Item
Inventory items
- **ItemID** (PK)
- ItemName
- ItemType
- UnitOfMeasure

### 8. DPRI
Drug and Pharmaceutical Resource Information (Reference Prices)
- **DPRIID** (PK)
- ItemID (FK → Item)
- ReferencePrice
- EffectiveDate
- Source

### 9. CentralInventoryBatch
Inventory batches with tracking
- **BatchID** (PK)
- ItemID (FK → Item)
- WarehouseID (FK → Warehouse)
- ExpiryDate
- QuantityOnHand
- QuantityReleased
- UnitCost

### 10. InventoryAdjustment
Track manual inventory quantity changes
- **AdjustmentID** (PK)
- BatchID (FK → CentralInventoryBatch)
- UserID (FK → Users)
- AdjustmentQuantity
- Reason
- AdjustmentDate

### 11. NoticeOfIssue
Issue reporting (Damage/Loss/Quality)
- **IssueID** (PK)
- BatchID (FK → CentralInventoryBatch)
- UserID (FK → Users)
- ReportDate
- IssueType
- QuantityAffected
- PhotoPath
- StatusType
- Remarks

### 12. Requisition
Requisition headers
- **RequisitionID** (PK)
- HealthCenterID (FK → HealthCenters)
- UserID (FK → Users)
- RequestDate
- StatusType

### 13. RequisitionItem
Requisition line items
- **RequisitionItemID** (PK)
- RequisitionID (FK → Requisition)
- ItemID (FK → Item)
- QuantityRequested

### 14. ApprovalLog
Approval workflow tracking
- **ApprovalLogID** (PK)
- RequisitionID (FK → Requisition)
- UserID (FK → Users)
- Decision
- DecisionDate

### 15. Issuance
Issuance records
- **IssuanceID** (PK)
- RequisitionID (FK → Requisition)
- UserID (FK → Users)
- IssueDate
- StatusType

### 16. IssuanceItem
Issued item details
- **IssuanceItemID** (PK)
- IssuanceID (FK → Issuance)
- BatchID (FK → CentralInventoryBatch)
- RequisitionItemID (FK → RequisitionItem)
- QuantityIssued

### 17. RequisitionAdjustment
Adjustment headers for issued items
- **RequisitionAdjustmentID** (PK)
- IssuanceID (FK → Issuance)
- UserID (FK → Users)
- AdjustmentType
- AdjustmentDate
- Reason

### 18. RequisitionAdjustmentDetail
Adjustment details for issued items
- **RAD** (PK)
- RequisitionAdjustmentID (FK → RequisitionAdjustment)
- BatchID (FK → CentralInventoryBatch)
- QuantityAdjusted

### 19. Contract
Supplier contracts
- **ContractID** (PK)
- SupplierID (FK → Supplier)
- ContractNumber
- StartDate, EndDate
- ContractAmount
- StatusType

### 20. ProcurementOrder
Procurement order headers (Renamed from PurchaseOrder)
- **POID** (PK)
- UserID (FK → Users)
- SupplierID (FK → Supplier)
- HealthCenterID (FK → HealthCenters)
- ContractID (FK → Contract)
- DocumentType
- PONumber (Unique)
- PODate
- StatusType

### 21. ProcurementOrderItem
Procurement order line items (Renamed from PurchaseOrderItem)
- **POItemID** (PK)
- POID (FK → ProcurementOrder)
- ItemID (FK → Item)
- QuantityOrdered
- UnitCost

### 22. Receiving
Receiving records
- **ReceivingID** (PK)
- UserID (FK → Users)
- POID (FK → ProcurementOrder)
- ReceivedDate

### 23. ReceivingItem
Received item details
- **ReceivingItemID** (PK)
- ReceivingID (FK → Receiving)
- BatchID (FK → CentralInventoryBatch)
- QuantityReceived

### 24. Report
Generated reports
- **ReportID** (PK)
- UserID (FK → Users)
- ReportType
- GeneratedDate
- GeneratedForOffice

---

## Key Changes in 2026-02-09 Update

1. **Table Renames**: `PurchaseOrder` -> `ProcurementOrder`, `PurchaseOrderItem` -> `ProcurementOrderItem`.
2. **RAD**: Renamed `RADID` to `RAD` in `RequisitionAdjustmentDetail`.
3. **HealthCenterID in PO**: Added `HealthCenterID` to `ProcurementOrder`.
4. **New Tables**: `DPRI`, `Contract`, `InventoryAdjustment`.
5. **Relationships**: Added `ContractID` and `DocumentType` to `ProcurementOrder`.
