<?php
// components/requisition_details_modal.php
?>

<div id="requisitionDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-2xl transform transition-all relative">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 id="reqModalTitle" class="text-xl font-semibold text-slate-900 dark:text-white">Requisition Details</h3>
                <button onclick="closeRequisitionDetailsModal()" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 text-2xl font-bold">&times;</button>
            </div>
            
            <div class="grid grid-cols-2 gap-4 text-sm mb-6 text-slate-600 dark:text-slate-400">
                <div><strong>Health Center:</strong> <span id="reqHealthCenter"></span></div>
                <div><strong>Status:</strong> <span id="reqStatus"></span></div>
                <div><strong>Requested By:</strong> <span id="reqBy"></span></div>
                <div><strong>Date:</strong> <span id="reqDate"></span></div>
            </div>

            <h4 class="font-semibold text-slate-900 dark:text-white mb-2">Requested Items</h4>
            <div class="max-h-48 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded-lg p-2 mb-6">
                <ul id="reqItemsList" class="divide-y divide-slate-100 dark:divide-slate-700 text-sm text-slate-600 dark:text-slate-400">
                    <!-- Items populated via JS -->
                </ul>
            </div>

            <div id="approvalActions" class="hidden mt-6 pt-4 border-t border-slate-200 dark:border-slate-700 flex justify-end space-x-3">
                <button onclick="updateRequisitionStatus('Rejected')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">Reject</button>
                <button onclick="updateRequisitionStatus('Approved')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium text-sm transition-colors">Approve</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentRequisitionId = null;

function openRequisitionDetailsModal(requisition) {
    currentRequisitionId = requisition.RequisitionID;
    
    document.getElementById('reqModalTitle').textContent = `Requisition Details - ${requisition.RequisitionNumber}`;
    document.getElementById('reqHealthCenter').textContent = requisition.HealthCenterName;
    document.getElementById('reqStatus').textContent = requisition.StatusType;
    document.getElementById('reqBy').textContent = requisition.RequestedByFullName;
    document.getElementById('reqDate').textContent = new Date(requisition.RequestedDate).toLocaleDateString();
    
    const list = document.getElementById('reqItemsList');
    list.innerHTML = '';
    
    requisition.RequisitionItems.forEach(item => {
        const li = document.createElement('li');
        li.className = 'flex justify-between p-2';
        
        // We need item names. Ideally passed in or looked up.
        // For simplicity, we'll try to find it in the availableItems global from the form modal if present,
        // otherwise just show ID or we need a cleaner way. 
        // Let's assume we can pass the ItemName from the server or lookup.
        // The PHP loop in requisitions.php should probably attach ItemNames to the object.
        const itemName = item.ItemName || item.ItemID; 
        
        li.innerHTML = `<span>${itemName}</span><span>Qty: ${item.QuantityRequested}</span>`;
        list.appendChild(li);
    });
    
    const userRole = "<?php echo $_SESSION['user']['Role']; ?>"; // Basic check
    const canApprove = ['Administrator', 'Head Pharmacist'].includes(userRole);
    
    const actions = document.getElementById('approvalActions');
    if (requisition.StatusType === 'Pending' && canApprove) {
        actions.classList.remove('hidden');
    } else {
        actions.classList.add('hidden');
    }
    
    document.getElementById('requisitionDetailsModal').classList.remove('hidden');
}

function closeRequisitionDetailsModal() {
    document.getElementById('requisitionDetailsModal').classList.add('hidden');
}

function updateRequisitionStatus(status) {
    if (!confirm(`Are you sure you want to ${status.toLowerCase()} this requisition?`)) return;
    
    const formData = new FormData();
    formData.append('action', 'update_requisition_status');
    formData.append('requisitionId', currentRequisitionId);
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
