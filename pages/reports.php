<?php
// Load persistent reports history
$reports = get_data('reports');
?>

<div class="animate-fade-in space-y-6" x-data="reportsComponent()">
    <!-- Consolidated Header: Title & Tab Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white" 
                x-text="activeTab === 'generate' ? 'Generate Report' : 'Report History'">
            </h2>
            <p x-show="activeTab === 'generate'" class="text-slate-500 font-medium text-sm">Create detailed inventory and financial reports for various departments.</p>
            <p x-show="activeTab === 'history'" class="text-slate-500 font-medium text-sm">Review previously generated reports and verification documents.</p>
        </div>

        <div class="flex p-1 bg-slate-100 dark:bg-slate-900/50 rounded-xl border border-slate-200/50 dark:border-slate-800/50">
            <button 
                @click="activeTab = 'generate'"
                :class="activeTab === 'generate' ? 'bg-white dark:bg-slate-800 text-teal-600 shadow-sm border border-slate-200/50 dark:border-slate-700/50' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                class="px-5 py-2 text-sm font-bold rounded-lg transition-all">Generate Report</button>
            <button 
                @click="activeTab = 'history'"
                :class="activeTab === 'history' ? 'bg-white dark:bg-slate-800 text-teal-600 shadow-sm border border-slate-200/50 dark:border-slate-700/50' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                class="px-5 py-2 text-sm font-bold rounded-lg transition-all">Report History</button>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-200/60 dark:border-slate-700/60 overflow-hidden">
        <!-- Content Area -->
        <div class="p-8">
            <!-- Generate Form Tab -->
            <div x-show="activeTab === 'generate'" x-cloak class="animate-fade-in">
                <form @submit.prevent="simulateGeneration(reportType, $refs.officeSelect.value, reportType === 'receipt_confirmation' ? $refs.poSelect.value : (reportType === 'stock_card' ? $refs.itemSelect.value : ''))" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                    <input type="hidden" name="action" value="generate_report">
                    
                    <div :class="(reportType === 'receipt_confirmation' || reportType === 'stock_card') ? 'md:col-span-3' : 'md:col-span-4'">
                        <label for="reportType" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Report Type</label>
                        <select id="reportType" name="reportType" x-model="reportType" class="form-select w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl py-3 text-sm font-medium">
                            <option value="inventory_valuation">Inventory Valuation</option>
                            <option value="receipt_confirmation">Receipt Confirmation</option>
                            <option value="stock_card">Stock Card & Ledger</option>
                        </select>
                    </div>

                    <!-- Dynamic PO Field for Receipt Confirmation -->
                    <template x-if="reportType === 'receipt_confirmation'">
                        <div class="md:col-span-3 animate-fade-in">
                            <label for="poNumber" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Completed Purchase Order</label>
                            <select id="poNumber" name="poNumber" x-ref="poSelect" class="form-select w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl py-3 text-sm font-medium">
                                <template x-for="po in completedPOs" :key="po.id">
                                    <option :value="po.id" x-text="po.label"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <!-- Dynamic Item Field for Stock Card & Ledger -->
                    <template x-if="reportType === 'stock_card'">
                        <div class="md:col-span-3 animate-fade-in">
                            <label for="itemSelect" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Item</label>
                            <select id="itemSelect" name="itemSelect" x-ref="itemSelect" class="form-select w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl py-3 text-sm font-medium">
                                <template x-for="item in items" :key="item.id">
                                    <option :value="item.id" x-text="item.label"></option>
                                </template>
                            </select>
                        </div>
                    </template>

                    <div :class="(reportType === 'receipt_confirmation' || reportType === 'stock_card') ? 'md:col-span-3' : 'md:col-span-4'">
                        <label for="forOffice" class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">For Office</label>
                        <select id="forOffice" name="forOffice" x-ref="officeSelect" class="form-select w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded-xl py-3 text-sm font-medium">
                            <template x-for="office in offices" :key="office.id">
                                <option :value="office.id" x-text="office.name"></option>
                            </template>
                        </select>
                    </div>

                    <div :class="(reportType === 'receipt_confirmation' || reportType === 'stock_card') ? 'md:col-span-3' : 'md:col-span-4'">
                        <button type="submit" class="w-full btn btn-primary py-3.5 rounded-xl font-bold shadow-teal-900/10">Generate Report</button>
                    </div>
                </form>
            </div>

            <!-- History Tab -->
            <div x-show="activeTab === 'history'" x-cloak class="animate-fade-in">
                <h3 class="text-lg font-bold text-slate-800 dark:text-white mb-6">Report History</h3>
                
                <div class="table-container border-slate-100 dark:border-slate-700/50">
                    <table class="min-w-full">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/50 text-[10px] uppercase tracking-widest font-bold text-slate-400">
                            <tr>
                                <th class="px-6 py-4 text-left">Report ID</th>
                                <th class="px-6 py-4 text-left">Type</th>
                                <th class="px-6 py-4 text-left">Generated For</th>
                                <th class="px-6 py-4 text-left">Generated By</th>
                                <th class="px-6 py-4 text-left">Date</th>
                                <th class="px-6 py-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            <template x-if="history.length === 0">
                                <tr>
                                    <td colspan="6" class="px-6 py-20 text-center">
                                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">No reports have been generated yet.</p>
                                    </td>
                                </tr>
                            </template>
                            <template x-for="rpt in history" :key="rpt.ReportID">
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                                    <td class="px-6 py-4 text-sm font-bold text-slate-900 dark:text-white" x-text="rpt.ReportID"></td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400" x-text="rpt.ReportType"></td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400" x-text="rpt.GeneratedForOffice"></td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-600 dark:text-slate-400" x-text="rpt.GeneratedByFullName"></td>
                                    <td class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-tighter" x-text="rpt.GeneratedDate"></td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="viewReport(rpt.ReportID)" class="text-teal-600 hover:text-teal-700 font-bold text-[10px] uppercase tracking-widest border border-teal-100 dark:border-teal-900/40 px-3 py-1.5 rounded-lg transition-all">View Details</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/report_viewer_modal.php'; ?>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
