<?php
require_once 'includes/db.php';
global $db;
$users = $db->fetchAll("SELECT Username, Role FROM Users WHERE Role = 'Health Center User'");
echo json_encode($users, JSON_PRETTY_PRINT);
