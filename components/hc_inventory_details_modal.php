<!-- HC Inventory Details Modal -->
<div id="hcInventoryDetailsModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex justify-center items-center z-[9999] p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-slate-200/60 dark:border-slate-700/60 animate-fade-in" onclick="event.stopPropagation()">

        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-0.5">Inventory Batch</p>
                <h3 id="hciModalItemName" class="text-xl font-extrabold text-slate-900 dark:text-white font-display"></h3>
            </div>
            <button onclick="document.getElementById('hcInventoryDetailsModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors mt-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-3">
            <!-- Quantity highlight -->
            <div class="flex items-center justify-between bg-teal-50 dark:bg-teal-900/20 border border-teal-100 dark:border-teal-800/40 rounded-xl px-5 py-4">
                <div>
                    <p class="text-[10px] font-bold text-teal-500 uppercase tracking-widest mb-0.5">Quantity On Hand</p>
                    <p id="hciModalQty" class="text-3xl font-black text-teal-600 dark:text-teal-400"></p>
                </div>
                <div class="p-3 bg-teal-100 dark:bg-teal-900/40 rounded-xl">
                    <svg class="w-7 h-7 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>

            <!-- Detail rows -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/50">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Category</p>
                    <p id="hciModalCategory" class="text-sm font-bold text-slate-800 dark:text-white"></p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/50">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Batch ID</p>
                    <p id="hciModalBatchID" class="text-sm font-bold font-mono text-slate-800 dark:text-white"></p>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 border border-slate-100 dark:border-slate-700/50 col-span-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Expiry Date</p>
                    <p id="hciModalExpiry" class="text-sm font-bold text-slate-800 dark:text-white"></p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700/50 flex justify-end">
            <button onclick="document.getElementById('hcInventoryDetailsModal').classList.add('hidden')"
                class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-all">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function openHCInventoryDetailsModal(item) {
    document.getElementById('hciModalItemName').textContent = item.ItemName || 'Unknown Item';
    document.getElementById('hciModalQty').textContent = parseInt(item.QuantityOnHand || 0).toLocaleString();
    document.getElementById('hciModalCategory').textContent = item.ItemType || 'N/A';
    document.getElementById('hciModalBatchID').textContent = item.BatchID || 'N/A';

    const expiry = item.ExpiryDate;
    document.getElementById('hciModalExpiry').textContent = expiry
        ? new Date(expiry).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
        : 'N/A (No Expiry)';

    document.getElementById('hcInventoryDetailsModal').classList.remove('hidden');
}
</script>
