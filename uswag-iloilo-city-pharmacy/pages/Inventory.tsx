import React, { useState, useMemo } from 'react';
import { CentralInventoryBatch, Item } from '../types';

interface InventoryProps {
  inventory: CentralInventoryBatch[];
  items: Item[];
  onInitiateAdjustment: (itemId: string, batchId: string) => void;
}

const Inventory: React.FC<InventoryProps> = ({ inventory, items, onInitiateAdjustment }) => {
  const [expandedItem, setExpandedItem] = useState<string | null>(null);

  const inventoryData = useMemo(() => {
    return items.map(item => {
      const batches = inventory
        .filter(batch => batch.ItemID === item.ItemID)
        .sort((a, b) => new Date(a.ExpiryDate).getTime() - new Date(b.ExpiryDate).getTime());
      
      const totalQuantity = batches.reduce((sum, batch) => sum + batch.QuantityOnHand, 0);
      const nextToExpire = batches.length > 0 ? batches[0].ExpiryDate : 'N/A';

      return {
        ...item,
        totalQuantity,
        nextToExpire,
        batches,
      };
    });
  }, [items, inventory]);

  const toggleItem = (itemId: string) => {
    setExpandedItem(expandedItem === itemId ? null : itemId);
  };

  return (
    <div className="bg-[var(--color-bg-surface)] p-6 md:p-8 rounded-xl shadow-md">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-xl font-semibold text-[var(--color-text-base)]">Inventory Status</h2>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full text-sm text-left text-[var(--color-text-muted)]">
          <thead className="text-xs text-[var(--color-text-muted)] uppercase bg-[var(--color-bg-muted)]">
            <tr>
              <th scope="col" className="px-6 py-3 w-10"></th>
              <th scope="col" className="px-6 py-3">Item ID</th>
              <th scope="col" className="px-6 py-3">Item Name</th>
              <th scope="col" className="px-6 py-3">Category</th>
              <th scope="col" className="px-6 py-3">Total Quantity</th>
              <th scope="col" className="px-6 py-3">Unit</th>
              <th scope="col" className="px-6 py-3">Next to Expire (FEFO)</th>
            </tr>
          </thead>
          <tbody>
            {inventoryData.map(item => (
              <React.Fragment key={item.ItemID}>
                <tr className="bg-[var(--color-bg-surface)] border-b border-[var(--color-border)] hover:bg-[var(--color-bg-muted)] cursor-pointer" onClick={() => toggleItem(item.ItemID)}>
                  <td className="px-6 py-4">
                     <svg className={`w-5 h-5 transition-transform duration-200 ${expandedItem === item.ItemID ? 'rotate-90' : ''}`} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clipRule="evenodd" />
                    </svg>
                  </td>
                  <td className="px-6 py-4">{item.ItemID}</td>
                  <td className="px-6 py-4 font-medium text-[var(--color-text-base)]">{item.ItemName}</td>
                  <td className="px-6 py-4">{item.ItemType}</td>
                  <td className="px-6 py-4">{item.totalQuantity.toLocaleString()}</td>
                  <td className="px-6 py-4">{item.UnitOfMeasure}</td>
                  <td className="px-6 py-4">{new Date(item.nextToExpire).toLocaleDateString()}</td>
                </tr>
                {expandedItem === item.ItemID && (
                  <tr>
                    <td colSpan={7} className="p-0">
                      <div className="p-4 bg-slate-50 dark:bg-slate-900/50">
                        <div className="px-2">
                            <h4 className="font-semibold mb-2 text-[var(--color-text-base)]">Batches (First-Expiry, First-Out)</h4>
                            <table className="w-full text-sm text-left text-[var(--color-text-muted)]">
                               <thead className="text-xs text-[var(--color-text-muted)] uppercase bg-[var(--color-border)]">
                                    <tr>
                                        <th className="px-4 py-2">Expiry Date</th>
                                        <th className="px-4 py-2">Quantity</th>
                                        <th className="px-4 py-2">Cost/Item</th>
                                        <th className="px-4 py-2">Action</th>
                                    </tr>
                               </thead>
                               <tbody>
                                    {item.batches.map((batch, index) => (
                                        <tr key={batch.BatchID} className={`border-b border-[var(--color-border)] ${index === 0 ? 'bg-[var(--color-success-light)] font-semibold text-[var(--color-text-success)]' : 'bg-[var(--color-bg-surface)]'}`}>
                                            <td className="px-4 py-2">{new Date(batch.ExpiryDate).toLocaleDateString()}</td>
                                            <td className="px-4 py-2">{batch.QuantityOnHand.toLocaleString()}</td>
                                            <td className="px-4 py-2">${batch.UnitCost.toFixed(2)}</td>
                                            <td className="px-4 py-2">
                                                <button 
                                                    onClick={() => onInitiateAdjustment(item.ItemID, batch.BatchID)}
                                                    className="px-2 py-1 text-xs font-medium text-white bg-[var(--color-warning)] rounded-md hover:bg-[var(--color-warning-hover)] shadow-sm"
                                                >
                                                    Adjust
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                               </tbody>
                            </table>
                          </div>
                      </div>
                    </td>
                  </tr>
                )}
              </React.Fragment>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default Inventory;