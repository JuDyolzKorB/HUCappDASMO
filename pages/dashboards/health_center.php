<?php
// pages/dashboards/health_center.php

$myRequisitions = array_filter($requisitions, function($r) use ($user) {
    return $r['UserID'] === $user['UserID'];
});

$pendingCount = count(array_filter($myRequisitions, function($r) { return $r['StatusType'] === 'Pending'; }));
$approvedCount = count(array_filter($myRequisitions, function($r) { return ($r['StatusType'] === 'Approved' || $r['StatusType'] === 'Processed'); }));
$rejectedCount = count(array_filter($myRequisitions, function($r) { return $r['StatusType'] === 'Rejected'; }));

?>
<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Health Center Dashboard</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Manage your medical supply requisitions.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Pending -->
        <div class="bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium opacity-90">My Pending Requisitions</h3>
                <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <p class="text-3xl font-bold"><?php echo $pendingCount; ?></p>
        </div>

        <!-- Approved -->
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium opacity-90">My Approved Requisitions</h3>
                 <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-3xl font-bold"><?php echo $approvedCount; ?></p>
        </div>

        <!-- Rejected -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 text-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium opacity-90">My Rejected Requisitions</h3>
                 <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-3xl font-bold"><?php echo $rejectedCount; ?></p>
        </div>
    </div>

    <!-- Recent Requisitions -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md overflow-hidden border border-slate-200 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">My Recent Requisitions</h3>
            <a href="index.php?page=requisitions" class="bg-primary hover:bg-opacity-90 text-white px-4 py-2 rounded-md shadow-sm text-sm font-medium transition-colors">
                New Requisition
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Req #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date Requested</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Total Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    <?php 
                    $recentMyReqs = array_slice($myRequisitions, 0, 5); 
                    if (empty($recentMyReqs)):
                    ?>
                         <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">No requisitions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMyReqs as $req): 
                             $totalItems = 0;
                             foreach($req['RequisitionItems'] ?? [] as $item) $totalItems += $item['QuantityRequested'];
                        ?>
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-900 dark:text-white"><?php echo $req['RequisitionNumber']; ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?php echo date('M d, Y', strtotime($req['RequestedDate'])); ?></td>
                            <td class="px-6 py-4 text-slate-500 dark:text-slate-400"><?php echo $totalItems; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                        if($req['StatusType'] === 'Approved') echo 'bg-green-100 text-green-800';
                                        elseif($req['StatusType'] === 'Rejected') echo 'bg-red-100 text-red-800';
                                        elseif($req['StatusType'] === 'Pending') echo 'bg-yellow-100 text-yellow-800';
                                        else echo 'bg-blue-100 text-blue-800';
                                    ?>">
                                    <?php echo $req['StatusType']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
