<?php
// components/requisition_details_modal.php
?>

<div id="requisitionDetailsModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden z-[9999] flex justify-center items-center p-4 overflow-y-auto" onclick="if(event.target === this) closeRequisitionDetailsModal()">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all relative my-8 border border-slate-200/60 dark:border-slate-700/60" onclick="event.stopPropagation()">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 id="reqModalTitle" class="text-xl font-semibold text-slate-900 dark:text-white">Requisition Details</h3>
                <button onclick="closeRequisitionDetailsModal()" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 text-2xl font-bold">&times;</button>
            </div>
            
            <div class="grid grid-cols-2 gap-4 text-sm mb-6 text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-700 pb-4">
                <div><strong>Health Center:</strong> <span id="reqHealthCenter" class="text-slate-900 dark:text-white font-medium"></span></div>
                <div><strong>Status:</strong> <span id="reqStatus" class="px-2 py-0.5 rounded-full text-xs font-bold"></span></div>
                <div><strong>Requested By:</strong> <span id="reqBy" class="text-slate-900 dark:text-white font-medium"></span></div>
                <div><strong>Date:</strong> <span id="reqDate" class="text-slate-900 dark:text-white font-medium"></span></div>
            </div>

            <div class="space-y-6">
                <!-- Items Section -->
                <section>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-3">Requested Items</h4>
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                        <ul id="reqItemsList" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-600 dark:text-slate-400">
                            <!-- Items populated via JS -->
                        </ul>
                    </div>
                </section>

                <!-- Status History Section -->
                <section id="statusHistorySection">
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-400 mb-3">Status History</h4>
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                        <ul id="statusHistoryList" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-600 dark:text-slate-400">
                            <!-- History populated via JS -->
                        </ul>
                    </div>
                </section>

                <!-- Adjustments Section -->
                <section id="adjustmentsSection">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-slate-400">Adjustments</h4>
                        <?php if (in_array($_SESSION['user']['Role'], ['Administrator', 'Head Pharmacist'])): ?>
                        <button onclick="toggleAdjustmentForm()" id="addAdjBtn" class="text-xs font-bold text-primary hover:text-cyan-600 transition-colors">+ Add Adjustment</button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add Adjustment Form (Simple hidden toggle) -->
                    <div id="adjustmentForm" class="hidden bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-200 dark:border-slate-700 mb-4">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Type</label>
                                    <select id="adjType" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 outline-none">
                                        <option value="Damaged">Damaged</option>
                                        <option value="Lost">Lost</option>
                                        <option value="Quality Issue">Quality Issue</option>
                                        <option value="Return">Return</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Reason</label>
                                    <input type="text" id="adjReason" placeholder="Brief explanation..." class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary/20 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Items to Adjust</label>
                                <div id="adjItemsContainer" class="space-y-2 max-h-32 overflow-y-auto pr-2">
                                    <!-- Populated based on actual issuance/items -->
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button onclick="toggleAdjustmentForm()" class="px-3 py-1.5 text-xs font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancel</button>
                                <button onclick="submitAdjustment()" class="bg-primary hover:bg-opacity-90 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-all active:scale-95 shadow-lg shadow-teal-900/10">Save Adjustment</button>
                            </div>
                        </div>
                    </div>

                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                        <ul id="adjustmentsList" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-600 dark:text-slate-400">
                            <!-- Adjustments populated via JS -->
                        </ul>
                    </div>
                </section>
            </div>

            <div id="approvalActions" class="hidden mt-8 pt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end space-x-3">
                <button onclick="updateRequisitionStatus('Rejected')" class="bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 px-6 py-2 rounded-xl font-bold text-xs transition-all active:scale-95 uppercase tracking-widest">Reject</button>
                <button onclick="updateRequisitionStatus('Approved')" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2 rounded-xl font-bold text-xs transition-all active:scale-95 shadow-lg shadow-teal-900/10 uppercase tracking-widest">Approve</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRequisition = null;

