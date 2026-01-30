<?php
// pages/process_issuance.php

$reqId = $_GET['id'] ?? null;
if (!$reqId) {
    echo "Requisition ID is required.";
    exit;
}

$requisitions = get_data('requisitions');
$inventory = get_data('inventory');
$items = get_data('items');

// Find Requisition
$requisition = null;
foreach ($requisitions as $r) {
    if ($r['RequisitionID'] === $reqId) {
        $requisition = $r;
        break;
    }
}

if (!$requisition) {
    echo "Requisition not found.";
    exit;
}

// Calculate Allocation (FIFO)
$allocationPlan = [];
$hasShortage = false;

foreach ($requisition['RequisitionItems'] as $reqItem) {
    $itemId = $reqItem['ItemID'];
    $qtyNeeded = $reqItem['QuantityRequested'];
    
    // Find matching batches, sorted by ExpiryDate
    $batches = [];
    foreach ($inventory as $batch) {
        if ($batch['ItemID'] === $itemId && $batch['QuantityOnHand'] > 0) {
            $batches[] = $batch;
        }
    }
    
    usort($batches, function($a, $b) {
        $dateA = isset($a['DateReceived']) ? strtotime($a['DateReceived']) : 0;
        $dateB = isset($b['DateReceived']) ? strtotime($b['DateReceived']) : 0;
        return $dateA - $dateB;
    });
    
    $itemAllocation = [
        'reqItemId' => $reqItem['RequisitionItemID'],
        'itemId' => $itemId,
        'needed' => $qtyNeeded,
        'allocated' => [],
        'totalAllocated' => 0
    ];
    
    foreach ($batches as $batch) {
        if ($qtyNeeded <= 0) break;
        
        $take = min($qtyNeeded, $batch['QuantityOnHand']);
        $itemAllocation['allocated'][] = [
            'BatchID' => $batch['BatchID'],
            'Quantity' => $take,
            'ExpiryDate' => $batch['ExpiryDate']
        ];
        
        $qtyNeeded -= $take;
        $itemAllocation['totalAllocated'] += $take;
    }
    
    if ($qtyNeeded > 0) {
        $hasShortage = true;
    }
    
    $allocationPlan[] = $itemAllocation;
}

function getItemName($id, $items) {
    foreach ($items as $i) {
        if ($i['ItemID'] === $id) return $i['ItemName'];
    }
    return $id;
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Consolidated Header: Title & Back Link -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="space-y-1">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Process Issuance</h2>
            <p class="text-slate-500 font-medium text-sm">Reviewing allocation for Requisition <span class="text-teal-600 font-bold"><?php echo $requisition['RequisitionNumber']; ?></span>.</p>
        </div>
        <a href="index.php?page=issuance" class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-teal-600 transition-colors group">
            <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Issuance
        </a>
    </div>
    
    <div class="bg-white dark:bg-slate-800 shadow rounded-lg p-6">
        <div class="mb-6">
             <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Allocation Plan (FIFO)</h3>
             <p class="text-sm text-slate-500 dark:text-slate-400">Review the proposed inventory batches to be issued. Changes are final.</p>
        </div>
        
        <?php if ($hasShortage): ?>
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Shortage Detected</h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                        <p>Some items cannot be fully fulfilled with current stock. Proceeding will mark the requisition as partially fulfilled or allow for adjustment.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form id="processForm" class="space-y-6">
            <input type="hidden" name="action" value="process_issuance">
            <input type="hidden" name="requisitionId" value="<?php echo $reqId; ?>">
            <input type="hidden" name="allocationPlan" value='<?php echo json_encode($allocationPlan); ?>'>
            
            <div class="space-y-4">
                <?php foreach ($allocationPlan as $item): ?>
                <div class="border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-bold text-slate-900 dark:text-white"><?php echo getItemName($item['itemId'], $items); ?></h4>
                        <span class="text-sm font-medium <?php echo $item['totalAllocated'] < $item['needed'] ? 'text-red-600' : 'text-green-600'; ?>">
                            Allocated: <?php echo $item['totalAllocated']; ?> / <?php echo $item['needed']; ?>
                        </span>
                    </div>
                    
                    <?php if (empty($item['allocated'])): ?>
                        <p class="text-sm text-red-500">No stock available!</p>
                    <?php else: ?>
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Batch ID</th>
                                    <th class="px-2 py-1 text-left text-xs font-medium text-slate-500 dark:text-slate-400">Expiry</th>
                                    <th class="px-2 py-1 text-right text-xs font-medium text-slate-500 dark:text-slate-400">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($item['allocated'] as $alloc): ?>
                                <tr>
                                    <td class="px-2 py-1 text-slate-700 dark:text-slate-300"><?php echo $alloc['BatchID']; ?></td>
                                    <td class="px-2 py-1 text-slate-700 dark:text-slate-300"><?php echo $alloc['ExpiryDate']; ?></td>
                                    <td class="px-2 py-1 text-right text-slate-700 dark:text-slate-300"><?php echo $alloc['Quantity']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-primary hover:bg-opacity-90 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition-colors">
                    Confirm & Process
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('processForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if(!confirm('Are you sure you want to process this issuance? Inventory will be deducted.')) return;
    
    const formData = new FormData(this);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Issuance processed successfully!');
            window.location.href = 'index.php?page=issuance';
        } else {
            alert(data.message || 'Error processing issuance');
        }
    })
    .catch(error => console.error('Error:', error));
});
</script>
