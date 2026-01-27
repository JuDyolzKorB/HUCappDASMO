
import React, { useState, useMemo } from 'react';
import { Requisition, User, RequisitionItem } from '../types';
import Badge from '../components/Badge';
import RequisitionModal from './requisitions/RequisitionModal';
import RequisitionFormModal from './requisitions/RequisitionFormModal';

interface RequisitionsProps {
  requisitions: Requisition[];
  onUpdateStatus: (id: string, status: 'Approved' | 'Rejected') => void;
  onAddRequisition: (newReq: Omit<Requisition, 'RequisitionID' | 'RequisitionNumber' | 'ApprovalLogs' | 'RequisitionItems'> & { RequisitionItems: Omit<RequisitionItem, 'RequisitionItemID' | 'RequisitionID'>[] }) => void;
  onUpdateRequisition: (requisitionId: string, updatedItems: Omit<RequisitionItem, 'RequisitionItemID' | 'RequisitionID'>[]) => void;
  currentUser: User;
}

const Requisitions: React.FC<RequisitionsProps> = ({ requisitions, onUpdateStatus, onAddRequisition, onUpdateRequisition, currentUser }) => {
  const [selectedRequisition, setSelectedRequisition] = useState<Requisition | null>(null);
  const [editingRequisition, setEditingRequisition] = useState<Requisition | null>(null);
  const [isNewModalOpen, setIsNewModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');

  const filteredRequisitions = useMemo(() => {
    return requisitions.filter(req =>
        req.RequisitionNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
        req.HealthCenterName.toLowerCase().includes(searchTerm.toLowerCase()) ||
        req.RequestedByFullName.toLowerCase().includes(searchTerm.toLowerCase())
    );
  }, [requisitions, searchTerm]);

  const handleEditSubmit = (data: { requisitionId: string; updatedItems: Omit<RequisitionItem, 'RequisitionItemID' | 'RequisitionID'>[] }) => {
    onUpdateRequisition(data.requisitionId, data.updatedItems);
  };

  return (
    <>
      <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
          <div>
            <h2 className="text-2xl font-bold text-[var(--color-text-base)]">Medical Supply Requisitions</h2>
            <p className="text-sm text-[var(--color-text-muted)] mt-1">Manage and track all supply requests from health centers.</p>
          </div>
          <button onClick={() => setIsNewModalOpen(true)} className="btn btn-primary">
            New Requisition
          </button>
        </div>

        <div className="mb-4">
            <input 
                type="text"
                placeholder="Search by Req #, Health Center, or Requester..."
                value={searchTerm}
                onChange={e => setSearchTerm(e.target.value)}
                className="form-input max-w-sm"
            />
        </div>

        <div className="table-wrapper">
          <table className="custom-table">
            <thead>
              <tr>
                <th scope="col">Req #</th>
                <th scope="col">Health Center</th>
                <th scope="col">Requested By</th>
                <th scope="col">Date Requested</th>
                <th scope="col">Total Qty</th>
                <th scope="col">Status</th>
                <th scope="col">Action</th>
              </tr>
            </thead>
            <tbody>
              {filteredRequisitions.map((req) => (
                <tr key={req.RequisitionID}>
                  <td className="font-medium text-[var(--color-text-base)]">{req.RequisitionNumber}</td>
                  <td>{req.HealthCenterName}</td>
                  <td>{req.RequestedByFullName}</td>
                  <td>{new Date(req.RequestedDate).toLocaleDateString()}</td>
                  <td className="text-center">{req.RequisitionItems.reduce((sum, item) => sum + item.QuantityRequested, 0).toLocaleString()}</td>
                  <td><Badge status={req.StatusType} /></td>
                  <td className="space-x-4">
                    <button onClick={() => setSelectedRequisition(req)} className="font-medium text-[var(--color-primary)] hover:underline">View</button>
                    {req.StatusType === 'Pending' && req.UserID === currentUser.UserID && (
                      <button onClick={() => setEditingRequisition(req)} className="font-medium text-amber-600 hover:underline">Edit</button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      {selectedRequisition && <RequisitionModal isOpen={!!selectedRequisition} requisition={selectedRequisition} onClose={() => setSelectedRequisition(null)} onUpdateStatus={onUpdateStatus} currentUser={currentUser}/>}
      {isNewModalOpen && <RequisitionFormModal isOpen={isNewModalOpen} onClose={() => setIsNewModalOpen(false)} onSubmit={onAddRequisition} currentUser={currentUser} />}
      {editingRequisition && <RequisitionFormModal isOpen={!!editingRequisition} onClose={() => setEditingRequisition(null)} onSubmit={handleEditSubmit} currentUser={currentUser} existingRequisition={editingRequisition} />}
    </>
  );
};

export default Requisitions;