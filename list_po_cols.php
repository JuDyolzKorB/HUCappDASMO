<?php
require_once 'includes/db.php';
$db = Database::getInstance();
$columns = $db->fetchAll("DESC PurchaseOrder");
foreach($columns as $col) {
    echo $col['Field'] . "\n";
}
