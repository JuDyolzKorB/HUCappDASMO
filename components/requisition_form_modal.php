<?php
// components/requisition_form_modal.php
$healthCenters = get_data('health_centers');
$items = get_data('items');
?>

<div id="requisitionFormModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-3xl transform transition-all my-8 relative">
        <form id="requisitionForm" onsubmit="handleRequisitionSubmit(event)">
            <input type="hidden" name="action" value="create_requisition">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 border-b border-slate-200 dark:border-slate-700 pb-4">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white">New Requisition</h3>
                    <button type="button" onclick="closeRequisitionFormModal()" class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 text-2xl font-bold">&times;</button>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <label for="healthCenterId" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Health Center</label>
                        <select id="healthCenterId" name="healthCenterId" required class="block w-full pl-3 pr-10 py-2 text-base border-slate-300 dark:border-slate-600 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md dark:bg-slate-700 dark:text-white">
                            <?php foreach ($healthCenters as $hc): ?>
                                <option value="<?php echo $hc['HealthCenterID']; ?>"><?php echo $hc['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Items</label>
                        <div id="requisitionItemsContainer" class="space-y-3">
                            <!-- Items will be added here -->
                        </div>
                        <button type="button" onclick="addRequisitionItem()" class="mt-3 text-sm font-semibold text-primary hover:text-primary-hover">+ Add Item</button>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-8 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" onclick="closeRequisitionFormModal()" class="px-4 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 dark:bg-slate-700 dark:text-slate-200 dark:border-slate-600 dark:hover:bg-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">Submit Requisition</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const availableItems = <?php echo json_encode($items); ?>;

function openRequisitionFormModal() {
    document.getElementById('requisitionFormModal').classList.remove('hidden');
    // Identify if the container is empty, if so, add one row
    const container = document.getElementById('requisitionItemsContainer');
    if (container.children.length === 0) {
        addRequisitionItem();
    }
}

function closeRequisitionFormModal() {
    document.getElementById('requisitionFormModal').classList.add('hidden');
}

function addRequisitionItem() {
    const container = document.getElementById('requisitionItemsContainer');
    const index = container.children.length;
    
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2';
    div.innerHTML = `
        <select name="items[${index}][itemId]" class="block w-full pl-3 pr-10 py-2 text-base border-slate-300 dark:border-slate-600 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm rounded-md dark:bg-slate-700 dark:text-white">
            ${availableItems.map(i => `<option value="${i.ItemID}">${i.ItemName}</option>`).join('')}
        </select>
        <input type="number" name="items[${index}][quantity]" placeholder="Qty" min="1" required class="block w-32 pl-3 pr-3 py-2 border-slate-300 dark:border-slate-600 rounded-md shadow-sm focus:ring-primary focus:border-primary sm:text-sm dark:bg-slate-700 dark:text-white">
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700 text-xl font-bold px-2">&times;</button>
    `;
    container.appendChild(div);
}

async function handleRequisitionSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Requisition created successfully!');
            window.location.reload();
        } else {
            alert(result.message || 'Error creating requisition');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred');
    }
}
</script>
