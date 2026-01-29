<?php
$inventory = get_data('inventory');
$items = get_data('items');

// Aggregate inventory by ItemID to show total quantities and next expiry
$aggregatedInventory = [];
$batchesByItem = []; // Store batches grouped by ItemID

foreach ($inventory as $batch) {
    $itemId = $batch['ItemID'];
    
    if (!isset($aggregatedInventory[$itemId])) {
        // Find item details
        $itemDetails = null;
        foreach ($items as $item) {
            if ($item['ItemID'] === $itemId) {
                $itemDetails = $item;
                break;
            }
        }
        
        $aggregatedInventory[$itemId] = [
            'ItemID' => $itemId,
            'ItemName' => $itemDetails ? $itemDetails['ItemName'] : $itemId,
            'Category' => $itemDetails ? ($itemDetails['ItemType'] ?? 'N/A') : 'N/A',
            'Unit' => $itemDetails ? ($itemDetails['UnitOfMeasure'] ?? 'N/A') : 'N/A',
            'TotalQuantity' => 0,
            'NextExpiry' => null
        ];
        $batchesByItem[$itemId] = [];
    }
    
    // Add to total quantity
    $aggregatedInventory[$itemId]['TotalQuantity'] += $batch['QuantityOnHand'];
    
    // Track earliest expiry date (FEFO - First Expired, First Out)
    if ($batch['QuantityOnHand'] > 0) {
        if ($aggregatedInventory[$itemId]['NextExpiry'] === null || 
            strtotime($batch['ExpiryDate']) < strtotime($aggregatedInventory[$itemId]['NextExpiry'])) {
            $aggregatedInventory[$itemId]['NextExpiry'] = $batch['ExpiryDate'];
        }
        // Store batch for this item
        $batchesByItem[$itemId][] = $batch;
    }
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
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Item ID</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Item Name</th>
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
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400 font-medium italic">No inventory items found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($aggregatedInventory as $index => $item): ?>
                        <!-- Main Item Row -->
                        <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer" onclick="toggleBatches('<?php echo $item['ItemID']; ?>')">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $item['ItemID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white"><?php echo $item['ItemName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-primary dark:text-teal-400"><?php echo $item['Category']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white"><?php echo number_format($item['TotalQuantity']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-primary dark:text-teal-400"><?php echo $item['Unit']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                                <?php echo $item['NextExpiry'] ? date('n/j/Y', strtotime($item['NextExpiry'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <svg id="chevron-<?php echo $item['ItemID']; ?>" class="w-5 h-5 text-slate-400 inline-block transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </td>
                        </tr>
                        
                        <!-- Expandable Batch Details Row -->
                        <tr id="batches-<?php echo $item['ItemID']; ?>" class="hidden bg-slate-50/50 dark:bg-slate-900/30 border-b border-slate-200 dark:border-slate-700">
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
                                                        <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300"><?php echo date('n/j/Y', strtotime($batch['ExpiryDate'])); ?></td>
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

<script>
function toggleBatches(itemId) {
    const batchesRow = document.getElementById('batches-' + itemId);
    const chevron = document.getElementById('chevron-' + itemId);
    
    if (batchesRow.classList.contains('hidden')) {
        // Close all other open batch rows
        document.querySelectorAll('[id^="batches-"]').forEach(row => {
            row.classList.add('hidden');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(icon => {
            icon.style.transform = 'rotate(0deg)';
        });
        
        // Open this batch row
        batchesRow.classList.remove('hidden');
        chevron.style.transform = 'rotate(90deg)';
    } else {
        // Close this batch row
        batchesRow.classList.add('hidden');
        chevron.style.transform = 'rotate(0deg)';
    }
}
</script>
