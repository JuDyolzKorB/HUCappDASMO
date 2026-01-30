<?php
// pages/dashboards/warehouse.php

$pendingIssuanceCount = 0;
foreach ($requisitions as $r) {
    if ($r['StatusType'] === 'Approved') $pendingIssuanceCount++;
}

$pendingReceivingCount = 0;
foreach ($purchaseOrders as $po) {
    if ($po['StatusType'] === 'Approved') $pendingReceivingCount++;
}

$nearlyExpiredItems = 0;
$today = time();
$threeMonths = strtotime('+3 months', $today);

foreach ($inventory as $batch) {
    $expiry = strtotime($batch['ExpiryDate']);
    if ($expiry < $threeMonths) $nearlyExpiredItems++;
}

?>

<div class="space-y-8 animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white font-display tracking-tight">Warehouse Console</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">Operational command for inventory and logistics.</p>
        </div>
        <div class="flex items-center gap-3">
             <a href="index.php?page=purchase-orders" class="btn btn-primary px-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Purchase Order
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </div>
                <span class="status-badge status-pending">Action Required</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Requisitions to Issue</p>
            <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo $pendingIssuanceCount; ?></h3>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <span class="status-badge status-info">Monitoring</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">POs Awaiting Receiving</p>
            <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo $pendingReceivingCount; ?></h3>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <span class="status-badge status-danger">Expiring Soon</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Nearly Expired Items</p>
            <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo $nearlyExpiredItems; ?></h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pending Issuance Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white font-display">Pending Issuance</h3>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Approved requisitions</p>
                </div>
                <a href="index.php?page=issuance" class="text-xs font-bold text-teal-600 hover:text-teal-700 transition-colors">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                    <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Req #</th>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Health Center</th>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                        <?php 
                        $pendingIssuanceReqs = array_filter($requisitions, function($r) { return $r['StatusType'] === 'Approved'; });
                        if (empty($pendingIssuanceReqs)):
                        ?>
                            <tr><td colspan="3" class="px-6 py-8 text-center text-slate-500 font-medium">No requisitions pending issuance.</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice($pendingIssuanceReqs, 0, 5) as $req): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white text-sm"><?php echo $req['RequisitionNumber'] ?? 'REQ-Unknown'; ?></td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $req['HealthCenterName']; ?></td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-500 text-xs font-semibold"><?php echo date('M d, Y', strtotime($req['RequestedDate'] ?? $req['RequestDate'] ?? 'now')); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Awaiting Receiving Table -->
        <div class="table-container">
            <div class="table-header">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white font-display">Awaiting Receiving</h3>
                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Approved POs in transit</p>
                </div>
                <a href="index.php?page=receiving" class="text-xs font-bold text-teal-600 hover:text-teal-700 transition-colors">View All</a>
            </div>
             <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                    <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">PO #</th>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Supplier</th>
                            <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                        <?php 
                        $pendingReceivingPOs = array_filter($purchaseOrders, function($po) { return $po['StatusType'] === 'Approved'; });
                         if (empty($pendingReceivingPOs)):
                        ?>
                             <tr><td colspan="3" class="px-6 py-8 text-center text-slate-500 font-medium">No POs awaiting receiving.</td></tr>
                        <?php else: ?>
                             <?php foreach (array_slice($pendingReceivingPOs, 0, 5) as $po): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white text-sm"><?php echo $po['PONumber'] ?? 'PO-Unknown'; ?></td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $po['SupplierName'] ?? 'Unknown Supplier'; ?></td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-500 text-xs font-semibold"><?php echo date('M d, Y', strtotime($po['PODate'])); ?></td>
                            </tr>
                             <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
