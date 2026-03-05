<?php
require_once 'includes/db.php';
global $db;
$roles = $db->fetchAll("SELECT DISTINCT Role FROM Users");
foreach ($roles as $r) {
    echo $r['Role'] . "\n";
}
