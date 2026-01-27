
import { Item, HealthCenter, Requisition, CentralInventoryBatch, Issuance, IssuanceItem, Warehouse, Supplier, PurchaseOrder, Receiving, ReceivingItem, NoticeOfIssuance, RequisitionAdjustment, RequisitionAdjustmentDetail, Report, User, SecurityLog, TransactionAuditLog, Notification } from './types';

export const initialUsers: User[] = [
  { UserID: 'U001', Username: 'admin', FirstName: 'Admin', LastName: 'User', Role: 'Administrator', Password: 'password' },
  { UserID: 'U002', Username: 'hstaff1', FirstName: 'Health', LastName: 'Staff', Role: 'Health Center Staff', Password: 'password' },
  { UserID: 'U003', Username: 'hstaff2', FirstName: 'Center', LastName: 'User', Role: 'Health Center Staff', Password: 'password' },
  { UserID: 'U004', Username: 'hpharm', FirstName: 'Head', LastName: 'Pharmacist', Role: 'Head Pharmacist', Password: 'password' },
  { UserID: 'U006', Username: 'wstaff', FirstName: 'Warehouse', LastName: 'Staff', Role: 'Warehouse Staff', Password: 'password' },
  { UserID: 'U009', Username: 'frank', FirstName: 'Frank', LastName: 'Gold', Role: 'Accounting Office User', Password: 'password' },
  { UserID: 'U010', Username: 'grace', FirstName: 'Grace', LastName: 'Silver', Role: 'CMO/GSO/COA User', Password: 'password' },
];

export const initialItems: Item[] = [
  { ItemID: 'I001', ItemName: 'Paracetamol 500mg', ItemType: 'Analgesic', UnitOfMeasure: 'Tablet' },
  { ItemID: 'I002', ItemName: 'Amoxicillin 250mg', ItemType: 'Antibiotic', UnitOfMeasure: 'Capsule' },
  { ItemID: 'I003', ItemName: 'Gauze Pads 4x4', ItemType: 'Medical Supply', UnitOfMeasure: 'Pack' },
  { ItemID: 'I004', ItemName: 'Salbutamol Nebule', ItemType: 'Respiratory', UnitOfMeasure: 'Nebule' },
  { ItemID: 'I005', ItemName: 'Losartan 50mg', ItemType: 'Cardiovascular', UnitOfMeasure: 'Tablet' },
  { ItemID: 'I006', ItemName: 'Antiseptic Solution 500ml', ItemType: 'Antiseptic', UnitOfMeasure: 'Bottle' },
];

export const healthCenters: HealthCenter[] = [
    { HealthCenterID: 'HC01', Name: 'Central Health Unit', Address: '123 Health St, Central City' },
    { HealthCenterID: 'HC02', Name: 'North District Clinic', Address: '456 North Ave, Northtown' },
    { HealthCenterID: 'HC03', Name: 'Southside Medical Center', Address: '789 South Blvd, Southville' },
];

export const initialWarehouses: Warehouse[] = [
    { WarehouseID: 'W01', WarehouseName: 'Main Warehouse', Location: 'Central City', WarehouseType: 'Central' },
];

export const initialSuppliers: Supplier[] = [
    { SupplierID: 'S01', Name: 'MedSupply Inc.', Address: '123 Pharma Lane', ContactInfo: '555-1234' },
    { SupplierID: 'S02', Name: 'Global Health Distributors', Address: '456 Wellness Ave', ContactInfo: '555-5678' },
];

