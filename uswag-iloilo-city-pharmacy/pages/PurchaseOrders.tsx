
import React, { useState } from 'react';
import { PurchaseOrder, POStatus, User, PurchaseOrderItem } from '../types';
import Badge from '../components/Badge';
import { initialItems, healthCenters, initialSuppliers } from '../constants';

interface PurchaseOrdersPageProps {
  purchaseOrders: PurchaseOrder[];
  onAddPurchaseOrder: (newPO: Omit<PurchaseOrder, 'POID' | 'PONumber' | 'ApprovalLogs' | 'PurchaseOrderItems'> & { PurchaseOrderItems: Omit<PurchaseOrderItem, 'POItemID' | 'POID'>[] }) => void;
  currentUser: User;
  onUpdateStatus: (poid: string, status: 'Approved' | 'Rejected') => void;
}

const POModal: React.FC<{
    purchaseOrder: PurchaseOrder;
    onClose: () => void;
    currentUser: User;
    onUpdateStatus: (poid: string, status: 'Approved' | 'Rejected') => void;
}> = ({ purchaseOrder, onClose, currentUser, onUpdateStatus }) => {
    const getItemName = (itemId: string) => initialItems.find(i => i.ItemID === itemId)?.ItemName || 'Unknown Item';
    const canApprove = ['Administrator', 'Head Pharmacist'].includes(currentUser.Role);

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-[var(--color-bg-surface)] rounded-lg shadow-xl p-6 w-full max-w-3xl transform transition-all">
                <div className="flex justify-between items-center mb-4 border-b border-[var(--color-border)] pb-3">
                    <h3 className="text-xl font-semibold text-[var(--color-text-base)]">Purchase Order - {purchaseOrder.PONumber}</h3>
                    <button onClick={onClose} className="text-[var(--color-text-muted)] hover:text-[var(--color-text-base)] text-2xl font-bold">&times;</button>
                </div>
                <div className="grid grid-cols-2 gap-4 text-sm mb-4 text-[var(--color-text-muted)]">
                    <div><strong>Supplier:</strong> {purchaseOrder.SupplierName}</div>
                    <div><strong>Status:</strong> <Badge status={purchaseOrder.StatusType} /></div>
                    <div><strong>For:</strong> {purchaseOrder.HealthCenterName || 'Central Warehouse'}</div>
                    <div><strong>PO Date:</strong> {new Date(purchaseOrder.PODate).toLocaleDateString()}</div>
                </div>

                <h4 className="font-semibold text-[var(--color-text-base)] mb-2">Ordered Items</h4>
                <div className="max-h-48 overflow-y-auto border border-[var(--color-border)] rounded-lg">
                    <table className="w-full text-sm text-left">
                        <thead className="text-xs text-[var(--color-text-muted)] bg-[var(--color-bg-muted)]">
                            <tr>
                                <th className="px-4 py-2">Item</th>
                                <th className="px-4 py-2 text-right">Quantity Ordered</th>
                            </tr>
                        </thead>
                        <tbody className="text-[var(--color-text-muted)]">
                        {purchaseOrder.PurchaseOrderItems.map(item => (
                            <tr key={item.POItemID} className="border-b border-[var(--color-border)] last:border-b-0">
                                <td className="px-4 py-2">{getItemName(item.ItemID)}</td>
                                <td className="px-4 py-2 text-right">{item.QuantityOrdered.toLocaleString()}</td>
                            </tr>
                        ))}
                        </tbody>
                    </table>
                </div>

                 <h4 className="font-semibold text-[var(--color-text-base)] mb-2 mt-4">Approval History</h4>
                <div className="max-h-32 overflow-y-auto border border-[var(--color-border)] rounded-lg p-2 mb-4">
                    {purchaseOrder.ApprovalLogs.length > 0 ? (
                        <ul className="text-[var(--color-text-muted)]">
                            {purchaseOrder.ApprovalLogs.map((history) => (
                                <li key={history.ApprovalLogID} className="p-2 border-b border-[var(--color-border)] last:border-b-0">
                                    <div className="flex justify-between items-center">
                                        <span className="font-semibold text-[var(--color-text-base)]">{history.Decision} by {history.ApproverFullName}</span>
                                        <span className="text-xs">{new Date(history.DecisionDate).toLocaleString()}</span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-[var(--color-text-muted)] p-2">No approval history yet.</p>
                    )}
                </div>

                {purchaseOrder.StatusType === 'Pending' && canApprove && (
                    <div className="mt-4 pt-4 border-t border-[var(--color-border)]">
                        <div className="flex justify-end space-x-3 mt-4">
                            <button onClick={() => { onUpdateStatus(purchaseOrder.POID, 'Rejected'); onClose(); }} className="btn btn-danger">Reject</button>
                            <button onClick={() => { onUpdateStatus(purchaseOrder.POID, 'Approved'); onClose(); }} className="btn btn-success">Approve</button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

const NewPOModal: React.FC<{ onClose: () => void; onAddPurchaseOrder: PurchaseOrdersPageProps['onAddPurchaseOrder'], currentUser: User }> = ({ onClose, onAddPurchaseOrder, currentUser }) => {
    const isWarehouseUser = currentUser.Role === 'Warehouse Staff';
    const [supplierId, setSupplierId] = useState(initialSuppliers[0].SupplierID);
    const [healthCenterId, setHealthCenterId] = useState(isWarehouseUser ? '' : healthCenters[0].HealthCenterID);
    const [items, setItems] = useState<{ ItemID: string; QuantityOrdered: number }[]>([{ ItemID: initialItems[0].ItemID, QuantityOrdered: 1 }]);
    
    const handleItemChange = <K extends keyof (typeof items)[0]>(index: number, field: K, value: (typeof items)[0][K]) => {
        const newItems = [...items];
        newItems[index][field] = value;
        setItems(newItems);
    };

    const addItem = () => setItems([...items, { ItemID: initialItems[0].ItemID, QuantityOrdered: 1 }]);
    const removeItem = (index: number) => setItems(items.filter((_, i) => i !== index));

    const handleSubmit = () => {
        const selectedSupplier = initialSuppliers.find(s => s.SupplierID === supplierId);
        if (!selectedSupplier) return;

        const newPO: Omit<PurchaseOrder, 'POID' | 'PONumber' | 'ApprovalLogs' | 'PurchaseOrderItems'> & { PurchaseOrderItems: Omit<PurchaseOrderItem, 'POItemID' | 'POID'>[] } = {
            UserID: currentUser.UserID,
            SupplierID: supplierId,
            SupplierName: selectedSupplier.Name,
            PODate: new Date().toISOString(),
            StatusType: 'Pending' as POStatus,
            PurchaseOrderItems: items.map(i => ({...i, QuantityOrdered: Number(i.QuantityOrdered)})),
        };

        if (!isWarehouseUser) {
            const selectedHealthCenter = healthCenters.find(hc => hc.HealthCenterID === healthCenterId);
            if (!selectedHealthCenter) return;
            newPO.HealthCenterID = healthCenterId;
            newPO.HealthCenterName = selectedHealthCenter.Name;
        }

        onAddPurchaseOrder(newPO);
        onClose();
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-[var(--color-bg-surface)] rounded-lg shadow-xl p-6 w-full max-w-4xl transform transition-all">
                <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-4">New Purchase Order</h3>
                <div className="grid grid-cols-2 gap-4">
                     <div>
                        <label className="block text-sm font-medium text-[var(--color-text-muted)]">Supplier</label>
                        <select value={supplierId} onChange={e => setSupplierId(e.target.value)} className="form-select mt-1">
                            {initialSuppliers.map(s => <option key={s.SupplierID} value={s.SupplierID}>{s.Name}</option>)}
                        </select>
                    </div>
                    {!isWarehouseUser && (
                        <div>
                            <label className="block text-sm font-medium text-[var(--color-text-muted)]">Health Center</label>
                            <select value={healthCenterId} onChange={e => setHealthCenterId(e.target.value)} className="form-select mt-1">
                                {healthCenters.map(hc => <option key={hc.HealthCenterID} value={hc.HealthCenterID}>{hc.Name}</option>)}
                            </select>
                        </div>
                    )}
                </div>
                <div className="mt-4">
                    <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-2">Items</label>
                    
                    {/* Item Labels */}
                    <div className="grid grid-cols-9 gap-2 items-center px-2 pb-2 border-b border-[var(--color-border)]">
                        <div className="col-span-6 text-xs font-bold text-[var(--color-text-muted)] uppercase tracking-wider">Item Name</div>
                        <div className="col-span-2 text-xs font-bold text-[var(--color-text-muted)] uppercase tracking-wider">Quantity Ordered</div>
                        <div className="col-span-1"></div> {/* Spacer */}
                    </div>

                    <div className="space-y-2 mt-2 max-h-64 overflow-y-auto pr-2">
                        {items.map((item, index) => (
                            <div key={index} className="grid grid-cols-9 gap-2 items-center">
                                <select aria-label="Item Name" value={item.ItemID} onChange={e => handleItemChange(index, 'ItemID', e.target.value)} className="form-select col-span-6">
                                    {initialItems.map(i => <option key={i.ItemID} value={i.ItemID}>{i.ItemName}</option>)}
                                </select>
                                <input aria-label="Quantity" type="number" value={item.QuantityOrdered} onChange={e => handleItemChange(index, 'QuantityOrdered', parseInt(e.target.value, 10))} className="form-input col-span-2" />
                                <button aria-label="Remove Item" onClick={() => removeItem(index)} className="col-span-1 text-red-500 hover:text-red-700 text-2xl flex justify-center items-center h-full">&times;</button>
                            </div>
                        ))}
                    </div>
                    <button onClick={addItem} className="text-sm text-[var(--color-primary)] hover:underline mt-2">+ Add Item</button>
                </div>
                <div className="flex justify-end space-x-3 mt-6 pt-4 border-t border-[var(--color-border)]">
                    <button onClick={onClose} className="btn btn-secondary">Cancel</button>
                    <button onClick={handleSubmit} className="btn btn-primary">Create Purchase Order</button>
                </div>
            </div>
        </div>
    );
};


const PurchaseOrdersPage: React.FC<PurchaseOrdersPageProps> = ({ purchaseOrders, onAddPurchaseOrder, currentUser, onUpdateStatus }) => {
  const [selectedPO, setSelectedPO] = useState<PurchaseOrder | null>(null);
  const [isNewModalOpen, setIsNewModalOpen] = useState(false);

  const canCreatePO = ['Administrator', 'Head Pharmacist', 'Warehouse Staff'].includes(currentUser.Role);

  return (
    <>
      <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold text-[var(--color-text-base)]">Purchase Orders</h2>
          {canCreatePO && (
            <button onClick={() => setIsNewModalOpen(true)} className="btn btn-primary">
                New Purchase Order
            </button>
          )}
        </div>
        <div className="table-wrapper">
          <table className="custom-table">
            <thead>
              <tr>
                <th scope="col">PO #</th>
                <th scope="col">Supplier</th>
                <th scope="col">PO Date</th>
                <th scope="col">Status</th>
                <th scope="col">Action</th>
              </tr>
            </thead>
            <tbody>
              {purchaseOrders.map((po) => (
                    <tr key={po.POID}>
                        <td className="font-medium text-[var(--color-text-base)]">{po.PONumber}</td>
                        <td>{po.SupplierName}</td>
                        <td>{new Date(po.PODate).toLocaleDateString()}</td>
                        <td><Badge status={po.StatusType} /></td>
                        <td>
                            <button onClick={() => setSelectedPO(po)} className="font-medium text-[var(--color-primary)] hover:underline">View</button>
                        </td>
                    </tr>
                ))}
            </tbody>
          </table>
        </div>
      </div>
      {selectedPO && <POModal purchaseOrder={selectedPO} onClose={() => setSelectedPO(null)} currentUser={currentUser} onUpdateStatus={onUpdateStatus} />}
      {isNewModalOpen && <NewPOModal onClose={() => setIsNewModalOpen(false)} onAddPurchaseOrder={onAddPurchaseOrder} currentUser={currentUser} />}
    </>
  );
};

export default PurchaseOrdersPage;