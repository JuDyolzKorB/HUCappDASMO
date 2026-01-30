<?php
$user = $_SESSION['user'];
$users = get_data('users');

// Robustly get logs
$security_logs = get_data('security_logs');
$transaction_logs = get_data('transaction_logs');

if (!is_array($security_logs)) $security_logs = [];
if (!is_array($transaction_logs)) $transaction_logs = [];

// Helper to get user full name
function getLogUserName($userId, $users) {
    if ($userId === 'unknown') return 'Guest/System';
    foreach ($users as $u) {
        if ($u['UserID'] === $userId) return $u['FirstName'] . ' ' . $u['LastName'];
    }
    return $userId;
}

// Sort logs by newest first
usort($security_logs, function($a, $b) {
    return strtotime($b['ActionDate']) - strtotime($a['ActionDate']);
});
if (!empty($transaction_logs)) {
    usort($transaction_logs, function($a, $b) {
        return strtotime($b['ActionDate']) - strtotime($a['ActionDate']);
    });
}
?>

<div class="space-y-8 animate-fade-in" 
     x-data="{ 
        activeTab: 'profile',
        showNotification: false,
        notificationMsg: '',
        notificationType: 'success',
        notify(msg, type = 'success') {
            this.notificationMsg = msg;
            this.notificationType = type;
            this.showNotification = true;
            setTimeout(() => this.showNotification = false, 3000);
        }
     }"
     @notify.window="notify($event.detail.msg, $event.detail.type)"
