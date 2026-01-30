<?php
// pages/dashboards/accounting.php

$totalInventoryValue = 0;
foreach ($inventory as $batch) {
    // Inventory items need UnitCost, assume it's there or 0
    $cost = isset($batch['UnitCost']) ? $batch['UnitCost'] : 0;
    $totalInventoryValue += $batch['QuantityOnHand'] * $cost;
}

$completedPOs = 0;
$approvedPOs = [];
foreach ($purchaseOrders as $po) {
    if ($po['StatusType'] === 'Completed') $completedPOs++;
    if ($po['StatusType'] === 'Approved') $approvedPOs[] = $po;
}

$receivings = get_data('receivings');
?>

<div class="space-y-8 animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white font-display tracking-tight">Financial Console</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">Audit, procurement oversight, and inventory valuation.</p>
        </div>
        <div class="flex items-center gap-3">
             <button onclick="window.print()" class="btn bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 px-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Summary
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="status-badge status-success">Live Value</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Total Inventory Value</p>
            <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1">₱<?php echo number_format($totalInventoryValue, 2); ?></h3>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <span class="status-badge status-pending">In Verification</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">POs Awaiting Completion</p>
            <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo $completedPOs; ?></h3>
        </div>

        <a href="index.php?page=reports" class="stat-card group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <svg class="w-5 h-5 text-slate-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Full Compliance</p>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">Generate Reports</h3>
        </a>
    </div>

    <!-- Approved POs Table -->
    <div class="table-container">
        <div class="table-header">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-white font-display">Approved Purchase Orders</h3>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Pending delivery & verification</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">PO Number</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Supplier</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date Approved</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (empty($approvedPOs)): ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500 font-medium">No purchase orders are currently approved and awaiting delivery.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($approvedPOs, 0, 10) as $po): 
                             $approval = null;
                             foreach ($po['ApprovalLogs'] ?? [] as $log) {
                                 if ($log['Decision'] === 'Approved') $approval = $log;
                             }
                        ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white text-sm"><?php echo $po['PONumber']; ?></td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $po['SupplierName']; ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-500 text-xs font-semibold"><?php echo $approval ? date('M d, Y', strtotime($approval['DecisionDate'])) : 'N/A'; ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-success">Approved</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Receiving Activity -->
    <div class="table-container">
        <div class="table-header">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-white font-display">Recent Receiving Activity</h3>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Logs of recently validated stocks</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Receiving ID</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">PO Number</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Received By</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date Received</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                    <?php 
                    $users = get_data('users');
                    if (empty($receivings)): 
                    ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-500 font-medium">No receiving activities have been logged recently.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($receivings, 0, 10) as $rec): 
                            $poNum = 'N/A';
                            foreach ($purchaseOrders as $p) { if ($p['POID'] === $rec['POID']) { $poNum = $p['PONumber']; break; } }
                            $recUser = 'Unknown';
                             foreach ($users as $u) { if ($u['UserID'] === $rec['UserID']) { $recUser = $u['FirstName'] . ' ' . $u['LastName']; break; } }
                        ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white text-sm">
                                <span class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-xs"><?php echo $rec['ReceivingID']; ?></span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $poNum; ?></td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $recUser; ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-500 text-xs font-semibold"><?php echo date('M d, Y h:i A', strtotime($rec['ReceivedDate'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
