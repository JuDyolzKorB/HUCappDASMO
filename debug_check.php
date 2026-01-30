<?php
require_once 'includes/db.php';

echo "Dumping Users...\n";
$users = $db->fetchAll("SELECT * FROM Users");
foreach ($users as $u) {
    echo "ID: " . $u['UserID'] . " (Type: " . gettype($u['UserID']) . ")\n";
    echo "Username: " . $u['Username'] . "\n";
    echo "Password: " . substr($u['Password'], 0, 10) . "..." . "\n";
    echo "------------------\n";
}

echo "Done.\n";
