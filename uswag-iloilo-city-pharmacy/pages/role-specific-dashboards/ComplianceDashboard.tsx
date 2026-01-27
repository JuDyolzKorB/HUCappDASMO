import React from 'react';
import DashboardCard from '../../components/DashboardCard';
import { Page, CentralInventoryBatch, Item, Report } from '../../types';
import InventoryIcon from '../../components/icons/InventoryIcon';
import ReportsIcon from '../../components/icons/ReportsIcon';
import ShieldCheckIcon from '../../components/icons/ShieldCheckIcon';

interface ComplianceDashboardProps {
  inventory: CentralInventoryBatch[];
  items: Item[];
  reports: Report[];
  setCurrentPage: (page: Page) => void;
}

const ComplianceDashboard: React.FC<ComplianceDashboardProps> = ({ inventory, items, reports, setCurrentPage }) => {
    const totalInventoryValue = inventory.reduce((acc, batch) => acc + batch.QuantityOnHand * batch.UnitCost, 0);
    const totalReports = reports.length;

    return (
        <div className="space-y-8">
            <div>
                <h1 className="text-2xl font-bold text-[var(--color-text-base)]">Compliance & Oversight Dashboard</h1>
                <p className="text-sm text-[var(--color-text-muted)]">High-level inventory valuation and reporting summary.</p>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <DashboardCard title="Total Inventory Value" value={`$${totalInventoryValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`} icon={<InventoryIcon className="w-8 h-8"/>} color="bg-gradient-teal" />
                <DashboardCard title="Total Reports Generated" value={totalReports.toString()} icon={<ReportsIcon className="w-8 h-8"/>} color="bg-gradient-purple" />
                 <div 
                    onClick={() => setCurrentPage('Reports')} 
                    className="bg-[var(--color-bg-surface)] p-5 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 relative overflow-hidden cursor-pointer flex items-center justify-between pl-4 group"
                >
                    <div className="absolute top-0 left-0 h-full w-1.5 bg-[var(--color-success)]"></div>
                    <div>
                        <p className="text-sm font-medium text-[var(--color-text-muted)] tracking-wide">Compliance</p>
                        <p className="text-xl font-bold text-[var(--color-text-base)] mt-2 group-hover:text-[var(--color-primary)]">Access Full Reports</p>
                    </div>
                    <div className="text-slate-300">
                        <ShieldCheckIcon className="w-8 h-8" />
                    </div>
                </div>
            </div>

            <div className="bg-[var(--color-bg-surface)] p-6 rounded-xl shadow-md">
                <h3 className="text-lg font-semibold text-[var(--color-text-base)] mb-4">Recent Report History</h3>
                <div className="table-wrapper">
                    <table className="custom-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Type</th>
                            <th>Generated For</th>
                        </tr>
                    </thead>
                    <tbody>
                        {reports.slice(0, 10).map(report => (
                        <tr key={report.ReportID}>
                            <td className="font-medium text-[var(--color-text-base)]">{report.ReportID}</td>
                            <td>{report.ReportType}</td>
                            <td>{report.GeneratedForOffice}</td>
                        </tr>
                        ))}
                    </tbody>
                    </table>
                     {reports.length === 0 && (
                        <div className="text-center py-8 text-[var(--color-text-muted)]">
                            <p>No reports have been generated yet.</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ComplianceDashboard;