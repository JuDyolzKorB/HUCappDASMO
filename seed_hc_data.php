<?php
require_once 'includes/db.php';

$db = Database::getInstance();
echo "Starting HC Inventory Seeding...\n";

// 1. Get the User's Health Center (assuming user 'jc' or 'jessejot' or similar, but let's just get Central Health Unit)
$hcName = "Central Health Unit";
$hc = $db->fetchOne("SELECT HealthCenterID, Name, DatabaseName FROM HealthCenters WHERE Name LIKE ?", ["%$hcName%"]);

if (!$hc) {
    die("Error: Could not find '$hcName'.\n");
}

echo "Found Health Center: {$hc['Name']} (ID: {$hc['HealthCenterID']}, DB: {$hc['DatabaseName']})\n";

if (empty($hc['DatabaseName'])) {
    // Generate DB name if missing (logic from hc_db_manager)
    $dbName = 'hc_central_health_unit'; // simplified
    $db->execute("UPDATE HealthCenters SET DatabaseName = ? WHERE HealthCenterID = ?", [$dbName, $hc['HealthCenterID']]);
    $hc['DatabaseName'] = $dbName;
    echo "Assigned DB Name: $dbName\n";
}

// 2. Ensure Central Inventory has an Item and Batch
$item = $db->fetchOne("SELECT ItemID FROM Item LIMIT 1");
if (!$item) {
    echo "No items found. Creating dummy item...\n";
    $db->execute("INSERT INTO Item (ItemName, Description, Unit, ItemType, StockLevel) VALUES ('Paracetamol 500mg', 'Pain reliever', 'Tablet', 'Medicine', 1000)");
    $itemId = $db->lastInsertId();
} else {
    $itemId = $item['ItemID'];
}

$batch = $db->fetchOne("SELECT BatchID FROM CentralInventoryBatch WHERE ItemID = ?", [$itemId]);
if (!$batch) {
    echo "No batch found for Item $itemId. Creating dummy batch...\n";
    $batchNumber = 'BATCH-' . date('Ymd');
    $db->execute("INSERT INTO CentralInventoryBatch (ItemID, BatchNumber, Quantity, ExpiryDate, ArchiveStatus) VALUES (?, ?, 1000, '2027-12-31', 'Active')", [$itemId, $batchNumber]);
    $batchId = $db->lastInsertId();
} else {
    $batchId = $batch['BatchID'];
}

echo "Using ItemID: $itemId, BatchID: $batchId\n";

// 3. Connect to HC Database and Insert Inventory
try {
    // Force connection to HC DB
    $hcConn = Database::getHCConnection($hc['HealthCenterID']);
    if (!$hcConn) {
        die("Error: Could not connect to HC database.\n");
    }

    // Check if item already in HC inventory
    $stmt = $hcConn->prepare("SELECT InventoryID FROM HC_Inventory WHERE BatchID = ?");
    $stmt->execute([$batchId]);
    
    if ($stmt->fetch()) {
        echo "Item already exists in HC Inventory.\n";
    } else {
        echo "Inserting item into HC Inventory...\n";
        $stmt = $hcConn->prepare("INSERT INTO HC_Inventory (ItemID, BatchID, QuantityOnHand, ExpiryDate) VALUES (?, ?, 500, '2027-12-31')");
        $stmt->execute([$itemId, $batchId]);
        echo "Success! Added 500 units.\n";
    }
    
    // Check total items
    $count = $hcConn->query("SELECT COUNT(*) FROM HC_Inventory")->fetchColumn();
    echo "Total items in HC Inventory: $count\n";

} catch (Exception $e) {
    die("Error connecting/inserting to HC DB: " . $e->getMessage() . "\n");
}
