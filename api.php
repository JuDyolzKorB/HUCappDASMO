<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    if ($action === 'login') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        $users = get_data('users');
        $user = null;

        foreach ($users as $u) {
            // Check for username, hashed password (or plain text fallback), AND Role
            if ($u['Username'] === $username) {
                if (password_verify($password, $u['Password']) || $u['Password'] === $password) {
                    if ($u['Role'] === $role) {
                        $user = $u;
                    } else {
                        echo json_encode(['success' => false, 'message' => "Access denied for role: $role"]);
                        exit;
                    }
                    break;
                }
            }
        }

        if ($user) {
            $_SESSION['user'] = $user;
            log_security_event($user['UserID'], 'Login', 'Success', "User logged in as $role");
            echo json_encode(['success' => true, 'redirect' => 'index.php?page=dashboard']);
        } else {
            // Log failed attempt
            log_security_event('unknown', 'Login', 'Failure', "Failed login attempt for username: $username");
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } elseif ($action === 'logout') {
        if (isset($_SESSION['user'])) {
            log_security_event($_SESSION['user']['UserID'], 'Logout', 'Success', 'User logged out');
        }
        session_destroy();
        echo json_encode(['success' => true]);
    } elseif ($action === 'signup') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $firstName = $_POST['firstName'] ?? '';
        $middleName = $_POST['middleName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $role = $_POST['role'] ?? 'Health Center User';

        $users = get_data('users');
        
        // Check if username exists
        foreach ($users as $u) {
            if ($u['Username'] === $username) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }
        }

        // Create new user with HASHED password
        $newUser = [
            'UserID' => 'U' . str_pad(count($users) + 1, 3, '0', STR_PAD_LEFT),
            'Username' => $username,
            'Password' => password_hash($password, PASSWORD_DEFAULT),
            'FirstName' => $firstName,
            'MiddleName' => $middleName,
            'LastName' => $lastName,
            'Role' => $role
        ];

        $users[] = $newUser;
        
        if (save_data('users', $users)) {
             log_security_event($newUser['UserID'], 'Signup', 'Success', 'New user registered');
             echo json_encode(['success' => true, 'redirect' => 'index.php?page=login']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save user']);
        }
    } elseif ($action === 'add_warehouse') {
        $warehouses = get_data('warehouses');
        
        $newWarehouse = [
            'WarehouseID' => 'W' . str_pad(count($warehouses) + 1, 2, '0', STR_PAD_LEFT),
            'WarehouseName' => $_POST['warehouseName'],
            'Location' => $_POST['location'],
            'WarehouseType' => $_POST['warehouseType']
        ];
        
        $warehouses[] = $newWarehouse;
        
        if (save_data('warehouses', $warehouses)) {
            log_security_event($_SESSION['user']['UserID'], 'Warehouse', 'Success', 'Created warehouse ' . $newWarehouse['WarehouseID']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save warehouse']);
        }
    } elseif ($action === 'process_issuance') {
        $reqId = $_POST['requisitionId'];
        $allocationPlan = json_decode($_POST['allocationPlan'], true);
        
        if (!$allocationPlan) {
            echo json_encode(['success' => false, 'message' => 'Invalid allocation plan']);
            exit;
        }
        
        $inventory = get_data('inventory');
        $issuances = get_data('issuances');
        $requisitions = get_data('requisitions');
        
        // Find Requisition
        $req = null;
        foreach($requisitions as &$r) {
            if($r['RequisitionID'] === $reqId) {
                $req = &$r;
                break;
            }
        }
        
        if (!$req) {
             echo json_encode(['success' => false, 'message' => 'Requisition not found']);
             exit;
        }
        
        $issuanceId = 'ISS-' . date('Ymd') . '-' . rand(1000, 9999);
        $newIssuance = [
            'IssuanceID' => $issuanceId,
            'RequisitionID' => $reqId,
            'DateIssued' => date('Y-m-d H:i:s'),
            'IssuedByUserID' => $_SESSION['user']['UserID'],
            'IssuedItems' => []
        ];
        
        foreach ($allocationPlan as $item) {
             // Deduct from inventory
             foreach ($inventory as &$batch) {
                 if ($batch['BatchID'] === $item['batchId']) {
                     $batch['QuantityOnHand'] -= (int)$item['quantity'];
                     $newIssuance['IssuedItems'][] = [
                         'IssuanceItemID' => 'II-' . rand(10000, 99999),
                         'ItemID' => $batch['ItemID'],
                         'BatchID' => $batch['BatchID'],
                         'QuantityIssued' => (int)$item['quantity']
                     ];
                     break;
                 }
             }
        }
        
        $issuances[] = $newIssuance;
        $req['StatusType'] = 'Completed'; // Mark requisition as completed
        
        $userObj = $_SESSION['user'];
        $userName = $userObj['FirstName'] . ' ' . $userObj['LastName'];
        
        // Add additional metadata to issuance for history
        $newIssuance['IssuedByFullName'] = $userName;
        $newIssuance['StatusType'] = 'Issued';
        
        save_data('inventory', $inventory);
        save_data('issuances', $issuances);
        save_data('requisitions', $requisitions);
        
        logTransaction('Issued Items', 'Requisition', $reqId);
        
        echo json_encode(['success' => true]);

    } elseif ($action === 'receive_items') {
        $poid = $_POST['poid'] ?? $_POST['poId']; // Handle both for robustness
        $items = $_POST['items']; 
        
        $inventory = get_data('inventory');
        $purchaseOrders = get_data('purchase_orders');
        $receivings = get_data('receivings');
        
        // Create new receiving record
        $receivingId = 'RCV-' . date('Ymd') . '-' . rand(100, 999);
        $newReceiving = [
            'ReceivingID' => $receivingId,
            'POID' => $poid,
            'ReceivedDate' => date('Y-m-d H:i:s'),
            'UserID' => $_SESSION['user']['UserID'],
            'ReceivedItems' => []
        ];
        
        foreach ($items as $item) {
            $qtyReceived = (float)$item['quantityReceived'];
            if ($qtyReceived > 0) {
                // Add to Inventory Batches
                $batchId = 'BATCH-' . date('Ymd') . '-' . rand(1000, 9999);
                $newBatch = [
                    'BatchID' => $batchId,
                    'ItemID' => $item['itemId'],
                    'QuantityOnHand' => $qtyReceived,
                    'ExpiryDate' => $item['expiryDate'],
                    'UnitCost' => (float)$item['unitCost'],
                    'DateReceived' => date('Y-m-d'), // CRITICAL for FIFO
                    'WarehouseID' => 'W01' // Default
                ];
                $inventory[] = $newBatch;
                $newReceiving['ReceivedItems'][] = $newBatch;
            }
        }
        
        $receivings[] = $newReceiving;
        
        // Update PO Status
        foreach ($purchaseOrders as &$po) {
            if ($po['POID'] === $poid) {
                $po['StatusType'] = 'Completed'; 
                break;
            }
        }
        
        if (save_data('inventory', $inventory) && save_data('purchase_orders', $purchaseOrders) && save_data('receivings', $receivings)) {
             log_security_event($_SESSION['user']['UserID'], 'Receiving', 'Success', "Received items for PO $poid");
             logTransaction('Received Items', 'Purchase Order', $poid);
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save updates']);
        }

    } elseif ($action === 'create_requisition') {
        $healthCenterId = $_POST['healthCenterId'];
        $items = $_POST['items']; // Array of items
        
        // Lookup Health Center Name for denormalization (optional, but good for display)
        $healthCenters = get_data('health_centers');
        $hcName = 'Unknown';
        foreach($healthCenters as $hc) {
            if($hc['HealthCenterID'] === $healthCenterId) {
                $hcName = $hc['Name'];
                break;
            }
        }
        
        $reqId = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);
        $newReq = [
            'RequisitionID' => $reqId,
            'RequisitionNumber' => $reqId,
            'HealthCenterID' => $healthCenterId,
            'HealthCenterName' => $hcName,
            'UserID' => $_SESSION['user']['UserID'],
            'RequestedByFullName' => $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName'],
            'RequestedDate' => date('Y-m-d H:i:s'),
            'StatusType' => 'Pending',
            'RequisitionItems' => [],
            'ApprovalLogs' => []
        ];
        
        $reqItems = [];
        foreach($items as $i) {
            if((int)$i['quantity'] > 0) {
                $reqItems[] = [
                    'RequisitionItemID' => 'RI-' . rand(10000,99999),
                    'ItemID' => $i['itemId'],
                    'QuantityRequested' => (int)$i['quantity']
                ];
            }
        }
        
        if (empty($reqItems)) {
            echo json_encode(['success' => false, 'message' => 'No valid items requested']);
            exit;
        }
        
        $newReq['RequisitionItems'] = $reqItems;
        
        $requisitions = get_data('requisitions');
        $requisitions[] = $newReq;
        
        if (save_data('requisitions', $requisitions)) {
            log_security_event($_SESSION['user']['UserID'], 'Requisition', 'Success', "Created requisition $reqId");
            logTransaction('Created Requisition', 'Requisition', $reqId);
            echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save requisition']);
        }
    } elseif ($action === 'update_requisition_status') {
        $reqId = $_POST['requisitionId'];
        $status = $_POST['status']; // Approved or Rejected
        
        $requisitions = get_data('requisitions');
        $updated = false;
        
        foreach ($requisitions as &$r) {
            if ($r['RequisitionID'] === $reqId) {
                $r['StatusType'] = $status;
                
                // Add Log
                $r['ApprovalLogs'] = $r['ApprovalLogs'] ?? [];
                $r['ApprovalLogs'][] = [
                    'ApprovalLogID' => 'AL-' . time(),
                    'RequisitionID' => $reqId,
                    'UserID' => $_SESSION['user']['UserID'],
                    'ApproverFullName' => $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName'],
                    'Decision' => $status,
                    'DecisionDate' => date('Y-m-d H:i:s')
                ];
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            if (save_data('requisitions', $requisitions)) {
                logTransaction($status . ' Requisition', 'Requisition', $reqId);
                echo json_encode(['success' => true]);
            } else {
                 echo json_encode(['success' => false, 'message' => 'Failed to save updates']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Requisition not found']);
        }
    } elseif ($action === 'update_po_status') {
        $poId = $_POST['poId'];
        $status = $_POST['status']; 
        
        $purchaseOrders = get_data('purchase_orders');
        $updated = false;
        
        foreach ($purchaseOrders as &$po) {
            if ($po['POID'] === $poId) {
                $po['StatusType'] = $status;
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            if (save_data('purchase_orders', $purchaseOrders)) {
                logTransaction($status . ' Purchase Order', 'Purchase Order', $poId);
                echo json_encode(['success' => true]);
            } else {
                 echo json_encode(['success' => false, 'message' => 'Failed to save updates']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found']);
        }
    } elseif ($action === 'update_profile') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        
        $firstName = $_POST['firstName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $currUser = $_SESSION['user'];
        
        $users = get_data('users');
        $updated = false;
        
        foreach ($users as &$u) {
            if ($u['UserID'] === $currUser['UserID']) {
                $u['FirstName'] = $firstName;
                $u['LastName'] = $lastName;
                // Update session
                $_SESSION['user'] = $u;
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            if (save_data('users', $users)) {
                logTransaction('Updated Profile', 'User', $currUser['UserID']);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save user data']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } elseif ($action === 'update_password') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        $currUser = $_SESSION['user'];
        
        $users = get_data('users');
        $updated = false;
        
        foreach ($users as &$u) {
            if ($u['UserID'] === $currUser['UserID']) {
                // Verify current password
                if (!password_verify($currentPassword, $u['Password']) && $u['Password'] !== $currentPassword) {
                    echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
                    exit;
                }
                
                // Update password (hashed)
                $u['Password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $_SESSION['user'] = $u; // Optional, sessions usually don't need the password
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            if (save_data('users', $users)) {
                logTransaction('Changed Password', 'User', $currUser['UserID']);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save user data']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } elseif ($action === 'create_purchase_order') {
        // Validate user is logged in
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            exit;
        }
        
        $supplierId = $_POST['supplierId'] ?? '';
        $healthCenterId = $_POST['healthCenterId'] ?? '';
        $items = $_POST['items'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        
        // Validate inputs
        if (empty($supplierId)) {
            echo json_encode(['success' => false, 'message' => 'Supplier is required']);
            exit;
        }
        
        if (empty($items) || empty($quantities)) {
            echo json_encode(['success' => false, 'message' => 'At least one item is required']);
            exit;
        }
        
        // Load data
        $purchaseOrders = get_data('purchase_orders');
        $suppliers = get_data('suppliers');
        $healthCenters = get_data('health_centers');
        
        // Find supplier name
        $supplierName = '';
        foreach ($suppliers as $supplier) {
            if ($supplier['SupplierID'] === $supplierId) {
                $supplierName = $supplier['Name'];
                break;
            }
        }
        
        // Find health center name
        $healthCenterName = '';
        if (!empty($healthCenterId)) {
            foreach ($healthCenters as $hc) {
                if ($hc['HealthCenterID'] === $healthCenterId) {
                    $healthCenterName = $hc['Name'];
                    break;
                }
            }
        }
        
        // Generate POID
        $nextPoNum = count($purchaseOrders) + 1;
        $poid = 'PO' . str_pad($nextPoNum, 3, '0', STR_PAD_LEFT);
        
        // Generate PO Number (format: PO-YYXXXX)
        $year = date('y');
        $poNumber = 'PO-' . $year . str_pad($nextPoNum, 4, '0', STR_PAD_LEFT);
        
        // Build Purchase Order Items
        $poItems = [];
        $poItemCounter = 1;
        foreach ($items as $index => $itemId) {
            if (!empty($itemId) && !empty($quantities[$index]) && $quantities[$index] > 0) {
                $poItemId = 'POI' . str_pad(count($purchaseOrders) * 10 + $poItemCounter, 3, '0', STR_PAD_LEFT);
                $poItems[] = [
                    'POItemID' => $poItemId,
                    'POID' => $poid,
                    'ItemID' => $itemId,
                    'QuantityOrdered' => (int)$quantities[$index]
                ];
                $poItemCounter++;
            }
        }
        
        if (empty($poItems)) {
            echo json_encode(['success' => false, 'message' => 'No valid items to order']);
            exit;
        }
        
        // Create new Purchase Order
        $newPO = [
            'POID' => $poid,
            'UserID' => $_SESSION['user']['UserID'],
            'SupplierID' => $supplierId,
            'PONumber' => $poNumber,
            'PODate' => date('Y-m-d\TH:i:s\Z'),
            'StatusType' => 'Pending',
            'SupplierName' => $supplierName,
            'PurchaseOrderItems' => $poItems,
            'ApprovalLogs' => []
        ];
        
        // Add health center if provided
        if (!empty($healthCenterId)) {
            $newPO['HealthCenterID'] = $healthCenterId;
            $newPO['HealthCenterName'] = $healthCenterName;
        }
        
        // Save to database
        $purchaseOrders[] = $newPO;
        
        if (save_data('purchase_orders', $purchaseOrders)) {
            // Log transaction
            logTransaction('Created Purchase Order', 'Purchase Order', $poid);
            log_security_event($_SESSION['user']['UserID'], 'Purchase Order', 'Success', 'Created PO ' . $poNumber);
            
            echo json_encode([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'poNumber' => $poNumber
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save purchase order']);
        }
    } elseif ($action === 'save_report') {
        $report = json_decode($_POST['report'], true);
        if ($report) {
            $reports = get_data('reports');
            array_unshift($reports, $report);
            save_data('reports', $reports);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid report data']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
