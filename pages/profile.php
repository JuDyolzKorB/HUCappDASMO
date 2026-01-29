<?php
$user = getCurrentUser();
?>

<div class="space-y-8 animate-fade-in">
    <!-- Consolidated Header: Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">User Profile</h2>
            <p class="text-slate-500 font-medium text-sm">View and manage your personal details and application role.</p>
        </div>
    </div>

    <div class="max-w-3xl mx-auto w-full">
    <div class="bg-white dark:bg-slate-800 shadow rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700">
        <div class="px-4 py-5 sm:px-6 bg-slate-50 dark:bg-slate-700/50">
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white">User Profile</h3>
            <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">Personal details and application role.</p>
        </div>
        <div class="border-t border-slate-200 dark:border-slate-700">
            <dl>
                <div class="bg-gray-50 dark:bg-slate-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Full name</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:mt-0 sm:col-span-2"><?php echo $user['FirstName'] . ' ' . $user['LastName']; ?></dd>
                </div>
                <div class="bg-white dark:bg-slate-800/50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Application for</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:mt-0 sm:col-span-2">Uswag Iloilo City Pharmacy</dd>
                </div>
                <div class="bg-gray-50 dark:bg-slate-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Role</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:mt-0 sm:col-span-2"><?php echo $user['Role']; ?></dd>
                </div>
                <div class="bg-white dark:bg-slate-800/50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">User ID</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:mt-0 sm:col-span-2"><?php echo $user['UserID']; ?></dd>
                </div>
                 <div class="bg-gray-50 dark:bg-slate-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">Password</dt>
                    <dd class="mt-1 text-sm text-slate-900 dark:text-white sm:mt-0 sm:col-span-2">********</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
