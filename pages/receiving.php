<?php
$purchaseOrders = get_data('purchase_orders');
$receivings = get_data('receivings');
$users = get_data('users');

$posToReceive = array_filter($purchaseOrders, function($po) {
    return $po['StatusType'] === 'Approved';
});

// Helper functions (could be moved to functions.php eventually)
function getUserFullName($userId, $users) {
    foreach ($users as $u) {
        if ($u['UserID'] === $userId) return $u['FirstName'] . ' ' . $u['LastName'];
    }
    return 'Unknown User';
}

function getPONumber($poid, $purchaseOrders) {
    foreach ($purchaseOrders as $p) {
        if ($p['POID'] === $poid) return $p['PONumber'];
    }
    return $poid;
}

$tab = $_GET['tab'] ?? 'awaiting';
?>

<div class="space-y-6">
    <!-- Consolidated Header: Title & Tab Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                <?php echo $tab === 'awaiting' ? 'Awaiting Receiving' : 'Receiving History'; ?>
            </h2>
            <p class="text-slate-500 font-medium text-sm">
                <?php echo $tab === 'awaiting' ? 'Inspect and process incoming shipments from purchase orders.' : 'Logs of all recently received inventory items and batches.'; ?>
            </p>
        </div>

        <div class="flex space-x-1 bg-slate-100 dark:bg-slate-900/50 p-1 rounded-lg border border-slate-200/50 dark:border-slate-800/50">
            <a href="index.php?page=receiving&tab=awaiting" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $tab === 'awaiting' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary dark:text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-600/50'; ?>">Awaiting Receiving</a>
            <a href="index.php?page=receiving&tab=history" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $tab === 'history' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary dark:text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-600/50'; ?>">Receiving History</a>
        </div>
    </div>

    <?php if ($tab === 'awaiting'): ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Purchase Orders - Approved for Receiving</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">PO #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Supplier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">PO Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        <?php if (empty($posToReceive)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                    No purchase orders are currently approved for receiving.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posToReceive as $po): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $po['PONumber']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $po['SupplierName']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo date('M d, Y', strtotime($po['PODate'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo $po['StatusType']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="index.php?page=receive_items&id=<?php echo $po['POID']; ?>" class="text-primary hover:text-cyan-900 dark:hover:text-cyan-400 font-medium">Receive Items</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
             <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Receiving History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Receiving ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">PO #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Received Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Received By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                        <?php if (empty($receivings)): ?>
                             <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                    No receiving history found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($receivings as $rec): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $rec['ReceivingID']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-slate-500 dark:text-slate-400"><?php echo getPONumber($rec['POID'], $purchaseOrders); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo date('M d, Y h:i A', strtotime($rec['ReceivedDate'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo getUserFullName($rec['UserID'], $users); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
