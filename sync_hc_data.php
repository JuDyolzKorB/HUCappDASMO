<?php
require_once 'includes/db.php';
$db = Database::getInstance();

// 1. Find the North District Clinic
$hc = $db->fetchOne("SELECT HealthCenterID FROM HealthCenters WHERE Name LIKE '%North District%'");
if ($hc) {
    echo "Found Health Center: " . $hc['HealthCenterID'] . "\n";
    $db->execute("UPDATE HealthCenters SET DatabaseName = 'hc_north' WHERE HealthCenterID = ?", [$hc['HealthCenterID']]);
    echo "Updated North District Clinic with DatabaseName 'hc_north'\n";
    
    // 2. Find Chino John Gico and link him to this HC
    $user = $db->fetchOne("SELECT UserID FROM Users WHERE FName LIKE '%Chinno%' OR LName LIKE '%Gico%'");
    if ($user) {
        echo "Found User: " . $user['UserID'] . "\n";
        $db->execute("UPDATE Users SET HealthCenterID = ? WHERE UserID = ?", [$hc['HealthCenterID'], $user['UserID']]);
        echo "Linked user to health center.\n";
    } else {
        echo "User not found.\n";
    }
} else {
    echo "North District Clinic not found by name.\n";
}
