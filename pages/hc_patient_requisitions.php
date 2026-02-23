<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireRole(['Administrator', 'Health Center Staff']);

$user = getCurrentUser();
$healthCenterId = $user['HealthCenterID'] ?? null;
$healthCenterName = $user['HealthCenterName'] ?? 'Health Center';

if ($healthCenterId && empty($user['HealthCenterName'])) {
    $db = Database::getInstance();
    $hcRec = $db->fetchOne("SELECT Name FROM HealthCenters WHERE HealthCenterID = ?", [$healthCenterId]);
    if ($hcRec) {
        $healthCenterName = $hcRec['Name'];
    }
}

$hcConn = $healthCenterId ? Database::getHCConnection($healthCenterId) : null;

$patientRequisitions = [];
$inventoryItems = [];
$patientHistory = [];

if ($hcConn) {
    // Fetch Patient Requisitions
    try {
        $stmt = $hcConn->query("
            SELECT pr.*, 
                   CONCAT(s.FirstName, ' ', s.LastName) as StaffName 
            FROM PatientRequisition pr
            LEFT JOIN HC_Staff s ON pr.StaffID = s.StaffID
            ORDER BY pr.RequestDate DESC
        ");
        $patientRequisitions = $stmt->fetchAll();

        // Aggregate Patient History
        $stmt = $hcConn->query("
            SELECT 
                PatientName, 
                PatientAddress, 
                ContactNumber,
                MAX(RequestDate) as LastRequestDate,
                COUNT(*) as TotalRequests
            FROM PatientRequisition
            GROUP BY PatientName, ContactNumber
            ORDER BY LastRequestDate DESC
        ");
        $patientHistory = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Patient Requisitions Fetch Error: " . $e->getMessage());
    }

    // Fetch inventory separately so it always works even if above fails
    try {
        $mainDb = DB_NAME;
        $stmt = $hcConn->query("
            SELECT hci.ItemID, i.ItemName, SUM(hci.QuantityOnHand) as TotalAvail
            FROM hc_inventory hci
            LEFT JOIN $mainDb.Item i ON hci.ItemID = i.ItemID
            WHERE hci.QuantityOnHand > 0
            GROUP BY hci.ItemID, i.ItemName
        ");
        $inventoryItems = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("HC Inventory Fetch Error: " . $e->getMessage());
    }
}
?>

<div class="space-y-6" x-data="{ 
    activeTab: 'active',
    showModal: false,
    closeModal() {
        this.showModal = false;
        const form = document.getElementById('patientReqForm');
        const items = document.getElementById('patientItemsContainer');
        if (form) form.reset();
        if (items) items.innerHTML = '';
    },
    showDetailsModal: false,
    activeReqItems: [],
    activeReqDetails: null, // New state for details
    isLoadingDetails: false,
    async viewDetails(reqId) {
        this.activeReqId = reqId;
        this.showDetailsModal = true;
        this.isLoadingDetails = true;
        this.activeReqItems = [];
        this.activeReqDetails = null; // Reset
        
        try {
            const formData = new FormData();
            formData.append('action', 'get_hc_patient_requisition_items');
            formData.append('requisition_id', reqId);
            
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if (data.success) {
                this.activeReqItems = data.items;
                this.activeReqDetails = data.details; // Set details
            } else {
                alert(data.message);
                this.showDetailsModal = false;
            }
        } catch (e) {
            console.error(e);
            alert('Failed to load details');
            this.showDetailsModal = false;
        } finally {
            this.isLoadingDetails = false;
        }
    }
}" x-effect="if (showModal) { const c = document.getElementById('patientItemsContainer'); if (c && c.children.length === 0) addPatientItemRow(); }">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Patient Requisitions</h1>
            <p class="text-slate-500 dark:text-slate-400">Manage item requests for <strong><?php echo htmlspecialchars($healthCenterName); ?></strong>.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-900/50 rounded-xl border border-slate-200/60 dark:border-slate-700/60">
                <button @click="activeTab = 'active'" 
                    :class="activeTab === 'active' ? 'bg-white dark:bg-slate-800 text-primary shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all">
                    Active Requests
                </button>
                <button @click="activeTab = 'history'" 
                    :class="activeTab === 'history' ? 'bg-white dark:bg-slate-800 text-primary shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-4 py-1.5 rounded-lg text-sm font-bold transition-all ml-1">
                    Patient History
                </button>
            </div>
            <?php if ($hcConn): ?>
                <button @click="showModal = true" 
                    class="bg-primary hover:bg-opacity-90 text-white px-6 py-2.5 rounded-xl shadow-lg text-sm font-bold transition-all active:scale-95">
                    + New Request
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active Requests Tab -->
    <div x-show="activeTab === 'active'" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden animate-fade-in">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Patient Info</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">ID Proof</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Requested By</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($patientRequisitions)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400 italic">
                                No active patient requisitions found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patientRequisitions as $req): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    <?php echo date('M d, Y H:i', strtotime($req['RequestDate'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($req['PatientName']); ?></div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($req['ContactNumber'] ?? 'No Contact'); ?></div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[150px]"><?php echo htmlspecialchars($req['PatientAddress'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($req['IDProofPath']): ?>
                                        <a href="<?php echo htmlspecialchars($req['IDProofPath']); ?>" target="_blank" class="flex items-center gap-1 text-primary hover:underline text-xs font-medium">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            View ID
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 italic">No ID</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                    <?php echo htmlspecialchars($req['StaffName'] ?? 'Unknown'); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                        $statusColor = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                                        if ($req['StatusType'] === 'Approved') $statusColor = 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400';
                                        if ($req['StatusType'] === 'Denied') $statusColor = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-bold rounded-full <?php echo $statusColor; ?>">
                                        <?php echo $req['StatusType']; ?>
                                    </span>
                                    <?php if (!empty($req['ApproverName'])): ?>
                                        <div class="text-[10px] text-slate-400 mt-1">by <?php echo htmlspecialchars($req['ApproverName']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($req['StatusType'] === 'Denied' && !empty($req['Remarks'])): ?>
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1 italic max-w-[150px]">
                                            "<?php echo htmlspecialchars($req['Remarks']); ?>"
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <button @click="viewDetails(<?php echo $req['PatientRequisitionID']; ?>)" class="text-primary hover:underline text-sm font-medium">Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Patient History Tab -->
    <div x-show="activeTab === 'history'" x-cloak class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden animate-fade-in">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Patient Name</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Requests</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Last Request</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($patientHistory)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400 italic">
                                No patient history found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patientHistory as $pat): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm font-bold text-slate-900 dark:text-white">
                                    <?php echo htmlspecialchars($pat['PatientName']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                    <?php echo htmlspecialchars($pat['ContactNumber'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <?php echo htmlspecialchars($pat['PatientAddress'] ?? 'N/A'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400 font-medium">
                                    <?php echo $pat['TotalRequests']; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                    <?php echo date('M d, Y', strtotime($pat['LastRequestDate'])); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <button class="text-primary hover:underline text-sm font-medium">View History</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Patient Requisition Modal (Teleported to Body for z-index) -->
    <template x-teleport="body">
        <div x-show="showModal" 
             x-cloak
             class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
            <!-- Backdrop with blur -->
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                 @click="closeModal()"></div>
            
            <!-- Modal content -->
            <div x-show="showModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @keydown.escape.window="closeModal()"
                 class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative transition-all duration-300">
                <form id="patientReqForm" onsubmit="handlePatientReqSubmit(event)" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_hc_patient_requisition">
                    <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">New Patient Requisition</h3>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold"><?php echo htmlspecialchars($healthCenterName); ?></p>
                        </div>
                        <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 focus:outline-none text-2xl">&times;</button>
                    </div>
                    
                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                        <!-- Patient Info Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Patient Name</label>
                                <input type="text" name="patientName" required placeholder="Full Name" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Contact Number</label>
                                <input type="text" name="contactNumber" placeholder="Mobile / Phone" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">ID Proof (Photo)</label>
                                <input type="file" name="idProof" accept="image/*,application/pdf" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Address</label>
                                <textarea name="patientAddress" rows="2" placeholder="Full Address" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Additional Info</label>
                                <input type="text" name="otherInfo" placeholder="Status, Diagnosis, or Category" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                            </div>
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-700 pt-4">
                            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">Requested Items</label>
                            <div id="patientItemsContainer" x-ref="items" class="space-y-3">
                                <!-- Rows added via JS -->
                            </div>
                            <button type="button" onclick="addPatientItemRow()" class="mt-3 text-sm font-bold text-primary hover:text-primary-hover transition-colors flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add Another Item
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                        <button type="button" @click="closeModal()" class="px-5 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancel</button>
                        <button type="submit" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2 rounded-xl shadow-lg text-sm font-bold transition-all transform hover:scale-[1.02] active:scale-95">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    <!-- Details Modal -->
    <template x-teleport="body">
        <div x-show="showDetailsModal" 
             x-cloak
             class="fixed inset-0 z-[1000] flex items-center justify-center p-4">
            <div x-show="showDetailsModal"
                 x-transition.opacity.duration.300ms
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" 
                 @click="showDetailsModal = false"></div>
            
            <div x-show="showDetailsModal"
                 x-transition.scale.duration.300ms
                 class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative z-10 flex flex-col max-h-[90vh]">
                
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Requisition Details</h3>
                        <p class="text-xs text-slate-500" x-text="'ID: #' + activeReqId"></p>
                    </div>
                    <button @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <div x-show="isLoadingDetails" class="text-center py-8 text-slate-500">
                        <svg class="animate-spin h-8 w-8 mx-auto text-primary mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading details...
                    </div>

                    <div x-show="!isLoadingDetails && activeReqDetails">
                        <!-- Header Status Section -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div>
                                <div class="text-xs text-slate-500 uppercase tracking-wider font-bold mb-1">Status</div>
                                <span class="px-3 py-1 text-sm font-bold rounded-full inline-block"
                                      :class="{
                                          'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': activeReqDetails?.StatusType === 'Pending',
                                          'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400': activeReqDetails?.StatusType === 'Approved',
                                          'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': activeReqDetails?.StatusType === 'Denied'
                                      }"
                                      x-text="activeReqDetails?.StatusType">
                                </span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-slate-500 uppercase tracking-wider font-bold mb-1">Request Date</div>
                                <div class="text-sm font-medium text-slate-700 dark:text-slate-300" x-text="new Date(activeReqDetails?.RequestDate).toLocaleString()"></div>
                            </div>
                        </div>

                        <!-- Patient Info Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-100 dark:border-slate-700 pb-1">Patient Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs text-slate-500">Full Name</div>
                                        <div class="font-bold text-slate-900 dark:text-white text-lg" x-text="activeReqDetails?.PatientName"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-slate-500">Contact Number</div>
                                        <div class="font-medium text-slate-700 dark:text-slate-300" x-text="activeReqDetails?.ContactNumber || 'N/A'"></div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-slate-500">Address</div>
                                        <div class="font-medium text-slate-700 dark:text-slate-300" x-text="activeReqDetails?.PatientAddress || 'N/A'"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-100 dark:border-slate-700 pb-1">Request Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs text-slate-500">Requested By (Staff)</div>
                                        <div class="font-medium text-slate-700 dark:text-slate-300" x-text="activeReqDetails?.StaffName || 'Unknown'"></div>
                                    </div>
                                    <template x-if="activeReqDetails?.StatusType !== 'Pending'">
                                        <div>
                                            <div class="text-xs text-slate-500" x-text="activeReqDetails?.StatusType + ' By'"></div>
                                            <div class="font-medium text-slate-700 dark:text-slate-300" x-text="activeReqDetails?.ApproverName || 'Unknown'"></div>
                                            <div class="text-xs text-slate-400 mt-0.5" x-text="activeReqDetails?.ApprovedAt"></div>
                                        </div>
                                    </template>
                                    <template x-if="activeReqDetails?.IDProofPath">
                                        <div>
                                            <div class="text-xs text-slate-500 mb-1">ID Proof</div>
                                            <a :href="activeReqDetails?.IDProofPath" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors text-xs font-medium">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                View Document
                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks if Denied/Approved with remarks -->
                        <template x-if="activeReqDetails?.Remarks">
                            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-xl">
                                <div class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider mb-1">Remarks</div>
                                <div class="text-sm text-red-700 dark:text-red-300 italic" x-text="'&ldquo;' + activeReqDetails?.Remarks + '&rdquo;'"></div>
                            </div>
                        </template>

                        <!-- Items List -->
                        <div>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 border-b border-slate-100 dark:border-slate-700 pb-1">Requested Items</h4>
                            
                            <template x-if="activeReqItems.length === 0">
                                <p class="text-center text-slate-500 italic py-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl">No items found for this requisition.</p>
                            </template>
                            
                            <div x-show="activeReqItems.length > 0" class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-100 dark:divide-slate-700/50">
                                <template x-for="item in activeReqItems" :key="item.ItemID">
                                    <div class="flex justify-between items-center p-3 bg-slate-50/30 dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                            </div>
                                            <div>
                                                <div class="font-medium text-slate-900 dark:text-white" x-text="item.ItemName"></div>
                                                <div class="text-xs text-slate-500" x-text="item.Unit"></div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-xs text-slate-400 block mb-0.5">Qty</span>
                                            <span class="text-sm font-bold bg-white dark:bg-slate-900 px-3 py-1 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm" x-text="item.QuantityRequested"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700 flex justify-end flex-shrink-0">
                    <button @click="showDetailsModal = false" class="px-5 py-2 text-sm font-bold text-slate-600 hover:text-slate-800 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl shadow-sm hover:shadow transition-all">Close</button>
                </div>
            </div>
        </div>
    </template>
</div>



<script>
const inventoryItems = <?php echo json_encode($inventoryItems); ?>;

function addPatientItemRow() {
    const container = document.getElementById('patientItemsContainer');
    const index = container.children.length;
    
    const div = document.createElement('div');
    div.className = 'flex gap-3 items-end group animate-in slide-in-from-left duration-200';

    let itemInput;
    if (inventoryItems.length > 0) {
        itemInput = `
            <div class="flex-1">
                <select name="items[${index}][itemId]" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm">
                    <option value="">Select Item...</option>
                    ${inventoryItems.map(i => `<option value="${i.ItemID}">${i.ItemName} (${i.TotalAvail} avail)</option>`).join('')}
                </select>
            </div>`;
    } else {
        itemInput = `
            <div class="flex-1">
                <div class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl px-4 py-2">
                    ⚠ No inventory items available. Please ensure HC inventory is synced first.
                </div>
                <input type="hidden" name="items[${index}][itemId]" value="">
            </div>`;
    }

    div.innerHTML = `
        ${itemInput}
        <div class="w-24">
            <input type="number" name="items[${index}][quantity]" min="1" placeholder="Qty" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm">
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="p-2 text-slate-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>
    `;
    container.appendChild(div);
}

async function handlePatientReqSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // UI Loading state
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Submitting...';
    
    try {
        const response = await fetch('api.php', { method: 'POST', body: formData });
        const rawText = await response.text();
        let result;
        try {
            result = JSON.parse(rawText);
        } catch (parseErr) {
            console.error('Non-JSON response from api.php:', rawText);
            alert('Server returned an unexpected response:\n' + rawText.substring(0, 500));
            btn.disabled = false;
            btn.innerText = originalText;
            return;
        }

        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
            btn.disabled = false;
            btn.innerText = originalText;
        }
    } catch (error) {
        console.error('Fetch error:', error);
        alert('Network error: ' + error.message);
        btn.disabled = false;
        btn.innerText = originalText;
    }
}
</script>
