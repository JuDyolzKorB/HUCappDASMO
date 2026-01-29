<?php
$purchaseOrders = get_data('purchase_orders');
?>

<div class="space-y-6">
    <!-- Consolidated Header: Title & Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Purchase Orders</h2>
            <p class="text-slate-500 font-medium text-sm">Manage procurement and track orders from medical suppliers.</p>
        </div>
        <button onclick="document.getElementById('addPOModal').classList.remove('hidden')" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2.5 rounded-xl shadow-lg shadow-teal-900/10 text-sm font-bold transition-all active:scale-95">
            + New Purchase Order
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                         <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">PO No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Status</th>
                         <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                     <?php if (empty($purchaseOrders)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-500">No purchase orders found.</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $allItems = get_data('items');
                        $suppliers = get_data('suppliers');
                        $healthCenters = get_data('health_centers');
                        
                        foreach ($purchaseOrders as $po): 
                            $statusColor = 'bg-slate-100 text-slate-800';
                            if ($po['StatusType'] === 'Approved') $statusColor = 'bg-green-100 text-green-800';
                            if ($po['StatusType'] === 'Pending') $statusColor = 'bg-yellow-100 text-yellow-800';
                            if ($po['StatusType'] === 'Completed') $statusColor = 'bg-blue-100 text-blue-800';
                            
                            // Ensure SupplierName is populated
                            if (empty($po['SupplierName']) && !empty($po['SupplierID'])) {
                                foreach ($suppliers as $s) {
                                    if ($s['SupplierID'] === $po['SupplierID']) {
                                        $po['SupplierName'] = $s['Name'];
                                        break;
                                    }
                                }
                            }
                            
                            // Ensure HealthCenterName is populated
                            if (empty($po['HealthCenterName']) && !empty($po['HealthCenterID'])) {
                                foreach ($healthCenters as $hc) {
                                    if ($hc['HealthCenterID'] === $po['HealthCenterID']) {
                                        $po['HealthCenterName'] = $hc['Name'];
                                        break;
                                    }
                                }
                            }
                            
                            // Map item names
                            if (isset($po['PurchaseOrderItems']) && is_array($po['PurchaseOrderItems'])) {
                                foreach ($po['PurchaseOrderItems'] as &$poi) {
                                    foreach ($allItems as $i) {
                                        if ($i['ItemID'] === $poi['ItemID']) {
                                            $poi['ItemName'] = $i['ItemName'];
                                            break;
                                        }
                                    }
                                }
                            }
                            $poJson = htmlspecialchars(json_encode($po), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $po['PONumber']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $po['SupplierName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo date('M d, Y', strtotime($po['PODate'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                    <?php echo $po['StatusType']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick='openPODetailsModal(<?php echo $poJson; ?>)' class="text-primary hover:text-cyan-900 dark:hover:text-cyan-400">View</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'components/add_purchase_order_modal.php'; ?>
<?php include 'components/purchase_order_details_modal.php'; ?>
