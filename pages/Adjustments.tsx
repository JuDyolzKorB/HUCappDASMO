
// FIX: Import `useMemo` from react.
import React, { useState, useEffect, useMemo } from 'react';
import { NoticeOfIssuance, User, CentralInventoryBatch, Item, Requisition, Issuance, IssuanceItem, RequisitionAdjustment, RequisitionAdjustmentDetail, IssuedType, RequisitionItem } from '../types';

const NoticeOfIssuanceForm: React.FC<{
    inventory: CentralInventoryBatch[];
    items: Item[];
    currentUser: User;
    onAddNotice: (notice: Omit<NoticeOfIssuance, 'IssueID'>) => void;
    context: { itemId: string; batchId: string } | null;
    clearContext: () => void;
}> = ({ inventory, items, currentUser, onAddNotice, context, clearContext }) => {
    
    const [selectedItemId, setSelectedItemId] = useState(context?.itemId || '');
    const [selectedBatchId, setSelectedBatchId] = useState(context?.batchId || '');
    const [quantity, setQuantity] = useState(1);
    const [issueType, setIssueType] = useState<IssuedType>('Damaged');
    const [remarks, setRemarks] = useState('');

    useEffect(() => {
        if (context) {
            setSelectedItemId(context.itemId);
            setSelectedBatchId(context.batchId);
        }
        return () => {
            clearContext();
        }
    }, [context, clearContext]);

    const availableBatches = useMemo(() => {
        return inventory.filter(b => b.ItemID === selectedItemId && b.QuantityOnHand > 0);
    }, [selectedItemId, inventory]);
    
    const selectedBatch = inventory.find(b => b.BatchID === selectedBatchId);

    const handleSubmit = (e: React.FormEvent) => {
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
        // Reset form
        setSelectedItemId('');
        setSelectedBatchId('');
        setQuantity(1);
        setIssueType('Damaged');
        setRemarks('');
    };

    return (
        <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
            <h2 className="text-xl font-semibold text-[var(--color-text-base)] mb-1">Inventory Disposal</h2>
            <p className="text-sm text-[var(--color-text-muted)] mb-4">Dispose of items from inventory due to damage, expiration, or other discrepancies. This action permanently removes them from stock.</p>
            <form onSubmit={handleSubmit} className="space-y-4 border-t border-[var(--color-border)] pt-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text-muted)]">Item</label>
                        <select value={selectedItemId} onChange={e => {setSelectedItemId(e.target.value); setSelectedBatchId('');}} className="form-select mt-1">
                            <option value="">Select an item</option>
                            {items.map(item => <option key={item.ItemID} value={item.ItemID}>{item.ItemName}</option>)}
                        </select>
                    </div>
                     <div>
                        <label className="block text-sm font-medium text-[var(--color-text-muted)]">Batch</label>
                        <select value={selectedBatchId} onChange={e => setSelectedBatchId(e.target.value)} className="form-select mt-1" disabled={!selectedItemId}>
                             <option value="">Select a batch</option>
                             {availableBatches.map(b => <option key={b.BatchID} value={b.BatchID}>{`Batch ${b.BatchID.slice(-4)} (Expiry: ${new Date(b.ExpiryDate).toLocaleDateString()}, Qty: ${b.QuantityOnHand})`}</option>)}
                        </select>
                    </div>
                </div>
                 <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text-muted)]">Quantity to Dispose</label>
                        <input type="number" value={quantity} onChange={e => setQuantity(parseInt(e.target.value, 10))} className="form-input mt-1" max={selectedBatch?.QuantityOnHand} min="1" disabled={!selectedBatchId} />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text-muted)]">Reason</label>
                        <select value={issueType} onChange={e => setIssueType(e.target.value as IssuedType)} className="form-select mt-1">
                           <option>Damaged</option>
                           <option>Expired</option>
                           <option>Count Discrepancy</option>
                           <option>Other</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label className="block text-sm font-medium text-[var(--color-text-muted)]">Remarks</label>
                    <textarea value={remarks} onChange={e => setRemarks(e.target.value)} className="form-input mt-1" rows={3}></textarea>
                </div>
                <div className="text-right pt-2">
                    <button type="submit" className="btn btn-primary">Confirm Disposal</button>
                </div>
            </form>
        </div>
    );
};


