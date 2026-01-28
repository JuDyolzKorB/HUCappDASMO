
export type Page = 'Dashboard' | 'Requisitions' | 'Purchase Orders' | 'Receiving' | 'Inventory' | 'Warehouse' | 'Issuance' | 'Adjustments' | 'Reports' | 'Settings' | 'Profile';
export type SettingsPage = 'Profile' | 'Security' | 'Notifications' | 'Appearance' | 'Activity';
export type Theme = 'Light' | 'Dark' | 'System';
export type ToastType = 'success' | 'danger' | 'warning' | 'info';

export interface ToastNotification {
  id: string;
  message: string;
  type: ToastType;
}


export type UserRole = 
  | 'Health Center Staff'
  | 'Administrator'
  | 'Head Pharmacist'
  | 'Accounting Office User'
  | 'Warehouse Staff'
  | 'CMO/GSO/COA User';

export interface User {
  UserID: string;
  Username: string;
  FirstName: string;
  MiddleName?: string;
  LastName: string;
  Role: UserRole;
  Password?: string; // For simulation purposes
}

export interface Item {
  ItemID: string;
  ItemName: string;
  ItemType: string; // Category
  UnitOfMeasure: string;
}

export interface HealthCenter {
    HealthCenterID: string;
    Name: string;
    Address: string;
}

export type RequisitionStatus = 'Pending' | 'Approved' | 'Rejected' | 'Processed';
export type POStatus = 'Pending' | 'Approved' | 'Rejected' | 'Completed';
// FIX: Added 'Completed' to allow for a final status for inventory write-offs, aligning with similar workflows.
export type NoticeOfIssuanceStatus = 'Pending' | 'Approved' | 'Rejected' | 'Completed';


export interface RequisitionItem {
  RequisitionItemID: string;
  RequisitionID: string;
  ItemID: string;
  QuantityRequested: number;
}

export interface ApprovalLog {
  ApprovalLogID: string;
  RequisitionID: string;
  UserID: string;
  Decision: 'Approved' | 'Rejected';
  DecisionDate: string;
  ApproverFullName: string; // Denormalized for display
}

export interface Requisition {
  RequisitionID: string;
  RequisitionNumber: string; // Denormalized for display
  HealthCenterID: string;
  HealthCenterName: string; // Denormalized for display
  UserID: string;
  RequestedByFullName: string; // Denormalized for display
  RequestedDate: string;
  StatusType: RequisitionStatus;
  RequisitionItems: RequisitionItem[];
  ApprovalLogs: ApprovalLog[];
}

export interface CentralInventoryBatch {
  BatchID: string;
  ItemID: string;
  WarehouseID: string;
  ExpiryDate: string;
  QuantityOnHand: number;
  QuantityReleased?: number;
  UnitCost: number;
}

export type IssuanceStatus = 'Completed' | 'Partial' | 'Pending';

export interface IssuanceItem {
  IssuanceItemID: string; 
  IssuanceID: string; 
  BatchID: string; 
  RequisitionItemID: string;
  QuantityIssued: number;
}

export interface Issuance {
  IssuanceID: string;
  RequisitionID: string;
  UserID: string;
  IssuedByFullName: string; // Denormalized
  IssuedDate: string;
  StatusType: IssuanceStatus;
}

export interface Warehouse {
    WarehouseID: string;
    WarehouseName: string;
    Location: string;
    WarehouseType?: string;
}

export interface Supplier {
    SupplierID: string;
    Name: string;
    Address: string;
    ContactInfo: string;
}

export interface PurchaseOrderItem {
    POItemID: string;
    POID: string;
    ItemID: string;
    QuantityOrdered: number;
}

export interface POApprovalLog {
  ApprovalLogID: string;
  POID: string;
  UserID: string;
  Decision: 'Approved' | 'Rejected';
  DecisionDate: string;
  ApproverFullName: string;
}

export interface PurchaseOrder {
    POID: string;
    UserID: string;
    SupplierID: string;
    HealthCenterID?: string;
    PONumber: string;
    PODate: string;
    StatusType: POStatus;
    PurchaseOrderItems: PurchaseOrderItem[];
    ApprovalLogs: POApprovalLog[];
    // Denormalized for display
    SupplierName: string; 
    HealthCenterName?: string;
}


export interface Receiving {
    ReceivingID: string;
    UserID: string;
    POID: string;
    ReceivedDate: string;
}

export interface ReceivingItem {
    ReceivingItemID: string;
    ReceivingID: string;
    BatchID: string;
    QuantityReceived: number;
}

export type IssuedType = 'Damaged' | 'Expired' | 'Count Discrepancy' | 'Other';

export interface NoticeOfIssuance {
    IssueID: string;
    BatchID: string;
    UserID: string;
    IssuedDate: string;
    IssuedType: IssuedType;
    QuantityIssued: number;
    PhotoPath?: string;
    StatusType: NoticeOfIssuanceStatus;
    Remarks?: string;
}

export interface RequisitionAdjustment {
    RequisitionAdjustmentID: string;
    RequisitionID: string;
    UserID: string;
    AdjustmentType: 'Return to Stock' | 'Correction' | 'Other';
    AdjustmentDate: string;
    Reason: string;
}

export interface RequisitionAdjustmentDetail {
    RADetailID: string; 
    RequisitionAdjustmentID: string;
    BatchID: string;
    QuantityAdjusted: number; // Can be positive (return) or negative (correction)
}

export type ReportType = 'Inventory Valuation' | 'Stock Card & Ledger' | 'Receipt Confirmation';
export type OfficeType = 'Accounting' | 'COA' | 'GSO' | 'CMO';

export interface Report {
    ReportID: string;
    UserID: string;
    ReportType: ReportType;
    GeneratedDate: string;
    GeneratedForOffice: OfficeType;
    // Denormalized for display
    GeneratedByFullName: string;
    data: any; // Snapshot of the report data
}

// Audit and Security Logs
export type TransactionActionType = 
    | 'Create Requisition' | 'Approve Requisition' | 'Reject Requisition' | 'Adjust Requisition'
    | 'Create Purchase Order' | 'Approve Purchase Order' | 'Reject Purchase Order' | 'Receive PO Items' | 'Process Issuance'
    | 'Inventory Disposal' | 'Adjust Inventory (Requisition Return)'
    | 'Create Warehouse' | 'Generate Report';

export type ReferenceType = 'Requisition' | 'Purchase Order' | 'Issuance' | 'Receiving' | 'Adjustment' | 'Warehouse' | 'Report';

export interface TransactionAuditLog {
    AuditLogID: string;
    UserID: string;
    UserFullName: string; // Denormalized
    ActionType: TransactionActionType;
    ReferenceType: ReferenceType;
    ReferenceID: string; // e.g., RequisitionNumber, PONumber
    ActionDate: string;
}

export type SecurityActionType = 'User Login' | 'User Logout' | 'Failed Login';

export interface SecurityLog {
    SecurityLogID: string;
    UserID: string;
    UserFullName: string; // Denormalized
    ActionType: SecurityActionType;
    ActionDescription: string;
    IPAddress: string;
    MacAddress?: string;
    ActionDate: string;
}

export type NotificationType = 'requisition' | 'po' | 'inventory' | 'system' | 'alert';

export interface Notification {
  id: string;
  title: string;
  message: string;
  timestamp: string;
  isRead: boolean;
  type: NotificationType;
  targetRoles?: UserRole[];
}