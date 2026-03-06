<!-- components/add_item_modal.php -->
<div id="itemModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-[10000] items-center justify-center p-4" onclick="if(event.target === this) closeItemModal()">
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg border border-slate-200/60 dark:border-slate-700/60 mx-auto my-8" onclick="event.stopPropagation()">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50">
            <h3 id="modalTitle" class="text-xl font-bold text-slate-900 dark:text-white">Add New Item</h3>
        </div>
        <form id="itemForm" class="p-6 space-y-5">
            <input type="hidden" name="action" id="formAction" value="add_item">
            <input type="hidden" name="itemId" id="formItemId">
            
            <!-- Item Info -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Item Name</label>
                <input type="text" name="itemName" id="itemName" required class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:ring-teal-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Brand</label>
                    <input type="text" name="brand" id="itemBrand" placeholder="e.g. Unilab" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Dosage Unit</label>
                    <input type="text" name="dosageUnit" id="dosageUnit" placeholder="e.g. 500mg, 10mL" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                    <select name="itemType" id="itemType" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white">
                        <option value="Medicine">Medicine</option>
                        <option value="Supply">Supply</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="space-y-2">
                     <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Unit of Measure</label>
                     <input type="text" name="unitOfMeasure" id="unitOfMeasure" required class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white">
                </div>
            </div>

            <!-- Initial Batch Section -->
            <div id="batchSection">
                <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Initial Batch <span class="font-normal normal-case text-slate-400">(optional)</span></p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Lot Number</label>
                            <input type="text" name="lotNumber" id="lotNumber" placeholder="e.g. LOT-2025-001" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
                        </div>
                        <div class="space-y-2">
                             <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Batch ID (Manual)</label>
                             <input type="number" name="batchId" id="batchId" min="1" placeholder="Auto-assigned if empty" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mt-3">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Initial Quantity</label>
                            <input type="number" name="batchQty" id="batchQty" min="0" placeholder="0" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Unit Cost (₱)</label>
                            <input type="number" step="0.01" name="unitCost" id="unitCostInput" min="0" placeholder="0.00" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Expiry Date</label>
                            <input type="date" name="expiryDate" id="expiryDate" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-700/50">
                <button type="button" onclick="closeItemModal()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-teal-500 hover:bg-teal-600 rounded-lg shadow-lg shadow-teal-500/20 transition-all active:scale-95">Save Item</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddItemModal() {
    document.getElementById('modalTitle').innerText = 'Add New Item';
    document.getElementById('formAction').value = 'add_item';
    document.getElementById('formItemId').value = '';
    document.getElementById('itemForm').reset();
    document.getElementById('batchSection').style.display = 'block';
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
}

function openEditItemModal(item) {
    document.getElementById('modalTitle').innerText = 'Edit Item';
    document.getElementById('formAction').value = 'update_item';
    document.getElementById('formItemId').value = item.ItemID;
    document.getElementById('itemName').value = item.ItemName || '';
    document.getElementById('itemBrand').value = item.Brand || '';
    document.getElementById('dosageUnit').value = item.DosageUnit || '';
    document.getElementById('itemType').value = item.Category || 'Medicine';
    document.getElementById('unitOfMeasure').value = item.Unit || '';
    document.getElementById('batchSection').style.display = 'none';
    document.getElementById('itemModal').classList.remove('hidden');
    document.getElementById('itemModal').classList.add('flex');
}

function closeItemModal() {
    const m = document.getElementById('itemModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

// Teleport modal to body so it's not clipped by layout containers
(function() {
    const modal = document.getElementById('itemModal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
})();

// Handler for custom item creation (e.g. from PO modal)
window.onItemCreated = window.onItemCreated || function(newItem) {
    window.location.reload();
};

document.getElementById('itemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            closeItemModal();
            if (typeof window.onItemCreated === 'function') {
                window.onItemCreated(data.item || { ItemID: data.id, ItemName: formData.get('itemName') });
            }
        } else {
            alert(data.message || 'Error occurred');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Connection error');
    });
});
</script>
