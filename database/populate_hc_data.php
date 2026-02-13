<?php
/**
 * Populate Health Center Local Databases with Sample Data
 * 
 * This script iterates through all health centers in the main database
 * and seeds their local databases with staff and requisition data.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "<h2>Populating Health Center Data...</h2>";

global $db;
$healthCenters = $db->fetchAll("SELECT * FROM HealthCenters");

if (empty($healthCenters)) {
    die("<p style='color: red;'>No health centers found in the main database.</p>");
}

foreach ($healthCenters as $hc) {
    $hcId = $hc['HealthCenterID'];
    $hcName = $hc['Name'];
    $dbName = $hc['DatabaseName'];

    echo "<h3>Processing: $hcName (ID: $hcId, DB: $dbName)</h3>";

    if (empty($dbName)) {
        echo "<p style='color: orange;'>⚠ Warning: No database name assigned to $hcName. Skipping.</p>";
        continue;
    }

    try {
        $hcConn = Database::getHCConnection($hcId);
        if (!$hcConn) {
            echo "<p style='color: red;'>✖ Error: Could not connect to database `$dbName`.</p>";
            continue;
        }

        // 1. Seed HC_Staff
        echo "<p>✓ Seeding HC_Staff...</p>";
        $staffData = [
            ['FirstName' => 'Maria', 'LastName' => 'Santos', 'Role' => 'Nurse', 'Username' => 'maria.nurse', 'Password' => password_hash('password', PASSWORD_DEFAULT)],
            ['FirstName' => 'Jose', 'LastName' => 'Rizal', 'Role' => 'Doctor', 'Username' => 'jose.doctor', 'Password' => password_hash('password', PASSWORD_DEFAULT)],
            ['FirstName' => 'Juan', 'LastName' => 'Luna', 'Role' => 'Staff', 'Username' => 'juan.staff', 'Password' => password_hash('password', PASSWORD_DEFAULT)]
        ];

        $staffStmt = $hcConn->prepare("INSERT IGNORE INTO HC_Staff (FirstName, LastName, Role, Username, Password) VALUES (?, ?, ?, ?, ?)");
        foreach ($staffData as $s) {
            $staffStmt->execute([$s['FirstName'], $s['LastName'], $s['Role'], $s['Username'], $s['Password']]);
        }
        echo "<p style='color: green;'>✓ Staff seeded.</p>";

        // 2. Seed HC_Requisition
        echo "<p>✓ Seeding HC_Requisitions...</p>";
        
        // Get some staff IDs
        $stmt = $hcConn->query("SELECT StaffID FROM HC_Staff LIMIT 2");
        $staffIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($staffIds)) {
            echo "<p style='color: orange;'>⚠ Warning: No staff found to assign requisitions. Skipping req seeding.</p>";
            continue;
        }

        // Get some item IDs from main DB
        $mainItems = $db->fetchAll("SELECT ItemID FROM Item LIMIT 5");
        if (empty($mainItems)) {
            echo "<p style='color: orange;'>⚠ Warning: No items found in main DB to requisition. Skipping req seeding.</p>";
            continue;
        }

        $reqStmt = $hcConn->prepare("INSERT INTO HC_Requisition (StaffID, RequestDate, StatusType, Remarks) VALUES (?, ?, ?, ?)");
        $itemStmt = $hcConn->prepare("INSERT INTO HC_RequisitionItem (HCRequisitionID, ItemID, QuantityRequested) VALUES (?, ?, ?)");

        // Create 2 sample requisitions per center
        for ($i = 0; $i < 2; $i++) {
            $staffId = $staffIds[array_rand($staffIds)];
            $reqDate = date('Y-m-d H:i:s', strtotime("-$i days"));
            $status = ($i % 2 == 0) ? 'Pending' : 'Completed';
            $remarks = "Sample requisition $i for $hcName";
            
            $reqStmt->execute([$staffId, $reqDate, $status, $remarks]);
            $reqId = $hcConn->lastInsertId();

            // Add 1-3 items per requisition
            $numItems = rand(1, 3);
            $selectedItems = array_rand($mainItems, $numItems);
            if (!is_array($selectedItems)) $selectedItems = [$selectedItems];

            foreach ($selectedItems as $itemIdx) {
                $itemId = $mainItems[$itemIdx]['ItemID'];
                $qty = rand(5, 50);
                $itemStmt->execute([$reqId, $itemId, $qty]);
            }
        }
        echo "<p style='color: green;'>✓ Requisitions and items seeded.</p>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>Fatal Error processing $hcName: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Finalizing...</h2>";
echo "<p><a href='../index.php?page=hc_inventory'>Go to Health Center Inventory</a></p>";
