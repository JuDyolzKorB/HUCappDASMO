<?php
$user = getCurrentUser();
$healthCenterId = $user['HealthCenterID'] ?? null;
$userRole = $user['Role'] ?? 'User';

$requisitions = get_data('requisitions');

// Filter by Health Center if specified
if ($healthCenterId) {
    $requisitions = array_filter($requisitions, function($r) use ($healthCenterId) {
        return $r['HealthCenterID'] == $healthCenterId;
    });
}

// Get tab parameter
$tab = $_GET['tab'] ?? 'active';

// Filter requisitions based on tab
if ($tab === 'history') {
    $filteredRequisitions = array_filter($requisitions, function($r) {
        return in_array($r['StatusType'], ['Completed', 'Rejected']);
    });
} else {
    $filteredRequisitions = array_filter($requisitions, function($r) {
        return in_array($r['StatusType'], ['Pending', 'Approved']);
    });
}
?>

<div class="space-y-6">
    <!-- Consolidated Header: Title & Tab Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                <?php echo $tab === 'active' ? 'Request Queue' : 'Requisition History'; ?>
            </h2>
            <p class="text-slate-500 font-medium text-sm">
                <?php echo $tab === 'active' ? 'Monitor and manage all item requests from health centers and departments.' : 'Historical records of completed and rejected requisitions.'; ?>
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <!-- Tab Navigation -->
            <div class="flex space-x-1 bg-slate-200 dark:bg-slate-700/50 p-1 rounded-lg border border-slate-200/50 dark:border-slate-800/50">
                <a href="index.php?page=requisitions&tab=active" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $tab === 'active' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary dark:text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-600/50'; ?>">Active</a>
                <a href="index.php?page=requisitions&tab=history" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $tab === 'history' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary dark:text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-600/50'; ?>">History</a>
            </div>
            
            <?php if ($tab === 'active' && ($userRole === 'Administrator' || $userRole === 'Health Center Staff')): ?>
            <button onclick="openRequisitionFormModal()" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2.5 rounded-xl shadow-lg shadow-teal-900/10 text-sm font-bold transition-all active:scale-95">
                + New Requisition
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Requisitions Filter/Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                         <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Requisition No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Health Center</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Requested By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($filteredRequisitions)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">
                            <?php echo $tab === 'active' ? 'No active requisitions found.' : 'No requisition history found.'; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $allItems = get_data('items'); // Load all items to map names
                        foreach ($filteredRequisitions as $r): 
                            $statusColor = 'bg-slate-100 text-slate-800';
                            if ($r['StatusType'] === 'Approved') $statusColor = 'bg-green-100 text-green-800';
                            if ($r['StatusType'] === 'Rejected') $statusColor = 'bg-red-100 text-red-800';
                            if ($r['StatusType'] === 'Pending') $statusColor = 'bg-yellow-100 text-yellow-800';
                            if ($r['StatusType'] === 'Completed') $statusColor = 'bg-blue-100 text-blue-800';
                            
                            // Enrich Items with Names for JS
                            foreach ($r['RequisitionItems'] as &$ri) {
                                foreach ($allItems as $i) {
                                    if ($i['ItemID'] == $ri['ItemID']) {
                                        $ri['ItemName'] = $i['ItemName'];
                                        break;
                                    }
                                }
                            }
                            // Securely pass object to JS
                            $rJson = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $r['RequisitionNumber'] ?? 'REQ-Unknown'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $r['HealthCenterName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $r['RequestedByFullName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo date('M d, Y', strtotime($r['RequestedDate'] ?? $r['RequestDate'] ?? 'now')); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColor; ?>">
                                    <?php echo $r['StatusType']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick='openRequisitionDetailsModal(<?php echo $rJson; ?>)' class="text-primary hover:text-cyan-900 dark:hover:text-cyan-400">View</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'components/requisition_form_modal.php'; ?>
<?php include 'components/requisition_details_modal.php'; ?>
