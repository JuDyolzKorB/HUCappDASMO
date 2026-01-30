<?php
// components/add_purchase_order_modal.php
$suppliers = get_data('suppliers');
$healthCenters = get_data('health_centers');
$items = get_data('items');
?>

<div id="addPOModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden z-50 flex justify-center items-center p-4" onclick="if(event.target === this) closeAddPOModal()">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all relative border border-slate-200/60 dark:border-slate-700/60" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">New Purchase Order</h3>
            <button onclick="closeAddPOModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <form id="addPOForm" class="p-6 space-y-5">
            <input type="hidden" name="action" value="create_purchase_order">
            
            <!-- Supplier & Health Center Grid -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Supplier -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poSupplier">
                        Supplier
                    </label>
                    <div class="relative">
                        <select 
                            class="w-full px-4 py-2.5 pr-10 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer" 
                            id="poSupplier" 
                            name="supplierId" 
                            required>
                            <option value="">Select supplier...</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['SupplierID']; ?>"><?php echo $supplier['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Health Center -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poHealthCenter">
                        Health Center
                    </label>
                    <div class="relative">
                        <select 
                            class="w-full px-4 py-2.5 pr-10 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer" 
                            id="poHealthCenter" 
                            name="healthCenterId" 
                            required>
                            <option value="">Select health center...</option>
                            <?php foreach ($healthCenters as $hc): ?>
                                <option value="<?php echo $hc['HealthCenterID']; ?>"><?php echo $hc['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="space-y-3">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Items
                </label>
                
                <div id="poItemsContainer" class="space-y-2">
                    <!-- Initial item row -->
                    <div class="flex gap-2 items-start po-item-row">
                        <div class="relative flex-1">
                            <select 
                                class="w-full px-4 py-2.5 pr-10 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer" 
                                name="items[]" 
                                required>
                                <option value="">Select item...</option>
                                <?php foreach ($items as $item): ?>
                                    <option value="<?php echo $item['ItemID']; ?>"><?php echo $item['ItemName']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        <input 
                            type="number" 
                            name="quantities[]" 
                            placeholder="Qty" 
                            min="1" 
                            class="w-24 px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all" 
                            required>
                        <button 
                            type="button" 
                            onclick="removeItemRow(this)" 
                            class="p-2.5 text-red-500 hover:text-red-700 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Add Item Button -->
                <button 
                    type="button" 
                    onclick="addItemRow()" 
                    class="text-sm font-semibold text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Item
                </button>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button 
                    type="button" 
                    onclick="closeAddPOModal()" 
                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-all">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2.5 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-lg shadow-teal-900/20 transition-all active:scale-95">
                    Create Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function closeAddPOModal() {
    document.getElementById('addPOModal').classList.add('hidden');
    document.getElementById('addPOForm').reset();
    // Reset to single item row
    const container = document.getElementById('poItemsContainer');
    const rows = container.querySelectorAll('.po-item-row');
    for (let i = 1; i < rows.length; i++) {
        rows[i].remove();
    }
}

function addItemRow() {
    const container = document.getElementById('poItemsContainer');
    const firstRow = container.querySelector('.po-item-row');
    const newRow = firstRow.cloneNode(true);
    
    // Reset values
    newRow.querySelectorAll('select, input').forEach(el => el.value = '');
    
    container.appendChild(newRow);
}

function removeItemRow(button) {
    const container = document.getElementById('poItemsContainer');
    const rows = container.querySelectorAll('.po-item-row');
    
    // Don't remove if it's the only row
    if (rows.length > 1) {
        button.closest('.po-item-row').remove();
    } else {
        alert('At least one item is required');
    }
}

document.getElementById('addPOForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Purchase Order created successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Error creating purchase order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the purchase order');
    });
});
</script>
