<?php
require_once 'includes/db.php';
session_start();

$db = Database::getInstance();

echo "--- SESSION INFO ---\n";
echo "User ID: " . ($_SESSION['user']['UserID'] ?? 'N/A') . "\n";
echo "Role: " . ($_SESSION['user']['Role'] ?? 'N/A') . "\n";
echo "HC ID: " . ($_SESSION['user']['HealthCenterID'] ?? 'N/A') . "\n\n";

echo "--- REQUISITION DATA (LATEST 10) ---\n";
$reqs = $db->fetchAll("SELECT RequisitionID, HealthCenterID, StatusType FROM Requisition ORDER BY RequisitionID DESC LIMIT 10");
foreach ($reqs as $r) {
    echo "ID: {$r['RequisitionID']} | HC_ID: " . ($r['HealthCenterID'] ?? 'NULL') . " | Status: {$r['StatusType']}\n";
}
echo "\n";

echo "--- HEALTH CENTERS ---\n";
$hcs = $db->fetchAll("SELECT HealthCenterID, Name, DatabaseName FROM HealthCenters");
foreach ($hcs as $hc) {
    echo "ID: {$hc['HealthCenterID']} | Name: {$hc['Name']} | DB: " . ($hc['DatabaseName'] ?? 'NULL') . "\n";
}
echo "\n";

echo "--- INVoking MANUAL SYNC TEST ---\n";
// Attempt to sync for HC ID 2 (North District Clinic)
$hcId = 2;
$hcConn = Database::getHCConnection($hcId);
if ($hcConn) {
    echo "Connected to HC DB.\n";
    $sql = "SELECT ii.BatchID, ii.QuantityIssued, b.ItemID, b.ExpiryDate
            FROM IssuanceItem ii
            JOIN Issuance i ON ii.IssuanceID = i.IssuanceID
            JOIN Requisition r ON i.RequisitionID = r.RequisitionID
            JOIN CentralInventoryBatch b ON ii.BatchID = b.BatchID
            WHERE r.HealthCenterID = ? AND i.StatusType = 'Issued'";
    $issuances = $db->fetchAll($sql, [$hcId]);
    echo "Found " . count($issuances) . " issuances for HC ID $hcId.\n";
} else {
    echo "FAILED to connect to HC DB for ID $hcId.\n";
}
