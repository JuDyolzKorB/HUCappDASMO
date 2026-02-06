<?php
// Adjustments page
$items = get_data('items') ?? [];
$inventory = get_data('inventory') ?? [];
$requisitions = get_data('requisitions') ?? [];
$adjustment_logs = get_data('adjustment_logs') ?? []; // Placeholder for history

// Filter for completed/issued requisitions for returns
$completedRequisitions = array_filter($requisitions, function($req) {
    return in_array($req['StatusType'], ['Completed', 'Issued']);
});

// Handle pre-selected batch from URL
$preSelectedBatch = $_GET['batch'] ?? '';
$preSelectedItem = '';
if ($preSelectedBatch) {
    foreach ($inventory as $b) {
        if ($b['BatchID'] == $preSelectedBatch) {
            $preSelectedItem = $b['ItemID'];
            break;
        }
    }
}
?>

<div class="space-y-6" x-data="adjustmentFlow()">
    <!-- Consolidated Header: Title & Tab Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h3 class="text-2xl font-bold text-slate-800 dark:text-white" 
                x-text="activeTab === 'perform' ? 'Inventory Disposal' : 'Adjustment History'">
            </h3>
            <p x-show="activeTab === 'perform'" class="text-slate-500 font-medium text-sm">Dispose of items from inventory due to damage, expiration, or other discrepancies.</p>
            <p x-show="activeTab === 'history'" class="text-slate-500 font-medium text-sm">History of all stock disposals and item returns.</p>
        </div>

        <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-900/50 rounded-lg border border-slate-200/50 dark:border-slate-800/50">
            <button @click="activeTab = 'perform'" 
                :class="activeTab === 'perform' ? 'bg-white dark:bg-slate-800 text-teal-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-5 py-1.5 rounded-md text-sm font-semibold transition-all duration-200">
                Perform Adjustment
            </button>
            <button @click="activeTab = 'history'" 
                :class="activeTab === 'history' ? 'bg-white dark:bg-slate-800 text-teal-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-5 py-1.5 rounded-md text-sm font-semibold transition-all duration-200 ml-1">
                Adjustment History
            </button>
        </div>
    </div>

    <!-- Perform Adjustment Tab Content -->
    <div x-show="activeTab === 'perform'" x-cloak class="animate-fade-in space-y-12">
        <!-- Section: Inventory Disposal -->
        <div class="space-y-6">
            
            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden p-10">
                <form @submit.prevent="confirmDisposal" class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Select an item</label>
                            <select x-model="selectedItem" @change="updateBatches" required class="form-select bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all">
                                <option value="">Select an item</option>
                                <template x-for="item in items" :key="item.ItemID">
                                    <option :value="item.ItemID" x-text="item.ItemName"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Select a batch</label>
                            <select x-model="selectedBatch" required :disabled="!selectedItem" class="form-select bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all disabled:opacity-50">
                                <option value="">Select a batch</option>
                                <template x-for="batch in filteredBatches" :key="batch.BatchID">
                                    <option :value="batch.BatchID" x-text="`${batch.BatchID} (Exp: ${batch.ExpiryDate} | Qty: ${batch.QuantityOnHand})`"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Quantity to Dispose</label>
                            <input type="number" x-model="disposeQty" min="1" required class="form-input bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all" placeholder="1">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Reason</label>
                            <select x-model="disposeReason" required class="form-select bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all">
                                <option value="Damaged">Damaged</option>
                                <option value="Expired">Expired</option>
                                <option value="Lost">Lost</option>
                                <option value="Discrepancy">Discrepancy</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Remarks</label>
                        <textarea x-model="disposeRemarks" rows="4" class="form-input bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all resize-none" placeholder="Enter details for the adjustment..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Photo Evidence (Optional)</label>
                        <input type="file" x-ref="photoInput" accept="image/*" class="form-input bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                        <p class="text-xs text-slate-400 mt-2 ml-1">Upload a photo of the damaged/expired item (JPG, PNG, max 5MB)</p>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-10 py-3 rounded-xl font-bold transition-all shadow-lg shadow-teal-900/10 active:scale-95">Confirm Disposal</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-dashed border-slate-200 dark:border-slate-700/50 my-2"></div>

        <!-- Section: Inventory Return -->
        <div class="space-y-6">
            <div class="space-y-1">
                <h4 class="text-lg font-bold text-slate-800 dark:text-white">Inventory Return</h4>
                <p class="text-slate-500 font-medium text-sm">Return items from a previously issued requisition back into inventory. This directly increases stock levels.</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden p-10">
                <form @submit.prevent="processReturn" class="space-y-8">
                    <!-- Processed Requisition Search & Select -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Search by Batch ID</label>
                            <div class="relative">
                                <input type="text" x-model="searchBatchID" @keyup.enter="searchByBatch" class="form-input bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all" placeholder="Enter Batch ID...">
                                <button type="button" @click="searchByBatch" class="absolute right-2 top-2 p-1.5 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Processed Requisition</label>
                            <select x-model="returnReqID" required class="form-select bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all">
                                <option value="">Select a requisition</option>
                                <template x-for="req in completedRequisitions" :key="req.RequisitionID">
                                    <option :value="req.RequisitionID" x-text="`${req.RequisitionNumber} - ${req.HealthCenterName} (${req.RequestedDate && req.RequestedDate !== '0000-00-00 00:00:00' ? new Date(req.RequestedDate).toLocaleDateString() : 'N/A'})`"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Processed Requisition Items (Dynamic) -->
                    <div x-show="returnReqID" class="p-6 bg-slate-50 dark:bg-slate-900/30 rounded-2xl border border-slate-200 dark:border-slate-700/50">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Items issued in this requisition</label>
                        <div class="space-y-3">
                            <template x-for="item in returnItems" :key="item.IssuanceItemID">
                                <div x-show="!searchBatchID || item.BatchID == searchBatchID" class="flex items-center gap-4 bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                                    <input type="checkbox" x-model="item.selected" class="w-4 h-4 text-teal-600 rounded border-slate-300 focus:ring-teal-500">
                                    <div class="flex-1">
                                        <p class="text-sm font-bold text-slate-700 dark:text-white" x-text="item.ItemName"></p>
                                        <p class="text-[10px] text-slate-400" x-text="'Batch: ' + item.BatchID"></p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Qty</span>
                                        <input type="number" x-model="item.returnQty" :max="item.QuantityIssued" min="1" 
                                               class="w-20 bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-xs py-1 px-2 rounded-lg focus:ring-teal-500 focus:border-teal-500">
                                        <span class="text-[10px] text-slate-400" x-text="'/ ' + item.QuantityIssued"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3 ml-1">Reason for Return</label>
                        <textarea x-model="returnReason" rows="4" required class="form-input bg-slate-50 border-slate-200 text-sm py-3 px-4 rounded-xl focus:ring-teal-500 focus:border-teal-500 transition-all resize-none" placeholder="Enter reason for return..."></textarea>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" 
                                :disabled="!returnReqID || !returnItems.some(i => i.selected)"
                                class="bg-teal-600 hover:bg-teal-700 text-white px-10 py-3 rounded-xl font-bold transition-all shadow-lg shadow-teal-900/10 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            Process Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div x-show="activeTab === 'history'" x-cloak class="animate-fade-in">
        <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/60 dark:border-slate-700/60 shadow-sm overflow-hidden p-8">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/80 dark:bg-slate-900/50 rounded-xl">
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest rounded-l-xl">Type</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Item/Ref</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Quantity</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Reason</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Date</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest rounded-r-xl">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/50 dark:divide-slate-700/50">
                        <?php if (empty($adjustment_logs)): ?>
                            <tr><td colspan="6" class="py-12 text-center text-slate-400 font-medium text-sm italic">No adjustment history recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($adjustment_logs as $log): ?>
                            <tr class="hover:bg-slate-50/10 dark:hover:bg-slate-700/10 transition-colors">
                                <td class="px-6 py-5">
                                    <span class="px-3 py-1 text-[10px] font-bold uppercase rounded-full <?php echo $log['Type'] === 'Return' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600'; ?>">
                                        <?php echo $log['Type']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-sm font-bold text-slate-700 dark:text-white"><?php echo $log['Reference']; ?></td>
                                <td class="px-6 py-5 text-sm font-medium text-slate-500 dark:text-slate-400"><?php echo $log['Quantity']; ?></td>
                                <td class="px-6 py-5 text-sm text-slate-400 dark:text-slate-500"><?php echo $log['Reason']; ?></td>
                                <td class="px-6 py-5 text-sm font-medium text-slate-400"><?php echo date('n/j/Y', strtotime($log['Date'])); ?></td>
                                <td class="px-6 py-5">
                                    <button @click="viewAdjustment(<?php echo htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8'); ?>)"
                                            class="text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 font-bold text-xs uppercase tracking-widest border border-teal-200 dark:border-teal-800 px-3 py-1.5 rounded-lg transition-all hover:bg-teal-50 dark:hover:bg-teal-900/20">
                                        View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php include 'components/adjustment_details_modal.php'; ?>
