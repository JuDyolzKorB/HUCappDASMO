<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireRole(['Administrator', 'Health Center Staff']);

$user = getCurrentUser();
$healthCenterId = $user['HealthCenterID'] ?? null;

// Debug: If administrator and no HC assigned, maybe show a selector (not implemented yet)
if (!$healthCenterId && hasRole('Administrator')) {
    echo "<div class='p-4 bg-yellow-100 text-yellow-700 rounded mb-4'>Note: No Health Center assigned to your profile. Showing empty inventory.</div>";
}

$hcConn = $healthCenterId ? Database::getHCConnection($healthCenterId) : null;
$inventory = [];

if ($hcConn) {
    // Join with main Item table to get names
    // Note: Items are global, so we use Item table from 'hucappdb'
    // This is cross-database query if they are on same server.
    // If not, we might need to fetch item mapping separately.
    
    // For now, assume same MySQL server and use main DB name for Item join
    $mainDb = DB_NAME;
    $sql = "SELECT hci.*, i.ItemName, i.ItemType, i.UnitOfMeasure 
            FROM HC_Inventory hci
            LEFT JOIN $mainDb.Item i ON hci.ItemID = i.ItemID
            ORDER BY hci.CreatedAt DESC";
    
    try {
        $stmt = $hcConn->query($sql);
        $inventory = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("HC Inventory Fetch Error: " . $e->getMessage());
    }
}
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Health Center Inventory</h1>
            <p class="text-slate-500 dark:text-slate-400">Manage local stock received from the central warehouse.</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Item Name</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Batch ID</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center">Qty on Hand</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Expiry</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Added On</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400 italic">
                                No items found in your inventory.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($item['ItemName'] ?? 'Unknown Item'); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        <?php echo htmlspecialchars($item['ItemType'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-mono text-slate-500 dark:text-slate-400">
                                    #<?php echo htmlspecialchars($item['BatchID']); ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold <?php echo $item['QuantityOnHand'] > 10 ? 'text-teal-600 dark:text-teal-400' : 'text-amber-600 dark:text-amber-400'; ?>">
                                        <?php echo number_format($item['QuantityOnHand']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                    <?php echo $item['ExpiryDate'] ? date('M d, Y', strtotime($item['ExpiryDate'])) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-500">
                                    <?php echo date('M d, Y', strtotime($item['CreatedAt'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
