<?php
$inventory = get_data('inventory');
$items = get_data('items');

// Aggregate inventory by ItemID to show total quantities and next expiry
$aggregatedInventory = [];
$batchesByItem = []; // Store batches grouped by ItemID

// Index batches by ItemID for faster lookup
$batchesByItem = [];
foreach ($inventory as $batch) {
    // Only count batches with positive quantity
    // (Or include 0 if you want to see history, but usually we just want active stock)
    if ($batch['QuantityOnHand'] > 0) {
        $batchesByItem[$batch['ItemID']][] = $batch;
    }
}

$aggregatedInventory = [];
foreach ($items as $item) {
    $itemId = $item['ItemID'];
    
    // Calculate total quantity from batches
    $totalQty = 0;
    $nextExpiry = null;
    $itemBatches = $batchesByItem[$itemId] ?? [];
    
    foreach ($itemBatches as $b) {
        $totalQty += $b['QuantityOnHand'];
        
        // Track earliest expiry (FEFO)
        if ($nextExpiry === null || strtotime($b['ExpiryDate']) < strtotime($nextExpiry)) {
            $nextExpiry = $b['ExpiryDate'];
        }
    }

    $aggregatedInventory[] = [
        'ItemID'      => $itemId,
        'ItemName'    => $item['ItemName'],
        'Brand'       => $item['Brand'] ?? '',
        'DosageUnit'  => $item['DosageUnit'] ?? '',
        'Category'    => $item['ItemType'] ?? 'N/A',
        'Unit'        => $item['UnitOfMeasure'] ?? 'N/A',
        'TotalQuantity' => $totalQty,
        'NextExpiry'  => $nextExpiry
    ];
}

// Sort batches by expiry date (FEFO) for each item
foreach ($batchesByItem as $itemId => &$batches) {
    usort($batches, function($a, $b) {
        return strtotime($a['ExpiryDate']) - strtotime($b['ExpiryDate']);
    });
}

