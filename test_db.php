<?php
try {
    $dsn = "mysql:host=localhost;dbname=hucappdb;charset=utf8mb4";
    $conn = new PDO($dsn, 'root', '');
    echo "Connection successful!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
