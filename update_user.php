<?php
require_once 'includes/db.php';
$db = Database::getInstance();
$db->execute("UPDATE Users SET HealthCenterID = 1 WHERE UserID = 14");
echo "Updated user 14 to HC 1.\n";
