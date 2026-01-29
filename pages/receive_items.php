<?php
// pages/receive_items.php

$poid = $_GET['id'] ?? null;
if (!$poid) {
    echo "Purchase Order ID is required.";
    exit;
}

$purchaseOrders = get_data('purchase_orders');
$itemsData = get_data('items');

// Find PO
$purchaseOrder = null;
foreach ($purchaseOrders as $po) {
    if ($po['POID'] === $poid) {
        $purchaseOrder = $po;
        break;
    }
}

if (!$purchaseOrder) {
    echo "Purchase Order not found.";
    exit;
}

function getItemNameById($id, $itemsData) {
    foreach ($itemsData as $i) {
        if ($i['ItemID'] === $id) return $i['ItemName'];
    }
    return $id;
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Consolidated Header: Title & Back Link -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Receive Items</h2>
            <p class="text-slate-500 font-medium text-sm">Inspecting shipment for Purchase Order <span class="text-teal-600 font-bold"><?php echo $purchaseOrder['PONumber']; ?></span>.</p>
        </div>
        <a href="index.php?page=receiving" class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-teal-600 transition-colors group">
            <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Receiving
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-6">
        <form id="receiveForm" class="space-y-6">
            <input type="hidden" name="action" value="receive_items">
            <input type="hidden" name="poid" value="<?php echo $purchaseOrder['POID']; ?>">
            <input type="hidden" name="poNumber" value="<?php echo $purchaseOrder['PONumber']; ?>">
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Qty Ordered</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Qty Received</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Unit Cost</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Expiry Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        <?php foreach ($purchaseOrder['PurchaseOrderItems'] as $index => $item): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">
                                <?php echo getItemNameById($item['ItemID'], $itemsData); ?>
                                <input type="hidden" name="items[<?php echo $index; ?>][itemId]" value="<?php echo $item['ItemID']; ?>">
                                <input type="hidden" name="items[<?php echo $index; ?>][poItemId]" value="<?php echo $item['POItemID']; ?>">
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-slate-500 dark:text-slate-400">
                                <?php echo $item['QuantityOrdered']; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <input type="number" name="items[<?php echo $index; ?>][quantityReceived]" value="<?php echo $item['QuantityOrdered']; ?>" max="<?php echo $item['QuantityOrdered']; ?>" min="0" class="shadow-sm focus:ring-primary focus:border-primary block w-24 sm:text-sm border-slate-300 rounded-md dark:bg-slate-700 dark:border-slate-600 dark:text-white text-center">
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="relative rounded-md shadow-sm max-w-[100px] mx-auto">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" step="0.01" name="items[<?php echo $index; ?>][unitCost]" placeholder="0.00" required class="block w-full rounded-md border-slate-300 pl-7 focus:ring-primary focus:border-primary sm:text-sm dark:bg-slate-700 dark:border-slate-600 dark:text-white text-right">
                                </div>
                            </td>
                             <td class="px-4 py-3">
                                <input type="date" name="items[<?php echo $index; ?>][expiryDate]" required class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-slate-300 rounded-md dark:bg-slate-700 dark:border-slate-600 dark:text-white">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="submit" class="bg-primary hover:bg-opacity-90 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition-colors">
                    Confirm Receipt
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('receiveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if(!confirm('Are you sure you want to process this receipt? Inventory will be updated.')) return;
    
    const formData = new FormData(this);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Items received successfully!');
            window.location.href = 'index.php?page=receiving';
        } else {
            alert(data.message || 'Error processing receipt');
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>
