import React, { useState, useEffect, useMemo } from 'react';
import { NoticeOfIssuance, User, CentralInventoryBatch, Item, Requisition, Issuance, IssuanceItem, RequisitionAdjustment, RequisitionAdjustmentDetail, IssuedType, RequisitionItem } from '../types';

interface AdjustmentsPageProps {
    notices: NoticeOfIssuance[];
    inventory: CentralInventoryBatch[];
    items: Item[];
    onAddNotice: (newNotice: Omit<NoticeOfIssuance, 'IssueID'>) => void;
    currentUser: User;
    requisitions: Requisition[];
    issuances: Issuance[];
    issuanceItems: IssuanceItem[];
    requisitionAdjustments: RequisitionAdjustment[];
    requisitionAdjustmentDetails: RequisitionAdjustmentDetail[];
    onAddRequisitionAdjustment: (
        adjustment: Omit<RequisitionAdjustment, 'RequisitionAdjustmentID'>,
        details: Omit<RequisitionAdjustmentDetail, 'RADetailID' | 'RequisitionAdjustmentID'>[]
    ) => void;
    adjustmentContext: { itemId: string; batchId: string } | null;
    clearAdjustmentContext: () => void;
    users: User[];
}

const AdjustmentsPage: React.FC<AdjustmentsPageProps> = (props) => {
    const { notices, inventory, items, currentUser, requisitions, issuances, issuanceItems, requisitionAdjustments, requisitionAdjustmentDetails, users, onAddNotice, onAddRequisitionAdjustment, adjustmentContext, clearAdjustmentContext } = props;

    // State for tabs
    const [activeTab, setActiveTab] = useState<'perform' | 'history'>('perform');
    
    // State for Notice of Issuance Form (Disposal)
    const [selectedItemId, setSelectedItemId] = useState(adjustmentContext?.itemId || '');
    const [selectedBatchId, setSelectedBatchId] = useState(adjustmentContext?.batchId || '');
    const [quantity, setQuantity] = useState(1);
    const [issueType, setIssueType] = useState<IssuedType>('Damaged');
    const [remarks, setRemarks] = useState('');

    // State for Requisition Adjustment Form (Returns)
    const [selectedReqId, setSelectedReqId] = useState('');
    const [reason, setReason] = useState('');
    const [itemsToReturn, setItemsToReturn] = useState<{requisitionItemId: string, batchId: string, quantity: number}[]>([]);
    
    // State for history view
    const [expandedAdjustment, setExpandedAdjustment] = useState<string | null>(null);
    
    // Logic for Disposal Form
    useEffect(() => {
        if (adjustmentContext) {
            setSelectedItemId(adjustmentContext.itemId);
            setSelectedBatchId(adjustmentContext.batchId);
        }
        return () => {
            clearAdjustmentContext();
        }
    }, [adjustmentContext, clearAdjustmentContext]);

    const availableBatches = useMemo(() => inventory.filter(b => b.ItemID === selectedItemId && b.QuantityOnHand > 0), [selectedItemId, inventory]);
    const selectedBatch = inventory.find(b => b.BatchID === selectedBatchId);

    const handleDisposalSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedBatchId || quantity <= 0) return;
        onAddNotice({
            BatchID: selectedBatchId,
            UserID: currentUser.UserID,
            IssuedDate: new Date().toISOString(),
            IssuedType: issueType,
            QuantityIssued: quantity,
            StatusType: 'Completed',
            Remarks: remarks,
        });
        setSelectedItemId('');
        setSelectedBatchId('');
        setQuantity(1);
        setIssueType('Damaged');
        setRemarks('');
    };

    // Logic for Return Form
    const processedRequisitions = useMemo(() => requisitions.filter(r => r.StatusType === 'Processed'), [requisitions]);
    const selectedRequisition = useMemo(() => processedRequisitions.find(r => r.RequisitionID === selectedReqId), [processedRequisitions, selectedReqId]);
    const issuedItemsForReq = useMemo(() => {
        if (!selectedRequisition) return [];
        const issuance = issuances.find(i => i.RequisitionID === selectedRequisition.RequisitionID);
        if (!issuance) return [];
        return issuanceItems.filter(ii => ii.IssuanceID === issuance.IssuanceID);
    }, [selectedRequisition, issuances, issuanceItems]);

    const getRequisitionItem = (reqItemId: string): RequisitionItem | undefined => selectedRequisition?.RequisitionItems.find(ri => ri.RequisitionItemID === reqItemId);
    const handleReturnQtyChange = (issuedItem: IssuanceItem, qty: number) => {
        const existing = itemsToReturn.find(i => i.requisitionItemId === issuedItem.RequisitionItemID && i.batchId === issuedItem.BatchID);
        if (qty > 0) {
            setItemsToReturn(prev => existing ? prev.map(i => i === existing ? {...i, quantity: qty} : i) : [...prev, {requisitionItemId: issuedItem.RequisitionItemID, batchId: issuedItem.BatchID, quantity: qty}]);
        } else {
            setItemsToReturn(prev => prev.filter(i => i !== existing));
        }
    };
    
    const handleReturnSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if(!selectedReqId || itemsToReturn.length === 0 || !reason) return;
        const adjustment: Omit<RequisitionAdjustment, 'RequisitionAdjustmentID'> = {
            RequisitionID: selectedReqId, UserID: currentUser.UserID, AdjustmentType: 'Return to Stock', AdjustmentDate: new Date().toISOString(), Reason: reason
        };
        const details = itemsToReturn.map(item => ({ BatchID: item.batchId, QuantityAdjusted: item.quantity }));
        onAddRequisitionAdjustment(adjustment, details);
        setSelectedReqId('');
        setReason('');
        setItemsToReturn([]);
    };

    // Logic for History View
    const toggleAdjustment = (adjustmentId: string) => setExpandedAdjustment(prev => (prev === adjustmentId ? null : adjustmentId));
    const getUserFullName = (userId: string) => users.find(u => u.UserID === userId) ? `${users.find(u => u.UserID === userId)?.FirstName} ${users.find(u => u.UserID === userId)?.LastName}` : 'Unknown';
    const getItemNameFromBatch = (batchId: string) => items.find(i => i.ItemID === inventory.find(b => b.BatchID === batchId)?.ItemID)?.ItemName || 'Unknown Item';
    const getRequisitionNumber = (reqId: string) => requisitions.find(r => r.RequisitionID === reqId)?.RequisitionNumber || 'N/A';

    return (
        <div className="bg-[var(--color-bg-surface)] p-6 md:p-8 rounded-xl border border-[var(--color-border)] shadow-sm">
             <div className="flex justify-between items-center mb-6">
                <h2 className="text-2xl font-bold text-[var(--color-text-base)]">Inventory Adjustments</h2>
                <div className="flex space-x-1 bg-[var(--color-bg-base)] p-1 rounded-lg">
                    <button onClick={() => setActiveTab('perform')} className={`px-4 py-1.5 text-sm font-semibold rounded-md transition-colors ${activeTab === 'perform' ? 'bg-[var(--color-bg-surface)] shadow-sm text-[var(--color-primary)]' : 'text-[var(--color-text-muted)] hover:bg-white/50'}`}>Perform Adjustment</button>
                    <button onClick={() => setActiveTab('history')} className={`px-4 py-1.5 text-sm font-semibold rounded-md transition-colors ${activeTab === 'history' ? 'bg-[var(--color-bg-surface)] shadow-sm text-[var(--color-primary)]' : 'text-[var(--color-text-muted)] hover:bg-white/50'}`}>Adjustment History</button>
                </div>
            </div>

            {activeTab === 'perform' && (
                <div className="space-y-10">
                     {/* Disposal Form */}
                    <div>
                        <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-1">Inventory Disposal</h3>
                        <p className="text-sm text-[var(--color-text-muted)] mb-6">Dispose of items from inventory due to damage, expiration, or other discrepancies. This action permanently removes them from stock.</p>
                        <form onSubmit={handleDisposalSubmit} className="space-y-4 border-t border-[var(--color-border)] pt-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Item</label>
                                    <select value={selectedItemId} onChange={e => {setSelectedItemId(e.target.value); setSelectedBatchId('');}} className="form-select">
                                        <option value="">Select an item</option>
                                        {items.map(item => <option key={item.ItemID} value={item.ItemID}>{item.ItemName}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Batch</label>
                                    <select value={selectedBatchId} onChange={e => setSelectedBatchId(e.target.value)} className="form-select" disabled={!selectedItemId}>
                                        <option value="">Select a batch</option>
                                        {availableBatches.map(b => <option key={b.BatchID} value={b.BatchID}>{`Batch ${b.BatchID.slice(-4)} (Expiry: ${new Date(b.ExpiryDate).toLocaleDateString()}, Qty: ${b.QuantityOnHand})`}</option>)}
                                    </select>
                                </div>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Quantity to Dispose</label>
                                    <input type="number" value={quantity} onChange={e => setQuantity(parseInt(e.target.value, 10))} className="form-input" max={selectedBatch?.QuantityOnHand} min="1" disabled={!selectedBatchId} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Reason</label>
                                    <select value={issueType} onChange={e => setIssueType(e.target.value as IssuedType)} className="form-select">
                                        <option>Damaged</option><option>Expired</option><option>Count Discrepancy</option><option>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Remarks</label>
                                <textarea value={remarks} onChange={e => setRemarks(e.target.value)} className="form-input" rows={3}></textarea>
                            </div>
                            <div className="text-right pt-2"><button type="submit" className="btn btn-primary">Confirm Disposal</button></div>
                        </form>
                    </div>
                    
                    <div className="my-8 border-t border-dashed border-[var(--color-border)]"></div>

                    {/* Return Form */}
                    <div>
                         <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-1">Process Item Return</h3>
                        <p className="text-sm text-[var(--color-text-muted)] mb-6">Return items from a previously issued requisition back into inventory. This directly increases stock levels.</p>
                        <form onSubmit={handleReturnSubmit} className="space-y-4 border-t border-[var(--color-border)] pt-6">
                            <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Processed Requisition</label>
                                <select value={selectedReqId} onChange={e => setSelectedReqId(e.target.value)} className="form-select">
                                    <option value="">Select a requisition</option>
                                    {processedRequisitions.map(r => <option key={r.RequisitionID} value={r.RequisitionID}>{r.RequisitionNumber} - {r.HealthCenterName}</option>)}
                                </select>
                            </div>
                            {selectedRequisition && issuedItemsForReq.length > 0 && (
                                <div>
                                    <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-2">Items to Return</label>
                                    <div className="space-y-2 max-h-60 overflow-y-auto border border-[var(--color-border)] p-3 rounded-lg">
                                        {issuedItemsForReq.map(ii => {
                                            const reqItem = getRequisitionItem(ii.RequisitionItemID);
                                            if (!reqItem) return null;
                                            return (
                                            <div key={ii.IssuanceItemID} className="grid grid-cols-4 items-center gap-2 text-[var(--color-text-muted)]">
                                                <span className="col-span-2">{items.find(i => i.ItemID === reqItem.ItemID)?.ItemName || 'Unknown'} (from Batch {ii.BatchID.slice(-4)})</span>
                                                <span>Issued: {ii.QuantityIssued}</span>
                                                <input type="number" placeholder="Qty to Return" max={ii.QuantityIssued} min="0" onChange={e => handleReturnQtyChange(ii, parseInt(e.target.value) || 0)} className="form-input p-1" />
                                            </div>
                                            )
                                        })}
                                    </div>
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Reason for Return</label>
                                <textarea value={reason} onChange={e => setReason(e.target.value)} className="form-input" rows={3} required></textarea>
                            </div>
                            <div className="text-right pt-2"><button type="submit" className="btn btn-primary">Process Return</button></div>
                        </form>
                    </div>
                </div>
            )}

            {activeTab === 'history' && (
                <div className="space-y-10">
                     <div>
                        <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-4">Inventory Disposal History</h3>
                        <div className="table-wrapper">
                            <table className="custom-table">
                                <thead><tr><th>Issue ID</th><th>Item</th><th>Batch</th><th>Quantity</th><th>Reason</th><th>Disposed By</th><th>Date</th></tr></thead>
                                <tbody>
                                    {notices.map(notice => (
                                        <tr key={notice.IssueID}><td className="font-mono">{notice.IssueID}</td><td>{getItemNameFromBatch(notice.BatchID)}</td><td>{notice.BatchID}</td><td>{notice.QuantityIssued}</td><td>{notice.IssuedType}</td><td>{getUserFullName(notice.UserID)}</td><td>{new Date(notice.IssuedDate).toLocaleString()}</td></tr>
                                    ))}
                                </tbody>
                            </table>
                            {notices.length === 0 && <p className="text-center p-8 text-[var(--color-text-muted)]">No disposal history found.</p>}
                        </div>
                    </div>

                    <div>
                        <h3 className="text-xl font-semibold text-[var(--color-text-base)] mb-4">Item Return History</h3>
                        <div className="table-wrapper">
                            <table className="custom-table">
                                <thead><tr><th className="w-10"></th><th>Adjustment ID</th><th>Requisition #</th><th>Adjusted By</th><th>Date</th></tr></thead>
                                <tbody>
                                    {requisitionAdjustments.map(adj => (
                                        <React.Fragment key={adj.RequisitionAdjustmentID}>
                                            <tr className="cursor-pointer hover:bg-[var(--color-bg-muted)]" onClick={() => toggleAdjustment(adj.RequisitionAdjustmentID)}>
                                                <td><svg className={`w-5 h-5 transition-transform duration-200 ${expandedAdjustment === adj.RequisitionAdjustmentID ? 'rotate-90' : ''}`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fillRule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clipRule="evenodd" /></svg></td>
                                                <td className="font-mono">{adj.RequisitionAdjustmentID}</td><td>{getRequisitionNumber(adj.RequisitionID)}</td><td>{getUserFullName(adj.UserID)}</td><td>{new Date(adj.AdjustmentDate).toLocaleString()}</td>
                                            </tr>
                                            {expandedAdjustment === adj.RequisitionAdjustmentID && (
                                                <tr>
                                                    <td colSpan={5} className="p-4 bg-[var(--color-bg-muted)]">
                                                        <div className="px-4">
                                                            <p className="text-sm text-[var(--color-text-muted)] mb-2"><strong>Reason:</strong> {adj.Reason}</p>
                                                            <h4 className="font-semibold mb-2 text-[var(--color-text-base)]">Returned Items</h4>
                                                            <table className="w-full text-sm">
                                                                <thead className="text-xs text-[var(--color-text-muted)] uppercase bg-[var(--color-border)]"><tr><th className="px-4 py-2 text-left">Item</th><th className="px-4 py-2 text-left">Batch ID</th><th className="px-4 py-2 text-right">Quantity Returned</th></tr></thead>
                                                                <tbody>
                                                                    {requisitionAdjustmentDetails.filter(d => d.RequisitionAdjustmentID === adj.RequisitionAdjustmentID).map(detail => (<tr key={detail.RADetailID} className="border-b border-[var(--color-border)] bg-[var(--color-bg-surface)]"><td className="px-4 py-2">{getItemNameFromBatch(detail.BatchID)}</td><td className="px-4 py-2 font-mono">{detail.BatchID}</td><td className="px-4 py-2 text-right font-medium">{detail.QuantityAdjusted.toLocaleString()}</td></tr>))}
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>
                                            )}
                                        </React.Fragment>
                                    ))}
                                </tbody>
                            </table>
                            {requisitionAdjustments.length === 0 && <p className="text-center p-8 text-[var(--color-text-muted)]">No item return history found.</p>}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AdjustmentsPage;