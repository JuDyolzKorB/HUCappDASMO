<?php
require_once 'includes/db.php';
$db = Database::getInstance();
try {
    $columns = $db->fetchAll("DESC PurchaseOrder");
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
