<?php
$requisitions = get_data('requisitions');
$issuances = get_data('issuances');
$inventory = get_data('inventory');

// Helper
function getItemName($itemId, $items) {
    global $db;
    $items = get_data('items');
    foreach ($items as $item) {
        if ($item['ItemID'] === $itemId) return $item['ItemName'];
    }
    return $itemId;
}

$tab = $_GET['tab'] ?? 'request';
?>

<div class="space-y-6">
    <!-- Consolidated Header: Title & Tab Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">
                <?php echo $tab === 'request' ? 'Issuance Queue' : 'Issuance History'; ?>
            </h2>
            <p class="text-slate-500 font-medium text-sm">
                <?php echo $tab === 'request' ? 'Manage and process pending item requisitions.' : 'Historical records of all items issued to health centers.'; ?>
            </p>
        </div>

        <div class="flex space-x-1 bg-slate-200 dark:bg-slate-700/50 p-1 rounded-lg border border-slate-200/50 dark:border-slate-800/50">
             <a href="index.php?page=issuance&tab=request" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $tab === 'request' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary dark:text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-600/50'; ?>">Request Queue</a>
            <a href="index.php?page=issuance&tab=history" class="px-4 py-1.5 text-sm font-semibold rounded-md transition-colors <?php echo $tab === 'history' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary dark:text-white' : 'text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-600/50'; ?>">Issuance History</a>
        </div>
    </div>
    
    <?php if ($tab === 'request'): ?>
    <!-- Request Queue -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Pending Requisitions for Issuance</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                         <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Req No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Health Center</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Date Requested</th>
                         <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    <?php 
                    $pendingReqs = array_filter($requisitions, function($r) { return $r['StatusType'] === 'Approved'; });
                    if (empty($pendingReqs)): 
                    ?>
                        <tr><td colspan="5" class="px-6 py-4 text-center text-slate-500">No approved requisitions pending issuance.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingReqs as $req): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white"><?php echo $req['RequisitionNumber']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo $req['HealthCenterName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400"><?php echo date('M d, Y', strtotime($req['RequestedDate'])); ?></td>
                             <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Approved
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="index.php?page=process_issuance&id=<?php echo $req['RequisitionID']; ?>" class="text-primary hover:text-cyan-900 dark:hover:text-cyan-400 font-medium">Process Issuance</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <!-- History -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200/60 dark:border-slate-700/60 overflow-hidden">
        <div class="overflow-x-auto p-4">
             <table class="min-w-full">
                <thead>
                     <tr class="bg-slate-50/80 dark:bg-slate-900/50 rounded-xl">
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">Issuance ID</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Requisition ID</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Issued By</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">Date</th>
                     </tr>
                </thead>
                 <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100/50 dark:divide-slate-700/50">
                    <?php 
                    if (empty($issuances)): 
                    ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium italic">No issuance history found.</td></tr>
                    <?php else: 
                        $users = get_data('users');
                        $reqs = get_data('requisitions');
                        
                        foreach ($issuances as $iss): 
                            // Enrichment
                            $issuedByName = 'System/Unknown';
                            foreach ($users as $u) {
                                if ($u['UserID'] === ($iss['IssuedByUserID'] ?? '')) {
                                    $issuedByName = $u['FirstName'] . ' ' . $u['LastName'];
                                    break;
                                }
                            }
                            
                            $reqNum = $iss['RequisitionID'] ?? 'N/A';
                            foreach ($reqs as $r) {
                                if ($r['RequisitionID'] === $iss['RequisitionID']) {
                                    $reqNum = $r['RequisitionNumber'];
                                    break;
                                }
                            }
                    ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <?php echo $iss['IssuanceID']; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-600 dark:text-slate-400"><?php echo $reqNum; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-500 dark:text-slate-400"><?php echo $issuedByName; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-400 uppercase tracking-tight">
                                <?php echo isset($iss['DateIssued']) ? date('M d, Y • h:i A', strtotime($iss['DateIssued'])) : 'N/A'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                 </tbody>
             </table>
        </div>
    </div>
    <?php endif; ?>
</div>
