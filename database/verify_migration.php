<?php
require_once __DIR__ . '/../includes/db.php';
$db = Database::getInstance();
$conn = $db->getConnection();
$columns = $conn->query("SHOW COLUMNS FROM PurchaseOrder")->fetchAll(PDO::FETCH_COLUMN);

$missing = [];
if (!in_array('SupplierName', $columns)) $missing[] = 'SupplierName';
if (!in_array('SupplierAddress', $columns)) $missing[] = 'SupplierAddress';

if (empty($missing)) {
    echo "SUCCESS: All columns found.\n";
} else {
    echo "FAILURE: Missing columns: " . implode(', ', $missing) . "\n";
}
