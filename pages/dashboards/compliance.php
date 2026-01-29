<?php
// pages/dashboards/compliance.php

$totalInventoryValue = 0;
foreach ($inventory as $batch) {
    // Inventory items need UnitCost, assume it's there or 0
    $cost = isset($batch['UnitCost']) ? $batch['UnitCost'] : 0;
    $totalInventoryValue += $batch['QuantityOnHand'] * $cost;
}

// We don't have reports data loaded yet in dashboard.php usually, so let's check
// In a real app we might load it in index.php or here
// For now let's assume get_data works
$reports = get_data('reports') ?? []; 
$totalReports = count($reports);

?>

<div class="space-y-8 animate-fade-in">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white font-display tracking-tight">Compliance Command</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">High-level inventory valuation and reporting summary.</p>
        </div>
        <div class="flex items-center gap-3">
             <a href="index.php?page=reports" class="btn btn-primary px-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Full Access Reports
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="status-badge status-success">Valuated</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Total Inventory Value</p>
            <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1">₱<?php echo number_format($totalInventoryValue, 2); ?></h3>
        </div>

        <div class="stat-card">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <span class="status-badge status-info">Archived</span>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Total Reports Generated</p>
            <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1"><?php echo $totalReports; ?></h3>
        </div>

        <a href="index.php?page=inventory" class="stat-card group hover:scale-[1.02] transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-2xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <svg class="w-5 h-5 text-slate-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Asset Management</p>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">Audit Inventory</h3>
        </a>
    </div>

    <!-- Recent Report History -->
    <div class="table-container">
        <div class="table-header">
            <div>
                <h3 class="font-bold text-slate-800 dark:text-white font-display">Recent Report History</h3>
                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Chronological log of generated audits</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Report ID</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Type</th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Generated For</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="3" class="px-6 py-12 text-center text-slate-500 font-medium">No reports have been generated yet.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($reports, 0, 10) as $report): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-900 dark:text-white text-sm">
                                <span class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded text-xs"><?php echo $report['ReportID']; ?></span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $report['ReportType']; ?></td>
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $report['GeneratedForOffice']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