>
    <!-- Notification Toast -->
    <div x-show="showNotification" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2 scale-90"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-90"
         class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border"
         :class="notificationType === 'success' ? 'bg-white text-teal-600 border-teal-100' : 'bg-red-50 text-red-600 border-red-100'">
        <div class="flex-shrink-0">
            <template x-if="notificationType === 'success'">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </template>
            <template x-if="notificationType === 'error'">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </template>
        </div>
        <p class="font-bold text-sm" x-text="notificationMsg"></p>
    </div>

    <!-- Consolidated Header: Title -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white" 
                x-text="activeTab.charAt(0).toUpperCase() + activeTab.slice(1) + ' Settings'">
            </h2>
            <p class="text-slate-500 font-medium text-sm">Manage your account preferences, security, and application appearance.</p>
        </div>
    </div>

    <div class="h-full flex flex-col md:flex-row gap-8">
    <!-- Settings Navigation -->
    <div class="w-full md:w-64 flex-shrink-0">
        <div class="space-y-1">
            <button @click="activeTab = 'profile'" 
                :class="activeTab === 'profile' ? 'bg-primary/5 text-primary border-r-4 border-primary' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-all">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Profile
            </button>
            <button @click="activeTab = 'security'" 
                :class="activeTab === 'security' ? 'bg-primary/5 text-primary border-r-4 border-primary' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-all">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                Security
            </button>
            <button @click="activeTab = 'notifications'" 
                :class="activeTab === 'notifications' ? 'bg-primary/5 text-primary border-r-4 border-primary' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-all">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                Notifications
            </button>
            <button @click="activeTab = 'appearance'" 
                :class="activeTab === 'appearance' ? 'bg-primary/5 text-primary border-r-4 border-primary' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-all">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                Appearance
            </button>
            <button @click="activeTab = 'activity'" 
                :class="activeTab === 'activity' ? 'bg-primary/5 text-primary border-r-4 border-primary' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
                class="w-full flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-all">
                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Activity
            </button>
        </div>
    </div>

    <!-- Content Panel -->
    <div class="flex-1 space-y-8 max-w-5xl">
        <!-- Profile Tab -->
        <div x-show="activeTab === 'profile'" x-cloak class="animate-fade-in">
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-100 dark:border-slate-700/50">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Public Profile</h3>
                    <p class="text-sm text-slate-500 font-medium">This information will be displayed internally to other users.</p>
                </div>
                
                <form id="profileForm" class="p-8 pb-0">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">First Name</label>
                            <input type="text" name="firstName" value="<?php echo $user['FirstName'] ?? ''; ?>" class="form-input rounded-xl border-slate-200 dark:border-slate-700 py-3 px-4 text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Middle Name</label>
                            <input type="text" name="middleName" value="<?php echo $user['MiddleName'] ?? ''; ?>" class="form-input rounded-xl border-slate-200 dark:border-slate-700 py-3 px-4 text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Last Name</label>
                            <input type="text" name="lastName" value="<?php echo $user['LastName'] ?? ''; ?>" class="form-input rounded-xl border-slate-200 dark:border-slate-700 py-3 px-4 text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Username</label>
                            <input type="text" disabled value="<?php echo $user['Username'] ?? ''; ?>" class="form-input rounded-xl bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 py-3 px-4 text-slate-500 font-medium cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Role</label>
                            <input type="text" disabled value="<?php echo $user['Role'] ?? 'User'; ?>" class="form-input rounded-xl bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 py-3 px-4 text-slate-500 font-medium cursor-not-allowed">
                        </div>
                    </div>
                
                    <div class="bg-slate-50/50 dark:bg-slate-900/50 -mx-8 mt-8 p-6 flex justify-end">
                        <button type="submit" class="btn btn-primary px-10 py-3 rounded-xl font-bold shadow-teal-900/10 transition-all hover:scale-[1.02] active:scale-95">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        // Profile Form Handler
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitSettingsForm(this, 'Profile updated successfully!');
        });

        // Security Form Handler
        const securityForm = document.getElementById('securityForm');
        if (securityForm) {
            securityForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const pass1 = this.querySelector('[name="newPassword"]').value;
                const pass2 = this.querySelector('[name="confirmPassword"]').value;
                if (pass1 !== pass2) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { msg: 'New passwords do not match!', type: 'error' } }));
                    return;
                }
                submitSettingsForm(this, 'Password updated successfully!');
            });
        }

        // Universal Submission Function
        function submitSettingsForm(form, successMsg) {
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Updating...';
            
            const formData = new FormData(form);
            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { msg: successMsg, type: 'success' } }));
                    if (form.id === 'securityForm') form.reset(); 
                } else {
                    window.dispatchEvent(new CustomEvent('notify', { detail: { msg: data.message || 'Error updating settings', type: 'error' } }));
                }
            })
            .catch(err => {
                console.error(err);
                window.dispatchEvent(new CustomEvent('notify', { detail: { msg: 'An error occurred during submission.', type: 'error' } }));
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
        }

        // Appearance Handler
        function setTheme(theme) {
            if (theme === 'system') {
                localStorage.removeItem('color-theme');
            } else {
                localStorage.setItem('color-theme', theme);
            }
            if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            window.dispatchEvent(new CustomEvent('notify', { detail: { msg: 'Appearance preference saved!', type: 'success' } }));
        }
        </script>

        <!-- Activity Tab -->
        <div x-show="activeTab === 'activity'" x-cloak class="animate-fade-in space-y-6" x-data="{ activitySubTab: 'security' }">
            <div class="flex p-1 bg-slate-100 dark:bg-slate-900/50 rounded-lg border border-slate-200/50 dark:border-slate-800/50 inline-flex">
                <button @click="activitySubTab = 'security'" 
                    :class="activitySubTab === 'security' ? 'bg-white dark:bg-slate-800 text-teal-600 shadow-sm border border-slate-200/50 dark:border-slate-700/50' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                    class="px-5 py-2 text-sm font-bold rounded-lg transition-all">
                    Security Logs
                </button>
                <button @click="activitySubTab = 'transaction'" 
                    :class="activitySubTab === 'transaction' ? 'bg-white dark:bg-slate-800 text-teal-600 shadow-sm border border-slate-200/50 dark:border-slate-700/50' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                    class="px-5 py-2 text-sm font-bold rounded-lg transition-all">
                    Transaction Audit Logs
                </button>
            </div>

            <!-- Security Logs Content -->
            <div x-show="activitySubTab === 'security'" class="animate-fade-in">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-1">Security Activities</h3>
                        <p class="text-sm text-slate-500 font-medium">Detailed log of authentication and security events.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/80 dark:bg-slate-900/50 rounded-xl">
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">User</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Action</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Description</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Ip Address</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/50">
                                <?php if (empty($security_logs)): ?>
                                    <tr><td colspan="5" class="py-12 text-center text-slate-400 font-medium text-sm italic">No security logs recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($security_logs, 0, 15) as $log): 
                                        $logUser = 'System';
                                        foreach($users as $u) {
                                            if($u['UserID'] === $log['UserID']) {
                                                $logUser = $u['FirstName'] . ' ' . $u['LastName'];
                                                break;
                                            }
                                        }
                                    ?>
                                    <tr class="hover:bg-slate-50/10 dark:hover:bg-slate-700/10 transition-colors">
                                        <td class="px-6 py-5 text-sm font-bold text-slate-700 dark:text-white"><?php echo $logUser; ?></td>
                                        <td class="px-6 py-5 text-sm font-medium text-slate-500 dark:text-slate-400"><?php echo $log['ActionType']; ?></td>
                                        <td class="px-6 py-5 text-sm text-slate-400 dark:text-slate-500"><?php echo $log['Description']; ?></td>
                                        <td class="px-6 py-5 text-sm font-mono text-slate-400"><?php echo $log['IPAddress']; ?></td>
                                        <td class="px-6 py-5 text-sm font-medium text-slate-400"><?php echo date('n/j/Y, g:i:s A', strtotime($log['ActionDate'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Transaction Audit Logs Content -->
            <div x-show="activitySubTab === 'transaction'" class="animate-fade-in">
                <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden p-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-1">Transaction History</h3>
                        <p class="text-sm text-slate-500 font-medium">Comprehensive audit trail of all inventory and data changes.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/80 dark:bg-slate-900/50 rounded-xl">
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">User</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Action</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Type</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Reference Id</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/50">
                                <?php if (empty($transaction_logs)): ?>
                                    <tr><td colspan="5" class="py-12 text-center text-slate-400 font-medium text-sm italic">No transaction audit logs recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_slice($transaction_logs, 0, 15) as $log): ?>
                                    <tr class="hover:bg-slate-50/10 dark:hover:bg-slate-700/10 transition-colors">
                                        <td class="px-6 py-5 text-sm font-bold text-slate-700 dark:text-white"><?php echo $log['UserFullName']; ?></td>
                                        <td class="px-6 py-5 text-sm font-medium text-slate-500 dark:text-slate-400"><?php echo $log['ActionType']; ?></td>
                                        <td class="px-6 py-5 text-xs text-slate-400 dark:text-slate-500 font-bold uppercase tracking-widest"><?php echo $log['ReferenceType']; ?></td>
                                        <td class="px-6 py-5 text-sm font-mono text-primary/70 font-bold"><?php echo $log['ReferenceID']; ?></td>
                                        <td class="px-6 py-5 text-sm font-medium text-slate-400"><?php echo date('n/j/Y, g:i:s A', strtotime($log['ActionDate'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appearance Tab -->
        <div x-show="activeTab === 'appearance'" x-cloak class="animate-fade-in" x-data="{ currentTheme: localStorage.getItem('color-theme') || 'system' }">
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden">
                <div class="p-10 border-b border-slate-100 dark:border-slate-700/50">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Appearance</h3>
                    <p class="text-slate-500 font-medium">Customize the look and feel of the application.</p>
                </div>
                
                <div class="p-10">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Theme</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Light Theme Card -->
                        <button @click="currentTheme = 'light'; setTheme('light')" 
                            :class="currentTheme === 'light' ? 'border-teal-500 bg-teal-50/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                            class="relative p-6 rounded-2xl border-2 transition-all text-left">
                            <div x-show="currentTheme === 'light'" class="absolute top-3 right-3 text-teal-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="mb-4 text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-slate-800 dark:text-white mb-1">Light</h4>
                            <p class="text-xs text-slate-400 font-medium">Light theme for all pages.</p>
                        </button>

                        <!-- Dark Theme Card -->
                        <button @click="currentTheme = 'dark'; setTheme('dark')" 
                            :class="currentTheme === 'dark' ? 'border-teal-500 bg-teal-50/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                            class="relative p-6 rounded-2xl border-2 transition-all text-left">
                            <div x-show="currentTheme === 'dark'" class="absolute top-3 right-3 text-teal-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="mb-4 text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-slate-800 dark:text-white mb-1">Dark</h4>
                            <p class="text-xs text-slate-400 font-medium">Dark theme for all pages.</p>
                        </button>

                        <!-- System Theme Card -->
                        <button @click="currentTheme = 'system'; setTheme('system')" 
                            :class="currentTheme === 'system' ? 'border-teal-500 bg-teal-50/10' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200'"
                            class="relative p-6 rounded-2xl border-2 transition-all text-left">
                            <div x-show="currentTheme === 'system'" class="absolute top-3 right-3 text-teal-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </div>
                            <div class="mb-4 text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 21h6l-.75-4M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-slate-800 dark:text-white mb-1">System</h4>
                            <p class="text-xs text-slate-400 font-medium">Follows your system's appearance.</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Tab -->
        <div x-show="activeTab === 'security'" x-cloak class="animate-fade-in space-y-6" x-data="{ showCurr: false, showNew: false, showConfirm: false }">
            <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Security Settings</h3>
                <p class="text-sm text-slate-500 mb-8 font-medium">Update your password and manage account security.</p>
                
                <form id="securityForm" class="space-y-6 max-w-lg">
                    <input type="hidden" name="action" value="update_password">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Current Password</label>
                        <div class="relative">
                            <input :type="showCurr ? 'text' : 'password'" name="currentPassword" required class="form-input rounded-xl border-slate-200 dark:border-slate-700 py-3 pr-10">
                            <button type="button" @click="showCurr = !showCurr" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 focus:outline-none">
                                <svg x-show="!showCurr" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.42-7.108a1.012 1.012 0 0 1 1.638 0l4.42 7.108a1.012 1.012 0 0 1 0 .639l-4.42 7.108a1.012 1.012 0 0 1-1.638 0l-4.42-7.108Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="showCurr" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.828 9.828" /></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">New Password</label>
                        <div class="relative">
                            <input :type="showNew ? 'text' : 'password'" name="newPassword" required class="form-input rounded-xl border-slate-200 dark:border-slate-700 py-3 pr-10">
                            <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 focus:outline-none">
                                <svg x-show="!showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.42-7.108a1.012 1.012 0 0 1 1.638 0l4.42 7.108a1.012 1.012 0 0 1 0 .639l-4.42 7.108a1.012 1.012 0 0 1-1.638 0l-4.42-7.108Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="showNew" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.828 9.828" /></svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Confirm New Password</label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" name="confirmPassword" required class="form-input rounded-xl border-slate-200 dark:border-slate-700 py-3 pr-10">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 px-3 flex items-center text-slate-400 focus:outline-none">
                                <svg x-show="!showConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.42-7.108a1.012 1.012 0 0 1 1.638 0l4.42 7.108a1.012 1.012 0 0 1 0 .639l-4.42 7.108a1.012 1.012 0 0 1-1.638 0l-4.42-7.108Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <svg x-show="showConfirm" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.828 9.828" /></svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="submit" class="btn btn-primary px-8 py-3 rounded-xl font-bold">Update Password</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div x-show="activeTab === 'notifications'" x-cloak class="animate-fade-in" x-data="{ emailNotify: true, inAppNotify: true }">
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden">
                <div class="p-10 border-b border-slate-100 dark:border-slate-700/50">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Notification Settings</h3>
                    <p class="text-slate-500 font-medium">Manage how you receive notifications from the system.</p>
                </div>
                
                <div class="p-10 space-y-10">
                    <!-- Email Notifications -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">Email Notifications</h4>
                            <p class="text-sm text-slate-400 font-medium">Receive alerts and updates in your inbox.</p>
                        </div>
                        <button @click="emailNotify = !emailNotify" 
                            :class="emailNotify ? 'bg-teal-500' : 'bg-slate-200 dark:bg-slate-700'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none">
                            <span :class="emailNotify ? 'translate-x-6' : 'translate-x-1'"
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>

                    <!-- In-App Notifications -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-bold text-slate-800 dark:text-white mb-1">In-App Notifications</h4>
                            <p class="text-sm text-slate-400 font-medium">Show notifications inside the application header.</p>
                        </div>
                        <button @click="inAppNotify = !inAppNotify" 
                            :class="inAppNotify ? 'bg-teal-500' : 'bg-slate-200 dark:bg-slate-700'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none">
                            <span :class="inAppNotify ? 'translate-x-6' : 'translate-x-1'"
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                        </button>
                    </div>
                </div>

                <div class="bg-slate-50/50 dark:bg-slate-900/50 p-8 flex justify-end">
                    <button @click="alert('Notification preferences saved!')" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-teal-900/10 active:scale-95">Save Preferences</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
