import React from 'react';
import DashboardCard from '../../components/DashboardCard';
import { Page, PurchaseOrder, CentralInventoryBatch, Item, Receiving, User } from '../../types';
import InventoryIcon from '../../components/icons/InventoryIcon';
import ChecklistIcon from '../../components/icons/ChecklistIcon';
import ReportsIcon from '../../components/icons/ReportsIcon';
import Badge from '../../components/Badge';

interface AccountingDashboardProps {
    purchaseOrders: PurchaseOrder[];
    inventory: CentralInventoryBatch[];
    items: Item[];
    receivings: Receiving[];
    users: User[];
    setCurrentPage: (page: Page) => void;
}

const AccountingDashboard: React.FC<AccountingDashboardProps> = ({ purchaseOrders, inventory, items, receivings, users, setCurrentPage }) => {
    const totalInventoryValue = inventory.reduce((acc, batch) => acc + batch.QuantityOnHand * batch.UnitCost, 0);
    const completedPOs = purchaseOrders.filter(po => po.StatusType === 'Completed').length;
    const approvedPOs = purchaseOrders.filter(po => po.StatusType === 'Approved');
    
    const getUserFullName = (userId: string) => {
        const user = users.find(u => u.UserID === userId);
        return user ? `${user.FirstName} ${user.LastName}` : 'Unknown';
    };

     const getPONumber = (poid: string) => purchaseOrders.find(p => p.POID === poid)?.PONumber || 'N/A';

    return (
        <div className="space-y-8">
            <div>
                <h1 className="text-2xl font-bold text-[var(--color-text-base)]">Accounting Dashboard</h1>
                <p className="text-sm text-[var(--color-text-muted)]">Financial overview and receiving activity.</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <DashboardCard title="Total Inventory Value" value={`$${totalInventoryValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`} icon={<InventoryIcon className="w-8 h-8"/>} color="bg-[var(--color-primary)]" />
                <DashboardCard title="POs Awaiting Payment" value={completedPOs.toString()} icon={<ChecklistIcon className="w-8 h-8"/>} color="bg-[var(--color-success)]" />
                 <div 
                    onClick={() => setCurrentPage('Reports')} 
                    className="bg-[var(--color-bg-surface)] p-5 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 relative overflow-hidden cursor-pointer flex items-center justify-between pl-4 group"
                >
                    <div className="absolute top-0 left-0 h-full w-1.5 bg-[var(--color-success)]"></div>
                    <div>
                        <p className="text-sm font-medium text-[var(--color-text-muted)] tracking-wide">Compliance</p>
                        <p className="text-xl font-bold text-[var(--color-text-base)] mt-2 group-hover:text-[var(--color-primary)]">Generate Reports</p>
                    </div>
                    <div className="text-slate-300">
                        <ReportsIcon className="w-8 h-8" />
                    </div>
                </div>
            </div>

            <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-md">
                <h3 className="text-lg font-semibold text-[var(--color-text-base)] mb-4">Approved Purchase Orders (Pending Delivery)</h3>
                <div className="table-wrapper">
                    <table className="custom-table">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Supplier</th>
                                <th>Date Approved</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {approvedPOs.slice(0, 10).map(po => {
                                const approval = po.ApprovalLogs.find(log => log.Decision === 'Approved');
                                return (
                                    <tr key={po.POID}>
                                        <td className="font-medium text-[var(--color-text-base)]">{po.PONumber}</td>
                                        <td>{po.SupplierName}</td>
                                        <td>{approval ? new Date(approval.DecisionDate).toLocaleDateString() : 'N/A'}</td>
                                        <td><Badge status={po.StatusType} /></td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                     {approvedPOs.length === 0 && (
                        <div className="text-center py-8 text-[var(--color-text-muted)]">
                            <p>No purchase orders are currently approved and awaiting delivery.</p>
                        </div>
                    )}
                </div>
            </div>

            <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-md">
                <h3 className="text-lg font-semibold text-[var(--color-text-base)] mb-4">Recent Receiving Activity</h3>
                <div className="table-wrapper">
                    <table className="custom-table">
                        <thead>
                            <tr>
                                <th>Receiving ID</th>
                                <th>PO Number</th>
                                <th>Received By</th>
                                <th>Date Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            {receivings.slice(0, 10).map(rec => (
                                <tr key={rec.ReceivingID}>
                                    <td className="font-medium text-[var(--color-text-base)]">{rec.ReceivingID}</td>
                                    <td>{getPONumber(rec.POID)}</td>
                                    <td>{getUserFullName(rec.UserID)}</td>
                                    <td>{new Date(rec.ReceivedDate).toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                     {receivings.length === 0 && (
                        <div className="text-center py-8 text-[var(--color-text-muted)]">
                            <p>No receiving activities have been logged recently.</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AccountingDashboard;