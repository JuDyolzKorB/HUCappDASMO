<?php
require_once 'includes/db.php';
$db = Database::getInstance();
$user = $db->fetchOne("SELECT UserID, Username, FName, LName, Role, HealthCenterID FROM Users WHERE UserID = 11");
echo "--- USER 11 (Chinno John Gico) ---\n";
print_r($user);

$allRoles = $db->fetchAll("SELECT DISTINCT Role FROM Users");
echo "\n--- ALL ROLES IN DB ---\n";
foreach($allRoles as $r) echo "[{$r['Role']}]\n";
