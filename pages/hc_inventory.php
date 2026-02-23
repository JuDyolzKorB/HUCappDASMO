<?php
$hc_inventory = get_data('hc_inventory');
$user = getCurrentUser();
$hcId = $user['HealthCenterID'] ?? null;
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Health Center Inventory</h2>
            <p class="text-slate-500 font-medium text-sm">Inventory specific to your health center.</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Item Name</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Category</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Batch ID</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Quantity</th>
                        <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 dark:text-slate-300 uppercase tracking-widest">Expiry</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800">
                    <?php if (empty($hc_inventory)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium italic">No inventory found for this health center.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($hc_inventory as $item): ?>
                        <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white"><?php echo $item['ItemName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-primary dark:text-teal-400"><?php echo $item['ItemType']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $item['BatchID']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-900 dark:text-white"><?php echo number_format($item['QuantityOnHand']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $item['ExpiryDate'] ?: 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
