<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

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
            
            // Handle Health Center Context (Pre-assigned at Signup)
            if ($user['Role'] === 'Health Center Staff' && empty($user['HealthCenterID'])) {
                echo json_encode(['success' => false, 'message' => 'No health center assigned to this account. Please contact an administrator.']);
                exit;
            }

            $_SESSION['user'] = $user;
            
            // Proactively trigger provisioning if a health center is assigned
            if (isset($user['HealthCenterID'])) {
                Database::getHCConnection($user['HealthCenterID']);
            }

            log_security_event($user['UserID'], 'Login', 'Success', "User logged in as {$user['Role']}");
            echo json_encode(['success' => true, 'redirect' => 'index.php?page=dashboard']);
        } else {
            // Log failed attempt
            log_security_event('unknown', 'Login', 'Failure', "Failed login attempt for username: $username");
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        }
    } elseif ($action === 'switch_health_center') {
        if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'Administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $hcId = $_POST['healthCenterId'] ?? null;
        if ($hcId === 'none') {
            unset($_SESSION['user']['HealthCenterID']);
        } else {
            $_SESSION['user']['HealthCenterID'] = $hcId;
        }
        echo json_encode(['success' => true]);
        exit;
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
        $role = $_POST['role'] ?? 'User';
        $healthCenterId = $_POST['healthCenterId'] ?? null;
        $isNewHC = ($_POST['isNewHC'] ?? '0') === '1';

        // 1. Handle New Health Center if requested
        if ($isNewHC && $role === 'Health Center Staff') {
            $newHCName = $_POST['newHCName'] ?? '';
            $newHCAddress = $_POST['newHCAddress'] ?? '';
            if (!empty($newHCName)) {
                $hcData = [
                    'Name' => $newHCName,
                    'Address' => $newHCAddress
                ];
                if (save_data('health_centers', [$hcData])) {
                    global $db;
                    $healthCenterId = $db->lastInsertId();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create new health center']);
                    exit;
                }
            }
        }

        global $db;
        if ($db->find('users', 'Username', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }

        $newUser = [
            'Username' => $username,
            'Password' => password_hash($password, PASSWORD_DEFAULT),
            'FName' => $firstName,
            'MName' => $middleName,
            'LName' => $lastName,
            'Role' => $role,
            'HealthCenterID' => $healthCenterId
        ];
        
        if (save_data('users', [$newUser])) {
             // Proactively trigger provisioning if a health center is assigned
             if ($healthCenterId) {
                Database::getHCConnection($healthCenterId);
             }

             // UserID is unknown here unless we fetch or refactor save_data to return it.
             // For logs, we can just say 'New User'.
             log_security_event('system', 'Signup', 'Success', "New user registered: $username");
             echo json_encode(['success' => true, 'redirect' => 'index.php?page=login']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save user']);
        }
    } elseif ($action === 'check_username') {
        $username = $_POST['username'] ?? '';
        global $db;
        $user = $db->find('users', 'Username', $username);
        if ($user) {
            echo json_encode([
                'success' => true, 
                'healthCenterId' => $user['HealthCenterID'] ?? '',
                'role' => $user['Role'] ?? ''
            ]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
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
                    'ExpiryDate' => !empty($item['expiryDate']) ? $item['expiryDate'] : null,
                    'UnitCost' => (float)$item['unitCost'],
                    'DateReceived' => date('Y-m-d'),
                    'WarehouseID' => 1 // Default warehouse
                ];
            }
        }
        
        $receivings[] = $newReceiving;
        
        // Update PO Status
        $procurementOrders = get_data('procurement_orders');
        foreach ($procurementOrders as &$po) {
            if ($po['POID'] == $poid) {
                $po['StatusType'] = 'Completed'; 
                break;
            }
        }
        
        if (save_data('procurement_orders', $procurementOrders) && save_data('receivings', $receivings)) {
             log_security_event($_SESSION['user']['UserID'], 'Receiving', 'Success', "Received items for PO $poid");
             logTransaction('Received Items', 'Procurement Order', $poid);
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to save updates']);
        }

    } elseif ($action === 'create_requisition') {
        // ... (existing code for central requisitions)
        try {
            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['UserID'])) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                exit;
            }

            $healthCenterId = $_POST['healthCenterId'] ?? '';
            $healthCenterName = $_POST['healthCenterName'] ?? '';
            $healthCenterAddress = $_POST['healthCenterAddress'] ?? '';
            $items = $_POST['items'] ?? []; 
            
            if (empty($healthCenterId) && empty($healthCenterName)) {
                echo json_encode(['success' => false, 'message' => 'Health center is required']);
                exit;
            }
            
            if (empty($items) || !is_array($items)) {
                echo json_encode(['success' => false, 'message' => 'No items provided']);
                exit;
            }
            
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
                if(isset($i['quantity']) && (int)$i['quantity'] > 0 && isset($i['itemId'])) {
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
                log_security_event($_SESSION['user']['UserID'], 'Requisition', 'Success', "Created requisition with " . count($newReq['RequisitionItems']) . " items");
                echo json_encode(['success' => true, 'message' => 'Requisition created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save requisition to database']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server error occurred while saving requisition']);
        }

    } elseif ($action === 'create_local_requisition') {
        try {
            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['UserID'])) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                exit;
            }

            $user = $_SESSION['user'];
            $hcId = $user['HealthCenterID'] ?? null;
            if (!$hcId) {
                echo json_encode(['success' => false, 'message' => 'No health center assigned to your account']);
                exit;
            }

            $hcConn = Database::getHCConnection($hcId);
            if (!$hcConn) {
                echo json_encode(['success' => false, 'message' => 'Could not connect to health center database']);
                exit;
            }

            $staffName = $_POST['staffName'] ?? '';
            $items = $_POST['items'] ?? [];

            if (empty($staffName)) {
                echo json_encode(['success' => false, 'message' => 'Staff name is required']);
                exit;
            }

            // 1. Create or Find Staff in local DB
            $stmt = $hcConn->prepare("SELECT StaffID FROM hc_staff WHERE FirstName = ? LIMIT 1");
            $stmt->execute([$staffName]);
            $staff = $stmt->fetch();
            
            if ($staff) {
                $staffId = $staff['StaffID'];
            } else {
                $stmt = $hcConn->prepare("INSERT INTO hc_staff (FirstName, LastName, Role, Username, Password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$staffName, '', 'Staff', strtolower(str_replace(' ', '.', $staffName)), 'nopass']);
                $staffId = $hcConn->lastInsertId();
            }

            // 2. Create local requisition
            $stmt = $hcConn->prepare("INSERT INTO hc_requisition (StaffID, RequestDate, StatusType) VALUES (?, ?, ?)");
            $stmt->execute([$staffId, date('Y-m-d H:i:s'), 'Pending']);
            $localReqId = $hcConn->lastInsertId();

            // 3. Add items
            foreach ($items as $item) {
                $stmt = $hcConn->prepare("INSERT INTO hc_requisitionitem (HCRequisitionID, ItemID, QuantityRequested) VALUES (?, ?, ?)");
                $stmt->execute([$localReqId, $item['itemId'], $item['quantity']]);
            }

            echo json_encode(['success' => true, 'message' => 'Local requisition created successfully']);
            
        } catch (Exception $e) {
            error_log("Local Req Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }

    } elseif ($action === 'create_procurement_order') {
        // Validate user is logged in
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            exit;
        }
        
        $supplierId = $_POST['supplierId'] ?? '';
        $healthCenterId = $_POST['healthCenterId'] ?? ''; // Changed from WarehouseID
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
            'SupplierName' => $supplierName, // Kept for logic if db.php uses it
            'SupplierAddress' => $supplierAddress, // Kept for logic if db.php uses it
            'HealthCenterID' => !empty($healthCenterId) ? $healthCenterId : null,
            'ContractNumber' => $_POST['contractNumber'] ?? null,
            'ContractStartDate' => $_POST['contractStartDate'] ?? null,
            'ContractEndDate' => $_POST['contractEndDate'] ?? null,
            'ContractAmount' => $_POST['contractAmount'] ?? null,
            'DocumentType' => $_POST['documentType'] ?? 'PO',
            'PODate' => date('Y-m-d\TH:i:s\Z'),
            'StatusType' => 'Pending',
            'ProcurementOrderItems' => $poItems
        ];
        
        if (save_data('procurement_orders', [$newPO])) {
            log_security_event($_SESSION['user']['UserID'], 'Procurement Order', 'Success', 'Created New PO');
            echo json_encode([
                'success' => true,
                'message' => 'Procurement order created successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save procurement order']);
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
        $res = $db->execute("UPDATE ProcurementOrder SET StatusType = ? WHERE POID = ?", [$status, $poId]);
        
        if ($res) {
            log_security_event($_SESSION['user']['UserID'], 'Procurement Order', 'Success', "Updated PO $poId status to $status");
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
            
            log_security_event($_SESSION['user']['UserID'], 'Requisition', 'Success', "Updated requisition $reqId status to $status");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'process_issuance') {
        try {
            $requisitionId = $_POST['requisitionId'] ?? '';
            $allocationPlanJson = $_POST['allocationPlan'] ?? '';
            
            if (!$requisitionId || !$allocationPlanJson) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            $allocationPlan = json_decode($allocationPlanJson, true);
            if (!$allocationPlan) {
                echo json_encode(['success' => false, 'message' => 'Invalid allocation plan']);
                exit;
            }
            
            global $db;
            
            // Get Requisition info for HealthCenter link
            $req = $db->fetchOne("SELECT HealthCenterID FROM Requisition WHERE RequisitionID = ?", [$requisitionId]);
            $hcId = $req['HealthCenterID'] ?? null;
            $hcConn = $hcId ? Database::getHCConnection($hcId) : null;

            // 1. Create Issuance record
            $db->execute(
                "INSERT INTO Issuance (RequisitionID, UserID, IssueDate, StatusType) VALUES (?, ?, ?, ?)",
                [$requisitionId, $_SESSION['user']['UserID'], date('Y-m-d H:i:s'), 'Issued']
            );
            $issuanceId = $db->lastInsertId();
            
            // 2. Process each item allocation
            foreach ($allocationPlan as $itemPlan) {
                $reqItemId = $itemPlan['reqItemId'];
                
                // Get ItemID from RequisitionItem
                $ri = $db->fetchOne("SELECT ItemID FROM RequisitionItem WHERE RequisitionItemID = ?", [$reqItemId]);
                $itemId = $ri['ItemID'] ?? null;

                foreach ($itemPlan['allocated'] as $allocation) {
                    $batchId = $allocation['BatchID'];
                    $quantity = $allocation['Quantity'];
                    
                    // 2a. Create IssuanceItem record
                    $db->execute(
                        "INSERT INTO IssuanceItem (IssuanceID, BatchID, RequisitionItemID, QuantityIssued) VALUES (?, ?, ?, ?)",
                        [$issuanceId, $batchId, $reqItemId, $quantity]
                    );
                    
                    // 2b. Update inventory batch (decrease QuantityOnHand, increase QuantityReleased)
                    $db->execute(
                        "UPDATE CentralInventoryBatch 
                         SET QuantityOnHand = QuantityOnHand - ?, 
                             QuantityReleased = QuantityReleased + ? 
                         WHERE BatchID = ?",
                        [$quantity, $quantity, $batchId]
                    );

                    // 2c. Sync to Health Center Database if exists
                    if ($hcConn && $itemId) {
                        try {
                            // Check if item batch already exists in HC inventory
                            $stmt = $hcConn->prepare("SELECT InventoryID FROM HC_Inventory WHERE BatchID = ?");
                            $stmt->execute([$batchId]);
                            $exists = $stmt->fetch();

                            if ($exists) {
                                $stmt = $hcConn->prepare("UPDATE HC_Inventory SET QuantityOnHand = QuantityOnHand + ? WHERE InventoryID = ?");
                                $res = $stmt->execute([$quantity, $exists['InventoryID']]);
                                if (!$res) error_log("Failed to update HC_Inventory for BatchID $batchId");
                            } else {
                                // Get expiry from main batch
                                $mainBatch = $db->fetchOne("SELECT ExpiryDate FROM CentralInventoryBatch WHERE BatchID = ?", [$batchId]);
                                $expiry = $mainBatch['ExpiryDate'] ?? null;

                                $stmt = $hcConn->prepare("INSERT INTO HC_Inventory (ItemID, BatchID, QuantityOnHand, ExpiryDate) VALUES (?, ?, ?, ?)");
                                $res = $stmt->execute([$itemId, $batchId, $quantity, $expiry]);
                                if (!$res) error_log("Failed to insert into HC_Inventory for BatchID $batchId");
                            }
                        } catch (PDOException $e) {
                            error_log("HC Sync Error (Batch $batchId): " . $e->getMessage());
                        }
                    }
                }
            }
            
            // 3. Update requisition status to Completed
            $db->execute(
                "UPDATE Requisition SET StatusType = ? WHERE RequisitionID = ?",
                ['Completed', $requisitionId]
            );
            
            log_security_event($_SESSION['user']['UserID'], 'Issuance', 'Success', "Processed issuance for requisition $requisitionId");
            echo json_encode(['success' => true, 'message' => 'Issuance processed successfully']);
            
        } catch (Exception $e) {
            error_log("Process issuance error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }

    } elseif ($action === 'mark_notifications_read') {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        
        $userRole = $_SESSION['user']['Role'] ?? 'User';
        
        global $db;
        try {
            // Mark all notifications for this user's role as read
            $db->execute(
                "UPDATE Notifications 
                 SET isRead = 1 
                 WHERE (targetRoles IS NULL OR targetRoles = '' OR FIND_IN_SET(?, targetRoles) > 0)",
                [$userRole]
            );
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("Mark notifications read error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }

    } elseif ($action === 'update_profile') {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user']['UserID'];
        $firstName = $_POST['firstName'] ?? '';
        $middleName = $_POST['middleName'] ?? '';
        $lastName = $_POST['lastName'] ?? '';

        global $db;
        $user = $db->fetchOne("SELECT * FROM Users WHERE UserID = ?", [$userId]);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $user['FirstName'] = $firstName;
        $user['MiddleName'] = $middleName;
        $user['LastName'] = $lastName;
        $user['FName'] = $firstName; // Consistency
        $user['MName'] = $middleName;
        $user['LName'] = $lastName;

        if (save_data('users', [$user])) {
            $_SESSION['user'] = array_merge($_SESSION['user'], $user);
            log_security_event($userId, 'Profile Update', 'Success', 'Updated profile information');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }

    } elseif ($action === 'update_password') {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user']['UserID'];
        $currPass = $_POST['currentPassword'] ?? '';
        $newPass = $_POST['newPassword'] ?? '';

        global $db;
        $user = $db->fetchOne("SELECT * FROM Users WHERE UserID = ?", [$userId]);
        
        if (!$user || (!password_verify($currPass, $user['Password']) && $user['Password'] !== $currPass)) {
            echo json_encode(['success' => false, 'message' => 'Invalid current password']);
            exit;
        }

        $user['Password'] = password_hash($newPass, PASSWORD_DEFAULT);

        if (save_data('users', [$user])) {
            $_SESSION['user']['Password'] = $user['Password'];
            log_security_event($userId, 'Password Change', 'Success', 'Password updated successfully');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update password']);
        }

    } elseif ($action === 'update_settings') {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $userId = $_SESSION['user']['UserID'];
        global $db;
        $user = $db->fetchOne("SELECT * FROM Users WHERE UserID = ?", [$userId]);

        if (isset($_POST['emailNotifications'])) {
            $user['EmailNotifications'] = $_POST['emailNotifications'] === 'true' ? 1 : 0;
        }
        if (isset($_POST['inAppNotifications'])) {
            $user['InAppNotifications'] = $_POST['inAppNotifications'] === 'true' ? 1 : 0;
        }
        if (isset($_POST['themePreference'])) {
            $user['ThemePreference'] = $_POST['themePreference'];
        }

        if (save_data('users', [$user])) {
            $_SESSION['user'] = array_merge($_SESSION['user'], $user);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
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
                        if ($ri['ItemID'] == $extraId) {
                            $stockMoves[] = [
                                'date' => $rcv['ReceivedDate'],
                                'type' => 'Receiving',
                                'ref' => 'PO #' . ($rcv['PONumber'] ?? $rcv['POID']),
                                'batch' => $ri['BatchID'] ?? 'N/A',
                                'in' => (float)$ri['QuantityReceived'],
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
                          // Check if this batch belongs to our item
                          $batchInfo = $db->fetchOne("SELECT ItemID FROM CentralInventoryBatch WHERE BatchID = ?", [$ii['BatchID']]);
                          if ($batchInfo && $batchInfo['ItemID'] == $extraId) {
                             $stockMoves[] = [
                                 'date' => $iss['DateIssued'],
                                 'type' => 'Issuance',
                                 'ref' => 'REQ #' . ($iss['RequisitionNumber'] ?? $iss['RequisitionID']),
                                 'batch' => $ii['BatchID'],
                                 'in' => 0,
                                 'out' => (float)$ii['QuantityIssued']
                             ];
                          }
                      }
                 }
             }
             
             // 5. Transactions: Adjustments (Returns/Disposals)
             foreach ($adjustments as $adj) {
                 if ($adj['Type'] === 'Return') {
                    $details = $adj['Details'] ?? [];
                    foreach ($details as $d) {
                        if ($d['ItemID'] == $extraId) {
                            $stockMoves[] = [
                                'date' => $adj['Date'],
                                'type' => 'Return',
                                'ref' => $adj['Reference'],
                                'batch' => $d['BatchID'],
                                'in' => (float)$d['QuantityAdjusted'],
                                'out' => 0
                            ];
                        }
                    }
                 } elseif ($adj['Type'] === 'Disposal') {
                    // Check if this disposal's batch belongs to our item
                    $batchInfo = $db->fetchOne("SELECT ItemID FROM CentralInventoryBatch WHERE BatchID = ?", [$adj['BatchID']]);
                    if ($batchInfo && $batchInfo['ItemID'] == $extraId) {
                        $stockMoves[] = [
                            'date' => $adj['Date'],
                            'type' => 'Disposal',
                            'ref' => $adj['Reason'] ?: 'Stock Disposal',
                            'batch' => $adj['BatchID'],
                            'in' => 0,
                            'out' => (float)$adj['Quantity']
                        ];
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

    } elseif ($action === 'add_inventory_adjustment') {
        $batchId = $_POST['batchId'] ?? '';
        $qty = (int)($_POST['quantity'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        
        if (!$batchId || $qty == 0) {
            echo json_encode(['success' => false, 'message' => 'Batch ID and quantity are required']);
            exit;
        }

        global $db;
        $db->beginTransaction();
        try {
            // 1. Create InventoryAdjustment
            $db->execute(
                "INSERT INTO InventoryAdjustment (BatchID, UserID, AdjustmentQuantity, Reason, AdjustmentDate) 
                 VALUES (?, ?, ?, ?, ?)",
                [$batchId, $_SESSION['user']['UserID'], $qty, $reason, date('Y-m-d H:i:s')]
            );
            $adjustmentId = $db->lastInsertId();

            // 2. Update Inventory
            // For "Unused Stock" or manual positive adjustments, we increase QuantityOnHand
            // If the user purposefully wants to decrease, qty should be negative
            $db->execute(
                "UPDATE CentralInventoryBatch 
                 SET QuantityOnHand = QuantityOnHand + ?, QuantityReleased = QuantityReleased - ? 
                 WHERE BatchID = ?",
                [$qty, $qty, $batchId]
            );

            $db->commit();
            logTransaction('Manual Adjustment', 'InventoryAdjustment', $adjustmentId);
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

    } elseif ($action === 'bulk_import_items') {
        $newItemsData = $_POST['items'] ?? [];
        if (!is_array($newItemsData) || empty($newItemsData)) {
            echo json_encode(['success' => false, 'message' => 'No items provided for import']);
            exit;
        }

        $existingItems = get_data('items');
        
        // Find current max numeric ID
        $maxId = 0;
        foreach ($existingItems as $i) {
            if (preg_match('/I(\d+)/', $i['ItemID'], $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxId) $maxId = $num;
            }
        }

        $addedCount = 0;
        foreach ($newItemsData as $item) {
            $maxId++;
            $newItemId = 'I' . str_pad($maxId, 4, '0', STR_PAD_LEFT);
            
            $existingItems[] = [
                'ItemID' => $newItemId,
                'ItemName' => $item['ItemName'] ?? 'Unnamed Medicine',
                'ItemType' => $item['ItemType'] ?? 'Medicine',
                'UnitOfMeasure' => $item['UnitOfMeasure'] ?? 'Unit'
            ];
            $addedCount++;
        }

        if (save_data('items', $existingItems)) {
            log_security_event($_SESSION['user']['UserID'], 'Item', 'Success', "Bulk imported $addedCount items via DPRI");
            echo json_encode(['success' => true, 'count' => $addedCount]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save imported items']);
        }

    } elseif ($action === 'sync_hc_inventory') {
        try {
            if (!isset($_SESSION['user']['HealthCenterID'])) {
                echo json_encode(['success' => false, 'message' => 'No health center assigned to your account']);
                exit;
            }

            $hcId = $_SESSION['user']['HealthCenterID'];
            $hcConn = Database::getHCConnection($hcId);
            if (!$hcConn) {
                echo json_encode(['success' => false, 'message' => 'Could not connect to health center database (Check DatabaseName setting in HealthCenters table)']);
                exit;
            }

            global $db;
            
            // Get all successful issuances for this health center
            $sql = "SELECT ii.BatchID, ii.QuantityIssued, b.ItemID, b.ExpiryDate
                    FROM issuanceitem ii
                    JOIN issuance i ON ii.IssuanceID = i.IssuanceID
                    JOIN requisition r ON i.RequisitionID = r.RequisitionID
                    JOIN centralinventorybatch b ON ii.BatchID = b.BatchID
                    WHERE r.HealthCenterID = ? AND i.StatusType = 'Issued'";
            
            $issuances = $db->fetchAll($sql, [$hcId]);
            
            $syncedCount = 0;
            foreach ($issuances as $iss) {
                // Check if already in HC inventory
                $stmt = $hcConn->prepare("SELECT InventoryID FROM hc_inventory WHERE BatchID = ?");
                $stmt->execute([$iss['BatchID']]);
                if (!$stmt->fetch()) {
                    $stmt = $hcConn->prepare("INSERT INTO hc_inventory (ItemID, BatchID, QuantityOnHand, ExpiryDate) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$iss['ItemID'], $iss['BatchID'], $iss['QuantityIssued'], $iss['ExpiryDate']])) {
                        $syncedCount++;
                    }
                }
            }

            echo json_encode(['success' => true, 'message' => "Successfully synced $syncedCount new items to local inventory."]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Sync error: ' . $e->getMessage()]);
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
