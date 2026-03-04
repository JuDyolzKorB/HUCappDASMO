<!-- Issuance Details Modal -->
<div id="issuanceDetailsModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center z-[9999] p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg border border-slate-200/60 dark:border-slate-700/60 animate-fade-in" onclick="event.stopPropagation()">

        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-0.5">Issuance Record</p>
                <h3 id="issModalIssuanceID" class="text-xl font-extrabold text-slate-900 dark:text-white font-display"></h3>
            </div>
            <button onclick="document.getElementById('issuanceDetailsModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors mt-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/50">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Requisition No.</p>
                    <p id="issModalReqNum" class="text-sm font-bold text-slate-800 dark:text-white"></p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/50">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date Issued</p>
                    <p id="issModalDate" class="text-sm font-bold text-slate-800 dark:text-white"></p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/50 col-span-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Issued By</p>
                    <p id="issModalIssuedBy" class="text-sm font-bold text-slate-800 dark:text-white"></p>
                </div>
            </div>

            <!-- Items table -->
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Items Issued</p>
                <div id="issModalItemsContainer" class="overflow-hidden border border-slate-100 dark:border-slate-700 rounded-xl">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Item</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Qty</th>
                                <th class="px-4 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Batch</th>
                            </tr>
                        </thead>
                        <tbody id="issModalItemsBody" class="divide-y divide-slate-50 dark:divide-slate-700/50 dark:text-slate-300">
                            <tr><td colspan="3" class="px-4 py-4 text-center text-slate-400 text-xs italic">No item details available.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700/50 flex justify-end">
            <button onclick="document.getElementById('issuanceDetailsModal').classList.add('hidden')"
                class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-all">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function openIssuanceDetailsModal(issuance, reqNum, issuedByName) {
    document.getElementById('issModalIssuanceID').textContent = 'ISS-' + issuance.IssuanceID;
    document.getElementById('issModalReqNum').textContent = reqNum || 'N/A';
    document.getElementById('issModalIssuedBy').textContent = issuedByName || 'Unknown';

    const rawDate = issuance.DateIssued;
    document.getElementById('issModalDate').textContent = rawDate
        ? new Date(rawDate).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })
        : 'N/A';

    const tbody = document.getElementById('issModalItemsBody');
    const items = issuance.IssuanceItems || [];
    if (items.length > 0) {
        tbody.innerHTML = items.map(item => `
            <tr>
                <td class="px-4 py-3 font-medium">${item.ItemName || 'Item #' + item.ItemID}</td>
                <td class="px-4 py-3 text-center font-bold text-teal-600">${item.QuantityIssued}</td>
                <td class="px-4 py-3 font-mono text-xs text-slate-500">${item.BatchID || '-'}</td>
            </tr>
        `).join('');
    } else {
        tbody.innerHTML = '<tr><td colspan="3" class="px-4 py-4 text-center text-slate-400 text-xs italic">No item details available.</td></tr>';
    }

    document.getElementById('issuanceDetailsModal').classList.remove('hidden');
}
</script>
