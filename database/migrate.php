<?php
/**
 * Data Migration Script
 * Migrates data from JSON files to MySQL database
 * 
 * Usage: Run this script once after setting up the database schema
 * Access via browser: http://localhost/HUCappDASMO/database/migrate.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Set time limit for large migrations
set_time_limit(300);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border-radius: 4px; margin: 10px 0; }
        .step { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔄 Database Migration Tool</h1>";

$dataPath = __DIR__ . '/../data/';
$db = Database::getInstance();
$conn = $db->getConnection();

$migrationLog = [];
$errors = [];

function logMigration($message, $type = 'info') {
    global $migrationLog;
    $migrationLog[] = ['message' => $message, 'type' => $type];
    $class = $type;
    echo "<div class='$class'>$message</div>";
    flush();
}

function readJsonFile($filename) {
    global $dataPath;
    $path = $dataPath . $filename . '.json';
    if (!file_exists($path)) {
        return [];
    }
    $json = file_get_contents($path);
    return json_decode($json, true) ?? [];
}

try {
    // Test database connection
    logMigration("✓ Testing database connection...", 'info');
    $conn->query("SELECT 1");
    logMigration("✓ Database connection successful!", 'success');
    
    // Migrate Users
    echo "<div class='step'><h3>1. Migrating Users</h3>";
    $users = readJsonFile('users');
    if (!empty($users)) {
        foreach ($users as $user) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO Users (UserID, FName, MName, LName, Role, Username, Password) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     FName = VALUES(FName), MName = VALUES(MName), LName = VALUES(LName), 
                     Role = VALUES(Role), Username = VALUES(Username), Password = VALUES(Password)"
                );
                $stmt->execute([
                    $user['UserID'],
                    $user['FirstName'] ?? $user['FName'] ?? '',
                    $user['MiddleName'] ?? $user['MName'] ?? '',
                    $user['LastName'] ?? $user['LName'] ?? '',
                    $user['Role'],
                    $user['Username'],
                    $user['Password']
                ]);
            } catch (Exception $e) {
                logMigration("Error migrating user {$user['UserID']}: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($users) . " users", 'success');
    } else {
        logMigration("No users found in JSON", 'info');
    }
    echo "</div>";
    
    // Migrate Health Centers
    echo "<div class='step'><h3>2. Migrating Health Centers</h3>";
    $healthCenters = readJsonFile('health_centers');
    if (!empty($healthCenters)) {
        foreach ($healthCenters as $hc) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO HealthCenters (HealthCenterID, Name, Address) 
                     VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE Name = VALUES(Name), Address = VALUES(Address)"
                );
                $stmt->execute([
                    $hc['HealthCenterID'],
                    $hc['Name'],
                    $hc['Address'] ?? ''
                ]);
            } catch (Exception $e) {
                logMigration("Error migrating health center: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($healthCenters) . " health centers", 'success');
    } else {
        logMigration("No health centers found in JSON", 'info');
    }
    echo "</div>";
    
    // Migrate Warehouses
    echo "<div class='step'><h3>3. Migrating Warehouses</h3>";
    $warehouses = readJsonFile('warehouses');
    if (!empty($warehouses)) {
        foreach ($warehouses as $wh) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO Warehouse (WarehouseID, WarehouseName, Location, WarehouseType) 
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     WarehouseName = VALUES(WarehouseName), Location = VALUES(Location), WarehouseType = VALUES(WarehouseType)"
                );
                $stmt->execute([
                    $wh['WarehouseID'],
                    $wh['WarehouseName'],
                    $wh['Location'] ?? '',
                    $wh['WarehouseType'] ?? ''
                ]);
            } catch (Exception $e) {
                logMigration("Error migrating warehouse: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($warehouses) . " warehouses", 'success');
    } else {
        logMigration("No warehouses found in JSON", 'info');
    }
    echo "</div>";
    
    // Migrate Items
    echo "<div class='step'><h3>4. Migrating Items</h3>";
    $items = readJsonFile('items');
    if (!empty($items)) {
        foreach ($items as $item) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO Item (ItemID, ItemName, ItemType, UnitOfMeasure) 
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     ItemName = VALUES(ItemName), ItemType = VALUES(ItemType), UnitOfMeasure = VALUES(UnitOfMeasure)"
                );
                $stmt->execute([
                    $item['ItemID'],
                    $item['ItemName'],
                    $item['ItemType'] ?? null,
                    $item['UnitOfMeasure'] ?? ''
                ]);
            } catch (Exception $e) {
                logMigration("Error migrating item: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($items) . " items", 'success');
    } else {
        logMigration("No items found in JSON", 'info');
    }
    echo "</div>";
    
    // Migrate Suppliers
    echo "<div class='step'><h3>6. Migrating Suppliers</h3>";
    $suppliers = readJsonFile('suppliers');
    if (!empty($suppliers)) {
        foreach ($suppliers as $supplier) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO Supplier (SupplierID, Name, Address, ContactInfo) 
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     Name = VALUES(Name), Address = VALUES(Address), ContactInfo = VALUES(ContactInfo)"
                );
                $stmt->execute([
                    $supplier['SupplierID'],
                    $supplier['Name'],
                    $supplier['Address'] ?? '',
                    $supplier['ContactInfo'] ?? ''
                ]);
            } catch (Exception $e) {
                logMigration("Error migrating supplier: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($suppliers) . " suppliers", 'success');
    } else {
        logMigration("No suppliers found in JSON", 'info');
    }
    echo "</div>";
    
    // Migrate Inventory
    echo "<div class='step'><h3>7. Migrating Inventory Batches</h3>";
    $inventory = readJsonFile('inventory');
    if (!empty($inventory)) {
        foreach ($inventory as $batch) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO CentralInventoryBatch 
                     (BatchID, ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE 
                     QuantityOnHand = VALUES(QuantityOnHand), QuantityReleased = VALUES(QuantityReleased)"
                );
                $stmt->execute([
                    $batch['BatchID'],
                    $batch['ItemID'],
                    $batch['WarehouseID'] ?? 'W01',
                    $batch['ExpiryDate'] ?? null,
                    $batch['QuantityOnHand'] ?? 0,
                    $batch['QuantityReleased'] ?? 0,
                    $batch['UnitCost'] ?? 0,
                    $batch['DateReceived'] ?? date('Y-m-d')
                ]);
            } catch (Exception $e) {
                logMigration("Error migrating batch {$batch['BatchID']}: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($inventory) . " inventory batches", 'success');
    } else {
        logMigration("No inventory found in JSON", 'info');
    }
    echo "</div>";
    
    // Migrate Purchase Orders
    echo "<div class='step'><h3>8. Migrating Purchase Orders</h3>";
    $purchaseOrders = readJsonFile('purchase_orders');
    if (!empty($purchaseOrders)) {
        foreach ($purchaseOrders as $po) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO PurchaseOrder (POID, UserID, SupplierID, HealthCenterID, PONumber, PODate, StatusType) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE StatusType = VALUES(StatusType)"
                );
                $stmt->execute([
                    $po['POID'],
                    $po['UserID'] ?? null,
                    $po['SupplierID'] ?? null,
                    $po['HealthCenterID'] ?? null,
                    $po['PONumber'],
                    $po['PODate'],
                    $po['StatusType'] ?? 'Pending'
                ]);
                
                // Migrate PO Items
                if (isset($po['PurchaseOrderItems'])) {
                    foreach ($po['PurchaseOrderItems'] as $item) {
                        $stmt = $conn->prepare(
                            "INSERT INTO PurchaseOrderItem (POItemID, POID, ItemID, QuantityOrdered, UnitCost) 
                             VALUES (?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE QuantityOrdered = VALUES(QuantityOrdered)"
                        );
                        $stmt->execute([
                            $item['POItemID'],
                            $po['POID'],
                            $item['ItemID'],
                            $item['QuantityOrdered'],
                            $item['UnitCost'] ?? 0
                        ]);
                    }
                }
            } catch (Exception $e) {
                logMigration("Error migrating PO {$po['POID']}: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($purchaseOrders) . " purchase orders", 'success');
    } else {
        logMigration("No purchase orders found in JSON", 'info');
    }
    echo "</div>";
    
    // Migrate Requisitions
    echo "<div class='step'><h3>9. Migrating Requisitions</h3>";
    $requisitions = readJsonFile('requisitions');
    if (!empty($requisitions)) {
        foreach ($requisitions as $req) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO Requisition (RequisitionID, HealthCenterID, UserID, RequestDate, StatusType) 
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE StatusType = VALUES(StatusType)"
                );
                $stmt->execute([
                    $req['RequisitionID'],
                    $req['HealthCenterID'] ?? null,
                    $req['UserID'] ?? null,
                    $req['RequestedDate'] ?? $req['RequestDate'] ?? date('Y-m-d H:i:s'),
                    $req['StatusType'] ?? 'Pending'
                ]);
                
                // Migrate Requisition Items
                if (isset($req['RequisitionItems'])) {
                    foreach ($req['RequisitionItems'] as $item) {
                        $stmt = $conn->prepare(
                            "INSERT INTO RequisitionItem (RequisitionItemID, RequisitionID, ItemID, QuantityRequested) 
                             VALUES (?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE QuantityRequested = VALUES(QuantityRequested)"
                        );
                        $stmt->execute([
                            $item['RequisitionItemID'],
                            $req['RequisitionID'],
                            $item['ItemID'],
                            $item['QuantityRequested']
                        ]);
                    }
                }
                
                // Migrate Approval Logs
                if (isset($req['ApprovalLogs'])) {
                    foreach ($req['ApprovalLogs'] as $log) {
                        $stmt = $conn->prepare(
                            "INSERT INTO ApprovalLog (ApprovalLogID, RequisitionID, UserID, Decision, DecisionDate) 
                             VALUES (?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE Decision = VALUES(Decision)"
                        );
                        $stmt->execute([
                            $log['ApprovalLogID'],
                            $req['RequisitionID'],
                            $log['UserID'] ?? null,
                            $log['Decision'] ?? '',
                            $log['DecisionDate'] ?? date('Y-m-d H:i:s')
                        ]);
                    }
                }
            } catch (Exception $e) {
                logMigration("Error migrating requisition {$req['RequisitionID']}: " . $e->getMessage(), 'error');
            }
        }
        logMigration("✓ Migrated " . count($requisitions) . " requisitions", 'success');
    } else {
        logMigration("No requisitions found in JSON", 'info');
    }
    echo "</div>";
    
    logMigration("🎉 Migration completed successfully!", 'success');
    echo "<div class='info'><strong>Next Steps:</strong><br>
    1. Test your application functionality<br>
    2. Verify data in phpMyAdmin<br>
    3. Backup your JSON files (they are no longer being used)<br>
    4. You can now delete this migration script</div>";
    
} catch (Exception $e) {
    logMigration("❌ Migration failed: " . $e->getMessage(), 'error');
    echo "<div class='error'>Please check your database configuration and try again.</div>";
}

echo "</div></body></html>";
