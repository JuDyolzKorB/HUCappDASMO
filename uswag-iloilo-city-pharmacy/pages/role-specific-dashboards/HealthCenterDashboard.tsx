import React from 'react';
import DashboardCard from '../../components/DashboardCard';
import { Page, Requisition, User } from '../../types';
import Badge from '../../components/Badge';
import RequisitionsIcon from '../../components/icons/RequisitionsIcon';
import FileHeartIcon from '../../components/icons/FileHeartIcon';

interface HealthCenterDashboardProps {
  requisitions: Requisition[];
  currentUser: User;
  setCurrentPage: (page: Page) => void;
}

const HealthCenterDashboard: React.FC<HealthCenterDashboardProps> = ({ requisitions, currentUser, setCurrentPage }) => {
    const myRequisitions = requisitions.filter(r => r.UserID === currentUser.UserID);
    
    const pendingCount = myRequisitions.filter(r => r.StatusType === 'Pending').length;
    const approvedCount = myRequisitions.filter(r => r.StatusType === 'Approved' || r.StatusType === 'Processed').length;
    const rejectedCount = myRequisitions.filter(r => r.StatusType === 'Rejected').length;

    return (
        <div className="space-y-8">
             <div>
                <h1 className="text-2xl font-bold text-[var(--color-text-base)]">Health Center Dashboard</h1>
                <p className="text-sm text-[var(--color-text-muted)]">Manage your medical supply requisitions.</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <DashboardCard title="My Pending Requisitions" value={pendingCount.toString()} icon={<RequisitionsIcon className="w-8 h-8"/>} color="bg-[var(--color-warning)]" />
                <DashboardCard title="My Approved Requisitions" value={approvedCount.toString()} icon={<FileHeartIcon className="w-8 h-8"/>} color="bg-[var(--color-success)]" />
                <DashboardCard title="My Rejected Requisitions" value={rejectedCount.toString()} icon={<FileHeartIcon className="w-8 h-8"/>} color="bg-[var(--color-danger)]" />
            </div>

            <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-md">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="text-lg font-semibold text-[var(--color-text-base)]">My Recent Requisitions</h3>
                    <button onClick={() => setCurrentPage('Requisitions')} className="btn btn-primary">
                        New Requisition
                    </button>
                </div>
                <div className="table-wrapper">
                    <table className="custom-table">
                        <thead>
                            <tr>
                                <th>Req #</th>
                                <th>Date Requested</th>
                                <th>Total Items</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {myRequisitions.slice(0, 5).map(req => (
                                <tr key={req.RequisitionID}>
                                    <td className="font-medium text-[var(--color-text-base)]">{req.RequisitionNumber}</td>
                                    <td>{new Date(req.RequestedDate).toLocaleDateString()}</td>
                                    <td>{req.RequisitionItems.reduce((sum, item) => sum + item.QuantityRequested, 0)}</td>
                                    <td><Badge status={req.StatusType} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default HealthCenterDashboard;