function openRequisitionDetailsModal(requisition) {
    currentRequisition = requisition;
    
    document.getElementById('reqModalTitle').textContent = `Requisition Details - ${requisition.RequisitionNumber}`;
    document.getElementById('reqHealthCenter').textContent = requisition.HealthCenterName;
    
    const statusSpan = document.getElementById('reqStatus');
    statusSpan.textContent = requisition.StatusType;
    statusSpan.className = 'px-2 py-0.5 rounded-full text-xs font-bold ';
    if (requisition.StatusType === 'Approved') statusSpan.className += 'bg-green-100 text-green-800';
    else if (requisition.StatusType === 'Rejected') statusSpan.className += 'bg-red-100 text-red-800';
    else if (requisition.StatusType === 'Pending') statusSpan.className += 'bg-yellow-100 text-yellow-800';
    else statusSpan.className += 'bg-blue-100 text-blue-800';

    document.getElementById('reqBy').textContent = requisition.RequestedByFullName;
    document.getElementById('reqDate').textContent = new Date(requisition.RequestedDate || requisition.RequestDate).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    
    // Items
    const list = document.getElementById('reqItemsList');
    list.innerHTML = '';
    requisition.RequisitionItems.forEach(item => {
        const li = document.createElement('li');
        li.className = 'flex justify-between p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors';
        li.innerHTML = `<span class="font-medium text-slate-700 dark:text-slate-300">${item.ItemName || item.ItemID}</span><span class="text-slate-500">Qty: ${item.QuantityRequested}</span>`;
        list.appendChild(li);
    });

    // History
    const historyList = document.getElementById('statusHistoryList');
    historyList.innerHTML = '';
    if (requisition.ApprovalLogs && requisition.ApprovalLogs.length > 0) {
        requisition.ApprovalLogs.forEach(log => {
            const li = document.createElement('li');
            li.className = 'p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors';
            li.innerHTML = `
                <div class="flex justify-between items-start">
                    <span class="font-bold text-xs ${log.Decision === 'Approved' ? 'text-green-600' : 'text-red-600'}">${log.Decision}</span>
                    <span class="text-[10px] text-slate-400">${new Date(log.DecisionDate).toLocaleString()}</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">By: ${log.ApprovedByFullName || 'Unknown'}</div>
            `;
            historyList.appendChild(li);
        });
    } else {
        historyList.innerHTML = '<li class="p-3 text-center text-slate-400 italic text-xs">No status changes recorded</li>';
    }

    // Adjustments
    const adjList = document.getElementById('adjustmentsList');
    adjList.innerHTML = '';
    if (requisition.Adjustments && requisition.Adjustments.length > 0) {
        requisition.Adjustments.forEach(adj => {
            const li = document.createElement('li');
            li.className = 'p-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors';
            let detailsHtml = adj.Details.map(d => `<div class="text-[10px] text-slate-400 ml-2">• ${d.ItemName}: ${d.QuantityAdjusted} adjusted</div>`).join('');
            li.innerHTML = `
                <div class="flex justify-between items-start">
                    <span class="font-bold text-xs text-slate-700 dark:text-slate-200">${adj.AdjustmentType}</span>
                    <span class="text-[10px] text-slate-400">${new Date(adj.AdjustmentDate).toLocaleDateString()}</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">${adj.Reason || 'No reason provided'} (By: ${adj.AdjustedByFullName})</div>
                <div class="mt-1">${detailsHtml}</div>
            `;
            adjList.appendChild(li);
        });
    } else {
        adjList.innerHTML = '<li class="p-3 text-center text-slate-400 italic text-xs">No adjustments recorded</li>';
    }

    // Actions
    const userRole = "<?php echo $_SESSION['user']['Role']; ?>";
    const canApprove = ['Administrator', 'Head Pharmacist'].includes(userRole);
    const actions = document.getElementById('approvalActions');
    if (requisition.StatusType === 'Pending' && canApprove) {
        actions.classList.remove('hidden');
    } else {
        actions.classList.add('hidden');
    }

    // Adjustment Button Visibility
    const addAdjBtn = document.getElementById('addAdjBtn');
    if (addAdjBtn) {
        // Only allow adjustment if requisition is Completed/Issued
        if (requisition.StatusType === 'Completed' || requisition.StatusType === 'Issued') {
            addAdjBtn.classList.remove('hidden');
        } else {
            addAdjBtn.classList.add('hidden');
        }
    }
    
    document.getElementById('requisitionDetailsModal').classList.remove('hidden');
}

function toggleAdjustmentForm() {
    const form = document.getElementById('adjustmentForm');
    form.classList.toggle('hidden');
    
    if (!form.classList.contains('hidden')) {
        // Populate items for adjustment
        // We need Batch info from Issuance. In a real app, requisitions would link to issuances.
        // For now, let's assume we can adjust the requested items if they were issued.
        const container = document.getElementById('adjItemsContainer');
        container.innerHTML = '';
        
        // We need to know WHICH batches were issued. 
        // This data should ideally be in the requisition object now too.
        // Let's check for 'IssuedItems' or similar. 
        // If not present, we can't easily adjust specific batches without more DB lookups.
        // Mocking for now - showing items requested.
        currentRequisition.RequisitionItems.forEach(item => {
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2';
            div.innerHTML = `
                <input type="checkbox" class="adj-item-check" data-itemid="${item.ItemID}" checked>
                <span class="text-xs text-slate-600 flex-1">${item.ItemName || item.ItemID}</span>
                <input type="number" class="adj-item-qty w-16 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-2 py-1 text-xs outline-none" placeholder="Qty" value="1">
            `;
            container.appendChild(div);
        });
    }
}

function submitAdjustment() {
    const type = document.getElementById('adjType').value;
    const reason = document.getElementById('adjReason').value;
    const items = [];
    
    document.querySelectorAll('#adjItemsContainer div').forEach(div => {
        const check = div.querySelector('.adj-item-check');
        const qtyInput = div.querySelector('.adj-item-qty');
        if (check.checked) {
            items.push({
                itemID: check.dataset.itemid,
                quantity: qtyInput.value
            });
        }
    });

    if (items.length === 0) {
        alert('Please select at least one item to adjust.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_requisition_adjustment');
    formData.append('issuanceId', currentRequisition.IssuanceID || ''); // Need to ensure IssuanceID is in REQ object
    formData.append('adjustmentType', type);
    formData.append('reason', reason);
    formData.append('items', JSON.stringify(items));

    fetch('api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert('Adjustment saved successfully');
            window.location.reload();
        } else {
            alert(data.message || 'Error saving adjustment');
        }
    });
}

function closeRequisitionDetailsModal() {
    document.getElementById('requisitionDetailsModal').classList.add('hidden');
    document.getElementById('adjustmentForm').classList.add('hidden');
}

function updateRequisitionStatus(status) {
    if (!confirm(`Are you sure you want to ${status.toLowerCase()} this requisition?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'update_requisition_status');
    formData.append('requisitionId', currentRequisition.RequisitionID);
    formData.append('status', status);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert(`Requisition ${status} successfully!`);
            window.location.reload();
        } else {
            alert(data.message || 'Error updating status');
        }
    });
}
</script>
