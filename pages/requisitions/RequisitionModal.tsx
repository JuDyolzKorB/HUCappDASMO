
import React from 'react';
import { Requisition, User } from '../../types';
import Badge from '../../components/Badge';
import { initialItems } from '../../constants';
import Modal from '../../components/modals/Modal';

interface RequisitionModalProps {
  isOpen: boolean;
  requisition: Requisition;
  onClose: () => void;
  onUpdateStatus: (id: string, status: 'Approved' | 'Rejected') => void;
  currentUser: User;
}

const RequisitionModal: React.FC<RequisitionModalProps> = ({ isOpen, requisition, onClose, onUpdateStatus, currentUser }) => {
    const getItemName = (itemId: string) => initialItems.find(i => i.ItemID === itemId)?.ItemName || 'Unknown Item';
    const canApprove = ['Administrator', 'Head Pharmacist'].includes(currentUser.Role);
    const modalTitleId = React.useId();

    return (
        <Modal isOpen={isOpen} onClose={onClose} className="max-w-2xl" titleId={modalTitleId}>
            <div className="p-6">
                <div className="flex justify-between items-center mb-4">
                    <h3 id={modalTitleId} className="text-xl font-semibold text-[var(--color-text-base)]">Requisition Details - {requisition.RequisitionNumber}</h3>
                    <button onClick={onClose} className="text-[var(--color-text-subtle)] hover:text-[var(--color-text-base)] text-2xl">&times;</button>
                </div>
                <div className="grid grid-cols-2 gap-4 text-sm mb-4 text-[var(--color-text-muted)]">
                    <div><strong>Health Center:</strong> {requisition.HealthCenterName}</div>
                    <div><strong>Status:</strong> <Badge status={requisition.StatusType} /></div>
                    <div><strong>Requested By:</strong> {requisition.RequestedByFullName}</div>
                    <div><strong>Date Requested:</strong> {new Date(requisition.RequestedDate).toLocaleDateString()}</div>
                </div>

                <h4 className="font-semibold text-[var(--color-text-base)] mb-2">Requested Items</h4>
                <div className="max-h-48 overflow-y-auto border border-[var(--color-border)] rounded-lg p-2 mb-4">
                    <ul className="text-[var(--color-text-muted)]">
                        {requisition.RequisitionItems.map(item => (
                            <li key={item.RequisitionItemID} className="flex justify-between p-2 border-b border-[var(--color-border)] last:border-b-0">
                                <span>{getItemName(item.ItemID)}</span>
                                <span>Qty: {item.QuantityRequested.toLocaleString()}</span>
                            </li>
                        ))}
                    </ul>
                </div>

                <h4 className="font-semibold text-[var(--color-text-base)] mb-2 mt-4">Approval History</h4>
                <div className="max-h-32 overflow-y-auto border border-[var(--color-border)] rounded-lg p-2 mb-4">
                    {requisition.ApprovalLogs.length > 0 ? (
                        <ul className="text-[var(--color-text-muted)]">
                            {requisition.ApprovalLogs.map((history) => (
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
                
                {requisition.StatusType === 'Pending' && canApprove && (
                    <div className="mt-6 pt-4 border-t border-[var(--color-border)]">
                        <div className="flex justify-end space-x-3">
                            <button onClick={() => { onUpdateStatus(requisition.RequisitionID, 'Rejected'); onClose(); }} className="btn btn-danger">Reject</button>
                            <button onClick={() => { onUpdateStatus(requisition.RequisitionID, 'Approved'); onClose(); }} className="btn btn-success">Approve</button>
                        </div>
                    </div>
                )}
            </div>
        </Modal>
    );
};

export default RequisitionModal;
