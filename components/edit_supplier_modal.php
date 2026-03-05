<!-- Edit Supplier Modal -->
<div id="editSupplierModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden z-[9999] flex justify-center items-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg transform transition-all relative border border-slate-200/60 dark:border-slate-700/60" onclick="event.stopPropagation()">

        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Edit Supplier</h3>
            <button onclick="document.getElementById('editSupplierModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="editSupplierForm" class="p-6 space-y-5">
            <input type="hidden" name="action" value="edit_supplier">
            <input type="hidden" name="supplierID" id="editSupplierID">

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Supplier Name</label>
                <input type="text" name="name" id="editSupplierName" required
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all">
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Address</label>
                <textarea name="address" id="editSupplierAddress" rows="3"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all"></textarea>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Contact Info</label>
                <input type="text" name="contactInfo" id="editSupplierContact" placeholder="Email or Phone Number"
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all">
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="button" onclick="document.getElementById('editSupplierModal').classList.add('hidden')"
                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-all">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2.5 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-lg shadow-teal-900/20 transition-all active:scale-95">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSupplierModal(supplier) {
    document.getElementById('editSupplierID').value = supplier.SupplierID;
    document.getElementById('editSupplierName').value = supplier.Name || '';
    document.getElementById('editSupplierAddress').value = supplier.Address || '';
    document.getElementById('editSupplierContact').value = supplier.ContactInfo || '';
    document.getElementById('editSupplierModal').classList.remove('hidden');
}

document.getElementById('editSupplierForm').addEventListener('submit', function(e) {
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
                document.getElementById('editSupplierModal').classList.add('hidden');
                window.location.reload();
            } else {
                alert(data.message || 'Error updating supplier');
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
