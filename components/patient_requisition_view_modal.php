<div x-data="{
    isOpen: false,
    req: null,
    open(requisition) {
        this.req = requisition;
        this.isOpen = true;
    },
    close() {
        this.isOpen = false;
        this.req = null;
    },
    formatDate(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    },
    updateStatus(reqId, status) {
        if (!confirm('Are you sure you want to change status to ' + status + '?')) return;
        const formData = new FormData();
        formData.append('action', 'update_patient_requisition_status');
        formData.append('requisitionId', reqId);
        formData.append('status', status);

        fetch('api.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}" 
@open-patient-requisition-view-modal.window="open($event.detail.requisition)"
x-show="isOpen" 
class="fixed inset-0 z-[9999] overflow-y-auto" 
x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="close()"></div>

        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl p-8 border border-slate-200 dark:border-slate-700">
            <template x-if="req">
                <div>
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900 dark:text-white" x-text="'Requisition ' + req.RequisitionNumber"></h2>
                            <p class="text-sm text-slate-500 font-medium mt-1" x-text="formatDate(req.RequestDate)"></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold" 
                              :class="{
                                  'bg-yellow-100 text-yellow-800': req.StatusType === 'Pending',
                                  'bg-green-100 text-green-800': req.StatusType === 'Approved',
                                  'bg-red-100 text-red-800': req.StatusType === 'Rejected',
                                  'bg-blue-100 text-blue-800': req.StatusType === 'Completed'
                              }" 
                              x-text="req.StatusType">
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Patient Details</h3>
                            <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="req.PatientFullName"></p>
                            <p class="text-xs text-slate-500 mt-1" x-text="(req.Age || 'N/A') + ' years old • ' + (req.Gender || 'N/A')"></p>
                            <p class="text-xs text-slate-500 mt-1" x-text="'Contact: ' + (req.ContactInfo || 'N/A')"></p>
                            <p class="text-xs text-slate-500 mt-1" x-text="'ID Proof: ' + (req.IDProof || 'N/A')"></p>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Clinical Info</h3>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Diagnosis:</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400 italic" x-text="req.Diagnosis || 'None'"></p>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-3">Notes:</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400" x-text="req.Notes || 'No additional notes'"></p>
                        </div>
                    </div>

                    <div class="table-container p-0 border border-slate-100 dark:border-slate-700 overflow-hidden rounded-xl mb-8">
                        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                            <thead class="bg-slate-50 dark:bg-slate-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">Item Name</th>
                                    <th class="px-6 py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <template x-for="item in req.Items" :key="item.ItemID">
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                        <td class="px-6 py-4 text-sm font-medium text-slate-900 dark:text-white" x-text="item.ItemName"></td>
                                        <td class="px-6 py-4 text-sm text-center font-bold text-slate-600 dark:text-slate-400" x-text="item.QuantityRequested"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex gap-3">
                        <button @click="close()" class="flex-1 px-6 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all">
                            Close
                        </button>
                        <template x-if="req.StatusType === 'Approved'">
                            <button @click="updateStatus(req.PatientReqID, 'Completed')" class="flex-1 px-6 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg transition-all">
                                Dispense Medicine
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
