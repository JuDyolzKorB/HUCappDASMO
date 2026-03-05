<?php
$allPatients = get_data('patients');
$allItems = get_data('items');
?>
<div x-data="{}">
    <template x-teleport="body">
        <div x-data='{
            isOpen: false,
            patients: <?php echo json_encode($allPatients, JSON_HEX_APOS); ?>,
            items: <?php echo json_encode($allItems, JSON_HEX_APOS); ?>,
            form: {
                patientId: "",
                patientName: "",
                diagnosis: "",
                notes: "",
                contactInfo: "",
                idProof: "",
                selectedItems: []
            },
            searchItem: "",
            filteredItems() {
                if (!this.searchItem) return [];
                return this.items.filter(i => i.ItemName.toLowerCase().includes(this.searchItem.toLowerCase())).slice(0, 5);
            },
            addItem(item) {
                if (!item) return;
                if (!this.form.selectedItems.find(i => i.ItemID == item.ItemID)) {
                    this.form.selectedItems.push({ ...item, QuantityRequested: 1 });
                }
                this.searchItem = "";
            },
            removeItem(index) {
                this.form.selectedItems.splice(index, 1);
            },
            resetForm() {
                this.form = { patientId: "", patientName: "", diagnosis: "", notes: "", contactInfo: "", idProof: "", selectedItems: [] };
            },
            submit() {
                if ((!this.form.patientId && !this.form.patientName) || this.form.selectedItems.length === 0) {
                    alert("Please select/enter a patient and add at least one item.");
                    return;
                }

                const formData = new FormData();
                formData.append("action", "create_patient_requisition");
                formData.append("patientId", this.form.patientId);
                formData.append("patientName", this.form.patientName);
                formData.append("diagnosis", this.form.diagnosis);
                formData.append("notes", this.form.notes);
                formData.append("contactInfo", this.form.contactInfo);
                formData.append("idProof", this.form.idProof);
                formData.append("items", JSON.stringify(this.form.selectedItems));

                fetch("api.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }'
        x-show="isOpen"
        @open-patient-requisition-modal.window="
            resetForm();
            if ($event.detail && $event.detail.patientId) {
                form.patientId = $event.detail.patientId;
            }
            isOpen = true;
        "
        class="fixed inset-0 z-[9999] overflow-y-auto"
        x-cloak>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="isOpen = false"></div>

                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl p-8 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">New Drug Dispense Request</h2>
                    
                    <div class="space-y-6">
                        <!-- Select Patient or Manual Entry -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Registered Patient</label>
                                <select x-model="form.patientId" @change="if(form.patientId) form.patientName = ''" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                                    <option value="">Select Registered Patient</option>
                                    <template x-for="p in patients" :key="p.PatientID">
                                        <option :value="p.PatientID" x-text="p.LName + ', ' + p.FName"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Manual Patient Name (if not registered)</label>
                                <input type="text" x-model="form.patientName" @input="if(form.patientName) form.patientId = ''" placeholder="Full Name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Diagnosis</label>
                                <input type="text" x-model="form.diagnosis" placeholder="Fever, Cough, etc." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Notes</label>
                                <input type="text" x-model="form.notes" placeholder="Additional instructions" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Contact Info (for this req)</label>
                                <input type="text" x-model="form.contactInfo" placeholder="Mobile / Landline" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">ID Proof (for this req)</label>
                                <input type="text" x-model="form.idProof" placeholder="ID No / Type" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                        </div>

                        <!-- Add Items via Dropdown -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Select Drug/Medicine</label>
                            <div class="flex gap-2">
                                <select x-model="searchItem" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                                    <option value="">Choose a medicine...</option>
                                    <template x-for="item in items" :key="item.ItemID">
                                        <option :value="item.ItemID" x-text="item.ItemName"></option>
                                    </template>
                                </select>
                                <button type="button" @click="if(searchItem) { addItem(items.find(i => i.ItemID == searchItem)); searchItem = ''; }" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl font-bold hover:bg-slate-200 transition-all">
                                    Add
                                </button>
                            </div>
                        </div>

                        <!-- Item List -->
                        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200 dark:border-slate-700 p-2">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs text-slate-500 uppercase">
                                        <th class="px-4 py-2 text-left">Item Name</th>
                                        <th class="px-4 py-2 text-center" width="100">Qty</th>
                                        <th class="px-4 py-2 text-right" width="50"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(item, index) in form.selectedItems" :key="item.ItemID">
                                        <tr class="border-t border-slate-200 dark:border-slate-700">
                                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white" x-text="item.ItemName"></td>
                                            <td class="px-4 py-3">
                                                <input type="number" x-model="item.QuantityRequested" min="1" class="w-full text-center px-1 py-1 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button @click="removeItem(index)" class="text-red-500 hover:text-red-600">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="form.selectedItems.length === 0">
                                        <td colspan="3" class="px-4 py-8 text-center text-slate-400 text-sm italic">No items added yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="isOpen = false" class="flex-1 px-6 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all">
                                Cancel
                            </button>
                            <button type="button" @click="submit" class="flex-1 px-6 py-3 rounded-xl bg-primary text-white font-bold hover:bg-primary-hover shadow-lg transition-all">
                                Submit Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
