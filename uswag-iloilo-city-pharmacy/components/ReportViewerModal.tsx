
import React from 'react';
import { Report } from '../types';
import PrinterIcon from './icons/PrinterIcon';

const ReportViewerModal: React.FC<{ report: Report; onClose: () => void; }> = ({ report, onClose }) => {
  
  const handlePrint = () => {
    window.print();
  };

  const renderContent = () => {
    switch (report.ReportType) {
      case 'Inventory Valuation':
        return (
          <table className="w-full text-sm mt-4">
            <thead><tr className="border-b"><th className="text-left p-2">Item</th><th className="text-right p-2">Quantity</th><th className="text-right p-2">Avg. Cost</th><th className="text-right p-2">Total Value</th></tr></thead>
            <tbody>
              {report.data.map((item: any) => (
                <tr key={item.itemId} className="border-b">
                  <td className="p-2">{item.itemName}</td>
                  <td className="text-right p-2">{item.totalQuantity.toLocaleString()}</td>
                  <td className="text-right p-2">${item.averageCost.toFixed(2)}</td>
                  <td className="text-right p-2">${item.totalValue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
              ))}
            </tbody>
          </table>
        );
      case 'Stock Card & Ledger':
        return (
            <div className="space-y-6">
                <h3 className="text-xl font-bold mt-4 text-center">{report.data.itemName}</h3>
                <div>
                    <h4 className="text-lg font-semibold mb-2">Current Batches (Stock Card)</h4>
                    <table className="w-full text-sm mt-2">
                        <thead><tr className="border-b"><th className="text-left p-2">Batch ID</th><th className="text-right p-2">Quantity</th><th className="text-left p-2">Expiry</th><th className="text-right p-2">Unit Cost</th></tr></thead>
                        <tbody>
                            {report.data.batches.map((batch: any) => (
                                <tr key={batch.batchId} className="border-b">
                                    <td className="p-2 font-mono">{batch.batchId}</td>
                                    <td className="text-right p-2">{batch.quantity.toLocaleString()}</td>
                                    <td className="p-2">{new Date(batch.expiry).toLocaleDateString()}</td>
                                    <td className="text-right p-2">${batch.cost.toFixed(2)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                 <div>
                    <h4 className="text-lg font-semibold mb-2">Transaction Ledger</h4>
                    <table className="w-full text-sm mt-2">
                        <thead><tr className="border-b"><th className="text-left p-2">Date</th><th className="text-left p-2">Type</th><th className="text-left p-2">Description</th><th className="text-right p-2">In</th><th className="text-right p-2">Out</th><th className="text-right p-2">Balance</th></tr></thead>
                        <tbody>
                            {report.data.transactions.map((t: any, index: number) => (
                                <tr key={index} className="border-b">
                                    <td className="p-2">{t.date === 'Initial' ? 'Initial' : new Date(t.date).toLocaleDateString()}</td>
                                    <td className="p-2">{t.type}</td>
                                    <td className="p-2">{t.description}</td>
                                    <td className="text-right p-2">{t.qty_in ? t.qty_in.toLocaleString() : '-'}</td>
                                    <td className="text-right p-2">{t.qty_out ? t.qty_out.toLocaleString() : '-'}</td>
                                    <td className="text-right p-2 font-semibold">{t.balance.toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        );
      case 'Receipt Confirmation':
         return (
            <div className="space-y-4 mt-4">
                <div className="grid grid-cols-2 gap-x-4 gap-y-1">
                    <div><strong>PO Number:</strong> {report.data.poNumber}</div>
                    <div><strong>Supplier:</strong> {report.data.supplierName}</div>
                    <div><strong>PO Date:</strong> {new Date(report.data.poDate).toLocaleDateString()}</div>
                </div>
                <div className="grid grid-cols-2 gap-x-4 gap-y-1 border-t pt-2 mt-2">
                    <div><strong>Received Date:</strong> {new Date(report.data.receivedDate).toLocaleString()}</div>
                    <div><strong>Received By:</strong> {report.data.receivedBy}</div>
                </div>
                <table className="w-full text-sm mt-4">
                    <thead><tr className="border-b"><th className="text-left p-2">Item</th><th className="text-right p-2">Ordered</th><th className="text-right p-2">Received</th></tr></thead>
                    <tbody>
                        {report.data.items.map((item: any) => (
                            <tr key={item.itemName} className="border-b">
                                <td className="p-2">{item.itemName}</td>
                                <td className="text-right p-2">{item.quantityOrdered.toLocaleString()}</td>
                                <td className="text-right p-2">{item.quantityReceived.toLocaleString()}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
         );
      default:
        return <p>Report type not supported for viewing.</p>;
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 no-print">
      <div className="bg-[var(--color-bg-surface)] rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div className="printable-area p-8 flex-grow overflow-y-auto">
            <div className="text-center border-b pb-4">
                <h1 className="text-2xl font-bold text-[var(--color-text-base)]">Uswag Iloilo City Pharmacy Report</h1>
                <h2 className="text-xl font-semibold text-[var(--color-text-base)]">{report.ReportType}</h2>
            </div>
            <div className="grid grid-cols-2 gap-x-4 text-sm mt-4 text-[var(--color-text-muted)]">
                <div><strong>Report ID:</strong> {report.ReportID}</div>
                <div><strong>Generated For:</strong> {report.GeneratedForOffice}</div>
                <div><strong>Generated By:</strong> {report.GeneratedByFullName}</div>
                <div><strong>Generated Date:</strong> {new Date(report.GeneratedDate).toLocaleString()}</div>
            </div>
            <div className="mt-4">{renderContent()}</div>
        </div>
        <div className="flex justify-end space-x-3 p-4 border-t border-[var(--color-border)] bg-[var(--color-bg-muted)] rounded-b-lg no-print">
          <button onClick={onClose} className="btn btn-secondary">Close</button>
          <button onClick={handlePrint} className="btn btn-primary flex items-center gap-2">
            <PrinterIcon className="w-4 h-4" /> Print
          </button>
        </div>
      </div>
    </div>
  );
};

export default ReportViewerModal;