</div>

<script>
function adjustmentFlow() {
    return {
        activeTab: 'perform',
        items: <?php echo json_encode($items); ?>,
        inventory: <?php echo json_encode($inventory); ?>,
        completedRequisitions: <?php echo json_encode(array_values($completedRequisitions)); ?>,
        
        selectedItem: '',
        selectedBatch: '',
        filteredBatches: [],
        disposeQty: 1,
        disposeReason: 'Damaged',
        disposeRemarks: '',
        
        returnReqID: '',
        searchBatchID: '',
        returnReason: '',
        returnItems: [],

        init() {
            this.$watch('returnReqID', (val) => {
                if (!val) {
                    this.returnItems = [];
                    return;
                }
                const req = this.completedRequisitions.find(r => r.RequisitionID == val);
                if (req && req.IssuedItems) {
                    this.returnItems = req.IssuedItems.map(i => ({
                        ...i,
                        selected: this.searchBatchID ? (i.BatchID == this.searchBatchID) : true,
                        returnQty: i.QuantityIssued || i.QuantityRequested // Fallback
                    }));
                } else {
                    this.returnItems = [];
                }
            });

            // Handle pre-selected data from URL
            const urlBatch = '<?php echo $preSelectedBatch; ?>';
            const urlItem = '<?php echo $preSelectedItem; ?>';
            
            if (urlItem) {
                this.selectedItem = urlItem;
                this.updateBatches();
                if (urlBatch) {
                    this.selectedBatch = urlBatch;
                }
            }

            // Watch for searchBatchID changes to auto-filter if requisition is already selected
            this.$watch('searchBatchID', (val) => {
                if (val && this.returnItems.length > 0) {
                    this.returnItems.forEach(i => {
                        i.selected = (i.BatchID == val);
                    });
                }
            });
        },

        searchByBatch() {
            if (!this.searchBatchID) return;
            
            // Look through completed requisitions and their issued items
            const foundReq = this.completedRequisitions.find(req => {
                if (!req.IssuedItems) return false;
                return req.IssuedItems.some(item => item.BatchID == this.searchBatchID);
            });

            if (foundReq) {
                this.returnReqID = foundReq.RequisitionID;
            } else {
                alert('No issued requisition found for this Batch ID.');
            }
        },
        
        // Modal state
        showAdjustmentModal: false,
        selectedAdjustment: null,
        editMode: false,
        editData: {
            quantity: 0,
            reason: ''
        },
        
        updateBatches() {
            this.selectedBatch = '';
            // Use == for type-insensitive comparison or cast both to string
            this.filteredBatches = this.inventory.filter(b => b.ItemID == this.selectedItem);
        },
        
        viewAdjustment(adjustment) {
            this.selectedAdjustment = adjustment;
            this.editMode = false;
            this.showAdjustmentModal = true;
        },
        
        enterEditMode() {
            this.editMode = true;
            this.editData.quantity = this.selectedAdjustment.Quantity;
            this.editData.reason = this.selectedAdjustment.Reason;
        },
        
        cancelEdit() {
            this.editMode = false;
            this.editData = { quantity: 0, reason: '' };
        },
        
        async saveEdit() {
            if (!this.selectedAdjustment) return;
            
            const formData = new FormData();
            formData.append('action', 'update_adjustment');
            formData.append('adjustmentId', this.selectedAdjustment.ID);
            formData.append('adjustmentType', this.selectedAdjustment.Type);
            formData.append('quantity', this.editData.quantity);
            formData.append('reason', this.editData.reason);
            
            // Add photo if selected (for disposals)
            if (this.selectedAdjustment.Type === 'Disposal' && this.$refs.editPhotoInput?.files[0]) {
                formData.append('photo', this.$refs.editPhotoInput.files[0]);
            }
            
            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('Adjustment updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                alert('An error occurred.');
                console.error(e);
            }
        },
        
        async confirmDisposal() {
            if(!this.selectedBatch) return;
            
            const formData = new FormData();
            formData.append('action', 'dispose_stock');
            formData.append('batchId', this.selectedBatch);
            formData.append('quantity', this.disposeQty);
            formData.append('reason', this.disposeReason);
            formData.append('remarks', this.disposeRemarks);
            
            // Add photo if selected
            const photoFile = this.$refs.photoInput.files[0];
            if (photoFile) {
                formData.append('photo', photoFile);
            }
            
            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('Stock disposal confirmed!');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                alert('An error occurred.');
                console.error(e);
            }
        },
        
        async processReturn() {
            if(!this.returnReqID) return;
            
            const itemsToReturn = this.returnItems.filter(i => i.selected);
            if (itemsToReturn.length === 0) {
                alert('Please select at least one item to return.');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'return_stock');
            formData.append('requisitionId', this.returnReqID);
            formData.append('reason', this.returnReason);
            formData.append('items', JSON.stringify(itemsToReturn.map(i => ({
                batchId: i.BatchID,
                quantity: i.returnQty
            }))));
            
            try {
                const response = await fetch('api.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('Item return processed! Inventory updated.');
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (e) {
                alert('An error occurred.');
                console.error(e);
            }
        }
    }
}
</script>
