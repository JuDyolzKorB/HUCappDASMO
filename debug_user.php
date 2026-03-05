<?php
require_once 'includes/db.php';
$db = Database::getInstance();

echo "Finding user 'Jesse'...\n";
$user = $db->fetchOne("SELECT * FROM Users WHERE FName LIKE '%Jesse%' OR LName LIKE '%Jot%'");

if ($user) {
    echo "Found User: {$user['Username']} (ID: {$user['UserID']})\n";
    echo "HealthCenterID: {$user['HealthCenterID']}\n";
    
    if ($user['HealthCenterID']) {
        $hc = $db->fetchOne("SELECT * FROM HealthCenters WHERE HealthCenterID = ?", [$user['HealthCenterID']]);
        if ($hc) {
            echo "Health Center: {$hc['Name']}\n";
            echo "DatabaseName: {$hc['DatabaseName']}\n";
        } else {
            echo "Health Center record not found!\n";
        }
    } else {
        echo "No Health Center assigned!\n";
    }
} else {
    echo "User not found.\n";
}
