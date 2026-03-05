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
        $role = $_POST['role'] ?? 'Health Center Staff';

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
        
        global $db;
        $db->beginTransaction();
        
        try {
            // Fetch requisition to get HealthCenterID
            $req = $db->fetchOne("SELECT HealthCenterID FROM Requisition WHERE RequisitionID = ?", [$reqId]);
            $healthCenterId = $req['HealthCenterID'] ?? null;

            if (!$healthCenterId) {
                throw new Exception('Health center not found for this requisition');
            }

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

                     // Deduct from central inventory
                     $batch = $db->fetchOne("SELECT * FROM CentralInventoryBatch WHERE BatchID = ?", [$batchId]);
                     if (!$batch || $batch['QuantityOnHand'] < $qtyToIssue) {
                         throw new Exception("Insufficient stock for Batch ID $batchId");
                     }

                     $db->execute(
                        "UPDATE CentralInventoryBatch SET QuantityOnHand = QuantityOnHand - ?, QuantityReleased = QuantityReleased + ? WHERE BatchID = ?",
                        [$qtyToIssue, $qtyToIssue, $batchId]
                     );

                     $newIssuance['IssuedItems'][] = [
                         'BatchID' => $batchId,
                         'RequisitionItemID' => $planItem['reqItemId'] ?? null,
                         'QuantityIssued' => $qtyToIssue
                     ];

                     // Transfer to HC Inventory
                     $existingHCBatch = $db->fetchOne(
                        "SELECT HCBatchID FROM HCInventoryBatch WHERE HealthCenterID = ? AND ItemID = ? AND BatchID = ?",
                        [$healthCenterId, $batch['ItemID'], $batchId]
                     );

                     if ($existingHCBatch) {
                         $db->execute(
                            "UPDATE HCInventoryBatch SET QuantityOnHand = QuantityOnHand + ? WHERE HCBatchID = ?",
                            [$qtyToIssue, $existingHCBatch['HCBatchID']]
                         );
                     } else {
                         $db->execute(
                            "INSERT INTO HCInventoryBatch (HealthCenterID, ItemID, BatchID, ExpiryDate, QuantityOnHand, UnitCost) 
                             VALUES (?, ?, ?, ?, ?, ?)",
                            [$healthCenterId, $batch['ItemID'], $batchId, $batch['ExpiryDate'], $qtyToIssue, $batch['UnitCost']]
                         );
                     }
                 }
            }
            
            // Save Issuance
            $db->execute(
                "INSERT INTO Issuance (RequisitionID, UserID, IssueDate, StatusType) VALUES (?, ?, ?, 'Issued')",
                [$reqId, $_SESSION['user']['UserID'], date('Y-m-d H:i:s')]
            );
            $issuanceId = $db->lastInsertId();

            foreach ($newIssuance['IssuedItems'] as $item) {
                $db->execute(
                    "INSERT INTO IssuanceItem (IssuanceID, BatchID, RequisitionItemID, QuantityIssued) VALUES (?, ?, ?, ?)",
                    [$issuanceId, $item['BatchID'], $item['RequisitionItemID'], $item['QuantityIssued']]
                );
            }
            
            // Update Requisition Status
            $db->execute("UPDATE Requisition SET StatusType = 'Completed' WHERE RequisitionID = ?", [$reqId]);
            
            $db->commit();
            logTransaction('Issued Items', 'Requisition', $reqId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

    } elseif ($action === 'receive_items') {
        $poid = $_POST['poid'] ?? $_POST['poId'] ?? ''; 
        $items = $_POST['items'] ?? []; 
        
        if (!$poid) {
            echo json_encode(['success' => false, 'message' => 'PO ID is required']);
            exit;
        }

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
        
        if (empty($newReceiving['ReceivedItems'])) {
            echo json_encode(['success' => false, 'message' => 'No items were received']);
            exit;
        }

        global $db;
        $db->beginTransaction();
        try {
            // Update PO Status directly
            $res = $db->execute("UPDATE ProcurementOrder SET StatusType = 'Completed' WHERE POID = ?", [$poid]);
            if (!$res) throw new Exception("Failed to update procurement order status");

            // Save Receiving record
            if (!save_data('receivings', [$newReceiving])) {
                throw new Exception("Failed to save received items");
            }

            $db->commit();
            log_security_event($_SESSION['user']['UserID'], 'Receiving', 'Success', "Received items for PO $poid");
            logTransaction('Received Items', 'Procurement Order', $poid);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

    } elseif ($action === 'create_requisition') {
        try {
            // Validate user session
            if (!isset($_SESSION['user']) || !isset($_SESSION['user']['UserID'])) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                exit;
            }

            $healthCenterId = $_POST['healthCenterId'] ?? '';
            $healthCenterName = $_POST['healthCenterName'] ?? '';
            $healthCenterAddress = $_POST['healthCenterAddress'] ?? '';
            $items = $_POST['items'] ?? []; 
            
            // Validate health center
            if (empty($healthCenterId) && empty($healthCenterName)) {
                echo json_encode(['success' => false, 'message' => 'Health center is required']);
                exit;
            }
            
            // Validate items
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

    } elseif ($action === 'create_procurement_order') {
        // Validate user is logged in
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'User not authenticated']);
            exit;
        }
        
        $supplierId = $_POST['supplierId'] ?? '';
        $healthCenterId = $_POST['healthCenterId'] ?? '';
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
                    'UnitCost' => 0,
                    'ExpiryDate' => !empty($expiryDates[$index]) ? $expiryDates[$index] : null
                ];
            }
        }
        
        if (empty($poItems)) {
            echo json_encode(['success' => false, 'message' => 'No valid items to order']);
            exit;
        }

        // Handle document reference file upload
        $refFilePath = null;
        if (!empty($_FILES['refDocument']['name'])) {
            $file = $_FILES['refDocument'];
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $allowedExts  = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowedExts)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF and images are allowed.']);
                exit;
            }
            if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
                echo json_encode(['success' => false, 'message' => 'File too large. Maximum 10MB.']);
                exit;
            }

            $uploadDir = __DIR__ . '/uploads/po_docs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileName = 'po_ref_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                $refFilePath = 'uploads/po_docs/' . $fileName;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
                exit;
            }
        }
        
        $newPO = [
            'UserID' => $_SESSION['user']['UserID'],
            'SupplierID' => $supplierId,
            'SupplierName' => $supplierName,
            'SupplierAddress' => $supplierAddress,
            'HealthCenterID' => !empty($healthCenterId) ? $healthCenterId : null,
            'ContractNumber' => $_POST['contractNumber'] ?? null,
            'ContractStartDate' => $_POST['contractStartDate'] ?? null,
            'ContractEndDate' => $_POST['contractEndDate'] ?? null,
            'ContractAmount' => $_POST['contractAmount'] ?? null,
            'DocumentType' => $_POST['documentType'] ?? 'PO',
            'RefFileType' => $_POST['refFileType'] ?? null,
            'RefFilePath' => $refFilePath,
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

    } elseif ($action === 'update_patient_requisition_status') {
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
        
        if ($status === 'Completed') {
            $db->beginTransaction();
            try {
                // Get health center and items
                $patientReq = $db->fetchOne("SELECT HealthCenterID FROM HCPatientRequisition WHERE PatientReqID = ?", [$reqId]);
                if (!$patientReq) throw new Exception("Requisition not found.");
                
                $hcId = $patientReq['HealthCenterID'];
                $prItems = $db->fetchAll("SELECT ItemID, QuantityRequested FROM HCPatientRequisitionItem WHERE PatientReqID = ?", [$reqId]);

                foreach ($prItems as $item) {
                    $qtyNeeded = $item['QuantityRequested'];
                    $itemId = $item['ItemID'];

                    // Find available batches in HC inventory (FIFO)
                    $batches = $db->fetchAll(
                        "SELECT HCBatchID, QuantityOnHand FROM HCInventoryBatch 
                         WHERE HealthCenterID = ? AND ItemID = ? AND QuantityOnHand > 0 
                         ORDER BY DateReceivedAtHC ASC, HCBatchID ASC",
                        [$hcId, $itemId]
                    );

                    $totalAvailable = array_sum(array_column($batches, 'QuantityOnHand'));
                    if ($totalAvailable < $qtyNeeded) {
                        throw new Exception("Insufficient stock for Item ID $itemId in health center inventory. (Need $qtyNeeded, have $totalAvailable)");
                    }

                    foreach ($batches as $batch) {
                        if ($qtyNeeded <= 0) break;
                        $take = min($qtyNeeded, $batch['QuantityOnHand']);
                        
                        $db->execute(
                            "UPDATE HCInventoryBatch SET QuantityOnHand = QuantityOnHand - ? WHERE HCBatchID = ?",
                            [$take, $batch['HCBatchID']]
                        );
                        $qtyNeeded -= $take;
                    }
                }
                
                $res = $db->execute("UPDATE HCPatientRequisition SET StatusType = ? WHERE PatientReqID = ?", [$status, $reqId]);
                if (!$res) throw new Exception("Failed to update status.");
                
                $db->commit();
                log_security_event($_SESSION['user']['UserID'], 'Patient Requisition', 'Success', "Completed patient requisition $reqId and deducted stock.");
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $db->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
        }

        $res = $db->execute("UPDATE HCPatientRequisition SET StatusType = ? WHERE PatientReqID = ?", [$status, $reqId]);
        
        if ($res) {
            log_security_event($_SESSION['user']['UserID'], 'Patient Requisition', 'Success', "Updated patient requisition $reqId status to $status");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
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
        $itemName    = $_POST['itemName'] ?? '';
        $itemType    = $_POST['itemType'] ?? '';
        $unit        = $_POST['unitOfMeasure'] ?? '';

        // Optional batch fields
        $batchId    = isset($_POST['batchId']) && $_POST['batchId'] !== '' ? (int)$_POST['batchId'] : null;
        $lotNumber  = $_POST['lotNumber'] ?? '';
        $batchQty   = isset($_POST['batchQty']) && $_POST['batchQty'] !== '' ? (int)$_POST['batchQty'] : null;
        $unitCost   = isset($_POST['unitCost']) && $_POST['unitCost'] !== '' ? (float)$_POST['unitCost'] : null;
        $expiryDate = $_POST['expiryDate'] ?? '';
        $warehouseId = $_POST['warehouseId'] ?? 1;

        if (empty($itemName)) {
            echo json_encode(['success' => false, 'message' => 'Item name is required']);
            exit;
        }

        global $db;
        $brand      = $_POST['brand'] ?? '';
        $dosageUnit = $_POST['dosageUnit'] ?? '';
        $res = $db->execute(
            "INSERT INTO Item (ItemName, Brand, ItemType, UnitOfMeasure, DosageUnit) VALUES (?, ?, ?, ?, ?)",
            [$itemName, $brand ?: null, $itemType, $unit, $dosageUnit ?: null]
        );

        if ($res) {
            $newItemIdAsInt = $db->lastInsertId();
            $newItemId = 'I' . str_pad($newItemIdAsInt, 4, '0', STR_PAD_LEFT);
            log_security_event($_SESSION['user']['UserID'], 'Item', 'Success', "Added item $newItemId ($itemName)");

            // If a quantity was provided, create the initial batch
            if ($batchQty !== null && $batchQty > 0) {
                $expiryParam = !empty($expiryDate) ? $expiryDate : null;
                $lotParam    = !empty($lotNumber) ? $lotNumber : null;

                if ($batchId !== null) {
                    // User specified an explicit BatchID
                    $db->execute(
                        "INSERT INTO CentralInventoryBatch (BatchID, ItemID, LotNumber, QuantityOnHand, UnitCost, ExpiryDate, WarehouseID, DateReceived)
                         VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())",
                        [$batchId, $newItemIdAsInt, $lotParam, $batchQty, $unitCost, $expiryParam, $warehouseId]
                    );
                } else {
                    // Let DB auto-assign BatchID
                    $db->execute(
                        "INSERT INTO CentralInventoryBatch (ItemID, LotNumber, QuantityOnHand, UnitCost, ExpiryDate, WarehouseID, DateReceived)
                         VALUES (?, ?, ?, ?, ?, ?, CURDATE())",
                        [$newItemIdAsInt, $lotParam, $batchQty, $unitCost, $expiryParam, $warehouseId]
                    );
                }
            }

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save item']);
        }
        
    } elseif ($action === 'update_item') {
        $itemId = $_POST['itemId'] ?? ''; // This might be 'I0001' or '1'
        $itemName = $_POST['itemName'] ?? '';
        $itemType = $_POST['itemType'] ?? '';
        $unit = $_POST['unitOfMeasure'] ?? '';
        
        if (!$itemId || empty($itemName)) {
            echo json_encode(['success' => false, 'message' => 'Item ID and name are required']);
            exit;
        }

        // Resolve ItemID to INT
        $resolvedItemId = $itemId;
        if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
            $resolvedItemId = (int)$matches[1];
        }
        
        global $db;
        $brand      = $_POST['brand'] ?? '';
        $dosageUnit = $_POST['dosageUnit'] ?? '';
        $res = $db->execute(
            "UPDATE Item SET ItemName = ?, Brand = ?, ItemType = ?, UnitOfMeasure = ?, DosageUnit = ? WHERE ItemID = ?",
            [$itemName, $brand ?: null, $itemType, $unit, $dosageUnit ?: null, $resolvedItemId]
        );
        
        if ($res) {
             log_security_event($_SESSION['user']['UserID'], 'Item', 'Success', "Updated item $itemId");
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to update item']);
        }

    } elseif ($action === 'delete_item') {
        if ($_SESSION['user']['Role'] !== 'Administrator') {
            echo json_encode(['success' => false, 'message' => 'Only administrators can delete items.']);
            exit;
        }
        $itemId = $_POST['itemId'] ?? '';
        
        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Item ID is required']);
            exit;
        }

        // Resolve ItemID to INT
        $resolvedItemId = $itemId;
        if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
            $resolvedItemId = (int)$matches[1];
        }

        global $db;
        // Check if item has any batches/history before deleting (integrity check)
        $hasHistory = $db->fetchOne("SELECT BatchID FROM CentralInventoryBatch WHERE ItemID = ? LIMIT 1", [$resolvedItemId]);
        if ($hasHistory) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete item with inventory history. Consider deactivating it instead.']);
            exit;
        }

        $res = $db->execute("DELETE FROM Item WHERE ItemID = ?", [$resolvedItemId]);
        
        if ($res) {
             log_security_event($_SESSION['user']['UserID'], 'Item', 'Success', "Deleted item $itemId");
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
        }

    } elseif ($action === 'add_patient') {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $newPatient = [
            'HealthCenterID' => $_POST['healthCenterId'] ?? $_SESSION['user']['HealthCenterID'] ?? null,
            'FName' => $_POST['firstName'] ?? '',
            'MName' => $_POST['middleName'] ?? '',
            'LName' => $_POST['lastName'] ?? '',
            'Age' => $_POST['age'] ?? null,
            'Gender' => $_POST['gender'] ?? 'Other',
            'Address' => $_POST['address'] ?? '',
            'ContactNumber' => $_POST['contactNumber'] ?? '',
            'IDProof' => $_POST['idProof'] ?? ''
        ];

        if (empty($newPatient['FName']) || empty($newPatient['LName'])) {
            echo json_encode(['success' => false, 'message' => 'Patient name is required']);
            exit;
        }

        if (save_data('patients', [$newPatient])) {
            log_security_event($_SESSION['user']['UserID'], 'Patient', 'Success', "Added patient {$newPatient['FName']} {$newPatient['LName']}");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save patient']);
        }

    } elseif ($action === 'create_patient_requisition') {
        if (!isset($_SESSION['user'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $patientId = $_POST['patientId'] ?? '';
        $patientName = $_POST['patientName'] ?? '';
        $items = json_decode($_POST['items'] ?? '[]', true);
        $diagnosis = $_POST['diagnosis'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $healthCenterId = $_SESSION['user']['HealthCenterID'] ?? null;

        if (!$healthCenterId && !in_array($_SESSION['user']['Role'], ['Administrator', 'Head Pharmacist'])) {
            echo json_encode(['success' => false, 'message' => 'Your account is not assigned to a health center.']);
            exit;
        }

        if (!$patientId && !$patientName) {
            echo json_encode(['success' => false, 'message' => 'Patient selection or name is required']);
            exit;
        }

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'At least one item is required']);
            exit;
        }

        global $db;
        $db->beginTransaction();
        try {
            // If no patientId, create a new patient from the manual name
            if (!$patientId && $patientName) {
                $nameParts = explode(' ', trim($patientName), 2);
                $fName = $nameParts[0];
                $lName = $nameParts[1] ?? 'Doe';

                $newPatient = [
                    'HealthCenterID' => $healthCenterId,
                    'FName' => $fName,
                    'LName' => $lName,
                    'ContactNumber' => $_POST['contactInfo'] ?? '',
                    'IDProof' => $_POST['idProof'] ?? ''
                ];

                if (!save_data('patients', [$newPatient])) {
                    throw new Exception('Failed to create new patient record');
                }
                $patientId = $db->lastInsertId();
            }

            $newPR = [
                'PatientID' => $patientId,
                'UserID' => $_SESSION['user']['UserID'],
                'HealthCenterID' => $healthCenterId,
                'RequestDate' => date('Y-m-d H:i:s'),
                'StatusType' => 'Pending',
                'Diagnosis' => $diagnosis,
                'Notes' => $notes,
                'ContactInfo' => $_POST['contactInfo'] ?? '',
                'IDProof' => $_POST['idProof'] ?? '',
                'Items' => $items
            ];

            if (!save_data('patient_requisitions', [$newPR])) {
                throw new Exception('Failed to save patient requisition');
            }

            $newPrId = $db->lastInsertId();
            $db->commit();
            logTransaction('Created Patient Requisition', 'HCPatientRequisition', $newPrId);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

    } elseif ($action === 'get_history') {
        $type = $_POST['type'] ?? 'item_additions';
        $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 100;
        
        global $db;
        $data = [];

        try {
            if ($type === 'item_additions') {
                $data = $db->fetchAll("
                    SELECT 
                        r.ReceivedDate as Date,
                        i.ItemName,
                        i.ItemID,
                        ri.QuantityReceived as Quantity,
                        cib.BatchID,
                        cib.ExpiryDate,
                        CONCAT(u.FName, ' ', u.LName) as User,
                        'Addition' as Reference
                    FROM Receiving r
                    JOIN ReceivingItem ri ON r.ReceivingID = ri.ReceivingID
                    JOIN CentralInventoryBatch cib ON ri.BatchID = cib.BatchID
                    JOIN Item i ON cib.ItemID = i.ItemID
                    LEFT JOIN Users u ON r.UserID = u.UserID
                    ORDER BY r.ReceivedDate DESC
                    LIMIT ?
                ", [$limit]);
            } elseif ($type === 'hc_requisitions') {
                $data = $db->fetchAll("
                    SELECT 
                        r.RequestDate as Date,
                        r.RequisitionNumber as Reference,
                        hc.Name as HealthCenter,
                        i.ItemName,
                        ri.QuantityRequested as Quantity,
                        r.StatusType as Status,
                        CONCAT(u.FName, ' ', u.LName) as User
                    FROM Requisition r
                    JOIN RequisitionItem ri ON r.RequisitionID = ri.RequisitionID
                    JOIN Item i ON ri.ItemID = i.ItemID
                    JOIN HealthCenters hc ON r.HealthCenterID = hc.HealthCenterID
                    LEFT JOIN Users u ON r.UserID = u.UserID
                    ORDER BY r.RequestDate DESC
                    LIMIT ?
                ", [$limit]);
            } elseif ($type === 'warehouse_issuances') {
                $data = $db->fetchAll("
                    SELECT 
                        iss.IssueDate as Date,
                        req.RequisitionNumber as Reference,
                        hc.Name as HealthCenter,
                        i.ItemName,
                        ii.QuantityIssued as Quantity,
                        ii.BatchID,
                        CONCAT(u.FName, ' ', u.LName) as User
                    FROM Issuance iss
                    JOIN IssuanceItem ii ON iss.IssuanceID = ii.IssuanceID
                    JOIN Requisition req ON iss.RequisitionID = req.RequisitionID
                    JOIN HealthCenters hc ON req.HealthCenterID = hc.HealthCenterID
                    JOIN CentralInventoryBatch cib ON ii.BatchID = cib.BatchID
                    JOIN Item i ON cib.ItemID = i.ItemID
                    LEFT JOIN Users u ON iss.UserID = u.UserID
                    ORDER BY iss.IssueDate DESC
                    LIMIT ?
                ", [$limit]);
            } elseif ($type === 'adjustments') {
                $manualAdj = $db->fetchAll("
                    SELECT 
                        ia.AdjustmentDate as Date,
                        'Manual Adjustment' as Reference,
                        i.ItemName,
                        ia.AdjustmentQuantity as Quantity,
                        ia.Reason,
                        CONCAT(u.FName, ' ', u.LName) as User
                    FROM InventoryAdjustment ia
                    JOIN CentralInventoryBatch cib ON ia.BatchID = cib.BatchID
                    JOIN Item i ON cib.ItemID = i.ItemID
                    LEFT JOIN Users u ON ia.UserID = u.UserID
                ");

                $reqAdj = $db->fetchAll("
                    SELECT 
                        ra.AdjustmentDate as Date,
                        CONCAT('Requisition ', ra.AdjustmentType) as Reference,
                        i.ItemName,
                        rad.QuantityAdjusted as Quantity,
                        ra.Reason,
                        CONCAT(u.FName, ' ', u.LName) as User
                    FROM RequisitionAdjustment ra
                    JOIN RequisitionAdjustmentDetail rad ON ra.RequisitionAdjustmentID = rad.RequisitionAdjustmentID
                    JOIN CentralInventoryBatch cib ON rad.BatchID = cib.BatchID
                    JOIN Item i ON cib.ItemID = i.ItemID
                    LEFT JOIN Users u ON ra.UserID = u.UserID
                ");

                $disposals = $db->fetchAll("
                    SELECT 
                        noi.ReportDate as Date,
                        CONCAT('Notice: ', noi.IssueType) as Reference,
                        i.ItemName,
                        -noi.QuantityAffected as Quantity,
                        noi.Remarks as Reason,
                        CONCAT(u.FName, ' ', u.LName) as User
                    FROM NoticeOfIssue noi
                    JOIN CentralInventoryBatch cib ON noi.BatchID = cib.BatchID
                    JOIN Item i ON cib.ItemID = i.ItemID
                    LEFT JOIN Users u ON noi.UserID = u.UserID
                ");

                $data = array_merge($manualAdj, $reqAdj, $disposals);
                usort($data, function($a, $b) {
                    return strtotime($b['Date']) - strtotime($a['Date']);
                });
                $data = array_slice($data, 0, $limit);

            } elseif ($type === 'patient_list') {
                $data = $db->fetchAll("
                    SELECT 
                        pr.RequestDate as Date,
                        pr.RequisitionNumber as Reference,
                        CONCAT(p.FName, ' ', p.LName) as Patient,
                        i.ItemName,
                        pri.QuantityRequested as Quantity,
                        pr.StatusType as Status,
                        CONCAT(u.FName, ' ', u.LName) as User
                    FROM HCPatientRequisition pr
                    JOIN HCPatientRequisitionItem pri ON pr.PatientReqID = pri.PatientReqID
                    JOIN HCPatient p ON pr.PatientID = p.PatientID
                    JOIN Item i ON pri.ItemID = i.ItemID
                    LEFT JOIN Users u ON pr.UserID = u.UserID
                    ORDER BY pr.RequestDate DESC
                    LIMIT ?
                ", [$limit]);
            } elseif ($type === 'hc_inventory_additions') {
                $data = $db->fetchAll("
                    SELECT 
                        iss.IssueDate as Date,
                        req.RequisitionNumber as Reference,
                        hc.Name as HealthCenter,
                        i.ItemName,
                        ii.QuantityIssued as Quantity,
                        ii.BatchID,
                        CONCAT(u.FName, ' ', u.LName) as User,
                        'HC Arrival' as Type
                    FROM Issuance iss
                    JOIN IssuanceItem ii ON iss.IssuanceID = ii.IssuanceID
                    JOIN Requisition req ON iss.RequisitionID = req.RequisitionID
                    JOIN HealthCenters hc ON req.HealthCenterID = hc.HealthCenterID
                    JOIN CentralInventoryBatch cib ON ii.BatchID = cib.BatchID
                    JOIN Item i ON cib.ItemID = i.ItemID
                    LEFT JOIN Users u ON iss.UserID = u.UserID
                    WHERE req.StatusType = 'Completed'
                    ORDER BY iss.IssueDate DESC
                    LIMIT ?
                ", [$limit]);
            } elseif ($type === 'summary') {
                // Additive counting: Total items ever received by CENTRAL
                $data = $db->fetchAll("
                    SELECT 
                        i.ItemName,
                        i.ItemID,
                        IFNULL(SUM(ri.QuantityReceived), 0) as TotalAdded,
                        COUNT(DISTINCT r.ReceivingID) as TotalTransactions,
                        MAX(r.ReceivedDate) as LastReceived
                    FROM Item i
                    LEFT JOIN CentralInventoryBatch cib ON i.ItemID = cib.ItemID
                    LEFT JOIN ReceivingItem ri ON cib.BatchID = ri.BatchID
                    LEFT JOIN Receiving r ON ri.ReceivingID = r.ReceivingID
                    GROUP BY i.ItemID
                    HAVING TotalAdded > 0
                    ORDER BY TotalAdded DESC
                ");
            } elseif ($type === 'hc_summary') {
                // Additive counting: Total items ever received by ALL HEALTH CENTERS
                $data = $db->fetchAll("
                    SELECT 
                        i.ItemName,
                        i.ItemID,
                        IFNULL(SUM(ii.QuantityIssued), 0) as TotalAdded,
                        COUNT(DISTINCT iss.IssuanceID) as TotalTransactions,
                        MAX(iss.IssueDate) as LastReceived
                    FROM Item i
                    LEFT JOIN CentralInventoryBatch cib ON i.ItemID = cib.ItemID
                    LEFT JOIN IssuanceItem ii ON cib.BatchID = ii.BatchID
                    LEFT JOIN Issuance iss ON ii.IssuanceID = iss.IssuanceID
                    LEFT JOIN Requisition req ON iss.RequisitionID = req.RequisitionID
                    WHERE req.StatusType = 'Completed'
                    GROUP BY i.ItemID
                    HAVING TotalAdded > 0
                    ORDER BY TotalAdded DESC
                ");
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

    } elseif ($action === 'get_dashboard_stats') {
        $userRole = $_SESSION['user']['Role'];
        $requisitions = get_data('requisitions');
        $procurementOrders = get_data('procurement_orders');
        $inventory = get_data('inventory');
        $patientRequisitions = get_data('patient_requisitions');

        $stats = [
            'pending_reqs' => 0,
            'low_stock' => 0,
            'pending_pos' => 0,
            'pending_patient_reqs' => 0
        ];

        foreach ($requisitions as $r) {
            if ($r['StatusType'] === 'Pending') $stats['pending_reqs']++;
        }

        foreach ($procurementOrders as $po) {
            if ($po['StatusType'] === 'Pending') $stats['pending_pos']++;
        }

        foreach ($inventory as $batch) {
            if ($batch['QuantityOnHand'] < 500) $stats['low_stock']++;
        }

        foreach ($patientRequisitions as $pr) {
            if ($pr['StatusType'] === 'Pending') $stats['pending_patient_reqs']++;
        }

        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;

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
