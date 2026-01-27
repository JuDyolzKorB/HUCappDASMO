import React, { useState } from 'react';
import { PurchaseOrder, User, Receiving } from '../types';
import Badge from '../components/Badge';
import { initialItems } from '../constants';

type ReceivedItemState = {
    poItemId: string;
    itemId: string;
    unitCost: string; // Changed for free text input
    quantityOrdered: number;
    quantityReceived: number;
    expiryDate: string;
}

interface ReceivingModalProps {
    purchaseOrder: PurchaseOrder;
    onClose: () => void;
    onReceiveItems: (poid: string, poNumber: string, receivedItems: any[]) => void;
}

const ReceivingModal: React.FC<ReceivingModalProps> = ({ purchaseOrder, onClose, onReceiveItems }) => {
    const [receivedItems, setReceivedItems] = useState<ReceivedItemState[]>(
        purchaseOrder.PurchaseOrderItems.map(item => ({
            poItemId: item.POItemID,
            itemId: item.ItemID,
            unitCost: '', // Default to empty string
            quantityOrdered: item.QuantityOrdered,
            quantityReceived: item.QuantityOrdered,
            expiryDate: '',
        }))
    );
    
    const getItemName = (itemId: string) => initialItems.find(i => i.ItemID === itemId)?.ItemName || 'Unknown Item';

    const handleInputChange = (index: number, field: keyof ReceivedItemState, value: string | number) => {
        const updatedItems = [...receivedItems];
        (updatedItems[index] as any)[field] = value;
        setReceivedItems(updatedItems);
    };

    const handleSubmit = () => {
        const itemsToProcess = receivedItems
            .map(item => ({
                ...item,
                unitCost: parseFloat(item.unitCost) || 0,
            }))
            .filter(item => item.quantityReceived > 0 && item.unitCost > 0 && item.expiryDate);

        onReceiveItems(purchaseOrder.POID, purchaseOrder.PONumber, itemsToProcess);
        onClose();
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-[var(--color-bg-surface)] rounded-xl shadow-xl p-6 w-full max-w-4xl transform transition-all max-h-[90vh] flex flex-col modal-content">
                <div className="flex justify-between items-center mb-6 border-b border-[var(--color-border)] pb-4">
                    <h3 className="text-xl font-semibold text-[var(--color-text-base)]">Receive Items for {purchaseOrder.PONumber}</h3>
                    <button onClick={onClose} className="text-[var(--color-text-muted)] hover:text-[var(--color-text-base)] text-2xl font-bold">&times;</button>
                </div>
                <div className="flex-grow overflow-y-auto -mx-6 px-6">
                    <table className="w-full text-sm">
                        <thead className="text-xs text-[var(--color-text-muted)] uppercase bg-[var(--color-bg-muted)] sticky top-0">
                            <tr>
                                <th className="px-4 py-3 text-left">Item</th>
                                <th className="px-4 py-3 text-center">Qty Ordered</th>
                                <th className="px-4 py-3 text-center">Qty Received</th>
                                <th className="px-4 py-3 text-center">Unit Cost</th>
                                <th className="px-4 py-3 text-left">Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-[var(--color-border)] text-[var(--color-text-base)]">
                            {receivedItems.map((item, index) => (
                                <tr key={item.poItemId}>
                                    <td className="px-4 py-3 font-medium">{getItemName(item.itemId)}</td>
                                    <td className="px-4 py-3 text-center">{item.quantityOrdered.toLocaleString()}</td>
                                    <td className="px-4 py-3 text-center">
                                        <input
                                            type="number"
                                            value={item.quantityReceived}
                                            onChange={(e) => handleInputChange(index, 'quantityReceived', parseInt(e.target.value, 10) || 0)}
                                            className="form-input w-24 text-right pr-2"
                                            max={item.quantityOrdered}
                                            min="0"
                                        />
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <div className="relative inline-block">
                                            <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-[var(--color-text-muted)]">$</span>
                                            <input
                                                type="text"
                                                inputMode="decimal"
                                                value={item.unitCost}
                                                onChange={(e) => {
                                                    const { value } = e.target;
                                                    // Allow only numbers and a single decimal point, up to 2 decimal places
                                                    if (/^\d*\.?\d{0,2}$/.test(value)) {
                                                        handleInputChange(index, 'unitCost', value);
                                                    }
                                                }}
                                                className="form-input w-28 text-right pl-7 pr-3"
                                                required
                                                placeholder="0.00"
                                            />
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        <input
                                            type="date"
                                            value={item.expiryDate}
                                            onChange={(e) => handleInputChange(index, 'expiryDate', e.target.value)}
                                            className="form-input w-36"
                                            required
                                        />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="flex justify-end space-x-3 mt-8 pt-6 border-t border-[var(--color-border)]">
                    <button onClick={onClose} className="btn btn-secondary">Cancel</button>
                    <button onClick={handleSubmit} className="btn btn-primary">Confirm Receiving</button>
                </div>
            </div>
        </div>
    );
}


interface ReceivingPageProps {
  purchaseOrders: PurchaseOrder[];
  onReceiveItems: (poid: string, poNumber: string, receivedItems: any[]) => void;
  currentUser: User;
  receivings: Receiving[];
  users: User[];
}

const ReceivingPage: React.FC<ReceivingPageProps> = ({ purchaseOrders, onReceiveItems, currentUser, receivings, users }) => {
    const [selectedPO, setSelectedPO] = useState<PurchaseOrder | null>(null);
    const [activeTab, setActiveTab] = useState<'awaiting' | 'history'>('awaiting');

    const posToReceive = purchaseOrders.filter(po => po.StatusType === 'Approved');
    
    const getUserFullName = (userId: string) => {
        const user = users.find(u => u.UserID === userId);
        return user ? `${user.FirstName} ${user.LastName}` : 'Unknown User';
    };
     const getPONumber = (poid: string) => {
        const po = purchaseOrders.find(p => p.POID === poid);
        return po ? po.PONumber : poid; // Fallback to poid if not found
    };

    return (
        <>
            <div className="bg-[var(--color-bg-surface)] p-6 md:p-8 rounded-xl border border-[var(--color-border)] shadow-sm">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-2xl font-bold text-[var(--color-text-base)]">Receiving</h2>
                    <div className="flex space-x-1 bg-[var(--color-bg-base)] p-1 rounded-lg">
                        <button onClick={() => setActiveTab('awaiting')} className={`px-4 py-1.5 text-sm font-semibold rounded-md transition-colors ${activeTab === 'awaiting' ? 'bg-[var(--color-bg-surface)] shadow-sm text-[var(--color-primary)]' : 'text-[var(--color-text-muted)] hover:bg-white/50'}`}>Awaiting Receiving</button>
                        <button onClick={() => setActiveTab('history')} className={`px-4 py-1.5 text-sm font-semibold rounded-md transition-colors ${activeTab === 'history' ? 'bg-[var(--color-bg-surface)] shadow-sm text-[var(--color-primary)]' : 'text-[var(--color-text-muted)] hover:bg-white/50'}`}>Receiving History</button>
                    </div>
                </div>

                {activeTab === 'awaiting' && (
                    <div>
                        <h3 className="text-lg font-semibold text-[var(--color-text-base)] mb-4">Purchase Orders - Approved for Receiving</h3>
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
                                    {posToReceive.map(po => (
                                        <tr key={po.POID}>
                                            <td className="font-medium text-[var(--color-text-base)]">{po.PONumber}</td>
                                            <td>{po.SupplierName}</td>
                                            <td>{new Date(po.PODate).toLocaleDateString()}</td>
                                            <td><Badge status={po.StatusType} /></td>
                                            <td>
                                                <button onClick={() => setSelectedPO(po)} className="font-medium text-[var(--color-primary)] hover:underline">Receive Items</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {posToReceive.length === 0 && (
                                <div className="text-center py-8 text-[var(--color-text-muted)]">
                                    <p>No purchase orders are currently approved for receiving.</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
                
                {activeTab === 'history' && (
                    <div>
                        <h3 className="text-lg font-semibold text-[var(--color-text-base)] mb-4">Receiving History</h3>
                        <div className="table-wrapper">
                            <table className="custom-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Receiving ID</th>
                                        <th scope="col">PO #</th>
                                        <th scope="col">Received Date</th>
                                        <th scope="col">Received By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {receivings.map(rec => (
                                        <tr key={rec.ReceivingID}>
                                            <td className="font-medium text-[var(--color-text-base)]">{rec.ReceivingID}</td>
                                            <td className="font-mono">{getPONumber(rec.POID)}</td>
                                            <td>{new Date(rec.ReceivedDate).toLocaleString()}</td>
                                            <td>{getUserFullName(rec.UserID)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                             {receivings.length === 0 && (
                                <div className="text-center py-8 text-[var(--color-text-muted)]">
                                    <p>No receiving history found.</p>
                                </div>
                             )}
                        </div>
                    </div>
                )}
            </div>
            {selectedPO && (
                <ReceivingModal 
                    purchaseOrder={selectedPO}
                    onClose={() => setSelectedPO(null)}
                    onReceiveItems={onReceiveItems}
                />
            )}
        </>
    );
};

export default ReceivingPage;