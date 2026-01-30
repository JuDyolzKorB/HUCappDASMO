<?php
// pages/dashboard.php
$pageTitle = 'Dashboard Overview';

$userRole = $_SESSION['user']['Role'];
$role = $userRole;

// Determine which dashboard to include
$dashboardFile = '';
switch ($userRole) {
    case 'Administrator':
        $dashboardFile = 'dashboards/administrator.php'; // We use the logic below for admin for now
        break;
    case 'Health Center Staff':
        $dashboardFile = 'dashboards/health_center.php';
        break;
    case 'Head Pharmacist':
    case 'Warehouse Staff':
        $dashboardFile = 'dashboards/warehouse.php';
        break;
    case 'Accounting Office User':
        $dashboardFile = 'dashboards/accounting.php';
        break;
    case 'CMO/GSO/COA User':
        $dashboardFile = 'dashboards/compliance.php';
        break;
}

// Global data needed for dashboards
$requisitions = get_data('requisitions');
$purchaseOrders = get_data('purchase_orders');
$inventory = get_data('inventory');

if ($userRole !== 'Administrator' && $dashboardFile) {
    include $dashboardFile;
} else {
    // Default Administrator Dashboard
    $stats = [
        'pending_reqs' => 0,
        'low_stock' => 0,
        'pending_pos' => 0
    ];

    foreach ($requisitions as $r) {
        if ($r['StatusType'] === 'Pending') $stats['pending_reqs']++;
    }

    foreach ($purchaseOrders as $po) {
        if ($po['StatusType'] === 'Pending') $stats['pending_pos']++;
    }

    foreach ($inventory as $batch) {
        if ($batch['QuantityOnHand'] < 500) $stats['low_stock']++;
    }
    ?>
    
        <div class="space-y-8 animate-fade-in">
            <!-- Hero Section -->
            <div class="relative overflow-hidden rounded-3xl bg-slate-900 p-8 text-white shadow-2xl">
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-3xl font-extrabold font-display tracking-tight mb-2">Welcome Back, <?php echo $user['FirstName']; ?>! 👋</h2>
                        <p class="text-slate-400 font-medium max-w-lg">Manage your pharmacy operations with ease. Here's what's happening today in the <span class="text-teal-400 font-bold"><?php echo $role; ?></span> dashboard.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="btn bg-white/10 hover:bg-white/20 text-white backdrop-blur-md border border-white/10 px-6">
                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                           New Requisition
                        </button>
                    </div>
                </div>
                <!-- Decorative element -->
                <div class="absolute top-0 right-0 -mt-12 -mr-12 w-64 h-64 bg-teal-500/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-12 -ml-12 w-48 h-48 bg-blue-500/20 rounded-full blur-3xl"></div>
            </div>
        
            <!-- KPI Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                 <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 dark:bg-slate-700 px-2 py-1 rounded-lg">Real-time</span>
                    </div>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Pending Requisitions</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $stats['pending_reqs']; ?></h3>
                        <span class="text-xs font-bold text-emerald-500">+2 from yesterday</span>
                    </div>
                </div>

                 <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 dark:bg-slate-700 px-2 py-1 rounded-lg">Waitlist</span>
                    </div>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Pending POs</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $stats['pending_pos']; ?></h3>
                        <span class="text-xs font-bold text-slate-400">Stable</span>
                    </div>
                </div>

                 <div class="stat-card">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-2xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <span class="text-[10px] font-bold text-red-500 uppercase tracking-widest bg-red-50 dark:bg-red-900/10 px-2 py-1 rounded-lg">Critical</span>
                    </div>
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Low Stock Batches</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <h3 class="text-3xl font-bold text-slate-900 dark:text-white"><?php echo $stats['low_stock']; ?></h3>
                        <span class="text-xs font-bold text-red-500">Requires attention</span>
                    </div>
                </div>
            </div>
            
            <!-- Recent Requisitions Table -->
            <div class="table-container">
                <div class="table-header">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white font-display">Recent Requisitions</h3>
                        <p class="text-xs text-slate-500 font-medium">Monitoring latest inventory requests</p>
                    </div>
                    <button class="text-teal-600 hover:text-teal-700 text-sm font-bold transition-colors">View All &rarr;</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                        <thead class="bg-slate-50/50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Req No.</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Health Center</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
                                <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                            <?php foreach (array_slice($requisitions, 0, 5) as $r): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-teal-500"></div>
                                        <?php echo $r['RequisitionNumber']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 text-sm font-medium"><?php echo $r['HealthCenterName']; ?></td>
                                <td class="px-6 py-4 text-slate-500 dark:text-slate-500 text-xs font-semibold"><?php echo date('M d, Y', strtotime($r['RequestedDate'])); ?></td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $statusClass = 'status-pending';
                                        if ($r['StatusType'] === 'Approved') $statusClass = 'status-success';
                                        if ($r['StatusType'] === 'Rejected') $statusClass = 'status-danger';
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $r['StatusType']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="p-1.5 rounded-lg text-slate-400 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/20 transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php
}
?>
