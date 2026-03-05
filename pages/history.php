<?php
if (!isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}
?>

<div class="animate-fade-in space-y-6" x-data="historyComponent()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white font-display">System History</h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Comprehensive ledger of all item additions and movements.</p>
        </div>

        <div class="flex flex-wrap gap-2 p-1 bg-slate-200/50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-800 backdrop-blur-sm">
            <template x-for="tab in tabs" :key="tab.id">
                <button 
                    @click="setActiveTab(tab.id)"
                    :class="activeTab === tab.id ? 'bg-white dark:bg-slate-800 text-teal-600 dark:text-teal-400 shadow-sm border-slate-200 dark:border-slate-700' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 border-transparent'"
                    class="px-4 py-2 text-[10px] font-black uppercase tracking-wider rounded-lg transition-all border outline-none"
                    x-text="tab.label">
                </button>
            </template>
        </div>
    </div>

    <!-- Stats Section (For Summaries) -->
    <template x-if="activeTab === 'summary' || activeTab === 'hc_summary'">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2" x-text="activeTab === 'summary' ? 'Items in Central Warehouse' : 'Items in All Health Centers'"></div>
                <div class="text-3xl font-black text-slate-800 dark:text-white" x-text="historyData.length"></div>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Total Lifetime Arrivals</div>
                <div class="text-3xl font-black text-teal-500 font-display" x-text="historyData.reduce((acc, curr) => acc + parseInt(curr.TotalAdded || 0), 0).toLocaleString()"></div>
            </div>
            <div class="bg-white dark:bg-slate-800 p-6 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm">
                <div class="text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Total Ledger Entries</div>
                <div class="text-3xl font-black text-indigo-500 font-display" x-text="historyData.reduce((acc, curr) => acc + parseInt(curr.TotalTransactions || 0), 0)"></div>
            </div>
        </div>
    </template>

    <!-- Main Content Card -->
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200/60 dark:border-slate-700/60 overflow-hidden box-glow min-h-[500px]">
        <div class="p-6">
            <!-- Search and Controls -->
            <div class="flex flex-col md:flex-row gap-4 mb-6 items-center">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" x-model="searchQuery" placeholder="Filter history..." class="form-input pl-10 h-11 rounded-xl bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-sm">
                </div>
                
                <button @click="fetchHistory()" class="btn btn-primary px-6 h-11 rounded-xl flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Refresh
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-2xl border border-slate-100 dark:border-slate-700/50">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700/50">
                    <thead class="bg-slate-50/80 dark:bg-slate-900/50 text-[10px] uppercase tracking-widest font-bold text-slate-400">
                        <tr>
                            <template x-if="activeTab !== 'summary' && activeTab !== 'hc_summary'">
                                <th class="px-6 py-4 text-left font-black">Date</th>
                            </template>
                            <th class="px-6 py-4 text-left font-black">Item</th>
                            
                            <template x-if="activeTab === 'summary' || activeTab === 'hc_summary'">
                                <th class="px-6 py-4 text-left font-black text-teal-600">Lifetime Added</th>
                                <th class="px-6 py-4 text-left font-black">Ledger Count</th>
                                <th class="px-6 py-4 text-left font-black">Latest Transaction</th>
                            </template>

                            <template x-if="activeTab !== 'adjustments' && activeTab !== 'summary' && activeTab !== 'hc_summary'">
                                <th class="px-6 py-4 text-left font-black">Quantity</th>
                            </template>
                            
                            <template x-if="activeTab === 'hc_requisitions' || activeTab === 'warehouse_issuances' || activeTab === 'patient_list' || activeTab === 'hc_inventory_additions'">
                                <th class="px-6 py-4 text-left font-black" x-text="activeTab === 'patient_list' ? 'Patient' : 'Health Center'"></th>
                                <th class="px-6 py-4 text-left font-black">Reference</th>
                            </template>

                            <template x-if="activeTab === 'item_additions' || activeTab === 'warehouse_issuances' || activeTab === 'hc_inventory_additions'">
                                <th class="px-6 py-4 text-left font-black">Batch</th>
                            </template>
                            
                            <template x-if="activeTab === 'adjustments'">
                                <th class="px-6 py-4 text-left font-black">Adjustment</th>
                                <th class="px-6 py-4 text-left font-black">Reason</th>
                            </template>
                            
                            <template x-if="activeTab !== 'summary' && activeTab !== 'hc_summary'">
                                <th class="px-6 py-4 text-left font-black">Staff</th>
                            </template>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                        <template x-if="loading && historyData.length === 0">
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500"></div>
                                        <span class="text-sm font-medium text-slate-400">Retrieving system ledger...</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-if="!loading && filteredData.length === 0">
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-slate-200 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 17.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span class="text-sm font-medium text-slate-400">No ledger entries found.</span>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="(row, index) in filteredData" :key="index">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                <!-- Date -->
                                <template x-if="activeTab !== 'summary' && activeTab !== 'hc_summary'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-black text-slate-700 dark:text-slate-200" x-text="formatDate(row.Date)"></div>
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter" x-text="formatTime(row.Date)"></div>
                                    </td>
                                </template>

                                <!-- Item -->
                                <td class="px-6 py-4">
                                    <div class="text-sm font-black text-slate-900 dark:text-white" x-text="row.ItemName || row.Reference"></div>
                                    <template x-if="row.ItemID">
                                        <div class="text-[10px] font-bold text-teal-600" x-text="'PID-' + row.ItemID"></div>
                                    </template>
                                </td>

                                <!-- Summaries -->
                                <template x-if="activeTab === 'summary' || activeTab === 'hc_summary'">
                                    <td class="px-6 py-4">
                                        <div class="text-xl font-black text-emerald-600 dark:text-emerald-400" x-text="parseInt(row.TotalAdded).toLocaleString()"></div>
                                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Additions</div>
                                    </td>
                                </template>
                                <template x-if="activeTab === 'summary' || activeTab === 'hc_summary'">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-slate-600 dark:text-slate-400" x-text="row.TotalTransactions + ' entries'"></div>
                                    </td>
                                </template>
                                <template x-if="activeTab === 'summary' || activeTab === 'hc_summary'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs font-black text-slate-700 dark:text-slate-300" x-text="formatDate(row.LastReceived)"></div>
                                        <div class="text-[10px] font-bold text-slate-400" x-text="formatTime(row.LastReceived)"></div>
                                    </td>
                                </template>

                                <!-- Quantity -->
                                <template x-if="activeTab !== 'adjustments' && activeTab !== 'summary' && activeTab !== 'hc_summary'">
                                    <td class="px-6 py-4 text-sm font-black text-slate-900 dark:text-white">
                                        <span x-text="row.Quantity > 0 ? '+' : ''"></span><span x-text="row.Quantity"></span>
                                    </td>
                                </template>

                                <!-- Refs -->
                                <template x-if="activeTab === 'hc_requisitions' || activeTab === 'warehouse_issuances' || activeTab === 'patient_list' || activeTab === 'hc_inventory_additions'">
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-tight" x-text="row.HealthCenter || row.Patient || 'Central'"></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-black text-slate-400 font-mono" x-text="row.Reference"></div>
                                    </td>
                                </template>

                                <!-- Batch -->
                                <template x-if="activeTab === 'item_additions' || activeTab === 'warehouse_issuances' || activeTab === 'hc_inventory_additions'">
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-black font-mono border border-slate-200 dark:border-slate-700 px-2 py-0.5 rounded bg-slate-50 dark:bg-slate-900" x-text="row.BatchID"></span>
                                    </td>
                                </template>

                                <!-- Adjustments -->
                                <template x-if="activeTab === 'adjustments'">
                                    <td class="px-6 py-4 text-sm font-black" :class="row.Quantity > 0 ? 'text-emerald-500' : 'text-rose-500'">
                                        <span x-text="row.Quantity > 0 ? '+' : ''"></span><span x-text="row.Quantity"></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-bold text-slate-500 max-w-xs truncate" :title="row.Reason" x-text="row.Reason || 'System Adjustment'"></div>
                                        <div class="text-[9px] font-black text-slate-400 mt-1 uppercase" x-text="row.Reference"></div>
                                    </td>
                                </template>

                                <!-- Staff -->
                                <template x-if="activeTab !== 'summary' && activeTab !== 'hc_summary'">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-black text-slate-600" x-text="row.User ? row.User.substring(0, 1) : 'S'"></div>
                                            <div class="text-xs font-bold text-slate-600 dark:text-slate-300" x-text="row.User || 'System'"></div>
                                        </div>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function historyComponent() {
    return {
        activeTab: 'summary',
        tabs: [
            { id: 'summary', label: 'Item Additions (Central)' },
            { id: 'hc_summary', label: 'Item Additions (HC)' },
            { id: 'item_additions', label: 'Additions Log' },
            { id: 'hc_inventory_additions', label: 'HC Arrivals' },
            { id: 'hc_requisitions', label: 'Requisitions' },
            { id: 'warehouse_issuances', label: 'Issuances' },
            { id: 'patient_list', label: 'Patient List' },
            { id: 'adjustments', label: 'Adjustments' }
        ],
        historyData: [],
        searchQuery: '',
        loading: false,

        init() {
            this.fetchHistory();
            
            // Register global refresh function for real-time mode
            window.refreshPageData = () => {
                this.fetchHistory(true);
            };
        },

        setActiveTab(tabId) {
            this.activeTab = tabId;
            this.historyData = [];
            this.fetchHistory();
        },

        fetchHistory(isBackground = false) {
            if (!isBackground) this.loading = true;
            
            const formData = new FormData();
            formData.append('action', 'get_history');
            formData.append('type', this.activeTab);

            fetch('api.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.historyData = data.data;
                }
            })
            .catch(err => console.error(err))
            .finally(() => {
                if (!isBackground) this.loading = false;
            });
        },

        get filteredData() {
            if (!this.searchQuery) return this.historyData;
            const q = this.searchQuery.toLowerCase();
            return this.historyData.filter(i => 
                (i.ItemName && i.ItemName.toLowerCase().includes(q)) ||
                (i.Reference && i.Reference.toLowerCase().includes(q)) ||
                (i.HealthCenter && i.HealthCenter.toLowerCase().includes(q)) ||
                (i.Patient && i.Patient.toLowerCase().includes(q)) ||
                (i.User && i.User.toLowerCase().includes(q))
            );
        },

        formatDate(d) {
            if (!d) return '-';
            return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        },

        formatTime(d) {
            if (!d) return '';
            return new Date(d).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        }
    };
}
</script>