function reportsComponent() {
    return {
        activeTab: 'generate',
        reportType: 'inventory_valuation',
        completedPOs: [
            { id: 'PO-230003', label: 'PO-230003 - MedSupply Inc.' },
            { id: 'PO-230005', label: 'PO-230005 - PharmaLink Co.' }
        ],
        items: [
            { id: 'ITM001', label: 'Paracetamol 500mg' },
            { id: 'ITM002', label: 'Amoxicillin 250mg' },
            { id: 'ITM003', label: 'Gauze Pads 4x4' }
        ],
        history: <?php echo json_encode($reports); ?>,
        get offices() {
            if (this.reportType === 'inventory_valuation' || this.reportType === 'receipt_confirmation') {
                return [{ id: 'accounting', name: 'Accounting Office' }];
            } else if (this.reportType === 'stock_card') {
                return [
                    { id: 'cmo', name: 'CMO Office' },
                    { id: 'gso', name: 'GSO Office' },
                    { id: 'coa', name: 'COA Office' }
                ];
            }
            return [
                { id: 'accounting', name: 'Accounting Office' },
                { id: 'pharmacy', name: 'Main Pharmacy' },
                { id: 'warehouse', name: 'Warehouse Storage' },
                { id: 'compliance', name: 'Compliance Office' }
            ];
        },
        viewReport(reportId) {
            const report = this.history.find(r => r.ReportID === reportId);
            if (report) window.renderReport(report);
        },
        simulateGeneration(type, office, extraId = '') {
            const mockReport = {
                ReportID: 'REP-' + Math.floor(Math.random() * 90000000 + 10000000),
                ReportType: type === 'receipt_confirmation' ? 'Receipt Confirmation' : (type === 'inventory_valuation' ? 'Inventory Valuation' : (type === 'stock_card' ? 'Stock Card & Ledger' : 'Report')),
                GeneratedForOffice: office.charAt(0).toUpperCase() + office.slice(1),
                GeneratedByFullName: 'Admin User',
                GeneratedDate: new Date().toLocaleString(),
                data: type === 'receipt_confirmation' ? {
                    poNumber: extraId || 'PO-230003',
                    supplierName: 'MedSupply Inc.',
                    poDate: '9/15/2023',
                    receivedDate: '9/20/2023, 6:00:00 PM',
                    receivedBy: 'Warehouse Staff',
                    itemName: 'Amoxicillin 250mg',
                    quantityOrdered: 1500,
                    quantityReceived: 1500
                } : (type === 'inventory_valuation' ? [
                    { itemName: 'Paracetamol 500mq', totalQuantity: 7500, averageCost: 0.10, totalValue: 750.00 },
                    { itemName: 'Amoxicillin 250mg', totalQuantity: 2700, averageCost: 0.23, totalValue: 621.00 },
                    { itemName: 'Gauze Pads 4x4', totalQuantity: 8000, averageCost: 1.50, totalValue: 12000.00 },
                    { itemName: 'Salbutamol Nebule', totalQuantity: 150, averageCost: 2.10, totalValue: 315.00 },
                    { itemName: 'Losartan 50mg', totalQuantity: 2500, averageCost: 0.50, totalValue: 1250.00 },
                    { itemName: 'Antiseptic Solution 500ml', totalQuantity: 500, averageCost: 3.00, totalValue: 1500.00 }
                ] : (type === 'stock_card' ? {
                    itemName: extraId === 'ITM001' ? 'Paracetamol 500mg' : (extraId === 'ITM002' ? 'Amoxicillin 250mg' : 'Gauze Pads 4x4'),
                    itemType: 'Tablet',
                    currentBalance: 7500,
                    batches: [
                        { batchId: 'BATCH-2301', quantity: 4500, expiry: '12/2025', cost: 0.10 },
                        { batchId: 'BATCH-2304', quantity: 3000, expiry: '06/2026', cost: 0.10 }
                    ],
                    transactions: [
                        { date: '01/10/2023', type: 'Initial Stock', ref: 'System Migration', batch: 'BATCH-2301', in: 5000, out: 0, balance: 5000 },
                        { date: '01/15/2023', type: 'Issuance', ref: 'Health Center Alpha', batch: 'BATCH-2301', in: 0, out: 500, balance: 4500 },
                        { date: '01/20/2023', type: 'Receiving', ref: 'PO-230003', batch: 'BATCH-2304', in: 3000, out: 0, balance: 7500 }
                    ]
                } : {}))
            };
            this.history.unshift(mockReport);
            window.renderReport(mockReport);
            
            // Persistent Save
            const formData = new FormData();
            formData.append('action', 'save_report');
            formData.append('report', JSON.stringify(mockReport));
            fetch('api.php', { method: 'POST', body: formData });
        }
    };
}
</script>
