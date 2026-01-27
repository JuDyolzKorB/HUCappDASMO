import React from 'react';
import DashboardCard from '../../components/DashboardCard';
import { Page, Requisition, PurchaseOrder, CentralInventoryBatch, Item } from '../../types';
import IssuanceIcon from '../../components/icons/IssuanceIcon';
import ReceivingIcon from '../../components/icons/ReceivingIcon';
import AlertIcon from '../../components/icons/AlertIcon';
import TruckIcon from '../../components/icons/TruckIcon';

interface WarehouseDashboardProps {
    requisitions: Requisition[];
    purchaseOrders: PurchaseOrder[];
    inventory: CentralInventoryBatch[];
    items: Item[];
    setCurrentPage: (page: Page) => void;
}

const WarehouseDashboard: React.FC<WarehouseDashboardProps> = ({ requisitions, purchaseOrders, inventory, items, setCurrentPage }) => {
    const pendingIssuanceCount = requisitions.filter(r => r.StatusType === 'Approved').length;
    const pendingReceivingCount = purchaseOrders.filter(po => po.StatusType === 'Approved').length;
    const nearlyExpiredItems = inventory.filter(batch => {
        const expiryDate = new Date(batch.ExpiryDate);
        const threeMonthsFromNow = new Date();
        threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
        return expiryDate < threeMonthsFromNow;
    }).length;

    return (
        <div className="space-y-8">
            <div className="flex justify-between items-start">
                <div>
                    <h1 className="text-2xl font-bold text-[var(--color-text-base)]">Warehouse Dashboard</h1>
                    <p className="text-sm text-[var(--color-text-muted)] mt-1">Manage incoming and outgoing inventory.</p>
                </div>
                <button onClick={() => setCurrentPage('Purchase Orders')} className="btn btn-primary whitespace-nowrap">
                    Create New PO
                </button>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <DashboardCard title="Requisitions to Issue" value={pendingIssuanceCount.toString()} icon={<IssuanceIcon className="w-8 h-8"/>} color="bg-gradient-teal" />
                <DashboardCard title="POs Awaiting Receiving" value={pendingReceivingCount.toString()} icon={<TruckIcon className="w-8 h-8"/>} color="bg-gradient-cyan" />
                <DashboardCard title="Nearly Expired Items" value={nearlyExpiredItems.toString()} icon={<AlertIcon className="w-8 h-8"/>} color="bg-gradient-amber" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-md">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-semibold text-[var(--color-text-base)]">Pending Issuance</h3>
                        <button onClick={() => setCurrentPage('Issuance')} className="text-sm font-medium text-[var(--color-primary)] hover:underline">View All</button>
                    </div>
                    <div className="table-wrapper">
                        <table className="custom-table">
                            <thead><tr><th>Req #</th><th>Health Center</th><th>Date</th></tr></thead>
                            <tbody>
                                {requisitions.filter(r => r.StatusType === 'Approved').slice(0, 5).map(req => (
                                    <tr key={req.RequisitionID}>
                                        <td className="font-medium text-[var(--color-text-base)]">{req.RequisitionNumber}</td>
                                        <td>{req.HealthCenterName}</td>
                                        <td>{new Date(req.RequestedDate).toLocaleDateString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                 <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-md">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="text-lg font-semibold text-[var(--color-text-base)]">Awaiting Receiving</h3>
                        <button onClick={() => setCurrentPage('Receiving')} className="text-sm font-medium text-[var(--color-primary)] hover:underline">View All</button>
                    </div>
                    <div className="table-wrapper">
                        <table className="custom-table">
                            <thead><tr><th>PO #</th><th>Supplier</th><th>Date</th></tr></thead>
                            <tbody>
                                {purchaseOrders.filter(po => po.StatusType === 'Approved').slice(0, 5).map(po => (
                                    <tr key={po.POID}>
                                        <td className="font-medium text-[var(--color-text-base)]">{po.PONumber}</td>
                                        <td>{po.SupplierName}</td>
                                        <td>{new Date(po.PODate).toLocaleDateString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default WarehouseDashboard;