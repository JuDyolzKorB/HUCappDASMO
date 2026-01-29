<?php
// components/purchase_order_details_modal.php
?>

<div id="poDetailsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden z-50 flex justify-center items-center p-4" onclick="if(event.target === this) closePODetailsModal()">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all relative border border-slate-200/60 dark:border-slate-700/60" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 id="poModalTitle" class="text-xl font-bold text-slate-900 dark:text-white">Purchase Order Details</h3>
            <button onclick="closePODetailsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 space-y-6">
            <!-- PO Info Grid -->
            <div class="grid grid-cols-2 gap-x-8 gap-y-3">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Supplier:</p>
                    <p id="poSupplier" class="text-sm text-slate-700 dark:text-slate-200"></p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">Status:</p>
                    <div id="poStatusBadge"></div>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">For:</p>
                    <p id="poHealthCenter" class="text-sm text-slate-700 dark:text-slate-200"></p>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">PO Date:</p>
                    <p id="poDate" class="text-sm text-slate-700 dark:text-slate-200"></p>
                </div>
            </div>

            <!-- Ordered Items Section -->
            <div class="space-y-3">
                <h4 class="text-base font-bold text-slate-900 dark:text-white">Ordered Items</h4>
                <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200/60 dark:border-slate-700/60 overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-slate-100/80 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Item</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Quantity Ordered</th>
                            </tr>
                        </thead>
                        <tbody id="poItemsList" class="divide-y divide-slate-200/50 dark:divide-slate-700/50">
                            <!-- Items populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Approval History Section -->
            <div id="poApprovalHistory" class="space-y-3">
                <h4 class="text-base font-bold text-slate-900 dark:text-white">Approval History</h4>
                <div id="poApprovalList" class="min-h-[100px] flex flex-col justify-center items-center p-6 bg-white dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl">
                    <!-- Approval logs or placeholder populated via JS -->
                </div>
            </div>

            <!-- Approval Actions (for pending POs) -->
            <div id="poApprovalActions" class="hidden pt-2 flex justify-end gap-3">
                <button onclick="updatePOStatus('Rejected')" class="px-6 py-2.5 text-sm font-bold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-all active:scale-95">
                    Reject
                </button>
                <button onclick="updatePOStatus('Approved')" class="px-6 py-2.5 text-sm font-bold text-white bg-green-500 hover:bg-green-600 rounded-xl transition-all active:scale-95">
                    Approve
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPOId = null;

function openPODetailsModal(po) {
    currentPOId = po.POID;
    
    document.getElementById('poModalTitle').textContent = `Purchase Order - ${po.PONumber}`;
    document.getElementById('poSupplier').textContent = po.SupplierName || 'N/A';
    document.getElementById('poHealthCenter').textContent = po.HealthCenterName || 'Central Health Unit';
    document.getElementById('poDate').textContent = new Date(po.PODate).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' });
    
    // Status Badge
    const statusBadge = document.getElementById('poStatusBadge');
    let badgeClass = 'bg-slate-100 text-slate-800';
    if (po.StatusType === 'Approved') badgeClass = 'bg-green-100 text-green-800';
    if (po.StatusType === 'Pending') badgeClass = 'bg-yellow-100 text-yellow-800';
    if (po.StatusType === 'Completed') badgeClass = 'bg-blue-100 text-blue-800';
    statusBadge.innerHTML = `<span class="px-3 py-1 text-[10px] font-bold rounded-full ${badgeClass}">${po.StatusType}</span>`;
    
    // Items List
    const list = document.getElementById('poItemsList');
    list.innerHTML = '';
    
    po.PurchaseOrderItems.forEach(item => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-100/50 dark:hover:bg-slate-800/50 transition-colors';
        const itemName = item.ItemName || item.ItemID; 
        tr.innerHTML = `
            <td class="px-5 py-4 text-sm text-slate-700 dark:text-slate-300">${itemName}</td>
            <td class="px-5 py-4 text-sm font-semibold text-slate-900 dark:text-white text-right">${item.QuantityOrdered.toLocaleString()}</td>
        `;
        list.appendChild(tr);
    });
    
    // Approval History
    const approvalList = document.getElementById('poApprovalList');
    
    if (po.ApprovalLogs && po.ApprovalLogs.length > 0) {
        approvalList.className = "space-y-2 w-full";
        approvalList.innerHTML = '';
        po.ApprovalLogs.forEach(log => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-200/60 dark:border-slate-700/60';
            const logDate = new Date(log.DecisionDate).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
            div.innerHTML = `
                <span class="text-sm text-slate-700 dark:text-slate-300">Approved by <strong class="text-slate-900 dark:text-white">${log.ApproverFullName}</strong></span>
                <span class="text-xs text-slate-500 dark:text-slate-400">${logDate}</span>
            `;
            approvalList.appendChild(div);
        });
    } else {
        approvalList.className = "min-h-[100px] flex flex-col justify-center items-center p-6 bg-white dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl w-full";
        approvalList.innerHTML = '<p class="text-sm text-slate-400 font-medium tracking-wide">No approval history yet.</p>';
    }
    
    // Approval Actions
    const userRole = "<?php echo $_SESSION['user']['Role']; ?>";
    const canApprove = ['Administrator', 'Head Pharmacist'].includes(userRole);
    
    const actions = document.getElementById('poApprovalActions');
    if (po.StatusType === 'Pending' && canApprove) {
        actions.classList.remove('hidden');
    } else {
        actions.classList.add('hidden');
    }
    
    document.getElementById('poDetailsModal').classList.remove('hidden');
}

function closePODetailsModal() {
    document.getElementById('poDetailsModal').classList.add('hidden');
}

function updatePOStatus(status) {
    if (!confirm(`Are you sure you want to ${status.toLowerCase()} this purchase order?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'update_po_status');
    formData.append('poId', currentPOId);
    formData.append('status', status);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            alert(`Purchase Order ${status} successfully!`);
            window.location.reload();
        } else {
            alert(data.message || 'Error updating status');
        }
    });
}
</script>
