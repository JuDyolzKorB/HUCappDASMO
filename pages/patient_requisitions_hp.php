<?php
require_once 'includes/auth.php';
requireRole(['Administrator', 'Head Pharmacist']);
$user = getCurrentUser();
?>
<div class="space-y-6" x-data="patientReqApprovals()">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Patient Requisition Approvals</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Review and approve patient medication requests from all health centers.</p>
        </div>
        <div class="flex gap-2">
            <button @click="filterStatus = 'Pending'" :class="filterStatus === 'Pending' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all">Pending</button>
            <button @click="filterStatus = 'Approved'" :class="filterStatus === 'Approved' ? 'bg-teal-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all">Approved</button>
            <button @click="filterStatus = 'Denied'" :class="filterStatus === 'Denied' ? 'bg-red-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all">Denied</button>
            <button @click="filterStatus = ''" :class="filterStatus === '' ? 'bg-slate-800 text-white' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700'" class="px-4 py-2 rounded-xl text-sm font-bold transition-all">All</button>
        </div>
    </div>

    <!-- Loading State -->
    <div x-show="isLoading" class="flex justify-center py-16">
        <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Table -->
    <div x-show="!isLoading" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Health Center</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <template x-if="filtered.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 italic">No requisitions found.</td>
                        </tr>
                    </template>
                    <template x-for="req in filtered" :key="req.PatientRequisitionID + '-' + req.HealthCenterID">
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400" x-text="formatDate(req.RequestDate)"></td>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-white" x-text="req.HealthCenterName"></td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-slate-900 dark:text-white" x-text="req.PatientName"></div>
                                <div class="text-xs text-slate-500" x-text="req.ContactNumber"></div>
                            </td>
                            <td class="px-6 py-4">
                                <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-0.5">
                                    <template x-for="item in req.Items" :key="item.HCPRIID">
                                        <li x-text="(item.ItemName || 'Item #'+item.ItemID) + ' × ' + item.QuantityRequested"></li>
                                    </template>
                                </ul>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-bold rounded-full"
                                      :class="{
                                          'bg-amber-100 text-amber-700': req.StatusType === 'Pending',
                                          'bg-teal-100 text-teal-700': req.StatusType === 'Approved',
                                          'bg-red-100 text-red-700': req.StatusType === 'Denied'
                                      }"
                                      x-text="req.StatusType"></span>
                            </td>
                            <td class="px-6 py-4">
                                <button @click="openReview(req)"
                                        class="text-primary hover:underline text-sm font-bold">
                                    Review
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Review Modal -->
    <template x-teleport="body">
        <div x-show="showModal" x-cloak class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>

            <div x-show="showModal" x-transition class="relative z-10 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Review Requisition</h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
                </div>

                <div class="p-6 space-y-4" x-show="activeReq">
                    <!-- HC & Patient -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Health Center</p>
                            <p class="font-semibold text-slate-900 dark:text-white" x-text="activeReq?.HealthCenterName"></p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Date</p>
                            <p class="text-slate-600 dark:text-slate-300" x-text="formatDate(activeReq?.RequestDate)"></p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Patient Name</p>
                            <p class="font-semibold text-slate-900 dark:text-white" x-text="activeReq?.PatientName"></p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Contact</p>
                            <p class="text-slate-600 dark:text-slate-300" x-text="activeReq?.ContactNumber || '—'"></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Address</p>
                            <p class="text-slate-600 dark:text-slate-300" x-text="activeReq?.PatientAddress || '—'"></p>
                        </div>
                    </div>

                    <!-- Items -->
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Requested Items</p>
                        <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-700">
                            <template x-for="item in activeReq?.Items" :key="item.HCPRIID">
                                <div class="flex justify-between items-center px-4 py-2.5">
                                    <div>
                                        <span class="font-medium text-slate-900 dark:text-white text-sm" x-text="item.ItemName || 'Item #'+item.ItemID"></span>
                                        <span class="text-xs text-slate-500 ml-1" x-text="item.Unit ? '('+item.Unit+')' : ''"></span>
                                    </div>
                                    <span class="text-sm font-bold bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded-lg" x-text="'× '+item.QuantityRequested"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Remarks (optional)</label>
                        <textarea x-model="remarks" rows="2" placeholder="Add a note for the health center..."
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary/20 outline-none transition-all resize-none"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3 bg-slate-50 dark:bg-slate-900/50">
                    <button @click="showModal = false" class="px-5 py-2 text-sm font-bold text-slate-500 hover:text-slate-700">Cancel</button>
                    <button @click="submitDecision('Denied')"
                            :disabled="isSubmitting"
                            class="px-5 py-2 text-sm font-bold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all disabled:opacity-50">
                        <span x-show="!isSubmitting">Deny</span>
                        <span x-show="isSubmitting">...</span>
                    </button>
                    <button @click="submitDecision('Approved')"
                            :disabled="isSubmitting"
                            class="px-5 py-2 text-sm font-bold text-white bg-teal-500 hover:bg-teal-600 rounded-xl transition-all disabled:opacity-50">
                        <span x-show="!isSubmitting">Approve</span>
                        <span x-show="isSubmitting">...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function patientReqApprovals() {
    return {
        isLoading: true,
        requisitions: [],
        filterStatus: 'Pending',
        showModal: false,
        activeReq: null,
        remarks: '',
        isSubmitting: false,

        get filtered() {
            if (!this.filterStatus) return this.requisitions;
            return this.requisitions.filter(r => r.StatusType === this.filterStatus);
        },

        async init() {
            await this.loadRequisitions();
        },

        async loadRequisitions() {
            this.isLoading = true;
            try {
                const fd = new FormData();
                fd.append('action', 'get_all_patient_requisitions');
                const res = await fetch('api.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    this.requisitions = data.requisitions;
                } else {
                    console.error(data.message);
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        },

        openReview(req) {
            this.activeReq = req;
            this.remarks = req.Remarks || '';
            this.showModal = true;
        },

        async submitDecision(status) {
            if (!this.activeReq) return;
            this.isSubmitting = true;
            try {
                const fd = new FormData();
                fd.append('action', 'approve_patient_requisition');
                fd.append('hc_id', this.activeReq.HealthCenterID);
                fd.append('requisition_id', this.activeReq.PatientRequisitionID);
                fd.append('status', status);
                fd.append('remarks', this.remarks);

                const res = await fetch('api.php', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    this.showModal = false;
                    await this.loadRequisitions();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                alert('An error occurred.');
            } finally {
                this.isSubmitting = false;
            }
        },

        formatDate(d) {
            if (!d) return '—';
            return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
}
</script>
