<?php
require_once 'includes/db.php';
$hcConn = Database::getHCConnection(2);
if ($hcConn) {
    $items = $hcConn->query("SELECT * FROM HC_Inventory")->fetchAll();
    echo "--- HC_NORTH INVENTORY ---\n";
    print_r($items);
} else {
    echo "FAILED to connect to hc_north\n";
}
