
import React, { useState } from 'react';
import { Requisition, User, RequisitionStatus } from '../../types';
import { initialItems, healthCenters } from '../../constants';
import Modal from '../../components/modals/Modal';

type FormRequisitionItem = { ItemID: string; QuantityRequested: number | '' };

interface RequisitionFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (data: any) => void;
  currentUser: User;
  existingRequisition?: Requisition | null;
}

const RequisitionFormModal: React.FC<RequisitionFormModalProps> = ({ isOpen, onClose, onSubmit, currentUser, existingRequisition }) => {
    const isEditing = !!existingRequisition;
    const [healthCenterId, setHealthCenterId] = useState(existingRequisition?.HealthCenterID || healthCenters[0].HealthCenterID);
    const [items, setItems] = useState<FormRequisitionItem[]>(
      existingRequisition?.RequisitionItems.map(item => ({ ItemID: item.ItemID, QuantityRequested: item.QuantityRequested })) || 
      [{ ItemID: initialItems[0].ItemID, QuantityRequested: 1 }]
    );
    const modalTitleId = React.useId();
    
    const handleItemChange = (index: number, field: keyof FormRequisitionItem, value: string) => {
        const newItems = [...items];
        const currentItem = newItems[index];

        if (field === 'QuantityRequested') {
            if (value === '') {
                currentItem.QuantityRequested = '';
            } else {
                const num = parseInt(value, 10);
                if (!isNaN(num) && num >= 0) {
                    currentItem.QuantityRequested = num;
                }
            }
        } else { // 'ItemID'
            currentItem.ItemID = value;
        }
        setItems(newItems);
    };

    const addItem = () => setItems([...items, { ItemID: initialItems[0].ItemID, QuantityRequested: 1 }]);
    const removeItem = (index: number) => setItems(items.filter((_, i) => i !== index));

    const handleSubmit = () => {
        const finalItems = items
            .map(i => ({
                ItemID: i.ItemID,
                QuantityRequested: Number(i.QuantityRequested) || 0,
            }))
            .filter(i => i.QuantityRequested > 0);

        if (finalItems.length === 0) {
            onClose();
            return;
        }

        if (isEditing && existingRequisition) {
            onSubmit({
                requisitionId: existingRequisition.RequisitionID,
                updatedItems: finalItems
            });
        } else {
            const selectedHealthCenter = healthCenters.find(hc => hc.HealthCenterID === healthCenterId);
            if (!selectedHealthCenter) return;
            const newReq = {
                HealthCenterID: healthCenterId,
                HealthCenterName: selectedHealthCenter.Name,
                UserID: currentUser.UserID,
                RequestedByFullName: `${currentUser.FirstName} ${currentUser.LastName}`,
                RequestedDate: new Date().toISOString(),
                StatusType: 'Pending' as RequisitionStatus,
                RequisitionItems: finalItems
            };
            onSubmit(newReq);
        }
        onClose();
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} className="max-w-3xl" titleId={modalTitleId}>
            <div className="p-6">
                <h3 id={modalTitleId} className="text-xl font-semibold text-[var(--color-text-base)] mb-4">{isEditing ? `Edit Requisition ${existingRequisition?.RequisitionNumber}` : 'New Requisition'}</h3>
                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-[var(--color-text-muted)] mb-1">Health Center</label>
                        <select value={healthCenterId} onChange={e => setHealthCenterId(e.target.value)} className="form-select" disabled={isEditing}>
                            {healthCenters.map(hc => <option key={hc.HealthCenterID} value={hc.HealthCenterID}>{hc.Name}</option>)}
                        </select>
                    </div>
                    <div className="space-y-2">
                        <label className="block text-sm font-medium text-[var(--color-text-muted)]">Items</label>
                        {items.map((item, index) => (
                            <div key={index} className="flex items-center space-x-2">
                                <select value={item.ItemID} onChange={e => handleItemChange(index, 'ItemID', e.target.value)} className="form-select">
                                    {initialItems.map(i => <option key={i.ItemID} value={i.ItemID}>{i.ItemName}</option>)}
                                </select>
                                <input 
                                    type="number" 
                                    value={item.QuantityRequested} 
                                    onChange={e => handleItemChange(index, 'QuantityRequested', e.target.value)} 
                                    className="form-input w-40" 
                                    placeholder="Quantity" 
                                    min="1" />
                                <button onClick={() => removeItem(index)} className="text-red-500 hover:text-red-700 text-2xl">&times;</button>
                            </div>
                        ))}
                        <button onClick={addItem} className="text-sm font-semibold text-[var(--color-primary)] hover:underline">+ Add Item</button>
                    </div>
                </div>
                <div className="flex justify-end space-x-3 mt-6 pt-4 border-t border-[var(--color-border)]">
                    <button onClick={onClose} className="btn btn-secondary">Cancel</button>
                    <button onClick={handleSubmit} className="btn btn-primary">{isEditing ? 'Update Requisition' : 'Submit Requisition'}</button>
                </div>
            </div>
        </Modal>
    );
};

export default RequisitionFormModal;
