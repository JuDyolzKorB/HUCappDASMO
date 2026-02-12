<?php
require_once __DIR__ . '/../includes/db.php';
$db = Database::getInstance();
$batches = $db->fetchAll("SELECT * FROM CentralInventoryBatch");
echo "Count: " . count($batches) . "\n";
print_r($batches);
