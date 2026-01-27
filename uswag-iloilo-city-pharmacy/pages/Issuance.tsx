import React, { useState, useMemo } from 'react';
import { Requisition, CentralInventoryBatch, Item, User, Issuance, IssuanceItem, RequisitionItem } from '../types';
import Badge from '../components/Badge';
import { initialItems } from '../constants';
import AlertIcon from '../components/icons/AlertIcon';

type IssuanceCartItem = {
    requisitionItemId: string;
    itemId: string;
    quantityRequested: number;
    allocations: {
        batchId: string;
        quantityToIssue: number | '';
    }[];
    shortage: number;
};

const IssuanceModal: React.FC<{
    requisition: Requisition;
    inventory: CentralInventoryBatch[];
    items: Item[];
    onClose: () => void;
    onProcess: (issuanceItems: { batchId: string; requisitionItemId: string; quantityIssued: number }[]) => void;
    onInitiateAdjustment: () => void;
}> = ({ requisition, inventory, items, onClose, onProcess, onInitiateAdjustment }) => {
    
    const [cart] = useState<IssuanceCartItem[]>(() => {
        return requisition.RequisitionItems.map(reqItem => {
            const availableBatches = inventory
                .filter(b => b.ItemID === reqItem.ItemID && b.QuantityOnHand > 0)
                .sort((a, b) => new Date(a.ExpiryDate).getTime() - new Date(b.ExpiryDate).getTime());

            let remainingQtyToAllocate = reqItem.QuantityRequested;
            const allocations: { batchId: string; quantityToIssue: number }[] = [];

            for (const batch of availableBatches) {
                if (remainingQtyToAllocate <= 0) break;
                const qtyFromBatch = Math.min(remainingQtyToAllocate, batch.QuantityOnHand);
                allocations.push({ batchId: batch.BatchID, quantityToIssue: qtyFromBatch });
                remainingQtyToAllocate -= qtyFromBatch;
            }
            
            const totalAllocated = allocations.reduce((sum, alloc) => sum + alloc.quantityToIssue, 0);
            const shortage = reqItem.QuantityRequested - totalAllocated;

            return {
                requisitionItemId: reqItem.RequisitionItemID,
                itemId: reqItem.ItemID,
                quantityRequested: reqItem.QuantityRequested,
                allocations,
                shortage,
            };
        });
    });
    
    const hasShortage = useMemo(() => cart.some(item => item.shortage > 0), [cart]);

    const getItemName = (itemId: string) => items.find(i => i.ItemID === itemId)?.ItemName || 'Unknown';
    const getBatch = (batchId: string) => inventory.find(b => b.BatchID === batchId);

    const totalIssued = (item: IssuanceCartItem) => item.allocations.reduce((sum, alloc) => sum + Number(alloc.quantityToIssue), 0);
    const canProcess = cart.every(item => totalIssued(item) === item.quantityRequested);

    const handleSubmit = () => {
        const issuanceItemsToProcess = cart.flatMap(item => 
            item.allocations
                .filter(alloc => Number(alloc.quantityToIssue) > 0)
                .map(alloc => ({
                    batchId: alloc.batchId,
                    requisitionItemId: item.requisitionItemId,
                    quantityIssued: Number(alloc.quantityToIssue)
                }))
        );
        onProcess(issuanceItemsToProcess);
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-[var(--color-bg-surface)] rounded-xl shadow-xl p-6 w-full max-w-4xl max-h-[90vh] flex flex-col modal-content">
                <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-6">Process Issuance for {requisition.RequisitionNumber}</h3>
                <div className="flex-grow overflow-y-auto pr-2 -mx-6 px-6">
                    {cart.map((item) => (
                        <div key={item.requisitionItemId} className="mb-4 p-4 border border-[var(--color-border)] rounded-lg">
                            <div className="flex justify-between items-center mb-2">
                                <h4 className="font-bold text-[var(--color-text-base)]">{getItemName(item.itemId)}</h4>
                                <div className={`px-2 py-1 rounded text-xs font-semibold ${totalIssued(item) < item.quantityRequested ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>
                                    Issued: {totalIssued(item).toLocaleString()} / {item.quantityRequested.toLocaleString()}
                                </div>
                            </div>
                            {item.shortage > 0 && (
                                <div className="flex items-start p-3 my-2 text-sm text-[var(--color-text-warning)] rounded-lg bg-[var(--color-warning-light)]" role="alert">
                                    <AlertIcon className="w-5 h-5 mr-2 flex-shrink-0"/>
                                    <div>
                                        <span className="font-medium">Insufficient Stock!</span> Cannot fulfill the full request.
                                        <br />
                                        Shortfall of {item.shortage.toLocaleString()} unit(s).
                                    </div>
                                </div>
                            )}
                            <table className="w-full text-sm mt-3">
                                <thead><tr className="text-left text-xs uppercase text-[var(--color-text-muted)]"><th>Batch (Expiry)</th><th>Available</th><th>Issuing</th></tr></thead>
                                <tbody>
                                {item.allocations.map((alloc) => {
                                    const batch = getBatch(alloc.batchId);
                                    if (!batch) return null;
                                    return (
                                    <tr key={alloc.batchId} className="text-[var(--color-text-muted)]">
                                        <td>Batch {alloc.batchId.slice(-4)} ({new Date(batch.ExpiryDate).toLocaleDateString()})</td>
                                        <td>{batch.QuantityOnHand.toLocaleString()}</td>
                                        <td>
                                            <input 
                                                type="number" 
                                                value={alloc.quantityToIssue}
                                                className="form-input w-24 p-1 bg-[var(--color-bg-muted)] border-transparent cursor-not-allowed focus:ring-0"
                                                readOnly
                                            />
                                        </td>
                                    </tr>
                                    );
                                })}
                                </tbody>
                            </table>
                            {item.allocations.length === 0 && <p className="text-sm text-red-600 mt-2">No stock available for this item.</p>}
                        </div>
                    ))}
                </div>
                <div className="flex justify-end space-x-3 mt-8 pt-6 border-t border-[var(--color-border)]">
                    <button onClick={onClose} className="btn btn-secondary">Cancel</button>
                    <button onClick={onInitiateAdjustment} className={`btn transition-all duration-300 ${hasShortage ? 'btn-primary animate-pulse' : 'btn-secondary'}`}>Adjust Quantities</button>
                    <button onClick={handleSubmit} disabled={!canProcess} className="btn btn-primary">Process Issuance</button>
                </div>
            </div>
        </div>
    );
};

const AdjustmentModal: React.FC<{
    requisition: Requisition;
    items: Item[];
    onClose: () => void;
    onAdjust: (requisitionId: string, adjustments: { requisitionItemId: string; newQuantity: number }[], reason: string) => void;
}> = ({ requisition, items, onClose, onAdjust }) => {
    const [adjustments, setAdjustments] = useState<{[key: string]: number | ''}>(
        requisition.RequisitionItems.reduce((acc, item) => ({...acc, [item.RequisitionItemID]: item.QuantityRequested}), {})
    );
    const [reason, setReason] = useState('');

    const getItemName = (itemId: string) => items.find(i => i.ItemID === itemId)?.ItemName || 'Unknown';
    
    const handleAdjustmentChange = (reqItemId: string, value: string) => {
        if (value === '') {
            setAdjustments(prev => ({...prev, [reqItemId]: ''}));
        } else {
            const num = parseInt(value, 10);
            if (!isNaN(num) && num >= 0) {
                setAdjustments(prev => ({...prev, [reqItemId]: num}));
            }
        }
    }

    const handleSubmit = () => {
        const adjustmentData = Object.entries(adjustments)
            .map(([requisitionItemId, newQuantity]) => ({ requisitionItemId, newQuantity: Number(newQuantity) }))
            .filter(adj => {
                const originalItem = requisition.RequisitionItems.find(i => i.RequisitionItemID === adj.requisitionItemId);
                return originalItem && originalItem.QuantityRequested !== adj.newQuantity;
            });
        
        if(adjustmentData.length > 0 && reason) {
            onAdjust(requisition.RequisitionID, adjustmentData, reason);
            onClose();
        }
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 p-4">
            <div className="bg-[var(--color-bg-surface)] rounded-xl shadow-xl p-6 w-full max-w-2xl modal-content">
                <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-6">Adjust Quantities for {requisition.RequisitionNumber}</h3>
                 <div className="space-y-4 text-[var(--color-text-base)]">
                    {requisition.RequisitionItems.map(item => (
                        <div key={item.RequisitionItemID} className="flex items-center justify-between">
                            <span className="font-medium">{getItemName(item.ItemID)}</span>
                            <input 
                                type="number"
                                value={adjustments[item.RequisitionItemID]}
                                onChange={e => handleAdjustmentChange(item.RequisitionItemID, e.target.value)}
                                className="form-input w-32"
                                min="0"
                            />
                        </div>
                    ))}
                 </div>
                 <div className="mt-6">
                    <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Reason for Adjustment</label>
                    <textarea value={reason} onChange={e => setReason(e.target.value)} className="form-input" rows={3}></textarea>
                 </div>
                <div className="flex justify-end space-x-3 mt-8 pt-6 border-t border-[var(--color-border)]">
                    <button onClick={onClose} className="btn btn-secondary">Cancel</button>
                    <button onClick={handleSubmit} className="btn btn-primary">Save Adjustments</button>
                </div>
            </div>
        </div>
    )
}


const IssuancePage: React.FC<{
    requisitions: Requisition[];
    inventory: CentralInventoryBatch[];
    items: Item[];
    currentUser: User;
    onProcessIssuance: (requisitionId: string, reqNumber: string, issuanceItems: { batchId: string; requisitionItemId: string; quantityIssued: number }[]) => void;
    onAdjustRequisition: (requisitionId: string, adjustments: { requisitionItemId: string; newQuantity: number }[], reason: string) => void;
    issuances: Issuance[];
    issuanceItems: IssuanceItem[];
    users: User[];
}> = ({ requisitions, inventory, items, currentUser, onProcessIssuance, onAdjustRequisition, issuances, issuanceItems, users }) => {
    
    const [activeTab, setActiveTab] = useState<'pending' | 'history'>('pending');
    const [selectedReqForIssuance, setSelectedReqForIssuance] = useState<Requisition | null>(null);
    const [selectedReqForAdjustment, setSelectedReqForAdjustment] = useState<Requisition | null>(null);
    const [expandedIssuance, setExpandedIssuance] = useState<string | null>(null);
    
    const pendingRequisitions = useMemo(() => requisitions.filter(r => r.StatusType === 'Approved'), [requisitions]);
    
    const handleToggleIssuance = (issuanceId: string) => {
        setExpandedIssuance(prev => (prev === issuanceId ? null : issuanceId));
    };

    const getIssuanceDetails = (issuanceId: string) => {
        return issuanceItems
            .filter(ii => ii.IssuanceID === issuanceId)
            .map(ii => {
                const requisitionItem = requisitions.flatMap(r => r.RequisitionItems).find(ri => ri.RequisitionItemID === ii.RequisitionItemID);
                const item = items.find(i => i.ItemID === requisitionItem?.ItemID);
                const batch = inventory.find(b => b.BatchID === ii.BatchID);
                return {
                    issuanceItemId: ii.IssuanceItemID,
                    itemName: item?.ItemName || 'Unknown Item',
                    batchId: ii.BatchID,
                    expiryDate: batch?.ExpiryDate ? new Date(batch.ExpiryDate).toLocaleDateString() : 'N/A',
                    quantityIssued: ii.QuantityIssued,
                };
            });
    };
     const getUserName = (userId: string) => {
        const user = users.find(u => u.UserID === userId);
        return user ? `${user.FirstName} ${user.LastName}` : 'Unknown';
    }
     const getRequisitionNumber = (requisitionId: string) => requisitions.find(r => r.RequisitionID === requisitionId)?.RequisitionNumber;

    return (
        <>
        <div className="bg-[var(--color-bg-surface)] p-6 md:p-8 rounded-xl border border-[var(--color-border)] shadow-sm">
            <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold text-[var(--color-text-base)]">Issuance</h2>
                <div className="flex space-x-1 bg-[var(--color-bg-base)] p-1 rounded-lg">
                    <button onClick={() => setActiveTab('pending')} className={`px-4 py-1.5 text-sm font-semibold rounded-md transition-colors ${activeTab === 'pending' ? 'bg-[var(--color-bg-surface)] shadow-sm text-[var(--color-primary)]' : 'text-[var(--color-text-muted)] hover:bg-white/50'}`}>Pending for Issuance</button>
                    <button onClick={() => setActiveTab('history')} className={`px-4 py-1.5 text-sm font-semibold rounded-md transition-colors ${activeTab === 'history' ? 'bg-[var(--color-bg-surface)] shadow-sm text-[var(--color-primary)]' : 'text-[var(--color-text-muted)] hover:bg-white/50'}`}>Issuance History</button>
                </div>
            </div>

            {activeTab === 'pending' && (
                <div className="table-wrapper">
                    <table className="custom-table">
                        <thead><tr><th>Req #</th><th>Health Center</th><th>Date Approved</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                            {pendingRequisitions.map(req => {
                                const approval = req.ApprovalLogs.find(log => log.Decision === 'Approved');
                                return(
                                <tr key={req.RequisitionID}>
                                    <td className="font-medium text-[var(--color-text-base)]">{req.RequisitionNumber}</td>
                                    <td>{req.HealthCenterName}</td>
                                    <td>{approval ? new Date(approval.DecisionDate).toLocaleDateString() : 'N/A'}</td>
                                    <td><Badge status={req.StatusType}/></td>
                                    <td>
                                        <button onClick={() => setSelectedReqForIssuance(req)} className="font-medium text-[var(--color-primary)] hover:underline">Process</button>
                                    </td>
                                </tr>
                                );
                            })}
                        </tbody>
                    </table>
                     {pendingRequisitions.length === 0 && <p className="text-center p-8 text-[var(--color-text-muted)]">No requisitions are pending issuance.</p>}
                </div>
            )}

            {activeTab === 'history' && (
                 <div className="table-wrapper">
                    <table className="custom-table">
                        <thead>
                            <tr>
                                <th className="w-10"></th>
                                <th>Issuance ID</th>
                                <th>Requisition #</th>
                                <th>Issued By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {issuances.map(iss => {
                                return (
                                <React.Fragment key={iss.IssuanceID}>
                                    <tr className="cursor-pointer hover:bg-[var(--color-bg-muted)]" onClick={() => handleToggleIssuance(iss.IssuanceID)}>
                                        <td>
                                            <svg className={`w-5 h-5 transition-transform duration-200 ${expandedIssuance === iss.IssuanceID ? 'rotate-90' : ''}`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clipRule="evenodd" />
                                            </svg>
                                        </td>
                                        <td className="font-medium text-[var(--color-text-base)]">{iss.IssuanceID}</td>
                                        <td>{getRequisitionNumber(iss.RequisitionID)}</td>
                                        <td>{getUserName(iss.UserID)}</td>
                                        <td>{new Date(iss.IssuedDate).toLocaleString()}</td>
                                    </tr>
                                    {expandedIssuance === iss.IssuanceID && (
                                        <tr>
                                            <td colSpan={5} className="p-0">
                                                <div className="p-4 bg-slate-50 dark:bg-slate-900/50">
                                                    <div className="px-2">
                                                        <h4 className="font-semibold mb-2 text-[var(--color-text-base)]">Issued Items</h4>
                                                        <table className="w-full text-sm">
                                                            <thead className="text-xs text-[var(--color-text-muted)] uppercase bg-[var(--color-border)]">
                                                                <tr>
                                                                    <th className="px-4 py-2 text-left">Item</th>
                                                                    <th className="px-4 py-2 text-left">Batch ID</th>
                                                                    <th className="px-4 py-2 text-left">Expiry Date</th>
                                                                    <th className="px-4 py-2 text-right">Quantity Issued</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {getIssuanceDetails(iss.IssuanceID).map(detail => (
                                                                    <tr key={detail.issuanceItemId} className="border-b border-[var(--color-border)] bg-[var(--color-bg-surface)]">
                                                                        <td className="px-4 py-2">{detail.itemName}</td>
                                                                        <td className="px-4 py-2 font-mono">{detail.batchId}</td>
                                                                        <td className="px-4 py-2">{detail.expiryDate}</td>
                                                                        <td className="px-4 py-2 text-right font-medium">{detail.quantityIssued.toLocaleString()}</td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </React.Fragment>
                                );
                            })}
                        </tbody>
                    </table>
                     {issuances.length === 0 && <p className="text-center p-8 text-[var(--color-text-muted)]">No issuance history found.</p>}
                </div>
            )}
        </div>

        {selectedReqForIssuance && (
            <IssuanceModal 
                requisition={selectedReqForIssuance}
                inventory={inventory}
                items={items}
                onClose={() => setSelectedReqForIssuance(null)}
                onProcess={(issuanceItems) => {
                    onProcessIssuance(selectedReqForIssuance.RequisitionID, selectedReqForIssuance.RequisitionNumber, issuanceItems);
                    setSelectedReqForIssuance(null);
                }}
                onInitiateAdjustment={() => {
                    if (selectedReqForIssuance) {
                        setSelectedReqForAdjustment(selectedReqForIssuance);
                    }
                    setSelectedReqForIssuance(null);
                }}
            />
        )}

        {selectedReqForAdjustment && (
             <AdjustmentModal 
                requisition={selectedReqForAdjustment}
                items={items}
                onClose={() => setSelectedReqForAdjustment(null)}
                onAdjust={onAdjustRequisition}
            />
        )}
        </>
    );
};

export default IssuancePage;