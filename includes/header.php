<?php
// Fetch notifications for current user
$user = getCurrentUser();
$userRole = $user['Role'] ?? 'User';

global $db;
$notifications = [];
$unreadCount = 0;

try {
    // Get notifications for this user's role or all users
    $allNotifications = $db->fetchAll(
        "SELECT * FROM Notifications 
         WHERE (targetRoles IS NULL OR targetRoles = '' OR FIND_IN_SET(?, targetRoles) > 0)
         ORDER BY timestamp DESC 
         LIMIT 20",
        [$userRole]
    );
    
    if ($allNotifications) {
        $notifications = $allNotifications;
        // Count unread notifications
        foreach ($notifications as $notif) {
            if (!$notif['isRead']) {
                $unreadCount++;
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}
?>
<style>[x-cloak] { display: none !important; }</style>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('liveMode', {
        on: true, // Integrated real-time updates are now active by default
    });

    // Global poll management
    let pollInterval = null;
    const startGlobalPolling = () => {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(() => {
            if (Alpine.store('liveMode').on) {
                // If the page defines a refresh function, use it.
                // Otherwise do a full reload but ONLY if no modals are open
                const hasOpenModals = document.querySelector('[role="dialog"]:not([style*="display: none"])') !== null;
                if (window.refreshPageData && typeof window.refreshPageData === 'function') {
                    window.refreshPageData();
                } else if (!hasOpenModals) {
                    // Full reload fallback for static pages
                    // Filter out critical interactive pages where reload might lose state
                    const sensitivePages = ['login', 'signup', 'settings'];
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentPage = urlParams.get('page') || 'dashboard';
                    
                    if (!sensitivePages.includes(currentPage)) {
                        console.log('Live Mode: Auto-refreshing page...');
                        location.reload();
                    }
                }
            }
        }, 30000); // 30 seconds global poll
    };

    startGlobalPolling();
});
</script>
<header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-b border-slate-200/60 dark:border-slate-800/60">
    <div class="px-6 py-3.5 flex justify-between items-center">
        <div class="flex items-center space-x-4">
             <!-- Mobile Menu Button -->
             <button class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <?php
            $pageIcons = [
                'dashboard' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                'requisitions' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'purchase-orders' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                'receiving' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
                'inventory' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                'warehouse' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'issuance' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                'adjustments' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
                'reports' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'profile' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'
            ];
            $currentIcon = isset($pageIcons[$page]) ? $pageIcons[$page] : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            ?>
            <h1 class="text-xl font-bold text-slate-800 dark:text-white font-display tracking-tight flex items-center">
                <div class="p-1.5 bg-teal-50 dark:bg-teal-900/30 rounded-lg mr-3">
                    <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $currentIcon; ?>"></path>
                    </svg>
                </div>
                <?php echo isset($pageTitle) ? $pageTitle : 'Overview'; ?>
            </h1>

            <?php if (isset($user['HealthCenterID'])): 
                $hc = $db->fetchOne("SELECT Name FROM HealthCenters WHERE HealthCenterID = ?", [$user['HealthCenterID']]);
                $hcName = $hc['Name'] ?? 'Unknown Health Center';
            ?>
                <div class="hidden lg:flex items-center px-3 py-1 bg-teal-50 dark:bg-teal-900/20 border border-teal-100 dark:border-teal-800 rounded-full ml-4">
                    <span class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase tracking-tighter mr-2">Center:</span>
                    <span class="text-xs font-semibold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($hcName); ?></span>
                </div>
            <?php endif; ?>

            <?php if (hasRole('Administrator')): 
                $allHCs = get_data('health_centers');
            ?>
                <div class="hidden xl:block ml-4" x-data="{ switching: false }">
                    <select onchange="switchHealthCenter(this.value)" class="text-[11px] bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-lg py-1 px-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <option value="">Switch Health Center...</option>
                        <?php foreach ($allHCs as $hc): ?>
                            <option value="<?php echo $hc['HealthCenterID']; ?>" <?php echo (($user['HealthCenterID'] ?? '') == $hc['HealthCenterID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hc['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="none" <?php echo empty($user['HealthCenterID']) ? 'selected' : ''; ?>>None (Central Only)</option>
                    </select>
                </div>
                <script>
                function switchHealthCenter(hcId) {
                    const formData = new FormData();
                    formData.append('action', 'switch_health_center');
                    formData.append('healthCenterId', hcId);
                    
                    fetch('api.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) window.location.reload();
                        else alert('Failed to switch health center: ' + data.message);
                    });
                }
                </script>
            <?php endif; ?>
        </div>

        <!-- Right Side: Integrated Live Status & Profile -->
        <div class="flex items-center gap-3">
            <!-- Integrated Live Status Indicator -->
            <div x-data x-cloak class="flex items-center gap-2 px-3 py-1.5 bg-slate-100/30 dark:bg-slate-800/30 rounded-xl h-10 border border-transparent">
                <div class="flex items-center gap-1.5">
                    <div class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-teal-600 dark:text-teal-400">Live Sync</span>
                </div>
            </div>

            <div class="h-8 w-px bg-slate-200 dark:bg-slate-800 mx-1 hidden md:block"></div>

            <!-- Profile Dropdown -->
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button @click="open = !open" :class="{'bg-slate-100 dark:bg-slate-800': open}" class="flex items-center space-x-3 p-1 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-xs ring-2 ring-teal-600/20 shadow-lg shadow-teal-600/10">
                        <?php echo substr($user['FirstName'] ?? 'U', 0, 1) . substr($user['LastName'] ?? '', 0, 1); ?>
                    </div>
                    <div class="text-left hidden md:block">
                        <p class="text-xs font-bold text-slate-800 dark:text-white leading-tight"><?php echo $user['FirstName'] ?? 'User'; ?></p>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium"><?php echo $user['Role'] ?? 'User'; ?></p>
                    </div>
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                 <!-- Dropdown Menu -->
                 <div x-show="open" 
                      x-transition:enter="transition ease-out duration-200"
                      x-transition:enter-start="opacity-0 translate-y-2"
                      x-transition:enter-end="opacity-100 translate-y-0"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100 translate-y-0"
                      x-transition:leave-end="opacity-0 translate-y-2"
                      style="display: none;"
                      class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl py-2 border border-slate-200/60 dark:border-slate-800/60 z-50">
                    <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-800 mb-1">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">User Menu</p>
                    </div>
                    <a href="index.php?page=profile" class="flex items-center space-x-3 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-teal-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>My Profile</span>
                    </a>
                    <a href="index.php?page=settings" class="flex items-center space-x-3 px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-teal-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
                        <span>Account Settings</span>
                    </a>
                    <div class="border-t border-slate-100 dark:border-slate-800 my-1"></div>
                    <button @click="$dispatch('open-logout-modal')" class="flex items-center space-x-3 w-full text-left px-4 py-2 text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Sign Out</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Logout Confirmation Modal -->
<div x-data="{ open: false }" 
     @open-logout-modal.window="open = true" 
     class="relative z-[60]" 
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true"
     x-show="open"
     x-cloak>
    
    <!-- Background backdrop -->
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal panel -->
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @click.away="open = false"
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200 dark:border-slate-800">
                
                <div class="p-6">
                    <div class="mx-auto flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-12 sm:w-12 mb-4 sm:mb-0">
                        <svg class="h-8 w-8 text-red-600 dark:text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="sm:flex sm:items-start">
                        
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-bold leading-6 text-slate-900 dark:text-white" id="modal-title">Sign Out?</h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    Are you sure you want to sign out of your account? You will need to sign in again to access the system.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 dark:bg-slate-800/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                    <a href="index.php?page=logout" class="inline-flex w-full justify-center rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-red-500 sm:w-auto transition-all">
                        Yes, Sign Out
                    </a>
                    <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white dark:bg-slate-800 px-5 py-2.5 text-sm font-bold text-slate-900 dark:text-white shadow-sm ring-1 ring-inset ring-slate-300 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto transition-all">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAllAsRead() {
    fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_notifications_read'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to update notification count
        } else {
            console.error('Failed to mark notifications as read');
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
