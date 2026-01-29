<?php
$requisitions = get_data('requisitions');

// Filter requisitions based on role logic if needed
// For now, simple list
?>

<div class="space-y-6">
    <!-- Consolidated Header: Title & Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Request Queue</h2>
            <p class="text-slate-500 font-medium text-sm">Monitor and manage all item requests from health centers and departments.</p>
        </div>
        <button onclick="openRequisitionFormModal()" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2.5 rounded-xl shadow-lg shadow-teal-900/10 text-sm font-bold transition-all active:scale-95">
            + New Requisition
        </button>
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
                    <?php if (empty($requisitions)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">No requisitions found.</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $allItems = get_data('items'); // Load all items to map names
                        foreach ($requisitions as $r): 
                            $statusColor = 'bg-slate-100 text-slate-800';
                            if ($r['StatusType'] === 'Approved') $statusColor = 'bg-green-100 text-green-800';
                            if ($r['StatusType'] === 'Rejected') $statusColor = 'bg-red-100 text-red-800';
                            if ($r['StatusType'] === 'Pending') $statusColor = 'bg-yellow-100 text-yellow-800';
                            
                            // Enrich Items with Names for JS
                            foreach ($r['RequisitionItems'] as &$ri) {
                                foreach ($allItems as $i) {
                                    if ($i['ItemID'] === $ri['ItemID']) {
                                        $ri['ItemName'] = $i['ItemName'];
                                        break;
                                    }
                                }
                            }
                            // Securely pass object to JS
                            $rJson = htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $r['RequisitionNumber']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $r['HealthCenterName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $r['RequestedByFullName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo date('M d, Y', strtotime($r['RequestedDate'])); ?></td>
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
