<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireRole(['Administrator', 'Health Center Staff']);

$user = getCurrentUser();
$healthCenterId = $user['HealthCenterID'] ?? null;
$hcConn = $healthCenterId ? Database::getHCConnection($healthCenterId) : null;

$localRequisitions = [];

if ($hcConn) {
    try {
        $stmt = $hcConn->query("
            SELECT lr.*, CONCAT(s.FirstName, ' ', s.LastName) as StaffName 
            FROM hc_requisition lr
            LEFT JOIN hc_staff s ON lr.StaffID = s.StaffID
            ORDER BY lr.RequestDate DESC
        ");
        $localRequisitions = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Local Requisitions Fetch Error: " . $e->getMessage());
    }
}
?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Local Requisitions</h1>
            <p class="text-slate-500 dark:text-slate-400">Internal requests within this health center.</p>
        </div>
        <?php if ($hcConn): ?>
            <button onclick="openLocalReqModal()" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2.5 rounded-xl shadow-lg text-sm font-bold transition-all active:scale-95">
                + New Local Request
            </button>
        <?php endif; ?>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Requested By</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php if (empty($localRequisitions)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400 italic">
                                No local requisitions found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($localRequisitions as $req): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                    <?php echo date('M d, Y H:i', strtotime($req['RequestDate'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-white">
                                    <?php echo htmlspecialchars($req['StaffName']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $req['StatusType'] === 'Pending' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'; ?>">
                                        <?php echo $req['StatusType']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button class="text-primary hover:underline text-sm font-medium">Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Local Requisition Modal -->
<div id="localReqModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden scale-95 transition-all duration-300">
        <form id="localReqForm" onsubmit="handleLocalReqSubmit(event)">
            <input type="hidden" name="action" value="create_local_requisition">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">New Local Requisition</h3>
                <button type="button" onclick="closeLocalReqModal()" class="text-slate-400 hover:text-slate-600 focus:outline-none text-2xl">&times;</button>
            </div>
            
            <div class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Requesting Staff Name</label>
                    <input type="text" name="staffName" required placeholder="Enter staff name..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Requested Items</label>
                    <div id="localItemsContainer" class="space-y-3">
                        <!-- Rows added via JS -->
                    </div>
                    <button type="button" onclick="addLocalItemRow()" class="mt-3 text-sm font-bold text-primary hover:text-primary-hover transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Add Another Item
                    </button>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-200 dark:border-slate-700 flex justify-end gap-3">
                <button type="button" onclick="closeLocalReqModal()" class="px-5 py-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancel</button>
                <button type="submit" class="bg-primary hover:bg-opacity-90 text-white px-6 py-2 rounded-xl shadow-lg text-sm font-bold transition-all transform hover:scale-[1.02] active:scale-95">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
const inventoryItems = <?php 
    // Fetch local inventory items for the dropdown
    $itemsForDropdown = [];
    if ($hcConn) {
        $mainDb = DB_NAME;
        $stmt = $hcConn->query("SELECT hci.ItemID, i.ItemName, hci.QuantityOnHand 
                                FROM hc_inventory hci 
                                JOIN $mainDb.item i ON hci.ItemID = i.ItemID 
                                WHERE hci.QuantityOnHand > 0");
        $itemsForDropdown = $stmt->fetchAll();
    }
    echo json_encode($itemsForDropdown); 
?>;

function openLocalReqModal() {
    const modal = document.getElementById('localReqModal');
    modal.classList.remove('hidden');
    setTimeout(() => modal.firstElementChild.classList.remove('scale-95'), 10);
    
    // Add first row if empty
    const container = document.getElementById('localItemsContainer');
    if (container.children.length === 0) addLocalItemRow();
}

function closeLocalReqModal() {
    const modal = document.getElementById('localReqModal');
    modal.firstElementChild.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('localReqForm').reset();
        document.getElementById('localItemsContainer').innerHTML = '';
    }, 200);
}

function addLocalItemRow() {
    const container = document.getElementById('localItemsContainer');
    const index = container.children.length;
    
    const div = document.createElement('div');
    div.className = 'flex gap-3 items-end group';
    div.innerHTML = `
        <div class="flex-1">
            <select name="items[${index}][itemId]" required class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm">
                <option value="">Select Item...</option>
                ${inventoryItems.map(i => `<option value="${i.ItemID}">${i.ItemName} (${i.QuantityOnHand} avail)</option>`).join('')}
            </select>
        </div>
        <div class="w-24">
            <input type="number" name="items[${index}][quantity]" required min="1" placeholder="Qty" class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:ring-2 focus:ring-primary/20 outline-none transition-all text-sm">
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="p-2 text-slate-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        </button>
    `;
    container.appendChild(div);
}

async function handleLocalReqSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('api.php', { method: 'POST', body: formData });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An unexpected error occurred.');
    }
}
</script>
