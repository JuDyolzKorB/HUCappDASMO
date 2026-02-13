<?php
require_once 'includes/db.php';
session_start();

echo "--- SESSION USER ---\n";
print_r($_SESSION['user'] ?? 'No session user');
echo "\n";

$db = Database::getInstance();

echo "--- HEALTH CENTERS TABLE ---\n";
$hcs = $db->fetchAll("SELECT * FROM HealthCenters");
foreach ($hcs as $hc) {
    echo "ID: {$hc['HealthCenterID']} | Name: {$hc['Name']} | DB: " . ($hc['DatabaseName'] ?? 'NULL') . "\n";
}
echo "\n";

if (isset($_SESSION['user']['UserID'])) {
    echo "--- CURRENT USER DB RECORD ---\n";
    $u = $db->fetchOne("SELECT UserID, FName, LName, Role, HealthCenterID FROM Users WHERE UserID = ?", [$_SESSION['user']['UserID']]);
    print_r($u);
    echo "\n";
    
    if ($u['HealthCenterID']) {
        echo "--- TESTING HC CONNECTION for ID {$u['HealthCenterID']} ---\n";
        try {
            $hcConn = Database::getHCConnection($u['HealthCenterID']);
            if ($hcConn) {
                echo "SUCCESS: Connected to HC Database.\n";
                $tables = $hcConn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                echo "Tables in HC DB: " . implode(', ', $tables) . "\n";
            } else {
                echo "FAILURE: Connection returned null.\n";
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "WARNING: No HealthCenterID assigned to user in DB.\n";
    }
}
