
import React, { useState, useMemo, useEffect } from 'react';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import Dashboard from './pages/Dashboard';
import Requisitions from './pages/Requisitions';
import PurchaseOrdersPage from './pages/PurchaseOrders';
import ReceivingPage from './pages/Receiving';
import Inventory from './pages/Inventory';
import WarehousePage from './pages/Warehouse';
import AdjustmentsPage from './pages/Adjustments';
import Reports from './pages/Reports';
import Settings from './pages/Settings';
import SignIn from './pages/SignIn';
import SignUp from './pages/SignUp';
import IssuancePage from './pages/Issuance';
import Profile from './pages/Profile';
import { Page, User, Requisition, CentralInventoryBatch, UserRole, Issuance, IssuanceItem, RequisitionItem, PurchaseOrder, PurchaseOrderItem, Receiving, ReceivingItem, NoticeOfIssuance, RequisitionAdjustment, RequisitionAdjustmentDetail, Report, ReportType, OfficeType, SecurityLog, TransactionAuditLog, SecurityActionType, TransactionActionType, ReferenceType, Warehouse, Notification, NotificationType, Theme, ToastNotification, ToastType } from './types';
import { initialRequisitions, initialInventory, initialItems, initialIssuances, initialIssuanceItems, initialPurchaseOrders, initialReceivings, initialReceivingItems, initialNoticesOfIssuance, initialRequisitionAdjustments, initialRequisitionAdjustmentDetails, initialReports, initialSecurityLogs, initialTransactionAuditLogs, initialUsers, initialWarehouses, initialNotifications } from './constants';
import SignOutConfirmationModal from './components/modals/SignOutConfirmationModal';
import ToastContainer from './components/ToastContainer';

import HealthCenterDashboard from './pages/role-specific-dashboards/HealthCenterDashboard';
import WarehouseDashboard from './pages/role-specific-dashboards/WarehouseDashboard';
import AccountingDashboard from './pages/role-specific-dashboards/AccountingDashboard';
import ComplianceDashboard from './pages/role-specific-dashboards/ComplianceDashboard';

const adminAccessPages: Page[] = ['Dashboard', 'Requisitions', 'Purchase Orders', 'Receiving', 'Inventory', 'Warehouse', 'Issuance', 'Adjustments', 'Reports', 'Settings', 'Profile'];

const rolePermissions: Record<UserRole, Page[]> = {
    'Health Center Staff': ['Dashboard', 'Requisitions', 'Profile', 'Settings'],
    'Administrator': adminAccessPages,
    'Head Pharmacist': adminAccessPages,
    'Accounting Office User': ['Dashboard', 'Reports', 'Profile', 'Settings'],
    'Warehouse Staff': ['Dashboard', 'Receiving', 'Issuance', 'Inventory', 'Purchase Orders', 'Profile', 'Settings'],
    'CMO/GSO/COA User': ['Dashboard', 'Reports', 'Profile', 'Settings'],
};