// Convert to indexed array for easier iteration
$aggregatedInventory = array_values($aggregatedInventory);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Inventory Status</h2>
            <p class="text-slate-500 font-medium text-sm">Real-time overview of current stock levels and expiry information.</p>
        </div>
        <?php if ($userRole === 'Administrator' || $userRole === 'Head Pharmacist'): ?>
        <button onclick="openAddItemModal()" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2.5 rounded-xl shadow-lg shadow-teal-900/10 text-sm font-bold transition-all active:scale-95">
             + Add Item
         </button>
        <?php endif; ?>
    </div>

    <!-- Category Filter Tabs -->
    <div class="mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-2">
            <div class="flex gap-2">
                <button onclick="filterByCategory('all')" id="tab-all" class="category-tab active-tab px-6 py-2.5 rounded-lg text-sm font-bold transition-all">
                    All
                </button>
                <button onclick="filterByCategory('medicine')" id="tab-medicine" class="category-tab px-6 py-2.5 rounded-lg text-sm font-bold transition-all">
                    Medicine
                </button>
                <button onclick="filterByCategory('utility')" id="tab-utility" class="category-tab px-6 py-2.5 rounded-lg text-sm font-bold transition-all">
                    Utility
                </button>
                <button onclick="filterByCategory('others')" id="tab-others" class="category-tab px-6 py-2.5 rounded-lg text-sm font-bold transition-all">
                    Others
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Item ID</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Item Name</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Brand</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Dosage</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Category</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Total Quantity</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Unit</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Next to Expire (FEFO)</th>
                        <th class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800">
                     <?php if (empty($aggregatedInventory)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-slate-400 font-medium italic">No inventory items found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($aggregatedInventory as $index => $item): ?>
                        <?php
                            // Map category for filtering
                            $filterCategory = 'others';
                            if ($item['Category'] === 'Medicine') {
                                $filterCategory = 'medicine';
                            } elseif ($item['Category'] === 'Supply' || $item['Category'] === 'Equipment') {
                                $filterCategory = 'utility';
                            }
                        ?>
                        <!-- Main Item Row -->
                        <tr class="inventory-row border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer" data-category="<?php echo $filterCategory; ?>" onclick="toggleBatches('<?php echo $item['ItemID']; ?>')">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $item['ItemID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white"><?php echo $item['ItemName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo !empty($item['Brand']) ? htmlspecialchars($item['Brand']) : '<span class="text-slate-300 dark:text-slate-600">—</span>'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo !empty($item['DosageUnit']) ? htmlspecialchars($item['DosageUnit']) : '<span class="text-slate-300 dark:text-slate-600">—</span>'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-primary dark:text-teal-400"><?php echo $item['Category']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white"><?php echo number_format($item['TotalQuantity']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-primary dark:text-teal-400"><?php echo $item['Unit']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                <?php echo (strtoupper($item['Unit']) === 'UNIT') ? 'N/A' : ($item['NextExpiry'] ? date('n/j/Y', strtotime($item['NextExpiry'])) : 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end space-x-2">
                                     <?php if ($userRole === 'Administrator' || $userRole === 'Head Pharmacist'): ?>
                                     <button onclick="event.stopPropagation(); openEditItemModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-slate-400 hover:text-primary transition-colors">
                                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                     </button>
                                     <button onclick="event.stopPropagation(); deleteItem('<?php echo $item['ItemID']; ?>')" class="text-slate-400 hover:text-red-500 transition-colors">
                                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                     </button>
                                     <?php endif; ?>
                                     <button onclick="toggleBatches('<?php echo $item['ItemID']; ?>')" class="text-slate-400 hover:text-slate-600 transition-colors">
                                         <svg id="chevron-<?php echo $item['ItemID']; ?>" class="w-5 h-5 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                         </svg>
                                     </button>
                                </div>
                             </td>
                        </tr>
                        
                        <!-- Expandable Batch Details Row -->
                        <tr id="batches-<?php echo $item['ItemID']; ?>" class="batch-row hidden bg-slate-50/50 dark:bg-slate-900/30 border-b border-slate-200 dark:border-slate-700" data-category="<?php echo $filterCategory; ?>">
                            <td colspan="7" class="px-6 py-4">
                                <div class="pl-8">
                                    <h4 class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-3">Batches (First Expiry, First Out)</h4>
                                    <div class="bg-white dark:bg-slate-800 rounded-lg border border-slate-200/60 dark:border-slate-700/60 overflow-hidden">
                                        <table class="min-w-full">
                                            <thead class="bg-slate-100/80 dark:bg-slate-800/80">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300">Expiry Date</th>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300">Quantity</th>
                                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300">Cost/Item</th>
                                                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-600 dark:text-slate-300">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                                                <?php if (isset($batchesByItem[$item['ItemID']])): ?>
                                                    <?php foreach ($batchesByItem[$item['ItemID']] as $batch): ?>
                                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300"><?php echo (strtoupper($item['Unit']) === 'UNIT') ? 'N/A' : date('n/j/Y', strtotime($batch['ExpiryDate'])); ?></td>
                                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900 dark:text-white"><?php echo number_format($batch['QuantityOnHand']); ?></td>
                                                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">$<?php echo number_format($batch['UnitCost'], 2); ?></td>
                                                        <td class="px-4 py-3 text-right">
                                                            <button onclick="event.stopPropagation(); window.location.href='?page=adjustments&batch=<?php echo $batch['BatchID']; ?>'" class="px-3 py-1.5 text-xs font-bold text-white bg-orange-500 hover:bg-orange-600 rounded-md transition-colors">
                                                                Adjust
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="itemModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm overflow-y-auto h-full w-full z-[9999] flex items-center justify-center p-4" onclick="if(event.target === this) closeItemModal()">
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg border border-slate-200/60 dark:border-slate-700/60" onclick="event.stopPropagation()">
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50">
            <h3 id="modalTitle" class="text-xl font-bold text-slate-900 dark:text-white">Add New Item</h3>
        </div>
        <form id="itemForm" class="p-6 space-y-5">
            <input type="hidden" name="action" id="formAction" value="add_item">
            <input type="hidden" name="itemId" id="formItemId">
            
            <!-- Item Info -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Item Name</label>
                <input type="text" name="itemName" id="itemName" required class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white">
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

            <!-- Initial Batch Section (only shown when adding a new item) -->
            <div id="batchSection">
                <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Initial Batch <span class="font-normal normal-case text-slate-400">(optional)</span></p>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Batch ID</label>
                            <input type="number" name="batchId" id="batchId" min="1" placeholder="Auto-assigned if empty" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Lot Number</label>
                            <input type="text" name="lotNumber" id="lotNumber" placeholder="e.g. LOT-2025-001" class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400">
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

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeItemModal()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-700/50 rounded-lg">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-bold text-white bg-primary rounded-lg">Save Item</button>
            </div>
        </form>
    </div>
</div>


<style>
.category-tab {
    background-color: transparent;
    color: #64748b;
}

.category-tab:hover {
    background-color: rgba(20, 184, 166, 0.1);
    color: #14b8a6;
}

.active-tab {
    background-color: #14b8a6;
    color: white;
}

.active-tab:hover {
    background-color: #0d9488;
    color: white;
}

.dark .category-tab {
    color: #94a3b8;
}

.dark .category-tab:hover {
    background-color: rgba(20, 184, 166, 0.2);
    color: #5eead4;
}

.dark .active-tab {
    background-color: #14b8a6;
    color: white;
}

.dark .active-tab:hover {
    background-color: #0d9488;
}
</style>

<script>
function filterByCategory(category) {
    // Update active tab styling
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.remove('active-tab');
    });
    document.getElementById('tab-' + category).classList.add('active-tab');
    
    // Filter inventory rows
    const rows = document.querySelectorAll('.inventory-row');
    const batchRows = document.querySelectorAll('.batch-row');
    
    rows.forEach(row => {
        const rowCategory = row.getAttribute('data-category');
        if (category === 'all' || rowCategory === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
            // Also hide the corresponding batch row if it's expanded
            const itemId = row.querySelector('td').textContent.trim();
            const batchRow = document.getElementById('batches-' + itemId);
            if (batchRow && !batchRow.classList.contains('hidden')) {
                batchRow.classList.add('hidden');
                const chevron = document.getElementById('chevron-' + itemId);
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    // Hide batch rows for filtered out items
    batchRows.forEach(row => {
        const rowCategory = row.getAttribute('data-category');
        if (category !== 'all' && rowCategory !== category) {
            row.classList.add('hidden');
        }
    });
}

function toggleBatches(itemId) {
    const batchesRow = document.getElementById('batches-' + itemId);
    const chevron = document.getElementById('chevron-' + itemId);
    
    if (batchesRow.classList.contains('hidden')) {
        document.querySelectorAll('[id^="batches-"]').forEach(row => row.classList.add('hidden'));
        document.querySelectorAll('[id^="chevron-"]').forEach(icon => icon.style.transform = 'rotate(0deg)'); // Reset lookup
        
        batchesRow.classList.remove('hidden');
        if(chevron) chevron.style.transform = 'rotate(180deg)';
    } else {
        batchesRow.classList.add('hidden');
        if(chevron) chevron.style.transform = 'rotate(0deg)';
    }
}

function openAddItemModal() {
    document.getElementById('modalTitle').innerText = 'Add New Item';
    document.getElementById('formAction').value = 'add_item';
    document.getElementById('formItemId').value = '';
    document.getElementById('itemForm').reset();
    document.getElementById('batchSection').style.display = ''; // show batch section
    document.getElementById('itemModal').classList.remove('hidden');
}

function openEditItemModal(item) {
    document.getElementById('modalTitle').innerText = 'Edit Item';
    document.getElementById('formAction').value = 'update_item';
    document.getElementById('formItemId').value = item.ItemID;
    document.getElementById('itemName').value = item.ItemName;
    document.getElementById('itemBrand').value = item.Brand || '';
    document.getElementById('dosageUnit').value = item.DosageUnit || '';
    document.getElementById('itemType').value = item.Category;
    document.getElementById('unitOfMeasure').value = item.Unit;
    document.getElementById('batchSection').style.display = 'none'; // hide batch section for edits
    document.getElementById('itemModal').classList.remove('hidden');
}

function closeItemModal() {
    document.getElementById('itemModal').classList.add('hidden');
}

function deleteItem(itemId) {
    if(!confirm('Are you sure? This will delete the item and ALL its history/stock.')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_item');
    formData.append('itemId', itemId);
    
    fetch('api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) window.location.reload();
        else alert(data.message || 'Error');
    });
}

document.getElementById('itemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success) window.location.reload();
        else alert(data.message || 'Error');
    });
});
</script>
