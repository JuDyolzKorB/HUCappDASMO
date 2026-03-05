<div x-data="{}">
    <template x-teleport="body">
        <div x-data='{
            isOpen: false,
            patient: {
                PatientID: "",
                FName: "",
                MName: "",
                LName: "",
                Age: "",
                Gender: "Male",
                Address: "",
                ContactNumber: "",
                IDProof: ""
            },
            resetForm() {
                this.patient = {
                    PatientID: "",
                    FName: "",
                    MName: "",
                    LName: "",
                    Age: "",
                    Gender: "Male",
                    Address: "",
                    ContactNumber: "",
                    IDProof: ""
                };
            },
            save() {
                const formData = new FormData();
                formData.append("action", "add_patient");
                formData.append("patientId", this.patient.PatientID);
                formData.append("firstName", this.patient.FName);
                formData.append("middleName", this.patient.MName);
                formData.append("lastName", this.patient.LName);
                formData.append("age", this.patient.Age);
                formData.append("gender", this.patient.Gender);
                formData.append("address", this.patient.Address);
                formData.append("contactNumber", this.patient.ContactNumber);
                formData.append("idProof", this.patient.IDProof);

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
        @open-patient-modal.window="
            if ($event.detail && $event.detail.patient) {
                patient = { ...$event.detail.patient };
            } else {
                resetForm();
            }
            isOpen = true;
        "
        class="fixed inset-0 z-[9999] overflow-y-auto"
        x-cloak>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="isOpen = false"></div>

                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md p-8 border border-slate-200 dark:border-slate-700">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6" x-text="patient.PatientID ? 'Edit Patient' : 'Add New Patient'"></h2>
                    
                    <form @submit.prevent="save" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">First Name</label>
                                <input type="text" x-model="patient.FName" required placeholder="Juana" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Last Name</label>
                                <input type="text" x-model="patient.LName" required placeholder="Dela Cruz" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Middle Name</label>
                            <input type="text" x-model="patient.MName" placeholder="Cruz" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Age</label>
                                <input type="number" x-model="patient.Age" placeholder="25" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Gender</label>
                                <select x-model="patient.Gender" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Address</label>
                            <textarea x-model="patient.Address" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Contact Number</label>
                            <input type="text" x-model="patient.ContactNumber" placeholder="09123456789" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">ID Proof (ID Number / Type)</label>
                            <input type="text" x-model="patient.IDProof" placeholder="UMID: 1234-5678-9012" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                        </div>

                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="isOpen = false" class="flex-1 px-6 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 px-6 py-3 rounded-xl bg-primary text-white font-bold hover:bg-primary-hover shadow-lg transition-all">
                                Save Patient
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
