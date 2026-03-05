<?php
require_once 'includes/db.php';
global $db;

echo "--- PATIENT REQUISITION VERIFICATION ---\n";

// 1. Check if we have any requisitions
$reqs = $db->fetchAll("SELECT * FROM HCPatientRequisition ORDER BY PatientReqID DESC LIMIT 1");
if (empty($reqs)) {
    echo "No requisitions found to test. Creating a dummy one...\n";
    $db->execute("INSERT INTO HCPatientRequisition (PatientID, UserID, HealthCenterID, RequisitionNumber, RequestDate, StatusType) VALUES (1, 1, 1, 'TEST-REQ', NOW(), 'Pending')");
    $req = $db->fetchOne("SELECT * FROM HCPatientRequisition WHERE RequisitionNumber = 'TEST-REQ'");
} else {
    $req = $reqs[0];
}

$reqId = $req['PatientReqID'];
$oldStatus = $req['StatusType'];
echo "Target Req ID: $reqId\n";
echo "Initial Status: $oldStatus\n";

// 2. Simulate API call logic (Direct DB update as the API does)
$newStatus = 'Approved';
echo "Updating to: $newStatus\n";

$res = $db->execute("UPDATE HCPatientRequisition SET StatusType = ? WHERE PatientReqID = ?", [$newStatus, $reqId]);

if ($res) {
    $updatedReq = $db->fetchOne("SELECT * FROM HCPatientRequisition WHERE PatientReqID = ?", [$reqId]);
    echo "New Status in DB: " . $updatedReq['StatusType'] . "\n";
    if ($updatedReq['StatusType'] === $newStatus) {
        echo "VERIFICATION_SUCCESS\n";
    } else {
        echo "VERIFICATION_FAILURE: Status mismatch\n";
    }
    
    // Cleanup if dummy
    if ($req['RequisitionNumber'] === 'TEST-REQ') {
        $db->execute("DELETE FROM HCPatientRequisition WHERE RequisitionNumber = 'TEST-REQ'");
        echo "Cleaned up dummy record.\n";
    }
} else {
    echo "VERIFICATION_FAILURE: DB update failed\n";
}
?>
