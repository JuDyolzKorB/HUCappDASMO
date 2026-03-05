<?php
require_once 'includes/db.php';

$db = Database::getInstance();
$hcId = 2; // North District Clinic
$hcConn = Database::getHCConnection($hcId);

if (!$hcConn) {
    echo "ERROR: Could not connect to hc_north. Check HealthCenters table.\n";
    $hcInfo = $db->fetchOne("SELECT * FROM HealthCenters WHERE HealthCenterID = 2");
    print_r($hcInfo);
    exit;
}

echo "Starting Sync for HC ID $hcId (hc_north)...\n";

$sql = "SELECT ii.BatchID, ii.QuantityIssued, b.ItemID, b.ExpiryDate
        FROM IssuanceItem ii
        JOIN Issuance i ON ii.IssuanceID = i.IssuanceID
        JOIN Requisition r ON i.RequisitionID = r.RequisitionID
        JOIN CentralInventoryBatch b ON ii.BatchID = b.BatchID
        WHERE r.HealthCenterID = ? AND i.StatusType = 'Issued'";

$issuances = $db->fetchAll($sql, [$hcId]);
echo "Found " . count($issuances) . " items to sync.\n";

$synced = 0;
foreach ($issuances as $iss) {
    $stmt = $hcConn->prepare("SELECT InventoryID FROM HC_Inventory WHERE BatchID = ?");
    $stmt->execute([$iss['BatchID']]);
    if (!$stmt->fetch()) {
        $stmt = $hcConn->prepare("INSERT INTO HC_Inventory (ItemID, BatchID, QuantityOnHand, ExpiryDate) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$iss['ItemID'], $iss['BatchID'], $iss['QuantityIssued'], $iss['ExpiryDate']])) {
            $synced++;
        }
    }
}

echo "Successfully synced $synced new items into hc_north.\n";

// Ensure User is mapped
$db->execute("UPDATE Users SET HealthCenterID = 2 WHERE FName LIKE '%Chinno%' OR LName LIKE '%Gico%'");
echo "User mapping updated/verified.\n";
