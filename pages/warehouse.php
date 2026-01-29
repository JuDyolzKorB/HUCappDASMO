<?php
$warehouses = get_data('warehouses');
?>

<div class="space-y-6">
    <!-- Consolidated Header: Title & Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Warehouse Management</h2>
            <p class="text-slate-500 font-medium text-sm">Configure and manage storage locations for medical supplies.</p>
        </div>
        <button onclick="document.getElementById('addWarehouseModal').classList.remove('hidden')" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2.5 rounded-xl shadow-lg shadow-teal-900/10 text-sm font-bold transition-all active:scale-95">
            + Add Warehouse
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
         <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Warehouse Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Warehouse ID</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($warehouses)): ?>
                        <tr><td colspan="5" class="px-6 py-4 text-center text-slate-500">No warehouses found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $warehouse['WarehouseName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $warehouse['Location']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $warehouse['WarehouseType']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-mono"><?php echo $warehouse['WarehouseID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="#" class="text-primary hover:text-cyan-900 dark:hover:text-cyan-400">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Warehouse Modal -->
<div id="addWarehouseModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" onclick="if(event.target === this) this.classList.add('hidden')">
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md border border-slate-200/60 dark:border-slate-700/60 animate-fade-in" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">New Warehouse</h3>
        </div>
        
        <!-- Modal Body -->
        <form id="addWarehouseForm" class="p-6 space-y-5">
            <input type="hidden" name="action" value="add_warehouse">
            
            <!-- Warehouse Name -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="warehouseName">
                    Warehouse Name
                </label>
                <input 
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all" 
                    id="warehouseName" 
                    name="warehouseName" 
                    type="text" 
                    placeholder="Enter warehouse name"
                    required>
            </div>
            
            <!-- Location -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="location">
                    Location
                </label>
                <input 
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all" 
                    id="location" 
                    name="location" 
                    type="text" 
                    placeholder="Enter location"
                    required>
            </div>
            
            <!-- Warehouse Type -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="warehouseType">
                    Warehouse Type
                </label>
                <select 
                    class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer" 
                    id="warehouseType" 
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
                    onclick="document.getElementById('addWarehouseModal').classList.add('hidden')" 
                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-all">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2.5 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-lg shadow-teal-900/20 transition-all active:scale-95">
                    Add Warehouse
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('addWarehouseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error occurred');
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>
