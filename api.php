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
        if ($db->find('users', 'Username', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }

        $newUser = [
            'Username' => $username,
            'Password' => password_hash($password, PASSWORD_DEFAULT),
            'FirstName' => $firstName,
            'MiddleName' => $middleName,
            'LastName' => $lastName,
            'Role' => $role
        ];
        
        if (save_data('users', [$newUser])) {
             // UserID is unknown here unless we fetch or refactor save_data to return it.
             // For logs, we can just say 'New User'.
             log_security_event('system', 'Signup', 'Success', "New user registered: $username");
             echo json_encode(['success' => true, 'redirect' => 'index.php?page=login']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save user']);
        }
    } elseif ($action === 'add_warehouse') {
        $newWarehouse = [
            'WarehouseName' => $_POST['warehouseName'] ?? '',
            'Location' => $_POST['location'] ?? '',
            'WarehouseType' => $_POST['warehouseType'] ?? ''
        ];
        
        if (save_data('warehouses', [$newWarehouse])) {
            log_security_event($_SESSION['user']['UserID'], 'Warehouse', 'Success', 'Created warehouse ' . $newWarehouse['WarehouseName']);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save warehouse']);
        }

    } elseif ($action === 'add_supplier') {
        $name = $_POST['name'] ?? '';
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Supplier name is required']);
            exit;
        }

        $newSupplier = [
            'Name' => $name,
            'Address' => $_POST['address'] ?? '',
            'ContactInfo' => $_POST['contactInfo'] ?? ''
        ];
        
        if (save_data('suppliers', [$newSupplier])) {
            log_security_event($_SESSION['user']['UserID'], 'Supplier', 'Success', 'Created supplier ' . $name);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save supplier']);
        }

    } elseif ($action === 'process_issuance') {
        $reqId = $_POST['requisitionId'] ?? '';
        $allocationPlan = json_decode($_POST['allocationPlan'] ?? '[]', true);
        
        if (!$allocationPlan) {
            echo json_encode(['success' => false, 'message' => 'Invalid allocation plan']);
            exit;
        }
        
        $inventory = get_data('inventory');
        $issuances = []; // To be saved
        
        $newIssuance = [
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

                 // Deduct from inventory (in memory, then saved)
                 foreach ($inventory as &$batch) {
                     if ($batch['BatchID'] == $batchId) { // Loose comparison as ID might be int or string from JSON
                         $batch['QuantityOnHand'] -= $qtyToIssue;
                         $batch['QuantityReleased'] = ($batch['QuantityReleased'] ?? 0) + $qtyToIssue;
                         
                         $newIssuance['IssuedItems'][] = [
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
        
        // Update Requisition Status
        $requisitions = get_data('requisitions');
        foreach ($requisitions as &$r) {
            if ($r['RequisitionID'] == $reqId) {
                $r['StatusType'] = 'Completed';
                break;
            }
        }
        
        if (save_data('inventory', $inventory) && save_data('issuances', $issuances) && save_data('requisitions', $requisitions)) {
             logTransaction('Issued Items', 'Requisition', $reqId);
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to process issuance']);
        }

    } elseif ($action === 'receive_items') {
        $poid = $_POST['poid'] ?? $_POST['poId'] ?? ''; 
        $items = $_POST['items'] ?? []; 
        
        $receivings = [];
        $newReceiving = [
            'POID' => $poid,
            'ReceivedDate' => date('Y-m-d H:i:s'),
            'UserID' => $_SESSION['user']['UserID'],
            'ReceivedItems' => []
        ];
        
        foreach ($items as $item) {
            $qtyReceived = (float)$item['quantityReceived'];
            if ($qtyReceived > 0) {
                $newReceiving['ReceivedItems'][] = [
                    'ItemID' => $item['itemId'],
                    'QuantityOnHand' => $qtyReceived,
                    'ExpiryDate' => $item['expiryDate'], // Assuming expiry is passed from UI
                    'UnitCost' => (float)$item['unitCost'],
                    'DateReceived' => date('Y-m-d'),
                    'WarehouseID' => 1 // Default warehouse
                ];
            }
        }
        
        $receivings[] = $newReceiving;
        
        // Update PO Status
        $purchaseOrders = get_data('purchase_orders');
        foreach ($purchaseOrders as &$po) {
            if ($po['POID'] == $poid) {
                $po['StatusType'] = 'Completed'; 
                break;
            }
        }
        
        if (save_data('purchase_orders', $purchaseOrders) && save_data('receivings', $receivings)) {
             log_security_event($_SESSION['user']['UserID'], 'Receiving', 'Success', "Received items for PO $poid");
             logTransaction('Received Items', 'Purchase Order', $poid);
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save updates']);
        }

    } elseif ($action === 'create_requisition') {
        $healthCenterId = $_POST['healthCenterId'] ?? '';
        $healthCenterName = $_POST['healthCenterName'] ?? '';
        $healthCenterAddress = $_POST['healthCenterAddress'] ?? '';
        $items = $_POST['items'] ?? []; 
        
        $newReq = [
            'HealthCenterID' => $healthCenterId,
            'HealthCenterName' => $healthCenterName,
            'HealthCenterAddress' => $healthCenterAddress,
            'UserID' => $_SESSION['user']['UserID'],
            'RequestDate' => date('Y-m-d H:i:s'),
            'StatusType' => 'Pending',
            'RequisitionItems' => []
        ];
        
        foreach($items as $i) {
            if((int)$i['quantity'] > 0) {
                $newReq['RequisitionItems'][] = [
                    'ItemID' => $i['itemId'],
                    'QuantityRequested' => (int)$i['quantity']
                ];
            }
        }
        
        if (empty($newReq['RequisitionItems'])) {
            echo json_encode(['success' => false, 'message' => 'No valid items requested']);
            exit;
        }
        
        if (save_data('requisitions', [$newReq])) {
            log_security_event($_SESSION['user']['UserID'], 'Requisition', 'Success', "Created requisition");
            echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save requisition']);
        }

    } elseif ($action === 'create_purchase_order') {
        // Validate user is logged in
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            exit;
        }
        
        $supplierId = $_POST['supplierId'] ?? '';
        $warehouseId = $_POST['warehouseId'] ?? ''; // Expecting warehouseId now
        $items = $_POST['items'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $expiryDates = $_POST['expiryDates'] ?? [];
        
        $supplierName = $_POST['supplierName'] ?? '';
        $supplierAddress = $_POST['supplierAddress'] ?? '';

        if (empty($supplierId) && empty($supplierName)) {
            echo json_encode(['success' => false, 'message' => 'Supplier is required (Select one or enter name)']);
            exit;
        }
        
        if (empty($items) || empty($quantities)) {
            echo json_encode(['success' => false, 'message' => 'At least one item is required']);
            exit;
        }
        
        $poItems = [];
        foreach ($items as $index => $itemId) {
            if (!empty($itemId) && !empty($quantities[$index]) && $quantities[$index] > 0) {
                $poItems[] = [
                    'ItemID' => $itemId,
                    'QuantityOrdered' => (int)$quantities[$index],
                    'UnitCost' => 0, // Should be filled if known
                    'ExpiryDate' => !empty($expiryDates[$index]) ? $expiryDates[$index] : null
                ];
            }
        }
        
        if (empty($poItems)) {
            echo json_encode(['success' => false, 'message' => 'No valid items to order']);
            exit;
        }
        
        $newPO = [
            'UserID' => $_SESSION['user']['UserID'],
            'SupplierID' => $supplierId,
            'SupplierName' => $supplierName,
            'SupplierAddress' => $supplierAddress,
            'WarehouseID' => !empty($warehouseId) ? $warehouseId : 1, // Default to 1 if empty
            'PODate' => date('Y-m-d\TH:i:s\Z'),
            'StatusType' => 'Pending',
            'PurchaseOrderItems' => $poItems
        ];
        
        if (save_data('purchase_orders', [$newPO])) {
            log_security_event($_SESSION['user']['UserID'], 'Purchase Order', 'Success', 'Created New PO');
            echo json_encode([
                'success' => true,
                'message' => 'Purchase order created successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save purchase order']);
        }

    } elseif ($action === 'update_po_status') {
        $poId = $_POST['poId'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (!$poId || !$status) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        $allowed = ['Approved', 'Rejected', 'Pending', 'Completed'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        global $db;
        $res = $db->execute("UPDATE PurchaseOrder SET StatusType = ? WHERE POID = ?", [$status, $poId]);
        
        if ($res) {
            log_security_event($_SESSION['user']['UserID'], 'Purchase Order', 'Success', "Updated PO $poId status to $status");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'update_requisition_status') {
        $reqId = $_POST['requisitionId'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if (!$reqId || !$status) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        $allowed = ['Approved', 'Rejected', 'Pending', 'Completed'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        global $db;
        $res = $db->execute("UPDATE Requisition SET StatusType = ? WHERE RequisitionID = ?", [$status, $reqId]);
        
        if ($res) {
            // Log Approval
            $db->execute(
                "INSERT INTO ApprovalLog (RequisitionID, UserID, Decision, DecisionDate) VALUES (?, ?, ?, ?)",
                [$reqId, $_SESSION['user']['UserID'], $status, date('Y-m-d H:i:s')]
            );
            
            log_security_event($_SESSION['user']['UserID'], 'Requisition', 'Success', "Updated Requisition $reqId status to $status");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'generate_report') {
        $reportType = $_POST['reportType'] ?? '';
        $office = $_POST['forOffice'] ?? '';
        $extraId = $_POST['poNumber'] ?? $_POST['itemSelect'] ?? ''; // PO ID or Item ID depending on type
        
        $reportData = [];
        $inventory = get_data('inventory');
        $items = get_data('items');
        
        if ($reportType === 'inventory_valuation') {
            foreach ($items as $item) {
                $qty = 0;
                $val = 0;
                foreach ($inventory as $batch) {
                    if ($batch['ItemID'] == $item['ItemID']) {
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
            $receivings = get_data('receivings');
            $targetRec = null;
            foreach ($receivings as $rcv) {
                if ($rcv['POID'] == $extraId) {
                    $targetRec = $rcv; 
                    break;
                }
            }
             $reportData = [
                'poNumber' => $extraId,
                'supplierName' => 'Unknown',
                'receivedDate' => $targetRec['ReceivedDate'] ?? 'Pending',
                'receivedBy' => $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName'],
                'items' => $targetRec['ReceivedItems'] ?? []
            ];
        } elseif ($reportType === 'stock_card' || $reportType === 'stock_card_ledger') {
             $stockMoves = [];
             $receivings = get_data('receivings');
             $issuances = get_data('issuances');
             $adjustments = get_data('adjustment_logs'); // Get returns too
             
             // 1. Get Item info
             $targetItem = $db->fetchOne("SELECT * FROM Item WHERE ItemID = ?", [$extraId]);
             $itemName = $targetItem ? $targetItem['ItemName'] : "Item #$extraId";
             $itemType = $targetItem ? $targetItem['ItemType'] : "N/A";

             // 2. Get Current Batches
             $batches = $db->fetchAll("SELECT * FROM CentralInventoryBatch WHERE ItemID = ? AND QuantityOnHand > 0", [$extraId]);
             $formattedBatches = array_map(function($b) {
                return [
                    'batchId' => $b['BatchID'],
                    'quantity' => $b['QuantityOnHand'],
                    'expiry' => $b['ExpiryDate'],
                    'cost' => (float)$b['UnitCost']
                ];
             }, $batches);

             // 3. Transactions: Receivings
             foreach ($receivings as $rcv) {
                 if (isset($rcv['ReceivedItems'])) {
                    foreach ($rcv['ReceivedItems'] as $ri) {
                        $batchBatch = array_filter($inventory, fn($b) => $b['BatchID'] == $ri['BatchID']);
                        $batchBatch = reset($batchBatch);
                        if ($batchBatch && $batchBatch['ItemID'] == $extraId) {
                            $stockMoves[] = [
                                'date' => $rcv['ReceivedDate'],
                                'type' => 'Receiving',
                                'ref' => 'PO #' . ($rcv['PONumber'] ?? $rcv['POID']),
                                'batch' => $ri['BatchID'],
                                'in' => $ri['QuantityReceived'],
                                'out' => 0
                            ];
                        }
                    }
                 }
             }

             // 4. Transactions: Issuances
             foreach ($issuances as $iss) {
                 if (isset($iss['IssuedItems'])) {
                     foreach ($iss['IssuedItems'] as $ii) {
                         $batchBatch = array_filter($inventory, fn($b) => $b['BatchID'] == $ii['BatchID']);
                         $batchBatch = reset($batchBatch);
                         if ($batchBatch && $batchBatch['ItemID'] == $extraId) {
                            $stockMoves[] = [
                                'date' => $iss['DateIssued'],
                                'type' => 'Issuance',
                                'ref' => 'REQ #' . ($iss['RequisitionNumber'] ?? $iss['RequisitionID']),
                                'batch' => $ii['BatchID'],
                                'in' => 0,
                                'out' => $ii['QuantityIssued']
                            ];
                         }
                     }
                 }
             }
             
             // 5. Transactions: Adjustments (Returns/Disposals)
             foreach ($adjustments as $adj) {
                 // For now, only handle Returns that affect this item
                 if ($adj['Type'] === 'Return') {
                    // We need to check if this adjustment affects the target item
                    // Fetch details if not present
                    $details = $adj['Details'] ?? [];
                    foreach ($details as $d) {
                        if ($d['ItemID'] == $extraId) {
                            $stockMoves[] = [
                                'date' => $adj['Date'],
                                'type' => 'Return',
                                'ref' => $adj['Reference'],
                                'batch' => $d['BatchID'],
                                'in' => $d['QuantityAdjusted'],
                                'out' => 0
                            ];
                        }
                    }
                 }
             }

             usort($stockMoves, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
             });

             $balance = 0;
             foreach ($stockMoves as &$move) {
                 $balance += $move['in'];
                 $balance -= $move['out'];
                 $move['balance'] = $balance;
             }

             $reportData = [
                'itemName' => $itemName,
                'itemType' => $itemType,
                'currentBalance' => $balance,
                'batches' => $formattedBatches,
                'transactions' => $stockMoves
             ];
        }

        $generatedDate = date('Y-m-d H:i:s');
        $db->execute(
            "INSERT INTO Report (UserID, ReportType, GeneratedDate, GeneratedForOffice) VALUES (?, ?, ?, ?)",
            [$_SESSION['user']['UserID'], ucwords(str_replace('_', ' ', $reportType)), $generatedDate, $office]
        );
        $newReportID = $db->lastInsertId();
        
        $reportResponse = [
            'ReportID' => $newReportID,
            'UserID' => $_SESSION['user']['UserID'],
            'ReportType' => ucwords(str_replace('_', ' ', $reportType)),
            'GeneratedDate' => $generatedDate,
            'GeneratedForOffice' => $office,
            'GeneratedByFullName' => $_SESSION['user']['FirstName'] . ' ' . $_SESSION['user']['LastName'],
            'data' => $reportData
        ];
        
        echo json_encode(['success' => true, 'report' => $reportResponse]);

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
            $db->execute(
                "INSERT INTO NoticeOfIssue (BatchID, UserID, ReportDate, IssueType, QuantityAffected, Remarks, StatusType, PhotoPath) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
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
            $newIssueId = $db->lastInsertId();
            
            $db->commit();
            logTransaction('Stock Disposal', 'NoticeOfIssue', $newIssueId);
             echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'add_requisition_adjustment') {
        $issuanceId = $_POST['issuanceId'] ?? '';
        $adjustmentType = $_POST['adjustmentType'] ?? 'Damaged'; // e.g., Damaged, Lost, Quality Issue
        $reason = $_POST['reason'] ?? '';
        $items = json_decode($_POST['items'] ?? '[]', true); // Array of {BatchID, Quantity}

        if (!$issuanceId || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Issuance ID and items are required']);
            exit;
        }

        global $db;
        $db->beginTransaction();
        try {
            // 1. Create RequisitionAdjustment
            $db->execute(
                "INSERT INTO RequisitionAdjustment (IssuanceID, UserID, AdjustmentType, AdjustmentDate, Reason) 
                 VALUES (?, ?, ?, ?, ?)",
                [$issuanceId, $_SESSION['user']['UserID'], $adjustmentType, date('Y-m-d H:i:s'), $reason]
            );
            $adjustmentId = $db->lastInsertId();

            // 2. Create RequisitionAdjustmentDetail for each item
            foreach ($items as $item) {
                $batchId = $item['batchId'] ?? $item['BatchID'] ?? null;
                $qty = (int)($item['quantity'] ?? $item['Quantity'] ?? 0);

                if (!$batchId || $qty <= 0) continue;

                $db->execute(
                    "INSERT INTO RequisitionAdjustmentDetail (RequisitionAdjustmentID, BatchID, QuantityAdjusted) 
                     VALUES (?, ?, ?)",
                    [$adjustmentId, $batchId, $qty]
                );
            }

            $db->commit();
            logTransaction('Added Requisition Adjustment', 'RequisitionAdjustment', $adjustmentId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }

    } elseif ($action === 'update_adjustment') {

    } elseif ($action === 'return_stock') {
        $reqId = $_POST['requisitionId'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $items = json_decode($_POST['items'] ?? '[]', true); // [{batchId, quantity}]
        
        if (!$reqId) {
             echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
             exit;
        }
        
        global $db;
        $db->beginTransaction();
        try {
            // Find Issuance for this Requisition
            $issuance = $db->fetchOne("SELECT IssuanceID FROM Issuance WHERE RequisitionID = ?", [$reqId]);
            $issuanceId = $issuance ? $issuance['IssuanceID'] : null;

            // 1. Create RequisitionAdjustment
            $db->execute(
                "INSERT INTO RequisitionAdjustment (IssuanceID, UserID, AdjustmentType, AdjustmentDate, Reason) 
                 VALUES (?, ?, ?, ?, ?)",
                [$issuanceId, $_SESSION['user']['UserID'], 'Return', date('Y-m-d H:i:s'), $reason]
            );
            $adjustmentId = $db->lastInsertId();

            // 2. Create Details and Update Inventory
            foreach ($items as $item) {
                $batchId = $item['batchId'] ?? $item['BatchID'] ?? null;
                $qty = (int)($item['quantity'] ?? $item['Quantity'] ?? 0);

                if (!$batchId || $qty <= 0) continue;

                $db->execute(
                    "INSERT INTO RequisitionAdjustmentDetail (RequisitionAdjustmentID, BatchID, QuantityAdjusted) 
                     VALUES (?, ?, ?)",
                    [$adjustmentId, $batchId, $qty]
                );

                // For Returns, we increase inventory again
                $db->execute(
                    "UPDATE CentralInventoryBatch 
                     SET QuantityOnHand = QuantityOnHand + ?, QuantityReleased = QuantityReleased - ? 
                     WHERE BatchID = ?",
                    [$qty, $qty, $batchId]
                );
            }

            $db->commit();
            logTransaction('Stock Return', 'RequisitionAdjustment', $adjustmentId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
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
            if ($i['ItemID'] == $itemId) {
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
            if ($i['ItemID'] == $itemId) {
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