export const initialRequisitions: Requisition[] = [
  {
    RequisitionID: 'R00001',
    RequisitionNumber: 'REQ-240001',
    HealthCenterID: 'HC01',
    HealthCenterName: 'Central Health Unit',
    UserID: 'U002',
    RequestedByFullName: 'Health Staff',
    RequestedDate: '2023-10-01T10:00:00Z',
    StatusType: 'Approved',
    RequisitionItems: [
      { RequisitionItemID: 'RI001', RequisitionID: 'R00001', ItemID: 'I001', QuantityRequested: 1000 },
      { RequisitionItemID: 'RI002', RequisitionID: 'R00001', ItemID: 'I003', QuantityRequested: 500 },
    ],
    ApprovalLogs: [{ ApprovalLogID: 'AL001', RequisitionID: 'R00001', UserID: 'U004', ApproverFullName: 'Head Pharmacist', DecisionDate: '2023-10-02T14:00:00Z', Decision: 'Approved' }]
  },
  {
    RequisitionID: 'R00002',
    RequisitionNumber: 'REQ-240002',
    HealthCenterID: 'HC02',
    HealthCenterName: 'North District Clinic',
    UserID: 'U002',
    RequestedByFullName: 'Health Staff',
    RequestedDate: '2023-10-05T09:00:00Z',
    StatusType: 'Pending',
    RequisitionItems: [
      { RequisitionItemID: 'RI003', RequisitionID: 'R00002', ItemID: 'I002', QuantityRequested: 500 },
      { RequisitionItemID: 'RI004', RequisitionID: 'R00002', ItemID: 'I004', QuantityRequested: 200 },
      { RequisitionItemID: 'RI005', RequisitionID: 'R00002', ItemID: 'I006', QuantityRequested: 100 },
    ],
    ApprovalLogs: []
  },
  {
    RequisitionID: 'R00003',
    RequisitionNumber: 'REQ-240003',
    HealthCenterID: 'HC03',
    HealthCenterName: 'Southside Medical Center',
    UserID: 'U003',
    RequestedByFullName: 'Center User',
    RequestedDate: '2023-10-06T11:30:00Z',
    StatusType: 'Rejected',
    RequisitionItems: [
      { RequisitionItemID: 'RI006', RequisitionID: 'R00003', ItemID: 'I005', QuantityRequested: 2000 },
    ],
    ApprovalLogs: [{ ApprovalLogID: 'AL002', RequisitionID: 'R00003', UserID: 'U001', ApproverFullName: 'Admin User', DecisionDate: '2023-10-07T16:00:00Z', Decision: 'Rejected' }]
  },
];

export const initialPurchaseOrders: PurchaseOrder[] = [
    {
        POID: 'PO001',
        UserID: 'U002',
        SupplierID: 'S01',
        HealthCenterID: 'HC01',
        PONumber: 'PO-240001',
        PODate: '2023-10-10T09:00:00Z',
        StatusType: 'Approved',
        SupplierName: 'MedSupply Inc.',
        HealthCenterName: 'Central Health Unit',
        PurchaseOrderItems: [
            { POItemID: 'POI001', POID: 'PO001', ItemID: 'I001', QuantityOrdered: 5000 },
            { POItemID: 'POI002', POID: 'PO001', ItemID: 'I003', QuantityOrdered: 2000 },
        ],
        ApprovalLogs: [{ ApprovalLogID: 'POAL001', POID: 'PO001', UserID: 'U004', ApproverFullName: 'Head Pharmacist', DecisionDate: '2023-10-11T10:00:00Z', Decision: 'Approved' }]
    },
    {
        POID: 'PO002',
        UserID: 'U003',
        SupplierID: 'S02',
        HealthCenterID: 'HC02',
        PONumber: 'PO-240002',
        PODate: '2023-10-12T14:30:00Z',
        StatusType: 'Pending',
        SupplierName: 'Global Health Distributors',
        HealthCenterName: 'North District Clinic',
        PurchaseOrderItems: [
            { POItemID: 'POI003', POID: 'PO002', ItemID: 'I005', QuantityOrdered: 3000 },
        ],
        ApprovalLogs: [],
    },
    {
        POID: 'PO003',
        UserID: 'U006',
        SupplierID: 'S01',
        PONumber: 'PO-230003',
        PODate: '2023-09-15T11:00:00Z',
        StatusType: 'Completed',
        SupplierName: 'MedSupply Inc.',
        PurchaseOrderItems: [
            { POItemID: 'POI004', POID: 'PO003', ItemID: 'I002', QuantityOrdered: 1500 },
        ],
        ApprovalLogs: [{ ApprovalLogID: 'POAL002', POID: 'PO003', UserID: 'U004', ApproverFullName: 'Head Pharmacist', DecisionDate: '2023-09-16T10:00:00Z', Decision: 'Approved' }]
    }
];