const RequisitionAdjustmentForm: React.FC<{
    requisitions: Requisition[];
    issuances: Issuance[];
    issuanceItems: IssuanceItem[];
    inventory: CentralInventoryBatch[];
    items: Item[];
    currentUser: User;
    onAddRequisitionAdjustment: (
        adjustment: Omit<RequisitionAdjustment, 'RequisitionAdjustmentID'>,
        details: Omit<RequisitionAdjustmentDetail, 'RADetailID' | 'RequisitionAdjustmentID'>[]
    ) => void;
}> = ({ requisitions, issuances, issuanceItems, inventory, items, currentUser, onAddRequisitionAdjustment }) => {
    
    const processedRequisitions = requisitions.filter(r => r.StatusType === 'Processed');
    const [selectedReqId, setSelectedReqId] = useState('');
    const [reason, setReason] = useState('');
    const [itemsToReturn, setItemsToReturn] = useState<{requisitionItemId: string, batchId: string, quantity: number}[]>([]);

    const selectedRequisition = processedRequisitions.find(r => r.RequisitionID === selectedReqId);
    
    const issuedItemsForReq = useMemo(() => {
        if (!selectedRequisition) return [];
        const issuance = issuances.find(i => i.RequisitionID === selectedRequisition.RequisitionID);
        if (!issuance) return [];
        return issuanceItems.filter(ii => ii.IssuanceID === issuance.IssuanceID);
    }, [selectedRequisition, issuances, issuanceItems]);

    const getItemName = (itemId: string) => items.find(i => i.ItemID === itemId)?.ItemName || 'Unknown';
    const getRequisitionItem = (reqItemId: string): RequisitionItem | undefined => {
        return selectedRequisition?.RequisitionItems.find(ri => ri.RequisitionItemID === reqItemId);
    }
    
    const handleReturnQtyChange = (issuedItem: IssuanceItem, qty: number) => {
        const existing = itemsToReturn.find(i => i.requisitionItemId === issuedItem.RequisitionItemID && i.batchId === issuedItem.BatchID);
        if (qty > 0) {
            if (existing) {
                setItemsToReturn(itemsToReturn.map(i => i === existing ? {...i, quantity: qty} : i));
            } else {
                setItemsToReturn([...itemsToReturn, {requisitionItemId: issuedItem.RequisitionItemID, batchId: issuedItem.BatchID, quantity: qty}]);
            }
        } else {
            setItemsToReturn(itemsToReturn.filter(i => i !== existing));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if(!selectedReqId || itemsToReturn.length === 0 || !reason) return;
        
        const adjustment: Omit<RequisitionAdjustment, 'RequisitionAdjustmentID'> = {
            RequisitionID: selectedReqId,
            UserID: currentUser.UserID,
            AdjustmentType: 'Return to Stock',
            AdjustmentDate: new Date().toISOString(),
            Reason: reason
        };
        const details = itemsToReturn.map(item => ({
            BatchID: item.batchId,
            QuantityAdjusted: item.quantity
        }));

        onAddRequisitionAdjustment(adjustment, details);
        // Reset form
        setSelectedReqId('');
        setReason('');
        setItemsToReturn([]);
    };

    return (
         <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
            <h2 className="text-xl font-semibold text-[var(--color-text-base)] mb-1">Process Item Return</h2>
            <p className="text-sm text-[var(--color-text-muted)] mb-4">Return items from a previously issued requisition back into inventory. This directly increases stock levels.</p>
            <form onSubmit={handleSubmit} className="space-y-4 border-t border-[var(--color-border)] pt-4">
                 <div>
                    <label className="block text-sm font-medium text-[var(--color-text-muted)]">Processed Requisition</label>
                    <select value={selectedReqId} onChange={e => setSelectedReqId(e.target.value)} className="form-select mt-1">
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
                                    <span className="col-span-2">{getItemName(reqItem.ItemID)} (from Batch {ii.BatchID.slice(-4)})</span>
                                    <span>Issued: {ii.QuantityIssued}</span>
                                    <input type="number" placeholder="Qty to Return" max={ii.QuantityIssued} min="0" 
                                           onChange={e => handleReturnQtyChange(ii, parseInt(e.target.value) || 0)}
                                           className="form-input p-1" />
                                </div>
                                )
                            })}
                        </div>
                    </div>
                )}
                <div>
                    <label className="block text-sm font-medium text-[var(--color-text-muted)]">Reason for Return</label>
                    <textarea value={reason} onChange={e => setReason(e.target.value)} className="form-input mt-1" rows={3} required></textarea>
                </div>
                 <div className="text-right pt-2">
                    <button type="submit" className="btn btn-primary">Process Return</button>
                </div>
            </form>
        </div>
    );
};


const AdjustmentsPage: React.FC<{
    notices: NoticeOfIssuance[];
    inventory: CentralInventoryBatch[];
    items: Item[];
    onAddNotice: (newNotice: Omit<NoticeOfIssuance, 'IssueID'>) => void;
    currentUser: User;
    requisitions: Requisition[];
    issuances: Issuance[];
    issuanceItems: IssuanceItem[];
    requisitionAdjustments: RequisitionAdjustment[];
    onAddRequisitionAdjustment: (
        adjustment: Omit<RequisitionAdjustment, 'RequisitionAdjustmentID'>,
        details: Omit<RequisitionAdjustmentDetail, 'RADetailID' | 'RequisitionAdjustmentID'>[]
    ) => void;
    adjustmentContext: { itemId: string; batchId: string } | null;
    clearAdjustmentContext: () => void;
}> = (props) => {
    return (
        <div className="space-y-10">
             <div>
                <h1 className="text-3xl font-bold text-[var(--color-text-base)]">Inventory Adjustments</h1>
                <p className="text-[var(--color-text-muted)] mt-1">Directly modify inventory levels for returns, disposals, or other corrections.</p>
            </div>
            <NoticeOfIssuanceForm 
                inventory={props.inventory}
                items={props.items}
                currentUser={props.currentUser}
                onAddNotice={props.onAddNotice}
                context={props.adjustmentContext}
                clearContext={props.clearAdjustmentContext}
            />
            <RequisitionAdjustmentForm {...props} />
        </div>
    );
};

export default AdjustmentsPage;