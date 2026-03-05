<!-- Edit Warehouse Modal -->
<div id="editWarehouseModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999] flex items-center justify-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-slate-200/60 dark:border-slate-700/60 animate-fade-in" onclick="event.stopPropagation()">

        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Edit Warehouse</h3>
            <button onclick="document.getElementById('editWarehouseModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="editWarehouseForm" class="p-6 space-y-5">
            <input type="hidden" name="action" value="edit_warehouse">
            <input type="hidden" name="warehouseID" id="editWarehouseID">

            <!-- Warehouse Name -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="editWarehouseName">
                    Warehouse Name
                </label>
                <input
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all"
                    id="editWarehouseName"
                    name="warehouseName"
                    type="text"
                    placeholder="Enter warehouse name"
                    required>
            </div>

            <!-- Location -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="editWarehouseLocation">
                    Location
                </label>
                <input
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all"
                    id="editWarehouseLocation"
                    name="location"
                    type="text"
                    placeholder="Enter location"
                    required>
            </div>

            <!-- Warehouse Type -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="editWarehouseType">
                    Warehouse Type
                </label>
                <select
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer"
                    id="editWarehouseType"
                    name="warehouseType"
                    required>
                    <option value="Central">Central</option>
                    <option value="Satellite">Satellite</option>
                    <option value="Cold Storage">Cold Storage</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between gap-3 pt-4">
                <button
                    type="button"
                    onclick="document.getElementById('editWarehouseModal').classList.add('hidden')"
                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-all">
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-5 py-2.5 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-lg shadow-teal-900/20 transition-all active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditWarehouseModal(warehouse) {
    document.getElementById('editWarehouseID').value = warehouse.WarehouseID;
    document.getElementById('editWarehouseName').value = warehouse.WarehouseName || '';
    document.getElementById('editWarehouseLocation').value = warehouse.Location || '';
    document.getElementById('editWarehouseType').value = warehouse.WarehouseType || 'Central';
    document.getElementById('editWarehouseModal').classList.remove('hidden');
}

document.getElementById('editWarehouseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.innerText = 'Saving...';
    btn.disabled = true;

    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('editWarehouseModal').classList.add('hidden');
                window.location.reload();
            } else {
                alert(data.message || 'Error updating warehouse');
                btn.innerText = originalText;
                btn.disabled = false;
            }
        })
        .catch(() => {
            alert('An error occurred');
            btn.innerText = originalText;
            btn.disabled = false;
        });
});
</script>