const App: React.FC = () => {
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [authPage, setAuthPage] = useState<'signin' | 'signup'>('signin');
  const [currentPage, setCurrentPage] = useState<Page>('Dashboard');
  const [requisitions, setRequisitions] = useState<Requisition[]>(initialRequisitions);
  const [purchaseOrders, setPurchaseOrders] = useState<PurchaseOrder[]>(initialPurchaseOrders);
  const [inventory, setInventory] = useState<CentralInventoryBatch[]>(initialInventory);
  const [warehouses, setWarehouses] = useState<Warehouse[]>(initialWarehouses);
  const [issuances, setIssuances] = useState<Issuance[]>(initialIssuances);
  const [issuanceItems, setIssuanceItems] = useState<IssuanceItem[]>(initialIssuanceItems);
  const [receivings, setReceivings] = useState<Receiving[]>(initialReceivings);
  const [receivingItems, setReceivingItems] = useState<ReceivingItem[]>(initialReceivingItems);
  const [noticesOfIssuance, setNoticesOfIssuance] = useState<NoticeOfIssuance[]>(initialNoticesOfIssuance);
  const [requisitionAdjustments, setRequisitionAdjustments] = useState<RequisitionAdjustment[]>(initialRequisitionAdjustments);
  const [requisitionAdjustmentDetails, setRequisitionAdjustmentDetails] = useState<RequisitionAdjustmentDetail[]>(initialRequisitionAdjustmentDetails);
  const [reports, setReports] = useState<Report[]>(initialReports);
  const [securityLogs, setSecurityLogs] = useState<SecurityLog[]>(initialSecurityLogs);
  const [transactionAuditLogs, setTransactionAuditLogs] = useState<TransactionAuditLog[]>(initialTransactionAuditLogs);
  const [users, setUsers] = useState<User[]>(initialUsers);
  const [notifications, setNotifications] = useState<Notification[]>(initialNotifications);
  const [toasts, setToasts] = useState<ToastNotification[]>([]);
  const [adjustmentContext, setAdjustmentContext] = useState<{ itemId: string; batchId: string } | null>(null);
  const [isSignOutModalOpen, setIsSignOutModalOpen] = useState(false);
  const [theme, setTheme] = useState<Theme>('System');
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  useEffect(() => {
    const root = window.document.documentElement;
    const isDark = theme === 'Dark' || (theme === 'System' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    root.classList.toggle('dark', isDark);
  }, [theme]);
  
  const addToast = (message: string, type: ToastType = 'info') => {
    const id = `toast-${Date.now()}`;
    setToasts(prev => [...prev, { id, message, type }]);
  };

  const removeToast = (id: string) => {
    setToasts(prev => prev.filter(toast => toast.id !== id));
  };

  const addNotification = (newNotification: Omit<Notification, 'id' | 'timestamp' | 'isRead'>) => {
    const notification: Notification = {
        ...newNotification,
        id: `N-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
        timestamp: new Date().toISOString(),
        isRead: false,
    };
    setNotifications(prev => [notification, ...prev]);
  };

  const markNotificationsAsRead = () => {
    if (!currentUser) return;
    setNotifications(prev => prev.map(n => {
        const isTargeted = !n.targetRoles || n.targetRoles.includes(currentUser.Role);
        return isTargeted ? { ...n, isRead: true } : n;
    }));
  };

  const visibleNotifications = useMemo(() => {
    if (!currentUser) return [];

    const isAdminOrPharmacist = currentUser.Role === 'Administrator' || currentUser.Role === 'Head Pharmacist';

    if (isAdminOrPharmacist) {
      return notifications;
    }

    return notifications.filter(n => !n.targetRoles || n.targetRoles.includes(currentUser.Role));
  }, [notifications, currentUser]);


  const logSecurityEvent = (actionType: SecurityActionType, description: string, details: { user?: User; usernameAttempt?: string }) => {
    const newLog: SecurityLog = {
      SecurityLogID: `SEC-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      UserID: details.user?.UserID || 'N/A',
      UserFullName: details.user ? `${details.user.FirstName} ${details.user.LastName}` : (details.usernameAttempt || 'Unknown'),
      ActionType: actionType,
      ActionDescription: description,
      IPAddress: '192.168.1.1', // Simulated IP
      MacAddress: '00:00:0A:BB:28:FC', // Simulated MAC
      ActionDate: new Date().toISOString(),
    };
    setSecurityLogs(prev => [newLog, ...prev]);
  };

  const logTransaction = (actionType: TransactionActionType, referenceType: ReferenceType, referenceId: string) => {
    if (!currentUser) return;
    const newLog: TransactionAuditLog = {
      AuditLogID: `AUD-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      UserID: currentUser.UserID,
      UserFullName: `${currentUser.FirstName} ${currentUser.LastName}`,
      ActionType: actionType,
      ReferenceType: referenceType,
      ReferenceID: referenceId,
      ActionDate: new Date().toISOString(),
    };
    setTransactionAuditLogs(prev => [newLog, ...prev]);
  };

  const handleSignIn = (user: User) => {
    setToasts([]); // Clear previous toasts
    setCurrentUser(user);
    const accessiblePages = rolePermissions[user.Role];
    setCurrentPage(accessiblePages.includes('Dashboard') ? 'Dashboard' : accessiblePages[0]);
    logSecurityEvent('User Login', `User ${user.Username} logged in successfully.`, { user });
    addToast(`Welcome back, ${user.FirstName}!`, 'success');
    addNotification({ title: 'Login Successful', message: `Welcome back, ${user.FirstName}!`, type: 'system', targetRoles: [user.Role]});
  };
  
  const handleSignInFail = (username: string) => {
    logSecurityEvent('Failed Login', `Failed login attempt for username: ${username}.`, { usernameAttempt: username });
  };

  const handleSignUp = (newUser: Omit<User, 'UserID'>) => {
    setToasts([]); // Clear previous toasts
    const user: User = { ...newUser, UserID: `U-${Date.now()}`};
    setUsers(prev => [...prev, user]);
    setCurrentUser(user);
    const accessiblePages = rolePermissions[user.Role];
    setCurrentPage(accessiblePages.includes('Dashboard') ? 'Dashboard' : accessiblePages[0]);
    addToast(`Welcome, ${user.FirstName}! Your account has been created.`, 'success');
    logSecurityEvent('User Login', `New user ${user.Username} signed up and logged in.`, { user });
  };
  
  const handleSignOut = () => {
    if(currentUser) {
        logSecurityEvent('User Logout', `User ${currentUser.Username} logged out.`, { user: currentUser });
    }
    setCurrentUser(null);
    setAuthPage('signin');
    setTheme('System');
  };
  
  const handleOpenSignOutModal = () => setIsSignOutModalOpen(true);
  const handleCloseSignOutModal = () => setIsSignOutModalOpen(false);
  
  const handleConfirmSignOut = () => {
    handleSignOut();
    handleCloseSignOutModal();
    addToast('You have been signed out successfully.', 'success');
  };

  const handleUpdateProfile = (userId: string, updates: { FirstName: string; LastName: string }) => {
    if(!currentUser) return;
    setUsers(prevUsers => 
        prevUsers.map(user => user.UserID === userId ? { ...user, ...updates } : user)
    );
    setCurrentUser(prevUser => prevUser ? { ...prevUser, ...updates } : null);
    addNotification({
        title: 'Profile Updated',
        message: 'Your profile information has been successfully updated.',
        type: 'system',
        targetRoles: [currentUser.Role]
    });
  };

  const handleChangePassword = (userId: string, newPassword: string): boolean => {
      if(!currentUser) return false;
      setUsers(prevUsers => 
          prevUsers.map(user => user.UserID === userId ? { ...user, Password: newPassword } : user)
      );
      setCurrentUser(prevUser => prevUser ? { ...prevUser, Password: newPassword } : null);
      addNotification({
          title: 'Password Changed',
          message: 'Your password has been successfully changed.',
          type: 'system',
          targetRoles: [currentUser.Role]
      });
      return true;
  };

  const updateRequisitionStatus = (id: string, status: 'Approved' | 'Rejected') => {
    if (!currentUser) return;
    const requisitionToUpdate = requisitions.find(req => req.RequisitionID === id);

    setRequisitions(prev =>
      prev.map(req =>
        req.RequisitionID === id
          ? {
              ...req,
              StatusType: status,
              ApprovalLogs: [
                ...req.ApprovalLogs,
                {
                  ApprovalLogID: `AL-${Date.now()}`,
                  RequisitionID: id,
                  UserID: currentUser.UserID,
                  Decision: status,
                  DecisionDate: new Date().toISOString(),
                  ApproverFullName: `${currentUser.FirstName} ${currentUser.LastName}`,
                },
              ],
            }
          : req
      )
    );
    
    if (requisitionToUpdate) {
        logTransaction(status === 'Approved' ? 'Approve Requisition' : 'Reject Requisition', 'Requisition', requisitionToUpdate.RequisitionNumber);
        if (status === 'Approved') {
            addNotification({ type: 'requisition', title: 'Requisition Approved', message: `Req #${requisitionToUpdate.RequisitionNumber} has been approved.`, targetRoles: ['Health Center Staff', 'Warehouse Staff'] });
        }
    }
  };
  
  const updatePurchaseOrderStatus = (poid: string, status: 'Approved' | 'Rejected') => {
    if (!currentUser) return;
    const poToUpdate = purchaseOrders.find(po => po.POID === poid);

    setPurchaseOrders(prev =>
      prev.map(po =>
        po.POID === poid
          ? {
              ...po,
              StatusType: status,
              ApprovalLogs: [
                ...po.ApprovalLogs,
                {
                  ApprovalLogID: `POAL-${Date.now()}`,
                  POID: poid,
                  UserID: currentUser.UserID,
                  Decision: status,
                  DecisionDate: new Date().toISOString(),
                  ApproverFullName: `${currentUser.FirstName} ${currentUser.LastName}`,
                },
              ],
            }
          : po
      )
    );
    
    if (poToUpdate) {
        logTransaction(status === 'Approved' ? 'Approve Purchase Order' : 'Reject Purchase Order', 'Purchase Order', poToUpdate.PONumber);
        if (status === 'Approved') {
            addNotification({ type: 'po', title: 'PO Approved', message: `PO #${poToUpdate.PONumber} has been approved and sent to supplier.`, targetRoles: ['Warehouse Staff', 'Accounting Office User'] });
        }
    }
  };

  const addRequisition = (newReq: Omit<Requisition, 'RequisitionID' | 'RequisitionNumber' | 'ApprovalLogs' | 'RequisitionItems'> & { RequisitionItems: Omit<RequisitionItem, 'RequisitionItemID' | 'RequisitionID'>[] }) => {
    const requisitionId = `R${(requisitions.length + 1).toString().padStart(5, '0')}`;
    const requisitionNumber = `REQ-${Date.now().toString().slice(-6)}`;
    const newRequisition: Requisition = {
      ...newReq,
      RequisitionID: requisitionId,
      RequisitionNumber: requisitionNumber,
      ApprovalLogs: [],
      RequisitionItems: newReq.RequisitionItems.map((item, index) => ({
        ...item,
        RequisitionItemID: `RI-${Date.now()}-${index}`,
        RequisitionID: requisitionId,
      }))
    };
    setRequisitions(prev => [newRequisition, ...prev]);
    logTransaction('Create Requisition', 'Requisition', requisitionNumber);
    addNotification({ type: 'requisition', title: 'New Requisition Submitted', message: `Req #${requisitionNumber} from ${newReq.HealthCenterName} is pending review.`, targetRoles: ['Administrator', 'Head Pharmacist'] });
  };
  
   const handleUpdateRequisition = (
    requisitionId: string,
    updatedItems: Omit<RequisitionItem, 'RequisitionItemID' | 'RequisitionID'>[]
  ) => {
    let requisitionNumber = '';
    setRequisitions(prevReqs => 
        prevReqs.map(req => {
            if (req.RequisitionID === requisitionId) {
                requisitionNumber = req.RequisitionNumber; // capture for logging
                return {
                    ...req,
                    RequisitionItems: updatedItems.map((item, index) => ({
                        ...item,
                        RequisitionItemID: `RI-${Date.now()}-${index}`,
                        RequisitionID: requisitionId,
                    }))
                };
            }
            return req;
        })
    );
    if (requisitionNumber) {
        logTransaction('Adjust Requisition', 'Requisition', requisitionNumber);
    }
  };

  const addPurchaseOrder = (newPO: Omit<PurchaseOrder, 'POID' | 'PONumber' | 'ApprovalLogs' | 'PurchaseOrderItems'> & { PurchaseOrderItems: Omit<PurchaseOrderItem, 'POItemID' | 'POID'>[] }) => {
    const poId = `PO${(purchaseOrders.length + 1).toString().padStart(3, '0')}`;
    const poNumber = `PO-${Date.now().toString().slice(-6)}`;
    const newPurchaseOrder: PurchaseOrder = {
      ...newPO,
      POID: poId,
      PONumber: poNumber,
      ApprovalLogs: [],
      PurchaseOrderItems: newPO.PurchaseOrderItems.map((item, index) => ({
        ...item,
        POItemID: `POI-${Date.now()}-${index}`,
        POID: poId,
      }))
    };
    setPurchaseOrders(prev => [newPurchaseOrder, ...prev]);
    logTransaction('Create Purchase Order', 'Purchase Order', poNumber);
    addNotification({ type: 'po', title: 'New PO for Approval', message: `PO #${poNumber} requires approval.`, targetRoles: ['Administrator', 'Head Pharmacist'] });
  };

  const addNoticeOfIssuance = (newNotice: Omit<NoticeOfIssuance, 'IssueID'>) => {
    const notice: NoticeOfIssuance = {
        ...newNotice,
        IssueID: `ISSUE-${Date.now().toString().slice(-6)}`,
    };
    setNoticesOfIssuance(prev => [notice, ...prev]);
    setInventory(prev => prev.map(batch => {
        if (batch.BatchID === newNotice.BatchID) {
            return { ...batch, QuantityOnHand: batch.QuantityOnHand - newNotice.QuantityIssued };
        }
        return batch;
    }));
    logTransaction('Inventory Disposal', 'Adjustment', notice.IssueID);
  };

  const addRequisitionAdjustment = (
    adjustment: Omit<RequisitionAdjustment, 'RequisitionAdjustmentID'>,
    details: Omit<RequisitionAdjustmentDetail, 'RADetailID' | 'RequisitionAdjustmentID'>[]
  ) => {
      const adjustmentId = `RADJ-${Date.now().toString().slice(-6)}`;
      const newAdjustment: RequisitionAdjustment = { ...adjustment, RequisitionAdjustmentID: adjustmentId };
      setRequisitionAdjustments(prev => [newAdjustment, ...prev]);
      const newDetails: RequisitionAdjustmentDetail[] = details.map(d => ({
          ...d,
          RADetailID: `RADD-${Date.now()}-${Math.random()}`,
          RequisitionAdjustmentID: adjustmentId,
      }));
      setRequisitionAdjustmentDetails(prev => [...prev, ...newDetails]);
      setInventory(prevInventory => {
          const updatedInventory = [...prevInventory];
          details.forEach(detail => {
              const batchIndex = updatedInventory.findIndex(b => b.BatchID === detail.BatchID);
              if (batchIndex !== -1) {
                  updatedInventory[batchIndex].QuantityOnHand += detail.QuantityAdjusted;
              }
          });
          return updatedInventory;
      });
      logTransaction('Adjust Inventory (Requisition Return)', 'Adjustment', adjustmentId);
  };

  const handleReceiveItems = ( poid: string, poNumber: string, receivedItems: any[]) => {
      if (!currentUser) return;
      const receivedDate = new Date().toISOString();
      const receivingId = `REC-${Date.now().toString().slice(-6)}`;
      const newReceiving: Receiving = { ReceivingID: receivingId, UserID: currentUser.UserID, POID: poid, ReceivedDate: receivedDate };
      setReceivings(prev => [newReceiving, ...prev]);
      const newInventoryBatches: CentralInventoryBatch[] = [];
      const newReceivingItemsList: ReceivingItem[] = [];
      receivedItems.forEach(item => {
        if(item.quantityReceived > 0){
            const batchId = `B-${Date.now().toString().slice(-8)}-${item.itemId}`;
            newInventoryBatches.push({ BatchID: batchId, ItemID: item.itemId, WarehouseID: 'W01', ExpiryDate: item.expiryDate, QuantityOnHand: item.quantityReceived, UnitCost: item.unitCost });
            newReceivingItemsList.push({ ReceivingItemID: `RI-${batchId}`, ReceivingID: receivingId, BatchID: batchId, QuantityReceived: item.quantityReceived });
        }
      });
      setInventory(prev => [...prev, ...newInventoryBatches]);
      setReceivingItems(prev => [...prev, ...newReceivingItemsList]);
      setPurchaseOrders(prev => prev.map(po => po.POID === poid ? {...po, StatusType: 'Completed'} : po));
      logTransaction('Receive PO Items', 'Purchase Order', poNumber);
      addNotification({ type: 'inventory', title: 'Items Received', message: `Items for PO #${poNumber} have been received.`, targetRoles: ['Accounting Office User', 'Administrator', 'Head Pharmacist'] });
  };

  const handleAdjustRequisitionItems = (
    requisitionId: string,
    adjustments: { requisitionItemId: string; newQuantity: number }[],
    reason: string
  ) => {
    if (!currentUser) return;

    const requisitionToUpdate = requisitions.find(r => r.RequisitionID === requisitionId);
    if (!requisitionToUpdate) return;
    
    const reasonText = `${reason}. Adjusted items: ${adjustments.map(adj => {
        const item = requisitionToUpdate.RequisitionItems.find(i => i.RequisitionItemID === adj.requisitionItemId);
        const itemName = initialItems.find(i => i.ItemID === item?.ItemID)?.ItemName || 'Unknown';
        return `${itemName} (from ${item?.QuantityRequested} to ${adj.newQuantity})`;
    }).join(', ')}`;

    const adjustmentId = `RADJ-${Date.now().toString().slice(-6)}`;
    const newAdjustment: RequisitionAdjustment = {
      RequisitionAdjustmentID: adjustmentId,
      RequisitionID: requisitionId,
      UserID: currentUser.UserID,
      AdjustmentType: 'Correction',
      AdjustmentDate: new Date().toISOString(),
      Reason: reasonText,
    };
    setRequisitionAdjustments(prev => [newAdjustment, ...prev]);

    setRequisitions(prevReqs => prevReqs.map(req => {
      if (req.RequisitionID === requisitionId) {
        const updatedItems = req.RequisitionItems.map(item => {
          const adjustment = adjustments.find(adj => adj.requisitionItemId === item.RequisitionItemID);
          if (adjustment) {
            return { ...item, QuantityRequested: adjustment.newQuantity };
          }
          return item;
        });
        return { ...req, RequisitionItems: updatedItems };
      }
      return req;
    }));

    logTransaction('Adjust Requisition', 'Requisition', requisitionToUpdate.RequisitionNumber);
  };


  const handleProcessIssuance = ( requisitionId: string, reqNumber: string, issuanceItemsToProcess: { batchId: string; requisitionItemId: string; quantityIssued: number }[]) => {
      if (!currentUser) return;
      const issuanceId = `ISS-${Date.now().toString().slice(-6)}`;
      const newIssuance: Issuance = { IssuanceID: issuanceId, RequisitionID: requisitionId, UserID: currentUser.UserID, IssuedByFullName: `${currentUser.FirstName} ${currentUser.LastName}`, IssuedDate: new Date().toISOString(), StatusType: 'Completed' };
      setIssuances(prev => [newIssuance, ...prev]);
      const newIssuanceItemsList: IssuanceItem[] = [];
      setInventory(currentInventory => {
        const updatedInventory = [...currentInventory];
        issuanceItemsToProcess.forEach(itemToProcess => {
            newIssuanceItemsList.push({ IssuanceItemID: `ISSI-${Math.random().toString(36).substr(2, 9)}`, IssuanceID: newIssuance.IssuanceID, BatchID: itemToProcess.batchId, RequisitionItemID: itemToProcess.requisitionItemId, QuantityIssued: itemToProcess.quantityIssued });
            const batchIndex = updatedInventory.findIndex(b => b.BatchID === itemToProcess.batchId);
            if (batchIndex !== -1) {
                updatedInventory[batchIndex].QuantityOnHand -= itemToProcess.quantityIssued;
            }
        });
        return updatedInventory;
      });
      setIssuanceItems(prev => [...prev, ...newIssuanceItemsList]);
      setRequisitions(prev => prev.map(req => req.RequisitionID === requisitionId ? { ...req, StatusType: 'Processed' } : req ));
      logTransaction('Process Issuance', 'Requisition', reqNumber);
  };
  
  const handleGenerateReport = (reportType: ReportType, office: OfficeType, data: any) => {
    if (!currentUser) return;
    const newReport: Report = {
        ReportID: `REP-${Date.now().toString().slice(-8)}`, UserID: currentUser.UserID, ReportType: reportType, GeneratedDate: new Date().toISOString(), GeneratedForOffice: office, GeneratedByFullName: `${currentUser.FirstName} ${currentUser.LastName}`, data: data,
    };
    setReports(prev => [newReport, ...prev]);
    logTransaction('Generate Report', 'Report', newReport.ReportID);
  };

  const addWarehouse = (newWarehouse: Omit<Warehouse, 'WarehouseID'>) => {
    const warehouse: Warehouse = {
        ...newWarehouse,
        WarehouseID: `W-${Date.now().toString().slice(-4)}`
    };
    setWarehouses(prev => [warehouse, ...prev]);
    logTransaction('Create Warehouse', 'Warehouse', warehouse.WarehouseID);
  };

  const handleInitiateAdjustment = (itemId: string, batchId: string) => {
    setAdjustmentContext({ itemId, batchId });
    setCurrentPage('Adjustments');
  };

  const renderPage = () => {
    if (!currentUser) return null;
    const userPermissions = rolePermissions[currentUser.Role] || [];
    if (!userPermissions.includes(currentPage)) {
        const firstAccessiblePage = userPermissions[0];
        if (firstAccessiblePage) setCurrentPage(firstAccessiblePage);
        return null;
    }

    switch (currentPage) {
      case 'Dashboard':
        switch (currentUser.Role) {
            case 'Administrator':
            case 'Head Pharmacist':
                return <Dashboard requisitions={requisitions} inventory={inventory} items={initialItems} purchaseOrders={purchaseOrders} />;
            case 'Health Center Staff':
                return <HealthCenterDashboard requisitions={requisitions} currentUser={currentUser} setCurrentPage={setCurrentPage} />;
            case 'Warehouse Staff':
                return <WarehouseDashboard requisitions={requisitions} purchaseOrders={purchaseOrders} inventory={inventory} items={initialItems} setCurrentPage={setCurrentPage} />;
            case 'Accounting Office User':
                return <AccountingDashboard purchaseOrders={purchaseOrders} inventory={inventory} items={initialItems} receivings={receivings} users={users} setCurrentPage={setCurrentPage} />;
            case 'CMO/GSO/COA User':
                 return <ComplianceDashboard inventory={inventory} items={initialItems} reports={reports} setCurrentPage={setCurrentPage} />;
            default:
                 return <Dashboard requisitions={requisitions} inventory={inventory} items={initialItems} purchaseOrders={purchaseOrders} />;
        }
      case 'Requisitions': return <Requisitions requisitions={requisitions} onUpdateStatus={updateRequisitionStatus} onAddRequisition={addRequisition} onUpdateRequisition={handleUpdateRequisition} currentUser={currentUser} />;
      case 'Purchase Orders': return <PurchaseOrdersPage purchaseOrders={purchaseOrders} onAddPurchaseOrder={addPurchaseOrder} currentUser={currentUser} onUpdateStatus={updatePurchaseOrderStatus} />;
      case 'Receiving': return <ReceivingPage purchaseOrders={purchaseOrders} onReceiveItems={handleReceiveItems} currentUser={currentUser} receivings={receivings} users={users} />;
      case 'Inventory': return <Inventory inventory={inventory} items={initialItems} onInitiateAdjustment={handleInitiateAdjustment} />;
      case 'Warehouse': return <WarehousePage warehouses={warehouses} onAddWarehouse={addWarehouse} />;
      case 'Adjustments': return <AdjustmentsPage notices={noticesOfIssuance} inventory={inventory} items={initialItems} onAddNotice={addNoticeOfIssuance} currentUser={currentUser} requisitions={requisitions} issuances={issuances} issuanceItems={issuanceItems} requisitionAdjustments={requisitionAdjustments} onAddRequisitionAdjustment={addRequisitionAdjustment} adjustmentContext={adjustmentContext} clearAdjustmentContext={() => setAdjustmentContext(null)} />;
      case 'Issuance': return <IssuancePage requisitions={requisitions} inventory={inventory} items={initialItems} currentUser={currentUser} onProcessIssuance={handleProcessIssuance} onAdjustRequisition={handleAdjustRequisitionItems} issuances={issuances} issuanceItems={issuanceItems} users={users} />;
      case 'Reports': return <Reports
          inventory={inventory}
          items={initialItems}
          reports={reports}
          onGenerateReport={handleGenerateReport}
          currentUser={currentUser}
          purchaseOrders={purchaseOrders}
          receivings={receivings}
          receivingItems={receivingItems}
          users={users}
          issuances={issuances}
          issuanceItems={issuanceItems}
          requisitions={requisitions}
          noticesOfIssuance={noticesOfIssuance}
          requisitionAdjustments={requisitionAdjustments}
          requisitionAdjustmentDetails={requisitionAdjustmentDetails}
        />;
      case 'Settings': return <Settings 
          securityLogs={securityLogs} 
          transactionLogs={transactionAuditLogs} 
          currentUser={currentUser} 
          theme={theme} 
          onThemeChange={setTheme}
          onUpdateProfile={handleUpdateProfile}
          onChangePassword={handleChangePassword}
          addNotification={addNotification}
        />;
      case 'Profile': return <Profile currentUser={currentUser} />;
      default: return <div className="p-8 bg-[var(--color-bg-surface)] rounded-xl shadow-md"><h2 className="text-2xl font-semibold">{currentPage}</h2><p>This page is under construction.</p></div>;
    }
  };

  const renderAuth = () => {
    return (
        <>
            <ToastContainer toasts={toasts} onDismiss={removeToast} />
            {authPage === 'signin' ? 
                <SignIn onSignIn={handleSignIn} onSwitchToSignUp={() => setAuthPage('signup')} users={users} onSignInFail={handleSignInFail} /> :
                <SignUp onSignUp={handleSignUp} onSwitchToSignIn={() => setAuthPage('signin')} />
            }
        </>
    );
  };


  if (!currentUser) {
    return renderAuth();
  }

  return (
    <div className="flex h-screen bg-[var(--color-bg-base)]">
       <ToastContainer toasts={toasts} onDismiss={removeToast} />
       <Sidebar 
          currentPage={currentPage} 
          setCurrentPage={setCurrentPage} 
          userRole={currentUser.Role} 
          isOpen={isSidebarOpen}
          setIsOpen={setIsSidebarOpen}
        />
        {isSidebarOpen && <div onClick={() => setIsSidebarOpen(false)} className="fixed inset-0 bg-black/60 z-30 md:hidden"></div>}
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header 
          currentPage={currentPage} 
          onSignOut={handleOpenSignOutModal} 
          currentUser={currentUser} 
          notifications={visibleNotifications}
          onMarkNotificationsAsRead={markNotificationsAsRead}
          setCurrentPage={setCurrentPage}
          toggleSidebar={() => setIsSidebarOpen(prev => !prev)}
        />
        <main className="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
          {renderPage()}
        </main>
      </div>
       {isSignOutModalOpen && (
        <SignOutConfirmationModal
          isOpen={isSignOutModalOpen}
          onClose={handleCloseSignOutModal}
          onConfirm={handleConfirmSignOut}
        />
      )}
    </div>
  );
};

export default App;
