<?php
require_once 'includes/db.php';
$db = Database::getInstance();
$hc = $db->fetchOne("SELECT DatabaseName FROM HealthCenters WHERE HealthCenterID = 1");
if ($hc) {
    echo "DB: " . $hc['DatabaseName'];
} else {
    echo "Not Found";
}
