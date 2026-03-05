<?php
$suppliers = get_data('suppliers');
$healthCenters = get_data('health_centers');
$items = get_data('items');
$contracts = get_data('contracts');
?>

<div id="addPOModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden z-[9999] flex justify-center items-center p-4" onclick="if(event.target === this) closeAddPOModal()">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-2xl transform transition-all relative border border-slate-200/60 dark:border-slate-700/60" onclick="event.stopPropagation()">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700/50 flex justify-between items-center">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">New Procurement Order</h3>
            <button onclick="closeAddPOModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <form id="addPOForm" class="p-6 space-y-5 overflow-y-auto max-h-[80vh]" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_procurement_order">
            
            <!-- Supplier & Health Center Grid -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Supplier -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poSupplier">
                            Supplier
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="manualSupplierToggle" class="w-4 h-4 text-teal-600 rounded border-gray-300 focus:ring-teal-500" onchange="toggleSupplierInput(this)">
                            <label for="manualSupplierToggle" class="text-xs text-slate-500 dark:text-slate-400 cursor-pointer">Enter Manually</label>
                        </div>
                    </div>
                    
                    <!-- Select Dropdown -->
                    <div class="relative" id="supplierSelectContainer">
                        <select 
                            class="w-full px-4 py-2.5 pr-10 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer" 
                            id="poSupplier" 
                            name="supplierId" 
                            required>
                            <option value="">Select supplier...</option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['SupplierID']; ?>"><?php echo $supplier['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Manual Input Fields -->
                    <div id="supplierManualContainer" class="hidden space-y-2">
                        <input 
                            type="text" 
                            name="supplierName" 
                            id="poSupplierName"
                            placeholder="Supplier Name" 
                            class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all">
                        <textarea 
                            name="supplierAddress" 
                            id="poSupplierAddress"
                            placeholder="Supplier Address" 
                            rows="2"
                            class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all"></textarea>
                    </div>
                </div>
                
                <!-- Health Center -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poHealthCenter">
                        Health Center
                    </label>
                    <div class="relative">
                        <select 
                            class="w-full px-4 py-2.5 pr-10 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer" 
                            id="poHealthCenter" 
                            name="healthCenterId" 
                            required>
                            <option value="">Select health center...</option>
                            <?php foreach ($healthCenters as $hc): ?>
                                <option value="<?php echo $hc['HealthCenterID']; ?>"><?php echo $hc['Name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Reference Type & File Upload -->
            <div class="space-y-3">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Document Reference Type</label>
                <div class="flex gap-2 flex-wrap" id="refTypeButtons">
                    <label class="ref-type-btn flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-slate-200 dark:border-slate-700 cursor-pointer transition-all hover:border-teal-400 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50 dark:has-[:checked]:bg-teal-900/20">
                        <input type="radio" name="refFileType" value="Purchase Order" class="sr-only" onchange="updateRefTypeLabel()">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Purchase Order</span>
                    </label>
                    <label class="ref-type-btn flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-slate-200 dark:border-slate-700 cursor-pointer transition-all hover:border-teal-400 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50 dark:has-[:checked]:bg-teal-900/20">
                        <input type="radio" name="refFileType" value="Contract" class="sr-only" onchange="updateRefTypeLabel()">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Contract</span>
                    </label>
                    <label class="ref-type-btn flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-slate-200 dark:border-slate-700 cursor-pointer transition-all hover:border-teal-400 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50 dark:has-[:checked]:bg-teal-900/20">
                        <input type="radio" name="refFileType" value="Both" class="sr-only" onchange="updateRefTypeLabel()">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Both</span>
                    </label>
                </div>

                <!-- File Upload Zone -->
                <div id="refFileUploadZone"
                     class="relative border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-4 text-center cursor-pointer hover:border-teal-400 dark:hover:border-teal-500 transition-all"
                     onclick="document.getElementById('refDocument').click()"
                     ondragover="event.preventDefault(); this.classList.add('border-teal-400','bg-teal-50/30')"
                     ondragleave="this.classList.remove('border-teal-400','bg-teal-50/30')"
                     ondrop="handleRefDrop(event)">
                    <input type="file" id="refDocument" name="refDocument" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp" onchange="handleRefFileChange(this)">
                    <div id="refFileDisplay" class="space-y-1">
                        <svg class="mx-auto w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Click or drag &amp; drop to upload</p>
                        <p class="text-xs text-slate-400">PDF, JPG, PNG, WEBP — max 10MB</p>
                    </div>
                </div>
            </div>

            <!-- Contract Number -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poContractNumber">Contract Number</label>
                <div class="relative">
                    <input
                        type="text"
                        class="w-full px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all"
                        id="poContractNumber"
                        name="contractNumber"
                        placeholder="Enter contract number...">
                </div>
            </div>

            <!-- Contract Details Grid (Newly added) -->
            <div class="grid grid-cols-3 gap-4 pt-1 transition-all">
                 <!-- Start Date -->
                 <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poContractStartDate">
                        Start Date
                    </label>
                    <input 
                        type="date" 
                        class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all" 
                        id="poContractStartDate" 
                        name="contractStartDate">
                </div>
                <!-- End Date -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poContractEndDate">
                        End Date
                    </label>
                    <input 
                        type="date" 
                        class="w-full px-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all" 
                        id="poContractEndDate" 
                        name="contractEndDate">
                </div>
                <!-- Amount -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300" for="poContractAmount">
                        Contract Amount
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">₱</span>
                        <input 
                            type="number" 
                            step="0.01"
                            class="w-full pl-7 pr-3 py-2 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all" 
                            id="poContractAmount" 
                            name="contractAmount"
                            placeholder="0.00">
                    </div>
                </div>
            </div>

            <!-- Items Section -->
            <div class="space-y-3">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Items
                </label>
                
                <div id="poItemsContainer" class="space-y-2">
                    <!-- Initial item row -->
                    <div class="flex gap-2 items-start po-item-row">
                        <div class="relative flex-1">
                            <select 
                                class="w-full px-4 py-2.5 pr-10 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all appearance-none cursor-pointer" 
                                name="items[]" 
                                onchange="toggleExpiry(this)"
                                required>
                                <option value="">Select item...</option>
                                <?php 
                                $groups = [];
                                foreach ($items as $item) {
                                    $groups[$item['ItemType'] ?: 'Others'][] = $item;
                                }
                                foreach ($groups as $type => $typeItems): ?>
                                    <optgroup label="<?php echo $type; ?>">
                                        <?php foreach ($typeItems as $item): ?>
                                            <option value="<?php echo $item['ItemID']; ?>"><?php echo $item['ItemName']; ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        <input 
                            type="number" 
                            name="quantities[]" 
                            placeholder="Qty" 
                            min="1" 
                            class="w-24 px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all" 
                            required>
                        <input 
                            type="date" 
                            name="expiryDates[]" 
                            placeholder="Expiry Date" 
                            class="w-36 px-4 py-2.5 text-sm border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/50 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-teal-500 transition-all">
                        <button 
                            type="button" 
                            onclick="removeItemRow(this)" 
                            class="p-2.5 text-red-500 hover:text-red-700 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Add Item Button -->
                <button 
                    type="button" 
                    onclick="addItemRow()" 
                    class="text-sm font-semibold text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Item
                </button>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                <button 
                    type="button" 
                    onclick="closeAddPOModal()" 
                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-700/50 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-all">
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="px-5 py-2.5 text-sm font-bold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-lg shadow-teal-900/20 transition-all active:scale-95">
                    Create Procurement Order
                </button>
            </div>
        </form>
    </div>
</div>
</template>

<script>
function toggleSupplierInput(checkbox) {
    const selectContainer = document.getElementById('supplierSelectContainer');
    const manualContainer = document.getElementById('supplierManualContainer');
    const select = document.getElementById('poSupplier');
    const nameInput = document.getElementById('poSupplierName');
    
    if (checkbox.checked) {
        selectContainer.classList.add('hidden');
        manualContainer.classList.remove('hidden');
        select.removeAttribute('required');
        select.value = '';
        nameInput.setAttribute('required', 'required');
    } else {
        selectContainer.classList.remove('hidden');
        manualContainer.classList.add('hidden');
        select.setAttribute('required', 'required');
        nameInput.removeAttribute('required');
        nameInput.value = '';
        document.getElementById('poSupplierAddress').value = '';
    }
}

document.getElementById('poSupplier').addEventListener('change', function() {
    // No longer filtering contracts as it's a text input
});

// Initial item row
// Initial item row
function closeAddPOModal() {
    // Dispatch event for Alpine to catch
    window.dispatchEvent(new CustomEvent('close-add-po-modal'));
    
    document.getElementById('addPOForm').reset();
    
    // Reset manual toggle
    const toggle = document.getElementById('manualSupplierToggle');
    if (toggle && toggle.checked) {
        toggle.click(); // Uncheck and trigger handler
    }
    
    // Reset to single item row
    const container = document.getElementById('poItemsContainer');
    const rows = container.querySelectorAll('.po-item-row');
    for (let i = 1; i < rows.length; i++) {
        rows[i].remove();
    }
    
    // Reset file upload UI
    const refDisplay = document.getElementById('refFileDisplay');
    if (refDisplay) {
        refDisplay.innerHTML = `
            <svg class="mx-auto w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Click or drag &amp; drop to upload</p>
            <p class="text-xs text-slate-400">PDF, JPG, PNG, WEBP — max 10MB</p>`;
    }
    // Reset radio buttons
    document.querySelectorAll('input[name="refFileType"]').forEach(r => r.checked = false);
}

const itemsData = <?php echo json_encode($items); ?>;

function toggleExpiry(selectEl) {
    if (!selectEl) return;
    const row = selectEl.closest('.po-item-row');
    const expiryInput = row.querySelector('input[name="expiryDates[]"]');
    const itemId = selectEl.value;
    
    const item = itemsData.find(i => i.ItemID == itemId);
    if (item && item.UnitOfMeasure && item.UnitOfMeasure.toUpperCase() === 'UNIT') {
        expiryInput.setAttribute('disabled', 'disabled');
        expiryInput.classList.add('opacity-50', 'bg-slate-100');
        expiryInput.value = '';
    } else {
        expiryInput.removeAttribute('disabled');
        expiryInput.classList.remove('opacity-50', 'bg-slate-100');
    }
}

function addItemRow() {
    const container = document.getElementById('poItemsContainer');
    const firstRow = container.querySelector('.po-item-row');
    const newRow = firstRow.cloneNode(true);
    
    // Reset values and state
    newRow.querySelectorAll('select').forEach(el => el.value = '');
    newRow.querySelectorAll('input').forEach(el => {
        el.value = '';
        el.removeAttribute('disabled');
        el.classList.remove('opacity-50', 'bg-slate-100');
    });
    
    container.appendChild(newRow);
}

function removeItemRow(button) {
    const container = document.getElementById('poItemsContainer');
    const rows = container.querySelectorAll('.po-item-row');
    
    // Don't remove if it's the only row
    if (rows.length > 1) {
        button.closest('.po-item-row').remove();
    } else {
        alert('At least one item is required');
    }
}

function updateRefTypeLabel() {
    // Visual feedback is handled by has-[:checked] CSS
}

function renderRefFilePreview(file) {
    const display = document.getElementById('refFileDisplay');
    const isPdf = file.name.toLowerCase().endsWith('.pdf');
    display.innerHTML = `
        <div class="flex items-center gap-3 text-left p-1">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center ${isPdf ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'}">
                ${isPdf
                    ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17v-1h8v1H8zm0-3v-1h8v1H8zm0-3V10h5v1H8z"/></svg>'
                    : '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'
                }
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">${file.name}</p>
                <p class="text-xs text-slate-400">${(file.size / 1024).toFixed(1)} KB</p>
            </div>
            <button type="button" onclick="clearRefFile(event)" class="text-red-400 hover:text-red-600 flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`;
}

function handleRefFileChange(input) {
    if (input.files && input.files[0]) {
        renderRefFilePreview(input.files[0]);
    }
}

function handleRefDrop(event) {
    event.preventDefault();
    const zone = document.getElementById('refFileUploadZone');
    zone.classList.remove('border-teal-400', 'bg-teal-50/30');
    const file = event.dataTransfer.files[0];
    if (!file) return;
    const allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    const ext = file.name.split('.').pop().toLowerCase();
    if (!allowedExts.includes(ext)) {
        alert('Only PDF, JPG, PNG, and WEBP files are allowed.');
        return;
    }
    // Assign to the hidden file input via DataTransfer
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('refDocument').files = dt.files;
    renderRefFilePreview(file);
}

function clearRefFile(event) {
    event.stopPropagation();
    document.getElementById('refDocument').value = '';
    document.getElementById('refFileDisplay').innerHTML = `
        <svg class="mx-auto w-8 h-8 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Click or drag &amp; drop to upload</p>
        <p class="text-xs text-slate-400">PDF, JPG, PNG, WEBP — max 10MB</p>`;
}

document.getElementById('addPOForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Procurement Order created successfully!');
            window.location.reload();
        } else {
            alert(data.message || 'Error creating procurement order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the procurement order');
    });
});
</script>
