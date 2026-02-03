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

        global $db; // Ensure we have access to the DB instance
        // Use direct DB lookup instead of fetching all users
        $user = $db->find('users', 'Username', $username);

        if ($user) {
            // Verify password
            if (password_verify($password, $user['Password']) || $user['Password'] === $password) {
                // Success - $user is already set
            } else {
                $user = null; // Invalid password
            }
        }

        if ($user) {
            // Normalize keys for the application
            $user['FirstName'] = $user['FirstName'] ?? $user['FName'] ?? '';
            $user['MiddleName'] = $user['MiddleName'] ?? $user['MName'] ?? '';
            $user['LastName'] = $user['LastName'] ?? $user['LName'] ?? '';
            $user['Role'] = $user['Role'] ?? 'User'; // Ensure Role is set
            
            $_SESSION['user'] = $user;
            log_security_event($user['UserID'], 'Login', 'Success', "User logged in as {$user['Role']}");
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

        global $db;
        // Check if username exists using direct DB lookup
        if ($db->find('users', 'Username', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }

        // Generate ID (Simple increment)
        $count = $db->fetchOne("SELECT COUNT(*) as c FROM Users");
        $newUserId = (int)($count['c'] ?? 0) + 1;

        // Create new user with HASHED password
        $newUser = [
            'UserID' => $newUserId,
            'Username' => $username,
            'Password' => password_hash($password, PASSWORD_DEFAULT),
            'FirstName' => $firstName,
            'MiddleName' => $middleName,
            'LastName' => $lastName,
            'Role' => $role
        ];
        
        // Use save_data which handles the INSERT
        if (save_data('users', [$newUser])) {
             log_security_event($newUser['UserID'], 'Signup', 'Success', 'New user registered');
             echo json_encode(['success' => true, 'redirect' => 'index.php?page=login']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save user']);
        }
    } elseif ($action === 'add_warehouse') {
        $warehouses = get_data('warehouses');
        
        $newWarehouse = [
            'WarehouseID' => 'W' . str_pad(count($warehouses) + 1, 2, '0', STR_PAD_LEFT),
            'WarehouseName' => $_POST['warehouseName'] ?? '',
            'Location' => $_POST['location'] ?? '',
            'WarehouseType' => $_POST['warehouseType'] ?? ''
        ];
        
        $warehouses[] = $newWarehouse;
        
        if (save_data('warehouses', $warehouses)) {
            log_security_event($_SESSION['user']['UserID'], 'Warehouse', 'Success', 'Created warehouse ' . $newWarehouse['WarehouseID']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save warehouse']);
        }
    } elseif ($action === 'process_issuance') {
        $reqId = $_POST['requisitionId'] ?? '';
        $allocationPlan = json_decode($_POST['allocationPlan'] ?? '[]', true);
        
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
        
        foreach ($allocationPlan as $planItem) {
             if (empty($planItem['allocated'])) continue;

             foreach ($planItem['allocated'] as $allocatedBatch) {
                 $batchId = $allocatedBatch['BatchID'] ?? '';
                 $qtyToIssue = (int)($allocatedBatch['Quantity'] ?? 0);

                 if (!$batchId || $qtyToIssue <= 0) continue;

                 // Deduct from inventory
                 foreach ($inventory as &$batch) {
                     if ($batch['BatchID'] === $batchId) {
                         $batch['QuantityOnHand'] -= $qtyToIssue;
                         $batch['QuantityReleased'] = ($batch['QuantityReleased'] ?? 0) + $qtyToIssue;
                         
                         $newIssuance['IssuedItems'][] = [
                             'IssuanceItemID' => 'II-' . rand(10000, 99999),
                             'ItemID' => $batch['ItemID'],
                             'BatchID' => $batch['BatchID'],
                             'RequisitionItemID' => $planItem['reqItemId'] ?? null,
                             'QuantityIssued' => $qtyToIssue
                         ];
                         break;
                     }
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
        $poid = $_POST['poid'] ?? $_POST['poId'] ?? ''; 
        $items = $_POST['items'] ?? []; 
        
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
                // Generate Incremental Batch ID
                $maxId = 0;
                foreach ($inventory as $b) {
                    if (preg_match('/BATCH-(\d+)/', $b['BatchID'], $matches)) {
                        $num = (int)$matches[1];
                        if ($num > $maxId) $maxId = $num;
                    }
                }
                $batchId = 'BATCH-' . str_pad($maxId + 1, 5, '0', STR_PAD_LEFT);

                $newBatch = [
                    'BatchID' => $batchId,
                    'ItemID' => $item['itemId'],
                    'QuantityOnHand' => $qtyReceived,
                    'ExpiryDate' => $item['expiryDate'],
                    'UnitCost' => (float)$item['unitCost'],
                    'DateReceived' => date('Y-m-d'), // CRITICAL for FIFO
                    'WarehouseID' => 'W01', // Default
                    'QuantityReleased' => 0
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
        $healthCenterId = $_POST['healthCenterId'] ?? '';
        $items = $_POST['items'] ?? []; // Array of items
        
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
        $reqId = $_POST['requisitionId'] ?? '';
        $status = $_POST['status'] ?? ''; // Approved or Rejected
        
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
        $poId = $_POST['poId'] ?? '';
        $status = $_POST['status'] ?? ''; 
        
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
        $middleName = $_POST['middleName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';
        $currUser = $_SESSION['user'];
        
        $users = get_data('users');
        $updated = false;
        
        foreach ($users as &$u) {
            if ($u['UserID'] == $currUser['UserID']) {
                $u['FirstName'] = $firstName;
                $u['MiddleName'] = $middleName;
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
            if ($u['UserID'] == $currUser['UserID']) {
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
                // Return detailed debug info
                $debug = [
                    'user_id' => $currUser['UserID'],
                    'db_error_check_logs' => 'Check PHP error log for "Execute Error"'
                ];
                dump_debug_info();
                echo json_encode(['success' => false, 'message' => 'Database save failed. Debug: ' . json_encode($debug)]);
            }
        } else {
            // Debug info for "User not found"
             $debug = [
                'session_user_id' => $currUser['UserID'],
                'session_user_id_type' => gettype($currUser['UserID']),
                'users_count' => count($users)
            ];
            dump_debug_info();
            echo json_encode(['success' => false, 'message' => 'User not found in user list. Debug: ' . json_encode($debug)]);
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
    } elseif ($action === 'generate_report') {
        $reportType = $_POST['reportType'] ?? '';
        $office = $_POST['forOffice'] ?? '';
        $extraId = $_POST['poNumber'] ?? $_POST['itemSelect'] ?? ''; // PO ID or Item ID depending on type
        
        $reportId = 'REP-' . date('Ymd') . '-' . rand(1000, 9999);
        $generatedDate = date('Y-m-d H:i:s');
        $reportData = []; // This will be stored maybe in a separate table or file, or just returned for now. 
        // Note: The schema only has Report table metadata. Detailed report content requires a separate store (like ReportData table or JSON column).
        // Since schema is locked, we'll focus on saving metadata and returning display data.
        
        $inventory = get_data('inventory');
        $items = get_data('items');
        
        if ($reportType === 'inventory_valuation') {
            foreach ($items as $item) {
                $qty = 0;
                $val = 0;
                foreach ($inventory as $batch) {
                    if ($batch['ItemID'] === $item['ItemID']) {
                        $qty += $batch['QuantityOnHand'];
                        $val += ($batch['QuantityOnHand'] * $batch['UnitCost']);
                    }
                }
                if ($qty > 0) {
                    $reportData[] = [
                        'itemName' => $item['ItemName'],
                        'totalQuantity' => $qty,
                        'averageCost' => $qty > 0 ? round($val / $qty, 2) : 0,
                        'totalValue' => round($val, 2)
                    ];
                }
            }
        } elseif ($reportType === 'receipt_confirmation') {
            // Find receiving for PO
            $receivings = get_data('receivings');
            $targetRec = null;
            // Simplified: look for any receiving linked to this PO
            foreach ($receivings as $rcv) {
                if ($rcv['POID'] === $extraId) {
                    $targetRec = $rcv; 
                    break;
                }
            }
            // Populate basic info even if not found
             $reportData = [
                'poNumber' => $extraId,
                'supplierName' => 'Unknown', // Enh: join with PO/Supplier
                'receivedDate' => $targetRec['ReceivedDate'] ?? 'Pending',
                'receivedBy' => $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName'],
                'items' => $targetRec['ReceivedItems'] ?? []
            ];
        } elseif ($reportType === 'stock_card') {
             // Basic Stock Card Calculation
             $stockMoves = [];
             // 1. Receivings (In)
             $receivings = get_data('receivings');
             foreach ($receivings as $rcv) {
                 if (isset($rcv['ReceivedItems'])) {
                    foreach ($rcv['ReceivedItems'] as $ri) {
                        if (get_item_id_from_batch($ri['BatchID'], $inventory) === $extraId) {
                            $stockMoves[] = [
                                'date' => $rcv['ReceivedDate'],
                                'type' => 'Receiving',
                                'ref' => $rcv['POID'],
                                'in' => $ri['QuantityReceived'],
                                'out' => 0
                            ];
                        }
                    }
                 }
             }
             // 2. Issuances (Out)
             $issuances = get_data('issuances');
             foreach ($issuances as $iss) {
                 if (isset($iss['IssuedItems'])) {
                     foreach ($iss['IssuedItems'] as $ii) {
                         if (get_item_id_from_batch($ii['BatchID'], $inventory) === $extraId) {
                            $stockMoves[] = [
                                'date' => $iss['DateIssued'],
                                'type' => 'Issuance',
                                'ref' => $iss['RequisitionID'],
                                'in' => 0,
                                'out' => $ii['QuantityIssued']
                            ];
                         }
                     }
                 }
             }
             
             // Sort by date
             usort($stockMoves, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
             });
             
             $balance = 0;
             foreach ($stockMoves as &$move) {
                 $balance += $move['in'];
                 $balance -= $move['out'];
                 $move['balance'] = $balance;
             }
             $reportData = ['itemName' => $extraId, 'transactions' => $stockMoves];
        }

        $newReport = [
            'ReportID' => $reportId,
            'UserID' => $_SESSION['user']['UserID'],
            'ReportType' => ucwords(str_replace('_', ' ', $reportType)),
            'GeneratedDate' => $generatedDate,
            'GeneratedForOffice' => $office,
            'GeneratedByFullName' => $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName']
        ];
        
        // Save Metadata to DB
        $reports = get_data('reports'); // This now queries DB
        $reports[] = $newReport;
        save_data('reports', $reports); // This now inserts to DB
        
        // Return result with data for immediate display
        $newReport['data'] = $reportData;
        echo json_encode(['success' => true, 'report' => $newReport]);

    } elseif ($action === 'save_report') {
         // Deprecated but handled for backward compat if client sends it
         echo json_encode(['success' => true]);

    } elseif ($action === 'dispose_stock') {
        $batchId = $_POST['batchId'] ?? '';
        $qty = (int)($_POST['quantity'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        
        if (!$batchId || $qty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        global $db;
        $batch = $db->fetchOne("SELECT * FROM CentralInventoryBatch WHERE BatchID = ?", [$batchId]);
        
        if (!$batch || $batch['QuantityOnHand'] < $qty) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
            exit;
        }
        
        // Handle photo upload
        $photoPath = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/adjustments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF allowed.']);
                exit;
            }
            
            if ($_FILES['photo']['size'] > 5 * 1024 * 1024) { // 5MB limit
                echo json_encode(['success' => false, 'message' => 'File too large. Maximum 5MB allowed.']);
                exit;
            }
            
            $fileName = 'disposal_' . uniqid() . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                $photoPath = 'uploads/adjustments/' . $fileName;
            }
        }
        
        $db->beginTransaction();
        try {
            // Update Inventory
            $db->execute("UPDATE CentralInventoryBatch SET QuantityOnHand = QuantityOnHand - ? WHERE BatchID = ?", [$qty, $batchId]);
            
            // Create Notice Of Issue
            $issueId = 'ISS-' . uniqid();
            $db->execute(
                "INSERT INTO NoticeOfIssue (IssueID, BatchID, UserID, ReportDate, IssueType, QuantityAffected, Remarks, StatusType, PhotoPath) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $issueId,
                    $batchId,
                    $_SESSION['user']['UserID'],
                    date('Y-m-d H:i:s'),
                    $reason,
                    $qty,
                    $remarks,
                    'Open',
                    $photoPath
                ]
            );
            
            $db->commit();
            logTransaction('Stock Disposal', 'NoticeOfIssue', $issueId);
             echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'update_adjustment') {
        $adjustmentId = $_POST['adjustmentId'] ?? '';
        $adjustmentType = $_POST['adjustmentType'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        
        if (!$adjustmentId || !$adjustmentType) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        global $db;
        
        try {
            if ($adjustmentType === 'Disposal') {
                // Handle photo upload if provided
                $photoPath = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/uploads/adjustments/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
                        exit;
                    }
                    
                    if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
                        echo json_encode(['success' => false, 'message' => 'File too large']);
                        exit;
                    }
                    
                    $fileName = 'disposal_' . uniqid() . '.' . $fileExtension;
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                        $photoPath = 'uploads/adjustments/' . $fileName;
                    }
                }
                
                // Update NoticeOfIssue
                if ($photoPath) {
                    $db->execute(
                        "UPDATE NoticeOfIssue SET QuantityAffected = ?, Remarks = ?, PhotoPath = ? WHERE IssueID = ?",
                        [$quantity, $reason, $photoPath, $adjustmentId]
                    );
                } else {
                    $db->execute(
                        "UPDATE NoticeOfIssue SET QuantityAffected = ?, Remarks = ? WHERE IssueID = ?",
                        [$quantity, $reason, $adjustmentId]
                    );
                }
            } else {
                // Update RequisitionAdjustment
                $db->execute(
                    "UPDATE RequisitionAdjustment SET Reason = ? WHERE RequisitionAdjustmentID = ?",
                    [$reason, $adjustmentId]
                );
            }
            
            logTransaction('Updated Adjustment', $adjustmentType, $adjustmentId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'return_stock') {
        $reqId = $_POST['requisitionId'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if (!$reqId) {
             echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
             exit;
        }
        
        global $db;
        $adjId = 'RADJ-' . uniqid();
        
        // Just create record for now as specific item details aren't passed
        $res = $db->execute(
            "INSERT INTO RequisitionAdjustment (RequisitionAdjustmentID, IssuanceID, UserID, AdjustmentType, AdjustmentDate, Reason) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $adjId,
                null, // No issuance ID linked directly in form, could query via ReqID if needed
                $_SESSION['user']['UserID'],
                'Return',
                date('Y-m-d H:i:s'),
                $reason
            ]
        );
        
        if ($res) {
             logTransaction('Stock Return Request', 'RequisitionAdjustment', $adjId);
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'add_item') {
        $itemName = $_POST['itemName'] ?? '';
        $itemType = $_POST['itemType'] ?? '';
        $unit = $_POST['unitOfMeasure'] ?? '';
        
        $items = get_data('items');
        
        // Generate ItemID (I0001)
        $maxId = 0;
        foreach ($items as $i) {
            if (preg_match('/I(\d+)/', $i['ItemID'], $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxId) $maxId = $num;
            }
        }
        $newItemId = 'I' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
        
        $newItem = [
            'ItemID' => $newItemId,
            'ItemName' => $itemName,
            'ItemType' => $itemType,
            'UnitOfMeasure' => $unit
        ];
        
        $items[] = $newItem;
        
        if (save_data('items', $items)) {
             log_security_event($_SESSION['user']['UserID'], 'Item', 'Success', "Added item $newItemId");
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save item']);
        }
        
    } elseif ($action === 'update_item') {
        $itemId = $_POST['itemId'] ?? '';
        $itemName = $_POST['itemName'] ?? '';
        $itemType = $_POST['itemType'] ?? '';
        $unit = $_POST['unitOfMeasure'] ?? '';
        
        $items = get_data('items');
        $updated = false;
        
        foreach ($items as &$i) {
            if ($i['ItemID'] === $itemId) {
                $i['ItemName'] = $itemName;
                $i['ItemType'] = $itemType;
                $i['UnitOfMeasure'] = $unit;
                $updated = true;
                break;
            }
        }
        
        if ($updated && save_data('items', $items)) {
             log_security_event($_SESSION['user']['UserID'], 'Item', 'Success', "Updated item $itemId");
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to update item']);
        }

    } elseif ($action === 'delete_item') {
        $itemId = $_POST['itemId'] ?? '';
        
        $items = get_data('items');
        $newItems = [];
        $found = false;
        
        foreach ($items as $i) {
            if ($i['ItemID'] === $itemId) {
                $found = true;
                continue; // Skip logic to delete
            }
            $newItems[] = $i;
        }
        
        if ($found) {
            if (save_data('items', $newItems)) {
                 log_security_event($_SESSION['user']['UserID'], 'Item', 'Success', "Deleted item $itemId");
                 echo json_encode(['success' => true]);
            } else {
                 echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
            }
        } else {
             echo json_encode(['success' => false, 'message' => 'Item not found']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}

function dump_debug_info() {
    global $db;
    try {
        $users = $db->fetchAll("SELECT * FROM Users");
        $out = "Date: " . date('Y-m-d H:i:s') . "\n";
        $out .= "Session User: " . print_r($_SESSION['user'] ?? 'No Session', true) . "\n\n";
        $out .= "DB Users:\n";
        foreach ($users as $u) {
             $out .= "ID: " . $u['UserID'] . " (" . gettype($u['UserID']) . ")\n";
             $out .= "User: " . $u['Username'] . "\n";
             $out .= "PassHash: " . substr($u['Password'] ?? '', 0, 15) . "...\n";
             $out .= "------------------\n";
        }
        file_put_contents(__DIR__ . '/debug_dump.txt', $out);
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/debug_dump.txt', "Error dumping debug info: " . $e->getMessage());
    }
}
