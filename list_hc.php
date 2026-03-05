<?php
require_once 'includes/db.php';
$db = Database::getInstance();
$hcs = $db->fetchAll("SELECT * FROM HealthCenters");
foreach ($hcs as $r) {
    echo "ID: " . $r['HealthCenterID'] . " | Name: " . $r['Name'] . " | DB: " . ($r['DatabaseName'] ?? 'NULL') . "\n";
}
echo "\n--- USERS ---\n";
$users = $db->fetchAll("SELECT UserID, Username, HealthCenterID FROM Users");
foreach ($users as $u) {
    echo "ID: " . $u['UserID'] . " | User: " . $u['Username'] . " | HC_ID: " . ($u['HealthCenterID'] ?? 'NULL') . "\n";
}
