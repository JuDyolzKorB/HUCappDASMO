<?php
require_once 'includes/db.php';
global $db;
$users = $db->fetchAll("SELECT UserID, Username, Role FROM Users");
echo json_encode($users, JSON_PRETTY_PRINT);
