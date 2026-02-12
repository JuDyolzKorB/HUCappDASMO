<?php
// components/receiving_details_modal.php
?>

<div id="receivingDetailsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden z-50 flex justify-center items-center p-4" onclick="if(event.target === this) closeReceivingDetailsModal()">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-3xl transform transition-all relative border border-slate-200/60 dark:border-slate-700/60" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 id="receivingModalTitle" class="text-xl font-bold text-slate-900 dark:text-white">Receiving Details</h3>
            <button onclick="closeReceivingDetailsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-6">
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-x-8 gap-y-3">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Receiving ID:</p>
                    <p id="rcvId" class="text-sm text-slate-700 dark:text-slate-200 font-mono"></p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Received Date:</p>
                    <p id="rcvDate" class="text-sm text-slate-700 dark:text-slate-200"></p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">PO Number:</p>
                    <p id="rcvPONumber" class="text-sm text-slate-700 dark:text-slate-200 font-bold"></p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Received By:</p>
                    <p id="rcvUser" class="text-sm text-slate-700 dark:text-slate-200"></p>
                </div>
            </div>

            <!-- Received Items Table -->
            <div class="space-y-3">
                <h4 class="text-base font-bold text-slate-900 dark:text-white">Received Items & Batches</h4>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200/60 dark:border-slate-700/60 overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-slate-100/80 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Item / Batch</th>
                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Qty</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Unit Cost</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Expiry</th>
                            </tr>
                        </thead>
                        <tbody id="rcvItemsList" class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                            <!-- Items populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700/50 flex justify-end">
            <button onclick="closeReceivingDetailsModal()" class="px-6 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function openReceivingDetailsModal(rec, poNumber, userName) {
    document.getElementById('rcvId').textContent = rec.ReceivingID;
    document.getElementById('rcvDate').textContent = new Date(rec.ReceivedDate).toLocaleString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    document.getElementById('rcvPONumber').textContent = poNumber;
    document.getElementById('rcvUser').textContent = userName;

    const list = document.getElementById('rcvItemsList');
    list.innerHTML = '';
    
    rec.ReceivedItems.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-100/50 dark:hover:bg-slate-800/50 transition-colors';
        
        const expiry = item.ExpiryDate ? new Date(item.ExpiryDate).toLocaleDateString() : '<span class="text-slate-400">N/A</span>';
        const cost = item.UnitCost ? `₱${parseFloat(item.UnitCost).toLocaleString(undefined, {minimumFractionDigits: 2})}` : '₱0.00';
        
        tr.innerHTML = `
            <td class="px-5 py-4">
                <div class="text-sm font-medium text-slate-900 dark:text-white">${item.ItemName}</div>
                <div class="text-[10px] text-slate-500 font-mono">Batch: ${item.BatchID}</div>
            </td>
            <td class="px-5 py-4 text-sm text-center font-semibold text-slate-900 dark:text-white">
                ${item.QuantityReceived.toLocaleString()}
            </td>
            <td class="px-5 py-4 text-sm text-right text-slate-700 dark:text-slate-300">
                ${cost}
            </td>
            <td class="px-5 py-4 text-sm text-right text-slate-700 dark:text-slate-300">
                ${expiry}
            </td>
        `;
        list.appendChild(tr);
    });

    document.getElementById('receivingDetailsModal').classList.remove('hidden');
}

function closeReceivingDetailsModal() {
    document.getElementById('receivingDetailsModal').classList.add('hidden');
}
</script>
