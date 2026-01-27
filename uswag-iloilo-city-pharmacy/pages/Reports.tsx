
import React, { useState, useMemo, useEffect } from 'react';
import { CentralInventoryBatch, Item, Report, ReportType, OfficeType, User, PurchaseOrder, Receiving, ReceivingItem, Issuance, IssuanceItem, Requisition, NoticeOfIssuance, RequisitionAdjustment, RequisitionAdjustmentDetail } from '../types';
import ReportViewerModal from '../components/ReportViewerModal';

interface ReportsProps {
  inventory: CentralInventoryBatch[];
  items: Item[];
  reports: Report[];
  onGenerateReport: (reportType: ReportType, office: OfficeType, data: any) => void;
  currentUser: User;
  purchaseOrders: PurchaseOrder[];
  receivings: Receiving[];
  receivingItems: ReceivingItem[];
  users: User[];
  issuances: Issuance[];
  issuanceItems: IssuanceItem[];
  requisitions: Requisition[];
  noticesOfIssuance: NoticeOfIssuance[];
  requisitionAdjustments: RequisitionAdjustment[];
  requisitionAdjustmentDetails: RequisitionAdjustmentDetail[];
}

const Reports: React.FC<ReportsProps> = (props) => {
    const { items, reports, onGenerateReport, currentUser, purchaseOrders, receivings, receivingItems, users, inventory, issuances, issuanceItems, requisitions, noticesOfIssuance, requisitionAdjustments, requisitionAdjustmentDetails } = props;
    
    const availableReportTypes = useMemo((): ReportType[] => {
        if (!currentUser) return [];
        switch (currentUser.Role) {
            case 'Administrator':
            case 'Head Pharmacist':
                return ['Inventory Valuation', 'Receipt Confirmation', 'Stock Card & Ledger'];
            case 'Accounting Office User':
                return ['Inventory Valuation', 'Receipt Confirmation'];
            case 'CMO/GSO/COA User':
                return ['Stock Card & Ledger'];
            default:
                return [];
        }
    }, [currentUser]);

    const [reportType, setReportType] = useState<ReportType>(availableReportTypes[0]);
    const [officeType, setOfficeType] = useState<OfficeType>('Accounting');
    const [selectedItemId, setSelectedItemId] = useState<string>(items[0]?.ItemID || '');
    const [selectedPoId, setSelectedPoId] = useState<string>('');
    const [viewingReport, setViewingReport] = useState<Report | null>(null);

    useEffect(() => {
        if (availableReportTypes.length > 0 && !availableReportTypes.includes(reportType)) {
            setReportType(availableReportTypes[0]);
        }
    }, [availableReportTypes, reportType]);

    const accountingReports: ReportType[] = ['Inventory Valuation', 'Receipt Confirmation'];
    const complianceReports: ReportType[] = ['Stock Card & Ledger'];
    const officeOptions = useMemo(() => {
        if (accountingReports.includes(reportType)) return ['Accounting'];
        if (complianceReports.includes(reportType)) return ['CMO', 'GSO', 'COA'];
        return [];
    }, [reportType]);

    useEffect(() => {
        if (officeOptions.length > 0) {
            setOfficeType(officeOptions[0] as OfficeType);
        }
    }, [officeOptions]);
    
    const completedPurchaseOrders = useMemo(() => purchaseOrders.filter(po => po.StatusType === 'Completed'), [purchaseOrders]);

    useEffect(() => {
        if (reportType === 'Receipt Confirmation' && completedPurchaseOrders.length > 0) {
            setSelectedPoId(completedPurchaseOrders[0].POID);
        } else {
            setSelectedPoId('');
        }
    }, [reportType, completedPurchaseOrders]);

    const visibleReports = useMemo(() => {
        if (!currentUser) return [];
        const isAdmin = currentUser.Role === 'Administrator' || currentUser.Role === 'Head Pharmacist';
        if (isAdmin) {
            return reports;
        }
        if (currentUser.Role === 'Accounting Office User') {
            return reports.filter(r => r.GeneratedForOffice === 'Accounting');
        }
        if (currentUser.Role === 'CMO/GSO/COA User') {
            const complianceOffices: OfficeType[] = ['CMO', 'GSO', 'COA'];
            return reports.filter(r => complianceOffices.includes(r.GeneratedForOffice));
        }
        return [];
    }, [reports, currentUser]);

    const handleGenerate = () => {
        let reportData: any;
        switch(reportType) {
            case 'Inventory Valuation':
                reportData = items.map(item => {
                    const batches = inventory.filter(b => b.ItemID === item.ItemID);
                    const totalQuantity = batches.reduce((sum, b) => sum + b.QuantityOnHand, 0);
                    const totalValue = batches.reduce((sum, b) => sum + (b.QuantityOnHand * b.UnitCost), 0);
                    return { itemId: item.ItemID, itemName: item.ItemName, totalQuantity, totalValue, averageCost: totalQuantity > 0 ? totalValue / totalQuantity : 0 };
                });
                break;
            case 'Stock Card & Ledger':
                const selectedItem = items.find(i => i.ItemID === selectedItemId);
                if (!selectedItem) return;

                // Ledger part
                let runningBalance = 0;
                const transactions: any[] = [];
                 inventory.filter(b => b.ItemID === selectedItem.ItemID).forEach(b => {
                     transactions.push({ date: 'Initial', type: 'Stock', description: `Batch ${b.BatchID}`, qty_in: b.QuantityOnHand, qty_out: 0, balance: 0});
                });
                 issuances.forEach(iss => {
                    const issItems = issuanceItems.filter(ii => ii.IssuanceID === iss.IssuanceID);
                    issItems.forEach(ii => {
                         const batch = inventory.find(b => b.BatchID === ii.BatchID);
                         if(batch?.ItemID === selectedItem.ItemID) {
                             transactions.push({ date: iss.IssuedDate, type: 'OUT', description: `Issuance to ${requisitions.find(r => r.RequisitionID === iss.RequisitionID)?.HealthCenterName}`, qty_in: 0, qty_out: ii.QuantityIssued, balance: 0, reference: requisitions.find(r => r.RequisitionID === iss.RequisitionID)?.RequisitionNumber });
                         }
                    });
                });
                
                transactions.sort((a,b) => new Date(a.date).getTime() - new Date(b.date).getTime());
                transactions.forEach(t => {
                    runningBalance += (t.qty_in || 0) - (t.qty_out || 0);
                    t.balance = runningBalance;
                });

                // Stock Card part
                reportData = {
                    itemId: selectedItemId,
                    itemName: selectedItem.ItemName,
                    batches: inventory.filter(b => b.ItemID === selectedItemId).map(b => ({ batchId: b.BatchID, quantity: b.QuantityOnHand, expiry: b.ExpiryDate, cost: b.UnitCost })),
                    transactions: transactions,
                };
                break;
            case 'Receipt Confirmation':
                const po = purchaseOrders.find(p => p.POID === selectedPoId);
                const receiving = receivings.find(r => r.POID === selectedPoId);
                if (!po || !receiving) return;
                const receivingUser = users.find(u => u.UserID === receiving.UserID);
                reportData = {
                    poNumber: po.PONumber,
                    supplierName: po.SupplierName,
                    poDate: po.PODate,
                    receivedDate: receiving.ReceivedDate,
                    receivedBy: receivingUser ? `${receivingUser.FirstName} ${receivingUser.LastName}` : 'Unknown',
                    items: po.PurchaseOrderItems.map(poi => {
                        const receivedBatches = receivingItems.filter(ri => {
                            const batch = inventory.find(b => b.BatchID === ri.BatchID);
                            return batch?.ItemID === poi.ItemID && ri.ReceivingID === receiving.ReceivingID;
                        });
                        const quantityReceived = receivedBatches.reduce((sum, rb) => sum + rb.QuantityReceived, 0);
                        return { itemName: items.find(i => i.ItemID === poi.ItemID)?.ItemName || 'Unknown', quantityOrdered: poi.QuantityOrdered, quantityReceived };
                    })
                };
                break;
        }
        
        if (reportData) {
            onGenerateReport(reportType, officeType, reportData);
        }
    };

    return (
        <>
            <div className="space-y-10">
                <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
                    <h2 className="text-xl font-semibold text-[var(--color-text-base)] mb-4">Generate New Report</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                        <div>
                            <label className="block text-sm font-medium text-[var(--color-text-muted)]">Report Type</label>
                            <select value={reportType} onChange={e => setReportType(e.target.value as ReportType)} className="form-select mt-1">
                                {availableReportTypes.map(type => (
                                    <option key={type} value={type}>{type}</option>
                                ))}
                            </select>
                        </div>
                        
                        {reportType === 'Stock Card & Ledger' && (
                            <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)]">Item</label>
                                <select value={selectedItemId} onChange={e => setSelectedItemId(e.target.value)} className="form-select mt-1">
                                    {items.map(item => <option key={item.ItemID} value={item.ItemID}>{item.ItemName}</option>)}
                                </select>
                            </div>
                        )}

                        {reportType === 'Receipt Confirmation' && (
                             <div>
                                <label className="block text-sm font-medium text-[var(--color-text-muted)]">Completed Purchase Order</label>
                                <select value={selectedPoId} onChange={e => setSelectedPoId(e.target.value)} className="form-select mt-1">
                                    {completedPurchaseOrders.map(po => <option key={po.POID} value={po.POID}>{po.PONumber} - {po.SupplierName}</option>)}
                                </select>
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-[var(--color-text-muted)]">For Office</label>
                            <select value={officeType} onChange={e => setOfficeType(e.target.value as OfficeType)} className="form-select mt-1" disabled={officeOptions.length === 1}>
                                {officeOptions.map(opt => <option key={opt} value={opt}>{opt === 'Accounting' ? 'Accounting Office' : opt}</option>)}
                            </select>
                        </div>

                        <div className="lg:col-start-3">
                            <button onClick={handleGenerate} className="btn btn-primary w-full">Generate Report</button>
                        </div>
                    </div>
                </div>

                <div className="bg-[var(--color-bg-surface)] p-8 rounded-xl shadow-md">
                    <h2 className="text-xl font-semibold text-[var(--color-text-base)] mb-4">Report History</h2>
                    <div className="table-wrapper">
                        <table className="custom-table">
                            <thead>
                                <tr><th>Report ID</th><th>Type</th><th>Generated For</th><th>Generated By</th><th>Date</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                {visibleReports.map(report => (
                                    <tr key={report.ReportID}>
                                        <td className="font-medium text-[var(--color-text-base)] font-mono">{report.ReportID}</td>
                                        <td>{report.ReportType}</td>
                                        <td>{report.GeneratedForOffice}</td>
                                        <td>{report.GeneratedByFullName}</td>
                                        <td>{new Date(report.GeneratedDate).toLocaleString()}</td>
                                        <td>
                                            <button onClick={() => setViewingReport(report)} className="font-medium text-[var(--color-primary)] hover:underline">View & Print</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {visibleReports.length === 0 && <p className="text-center p-8 text-[var(--color-text-muted)]">No reports have been generated yet.</p>}
                    </div>
                </div>
            </div>
            {viewingReport && <ReportViewerModal report={viewingReport} onClose={() => setViewingReport(null)} />}
        </>
    );
};

export default Reports;