export const initialInventory: CentralInventoryBatch[] = [
  { BatchID: 'B001', ItemID: 'I001', ExpiryDate: '2025-12-31', QuantityOnHand: 4500, UnitCost: 0.10, WarehouseID: 'W01' },
  { BatchID: 'B002', ItemID: 'I001', ExpiryDate: '2025-06-30', QuantityOnHand: 3000, UnitCost: 0.11, WarehouseID: 'W01' },
  { BatchID: 'B003', ItemID: 'I002', ExpiryDate: '2026-02-28', QuantityOnHand: 1200, UnitCost: 0.25, WarehouseID: 'W01' },
  { BatchID: 'B004', ItemID: 'I003', ExpiryDate: '2027-01-31', QuantityOnHand: 8000, UnitCost: 1.50, WarehouseID: 'W01' },
  { BatchID: 'B005', ItemID: 'I004', ExpiryDate: '2025-05-31', QuantityOnHand: 150, UnitCost: 2.10, WarehouseID: 'W01' },
  { BatchID: 'B006', ItemID: 'I005', ExpiryDate: '2026-08-31', QuantityOnHand: 2500, UnitCost: 0.50, WarehouseID: 'W01' },
  { BatchID: 'B007', ItemID: 'I006', ExpiryDate: '2028-01-01', QuantityOnHand: 500, UnitCost: 3.00, WarehouseID: 'W01' },
  { BatchID: 'B008', ItemID: 'I002', ExpiryDate: '2026-09-30', QuantityOnHand: 1500, UnitCost: 0.22, WarehouseID: 'W01' },
];

export const initialIssuances: Issuance[] = [];
export const initialIssuanceItems: IssuanceItem[] = [];
export const initialReceivings: Receiving[] = [
    { ReceivingID: 'REC-001', UserID: 'U006', POID: 'PO003', ReceivedDate: '2023-09-20T10:00:00Z'}
];
export const initialReceivingItems: ReceivingItem[] = [
    { ReceivingItemID: 'RI-B008', ReceivingID: 'REC-001', BatchID: 'B008', QuantityReceived: 1500 }
];
export const initialNoticesOfIssuance: NoticeOfIssuance[] = [];
export const initialRequisitionAdjustments: RequisitionAdjustment[] = [];
export const initialRequisitionAdjustmentDetails: RequisitionAdjustmentDetail[] = [];
export const initialReports: Report[] = [];
export const initialSecurityLogs: SecurityLog[] = [];
export const initialTransactionAuditLogs: TransactionAuditLog[] = [];

export const initialNotifications: Notification[] = [
    { id: 'N001', title: 'System Update', message: 'The system will be undergoing scheduled maintenance tonight at 11 PM.', timestamp: new Date(Date.now() - 1000 * 60 * 5).toISOString(), isRead: true, type: 'system' }, // No targetRoles means it's global
    { id: 'N002', title: 'Low Stock Alert', message: 'Item "Paracetamol 500mg" is running low.', timestamp: new Date(Date.now() - 1000 * 60 * 30).toISOString(), isRead: true, type: 'alert', targetRoles: ['Administrator', 'Warehouse Staff', 'Head Pharmacist'] },
    { id: 'N003', title: 'PO Approved', message: 'Purchase Order PO-240001 has been approved.', timestamp: new Date(Date.now() - 1000 * 60 * 120).toISOString(), isRead: false, type: 'po', targetRoles: ['Warehouse Staff', 'Accounting Office User'] },
];
