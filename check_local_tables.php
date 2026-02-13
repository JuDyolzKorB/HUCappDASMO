<?php
require_once 'includes/db.php';
$hcConn = Database::getHCConnection(2); // North District Clinic
if ($hcConn) {
    echo "--- REQUISITIONS ---\n";
    print_r($hcConn->query("SELECT * FROM HC_Requisition")->fetchAll());
    echo "\n--- ITEMS ---\n";
    print_r($hcConn->query("SELECT * FROM HC_RequisitionItem")->fetchAll());
    echo "\n--- STAFF ---\n";
    print_r($hcConn->query("SELECT * FROM HC_Staff")->fetchAll());
} else {
    echo "FAILED to connect to hc_north\n";
}
