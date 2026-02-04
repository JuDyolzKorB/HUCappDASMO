<?php
// pages/suppliers.php
$suppliers = get_data('suppliers'); // Assuming get_data works for this case now

// Count total suppliers
$totalSuppliers = count($suppliers);
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Supplier Management</h2>
            <p class="text-slate-500 dark:text-slate-400">Manage your list of pharmaceutical suppliers</p>
        </div>
        
        <button onclick="document.getElementById('addSupplierModal').classList.remove('hidden')" 
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg transition-colors shadow-lg shadow-teal-900/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>Add Supplier</span>
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 p-6 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 text-purple-600 rounded-lg">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Suppliers</h3>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $totalSuppliers; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-700/50 text-xs uppercase font-semibold text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Supplier Name</th>
                        <th class="px-6 py-4">Address</th>
                        <th class="px-6 py-4">Contact Info</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p>No suppliers found. Add one to get started.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                    #<?php echo htmlspecialchars($supplier['SupplierID']); ?>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                    <?php echo htmlspecialchars($supplier['Name']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($supplier['Address'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($supplier['ContactInfo'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button class="text-teal-600 hover:text-teal-800 font-medium transition-colors">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'components/add_supplier_modal.php'; ?>
