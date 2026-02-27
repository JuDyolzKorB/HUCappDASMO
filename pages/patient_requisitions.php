<?php
// pages/patient_requisitions.php

$patients = get_data('patients');
$patientRequisitions = get_data('patient_requisitions');
$items = get_data('items');

$tab = $_GET['tab'] ?? 'requisitions';
?>

<div class="space-y-6" x-data="{ 
    tab: '<?php echo $tab; ?>',
    userRole: '<?php echo $userRole; ?>',
    openPatientModal() { $dispatch('open-patient-modal') },
    openRequestModal() { $dispatch('open-patient-requisition-modal') },
    viewRequisition(req) { $dispatch('open-patient-requisition-view-modal', { requisition: req }) },
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
}">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Patient Requisitions</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Manage patient records and drug dispense requests.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-slate-200 dark:bg-slate-700/50 p-1 rounded-lg">
                <button @click="tab = 'requisitions'" 
                        :class="tab === 'requisitions' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary' : 'text-slate-500'"
                        class="px-4 py-1.5 text-sm font-semibold rounded-md transition-all">Requisitions</button>
                <button @click="tab = 'patients'" 
                        :class="tab === 'patients' ? 'bg-white dark:bg-slate-600 shadow-sm text-primary' : 'text-slate-500'"
                        class="px-4 py-1.5 text-sm font-semibold rounded-md transition-all">Patients</button>
            </div>
            
            <template x-if="tab === 'patients'">
                <button @click="openPatientModal()" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-xl shadow-lg transition-all font-bold text-sm">
                    + Add Patient
                </button>
            </template>
            <template x-if="tab === 'requisitions'">
                <button @click="openRequestModal()" class="bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-xl shadow-lg transition-all font-bold text-sm">
                    + New Requisition
                </button>
            </template>
        </div>
    </div>

    <!-- Content -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        
        <!-- Requisitions Tab -->
        <div x-show="tab === 'requisitions'" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Req #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($patientRequisitions)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">No patient requisitions found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($patientRequisitions as $pr): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900 dark:text-white"><?php echo $pr['RequisitionNumber']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300"><?php echo $pr['PatientFullName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo date('M d, Y', strtotime($pr['RequestDate'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-bold rounded-full 
                                    <?php 
                                    switch($pr['StatusType']) {
                                        case 'Approved': echo 'bg-green-100 text-green-800'; break;
                                        case 'Rejected': echo 'bg-red-100 text-red-800'; break;
                                        case 'Completed': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-yellow-100 text-yellow-800'; break;
                                    }
                                    ?>">
                                    <?php echo $pr['StatusType']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button @click="viewRequisition(<?php echo htmlspecialchars(json_encode($pr)); ?>)" class="text-primary hover:underline font-bold mr-3">View</button>
                                <?php if ($pr['StatusType'] === 'Approved'): ?>
                                    <button @click="updateStatus('<?php echo $pr['PatientReqID']; ?>', 'Completed')" class="text-blue-600 hover:text-blue-700 font-bold mr-3">Dispense</button>
                                <?php endif; ?>
                                <?php if (($userRole === 'Administrator' || $userRole === 'Head Pharmacist') && $pr['StatusType'] === 'Pending'): ?>
                                    <button @click="updateStatus('<?php echo $pr['PatientReqID']; ?>', 'Approved')" class="text-green-600 hover:text-green-700 font-bold mr-3">Approve</button>
                                    <button @click="updateStatus('<?php echo $pr['PatientReqID']; ?>', 'Rejected')" class="text-red-600 hover:text-red-700 font-bold">Reject</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Patients Tab -->
        <div x-show="tab === 'patients'" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Age/Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($patients)): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">No patients registered.</td></tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800 dark:text-white"><?php echo $p['LName'] . ', ' . $p['FName']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300"><?php echo $p['Age'] . ' / ' . $p['Gender']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo $p['ContactNumber']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <button @click="$dispatch('open-patient-modal', { patient: <?php echo htmlspecialchars(json_encode($p)); ?> })" 
                                        class="text-primary hover:underline font-bold mr-3">Edit</button>
                                <button @click="$dispatch('open-patient-requisition-modal', { patientId: '<?php echo $p['PatientID']; ?>' })"
                                        class="text-teal-600 hover:text-teal-700 font-bold">New Req</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include 'components/patient_modal.php'; ?>
<?php include 'components/patient_requisition_modal.php'; ?>
<?php include 'components/patient_requisition_view_modal.php'; ?>
