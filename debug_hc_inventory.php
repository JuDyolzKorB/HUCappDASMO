<?php
require_once 'includes/db.php';
require_once 'includes/config.php';

// Simulate the environment
$db = Database::getInstance();
$hc = $db->fetchOne("SELECT * FROM HealthCenters WHERE HealthCenterID = 1");

if (!$hc) {
    die("HC ID 1 not found\n");
}

echo "HC DB Name: " . $hc['DatabaseName'] . "\n";
echo "Main DB Name: " . DB_NAME . "\n";

try {
    $hcConn = Database::getHCConnection(1);
    if (!$hcConn) {
        die("Could not connect to HC DB\n");
    }
    echo "Connected to HC DB.\n";

    // Check HC_Inventory
    $count = $hcConn->query("SELECT COUNT(*) FROM HC_Inventory")->fetchColumn();
    echo "HC_Inventory Count: $count\n";

    // Check Item in Main DB (via HC connection? No, via Main connection)
    // But the query does a cross-DB join.
    // Let's test if HC user can access Main DB
    try {
        $mainDb = DB_NAME;
        $itemCount = $hcConn->query("SELECT COUNT(*) FROM $mainDb.Item")->fetchColumn();
        echo "Main DB Item Count (accessed via HC Conn): $itemCount\n";
    } catch (Exception $e) {
        echo "Error accessing Main DB from HC Conn: " . $e->getMessage() . "\n";
    }

    // Run the actual query
    $sql = "
        SELECT hci.ItemID, i.ItemName, SUM(hci.QuantityOnHand) as TotalAvail
        FROM HC_Inventory hci 
        JOIN $mainDb.Item i ON hci.ItemID = i.ItemID 
        WHERE hci.QuantityOnHand > 0
        GROUP BY hci.ItemID, i.ItemName
    ";
    echo "Running Query:\n$sql\n";
    
    $stmt = $hcConn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Results Count: " . count($results) . "\n";
    print_r($results);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
