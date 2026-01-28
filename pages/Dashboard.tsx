
import React from 'react';
import DashboardCard from '../components/DashboardCard';
import { Requisition, CentralInventoryBatch, Item, PurchaseOrder } from '../types';
import RequisitionsIcon from '../components/icons/RequisitionsIcon';
import InventoryIcon from '../components/icons/InventoryIcon';
import AlertIcon from '../components/icons/AlertIcon';
import POIcon from '../components/icons/POIcon';
import Badge from '../components/Badge';

interface DashboardProps {
  requisitions: Requisition[];
  inventory: CentralInventoryBatch[];
  items: Item[];
  purchaseOrders: PurchaseOrder[];
}

const Dashboard: React.FC<DashboardProps> = ({ requisitions, inventory, items, purchaseOrders }) => {
  const pendingRequisitions = requisitions.filter(r => r.StatusType === 'Pending').length;
  const pendingPurchaseOrders = purchaseOrders.filter(po => po.StatusType === 'Pending').length;
  
  const totalInventoryValue = inventory.reduce((acc, batch) => acc + batch.QuantityOnHand * batch.UnitCost, 0);

  const nearlyExpiredItems = inventory.filter(batch => {
    const expiryDate = new Date(batch.ExpiryDate);
    const threeMonthsFromNow = new Date();
    threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
    return expiryDate < threeMonthsFromNow;
  }).length;

  return (
    <div className="space-y-10">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <DashboardCard title="Total Inventory Value" value={`$${totalInventoryValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`} icon={<InventoryIcon className="w-8 h-8"/>} color="bg-[var(--color-primary)]" />
        <DashboardCard title="Pending Requisitions" value={pendingRequisitions.toString()} icon={<RequisitionsIcon className="w-8 h-8"/>} color="bg-[var(--color-warning)]" />
        <DashboardCard title="Pending Purchase Orders" value={pendingPurchaseOrders.toString()} icon={<POIcon className="w-8 h-8"/>} color="bg-[var(--color-info)]" />
        <DashboardCard title="Nearly Expired Items" value={nearlyExpiredItems.toString()} icon={<AlertIcon className="w-8 h-8"/>} color="bg-[var(--color-danger)]" />
      </div>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-10">
        <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
            <h3 className="text-lg font-semibold text-[var(--color-text-base)] mb-4">Recent Requisitions for Approval</h3>
            <div className="table-wrapper">
                <table className="custom-table">
                <thead>
                    <tr>
                    <th>Req #</th>
                    <th>Health Center</th>
                    <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    {requisitions.filter(r => r.StatusType === 'Pending').slice(0, 5).map(req => (
                    <tr key={req.RequisitionID}>
                        <td className="font-medium text-[var(--color-text-base)]">{req.RequisitionNumber}</td>
                        <td>{req.HealthCenterName}</td>
                        <td><Badge status={req.StatusType}/></td>
                    </tr>
                    ))}
                </tbody>
                </table>
                {pendingRequisitions === 0 && (
                <div className="text-center py-8 text-[var(--color-text-muted)]">
                    <p>No requisitions are currently pending approval.</p>
                </div>
                )}
            </div>
        </div>

        <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
            <h3 className="text-lg font-semibold text-[var(--color-text-base)] mb-4">Pending Purchase Orders for Approval</h3>
            <div className="table-wrapper">
                <table className="custom-table">
                <thead>
                    <tr>
                    <th>PO #</th>
                    <th>Supplier</th>
                    <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    {purchaseOrders.filter(po => po.StatusType === 'Pending').slice(0, 5).map(po => (
                    <tr key={po.POID}>
                        <td className="font-medium text-[var(--color-text-base)]">{po.PONumber}</td>
                        <td>{po.SupplierName}</td>
                        <td>{new Date(po.PODate).toLocaleDateString()}</td>
                    </tr>
                    ))}
                </tbody>
                </table>
                {pendingPurchaseOrders === 0 && (
                <div className="text-center py-8 text-[var(--color-text-muted)]">
                    <p>No purchase orders are currently pending approval.</p>
                </div>
                )}
            </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;