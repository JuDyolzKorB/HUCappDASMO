<?php
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$userRole = $_SESSION['user']['Role'];

$menuItems = [
    ['label' => 'Dashboard', 'id' => 'dashboard', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>', 'roles' => ['Administrator', 'Health Center Staff', 'Head Pharmacist', 'Accounting Office User', 'Warehouse Staff', 'CMO/GSO/COA User']],
    ['label' => 'Requisitions', 'id' => 'requisitions', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>', 'roles' => ['Administrator', 'Health Center Staff', 'Head Pharmacist']],
    ['label' => 'Purchase Orders', 'id' => 'purchase-orders', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>', 'roles' => ['Administrator', 'Warehouse Staff', 'Head Pharmacist']],
    ['label' => 'Receiving', 'id' => 'receiving', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>', 'roles' => ['Administrator', 'Warehouse Staff']],
    ['label' => 'Inventory', 'id' => 'inventory', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>', 'roles' => ['Administrator', 'Warehouse Staff', 'Head Pharmacist', 'Accounting Office User', 'CMO/GSO/COA User']],
    ['label' => 'Warehouse', 'id' => 'warehouse', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>', 'roles' => ['Administrator', 'Warehouse Staff']],
    ['label' => 'Issuance', 'id' => 'issuance', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>', 'roles' => ['Administrator', 'Warehouse Staff']],
    ['label' => 'Adjustments', 'id' => 'adjustments', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>', 'roles' => ['Administrator', 'Head Pharmacist']],
    ['label' => 'Reports', 'id' => 'reports', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>', 'roles' => ['Administrator', 'Accounting Office User', 'CMO/GSO/COA User', 'Head Pharmacist']],
    ['label' => 'Settings', 'id' => 'settings', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>', 'roles' => ['Administrator', 'Health Center Staff', 'Accounting Office User', 'Warehouse Staff', 'Head Pharmacist', 'CMO/GSO/COA User']],
];
?>

<aside class="bg-slate-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col h-full overflow-hidden transition-all duration-300 border-r border-slate-800">
    <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-900">
        <h1 class="text-lg font-extrabold tracking-tight font-display">
            <span class="text-teal-400">USWAG</span> <span class="text-slate-400">PHARMA</span>
        </h1>
    </div>

    <div class="flex-1 overflow-y-auto py-6">
        <nav class="space-y-1.5 px-3">
            <?php foreach ($menuItems as $item): ?>
                <?php if (in_array($userRole, $item['roles'])): ?>
                    <a href="index.php?page=<?php echo $item['id']; ?>" 
                       class="group flex items-center px-4 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 <?php echo $currentPage === $item['id'] ? 'sidebar-active' : 'text-slate-400 hover:bg-slate-800/50 hover:text-white'; ?>">
                        <span class="<?php echo $currentPage === $item['id'] ? 'text-white' : 'text-slate-500 group-hover:text-teal-400'; ?> mr-3 flex-shrink-0 transition-colors">
                            <?php echo $item['icon']; ?>
                        </span>
                        <?php echo $item['label']; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="p-4 border-t border-slate-800 bg-slate-900/50 backdrop-blur-md space-y-4">
        <div class="flex items-center p-2 rounded-xl bg-slate-800/30 border border-slate-700/50">
            <div class="w-8 h-8 rounded-lg bg-teal-500/20 text-teal-400 flex items-center justify-center font-bold text-xs border border-teal-500/30">
                <?php echo substr($_SESSION['user']['FirstName'], 0, 1) . substr($_SESSION['user']['LastName'], 0, 1); ?>
            </div>
            <div class="ml-3 overflow-hidden">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-none mb-1">Authenticated</p>
                <p class="text-xs font-semibold text-slate-200 truncate"><?php echo $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName']; ?></p>
            </div>
        </div>

        <a href="index.php?page=logout" class="group flex items-center px-4 py-2.5 text-sm font-bold text-slate-400 hover:text-red-400 hover:bg-red-400/10 rounded-xl transition-all duration-200">
            <span class="mr-3 text-slate-500 group-hover:text-red-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            </span>
            Sign Out
        </a>
    </div>
</aside>
