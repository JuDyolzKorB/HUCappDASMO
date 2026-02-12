<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $conn;
    private $basePath; // Keep for backward compatibility

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->basePath = __DIR__ . '/../data/'; // For fallback/migration
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Legacy compatibility methods - now use MySQL
    public function read($filename) {
        // Map filename to table name
        $table = $this->mapFilenameToTable($filename);
        
        try {
            $stmt = $this->conn->query("SELECT * FROM $table");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Read Error ($table): " . $e->getMessage());
            return [];
        }
    }

    public function write($filename, $data) {
        // This method is deprecated - use specific insert/update methods instead
        // Kept for backward compatibility during migration
        return true;
    }

    public function find($filename, $key, $value) {
        $table = $this->mapFilenameToTable($filename);
        
        try {
            $stmt = $this->conn->prepare("SELECT * FROM $table WHERE $key = ? LIMIT 1");
            $stmt->execute([$value]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Find Error ($table): " . $e->getMessage());
            return null;
        }
    }

    private function mapFilenameToTable($filename) {
        $mapping = [
            'users' => 'Users',
            'health_centers' => 'HealthCenters',
            'warehouses' => 'Warehouse',
            'items' => 'Item',
            'suppliers' => 'Supplier',
            'procurement_orders' => 'ProcurementOrder',
            'procurement_order_items' => 'ProcurementOrderItem',
            'receivings' => 'Receiving',
            'receiving_items' => 'ReceivingItem',
            'inventory' => 'CentralInventoryBatch',
            'requisitions' => 'Requisition',
            'requisition_items' => 'RequisitionItem',
            'approval_logs' => 'ApprovalLog',
            'issuances' => 'Issuance',
            'issuance_items' => 'IssuanceItem',
            'requisition_adjustments' => 'RequisitionAdjustment',
            'requisition_adjustment_details' => 'RequisitionAdjustmentDetail',
            'notice_of_issues' => 'NoticeOfIssue',
            'transaction_logs' => 'TransactionAuditLog',
            'security_logs' => 'SecurityLog',
            'reports' => 'Report',
            'notifications' => 'Notifications'
        ];
        
        return $mapping[$filename] ?? $filename;
    }

    // Execute a prepared statement
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Execute Error: " . $e->getMessage());
            return false;
        }
    }

    // Fetch all results
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Fetch All Error: " . $e->getMessage());
            return [];
        }
    }

    // Fetch single row
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Fetch One Error: " . $e->getMessage());
            return null;
        }
    }

    // Get last insert ID
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }

    // Rollback transaction
    public function rollback() {
        return $this->conn->rollBack();
    }
}

// Global DB instance
$db = Database::getInstance();

// Helper functions for backward compatibility
function get_data($file) {
    global $db;
    
    // Special handling for complex queries
    $table = $file;
    
    switch($file) {
        case 'users':
            return $db->fetchAll("SELECT UserID, FName as FirstName, MName as MiddleName, LName as LastName, Role, Username, Password FROM Users");
            
        case 'warehouses':
            return $db->fetchAll("SELECT * FROM Warehouse");
            
        case 'health_centers':
            return $db->fetchAll("SELECT * FROM HealthCenters");
            
        case 'suppliers':
            return $db->fetchAll("SELECT * FROM Supplier");
            
        case 'items':
            return $db->fetchAll("SELECT * FROM Item");
            
        case 'contracts':
            return $db->fetchAll("SELECT * FROM Contract");
            
        case 'inventory':
            return $db->fetchAll("SELECT * FROM CentralInventoryBatch");
            
        case 'procurement_orders':
            // Get POs with their items (Renamed to ProcurementOrder)
            $pos = $db->fetchAll("
                SELECT 
                    po.*, 
                    s.Name as SupplierName,
                    hc.Name as HealthCenterName,
                    c.ContractNumber as LinkedContractNumber, 
                    c.StartDate, 
                    c.EndDate, 
                    c.ContractAmount 
                FROM ProcurementOrder po
                LEFT JOIN Supplier s ON po.SupplierID = s.SupplierID
                LEFT JOIN HealthCenters hc ON po.HealthCenterID = hc.HealthCenterID
                LEFT JOIN Contract c ON po.ContractID = c.ContractID
                ORDER BY po.PODate DESC
            ");
            foreach ($pos as &$po) {
                // Use the user-input ContractNumber if available, otherwise use the linked one
                if (empty($po['ContractNumber']) && !empty($po['LinkedContractNumber'])) {
                    $po['ContractNumber'] = $po['LinkedContractNumber'];
                }
                $po['ProcurementOrderItems'] = $db->fetchAll(
                    "SELECT * FROM ProcurementOrderItem WHERE POID = ?", 
                    [$po['POID']]
                );
                $po['ApprovalLogs'] = [];
            }
            return $pos;
            
        case 'requisitions':
            // Get requisitions with their items and approval logs
            // Join Issuance to get IssuanceID for adjustments
            $reqs = $db->fetchAll("
                SELECT r.*, i.IssuanceID 
                FROM Requisition r 
                LEFT JOIN Issuance i ON r.RequisitionID = i.RequisitionID
                ORDER BY r.RequestDate DESC
            ");
            foreach ($reqs as &$req) {
                $req['RequisitionItems'] = $db->fetchAll(
                    "SELECT ri.*, i.ItemName FROM RequisitionItem ri 
                     JOIN Item i ON ri.ItemID = i.ItemID
                     WHERE ri.RequisitionID = ?", 
                    [$req['RequisitionID']]
                );

                $req['IssuedItems'] = [];
                if ($req['IssuanceID']) {
                    $req['IssuedItems'] = $db->fetchAll(
                        "SELECT ii.*, i.ItemName, cib.ItemID 
                         FROM IssuanceItem ii
                         JOIN CentralInventoryBatch cib ON ii.BatchID = cib.BatchID
                         JOIN Item i ON cib.ItemID = i.ItemID
                         WHERE ii.IssuanceID = ?",
                        [$req['IssuanceID']]
                    );
                }

                $req['ApprovalLogs'] = $db->fetchAll(
                    "SELECT al.*, CONCAT(u.FName, ' ', u.LName) as ApprovedByFullName 
                     FROM ApprovalLog al 
                     LEFT JOIN Users u ON al.UserID = u.UserID
                     WHERE al.RequisitionID = ? 
                     ORDER BY al.DecisionDate DESC", 
                    [$req['RequisitionID']]
                );

                // Get Adjustments for this requisition
                // Adjustments are linked to Issuance, which is linked to Requisition
                $req['Adjustments'] = $db->fetchAll(
                    "SELECT ra.*, CONCAT(u.FName, ' ', u.LName) as AdjustedByFullName 
                     FROM RequisitionAdjustment ra
                     JOIN Issuance i ON ra.IssuanceID = i.IssuanceID
                     LEFT JOIN Users u ON ra.UserID = u.UserID
                     WHERE i.RequisitionID = ?
                     ORDER BY ra.AdjustmentDate DESC",
                    [$req['RequisitionID']]
                );

                foreach ($req['Adjustments'] as &$adj) {
                    $adj['Details'] = $db->fetchAll(
                        "SELECT rad.*, i.ItemName, i.ItemID, cib.BatchID as BatchLabel
                         FROM RequisitionAdjustmentDetail rad
                         LEFT JOIN CentralInventoryBatch cib ON rad.BatchID = cib.BatchID
                         LEFT JOIN Item i ON cib.ItemID = i.ItemID
                         WHERE rad.RequisitionAdjustmentID = ?",
                        [$adj['RequisitionAdjustmentID']]
                    );
                }
                
                // Get health center name
                $hc = $db->fetchOne(
                    "SELECT Name FROM HealthCenters WHERE HealthCenterID = ?", 
                    [$req['HealthCenterID']]
                );
                $req['HealthCenterName'] = $hc['Name'] ?? 'Unknown';
                
                // Get user name
                $user = $db->fetchOne(
                    "SELECT FName, LName FROM Users WHERE UserID = ?", 
                    [$req['UserID']]
                );
                $req['RequestedByFullName'] = ($user['FName'] ?? '') . ' ' . ($user['LName'] ?? '');
            }
            return $reqs;
            
        case 'issuances':
            $issuances = $db->fetchAll("SELECT * FROM Issuance ORDER BY IssueDate DESC");
            foreach ($issuances as &$iss) {
                $iss['IssuedItems'] = $db->fetchAll(
                    "SELECT * FROM IssuanceItem WHERE IssuanceID = ?", 
                    [$iss['IssuanceID']]
                );
                
                // Get user name
                $user = $db->fetchOne(
                    "SELECT FName, LName FROM Users WHERE UserID = ?", 
                    [$iss['UserID']]
                );
                $iss['IssuedByFullName'] = ($user['FName'] ?? '') . ' ' . ($user['LName'] ?? '');
                $iss['DateIssued'] = $iss['IssueDate'];
                $iss['IssuedByUserID'] = $iss['UserID'];
            }
            return $issuances;
            
        case 'receivings':
            $receivings = $db->fetchAll("SELECT * FROM Receiving ORDER BY ReceivedDate DESC");
            foreach ($receivings as &$rcv) {
                $rcv['ReceivedItems'] = $db->fetchAll(
                    "SELECT ri.*, cib.*, i.ItemName FROM ReceivingItem ri 
                     JOIN CentralInventoryBatch cib ON ri.BatchID = cib.BatchID 
                     JOIN Item i ON cib.ItemID = i.ItemID
                     WHERE ri.ReceivingID = ?", 
                    [$rcv['ReceivingID']]
                );
            }
            return $receivings;
            
        case 'security_logs':
            return $db->fetchAll("SELECT * FROM SecurityLog ORDER BY ActionDate DESC LIMIT 100");
            
        case 'transaction_logs':
            return $db->fetchAll("SELECT * FROM TransactionAuditLog ORDER BY ActionDate DESC LIMIT 100");
            
        case 'adjustment_logs':
            // Fetch disposal records from NoticeOfIssue
            $disposals = $db->fetchAll("
                SELECT 
                    noi.IssueID as ID,
                    'Disposal' as Type,
                    CONCAT(i.ItemName, ' (', noi.BatchID, ')') as Reference,
                    noi.QuantityAffected as Quantity,
                    CONCAT(noi.IssueType, ': ', COALESCE(noi.Remarks, '')) as Reason,
                    noi.ReportDate as Date,
                    noi.PhotoPath,
                    noi.BatchID
                FROM NoticeOfIssue noi
                LEFT JOIN CentralInventoryBatch cib ON noi.BatchID = cib.BatchID
                LEFT JOIN Item i ON cib.ItemID = i.ItemID
                ORDER BY noi.ReportDate DESC
            ");
            
            // Fetch return records from RequisitionAdjustment
            $returns = $db->fetchAll("
                SELECT 
                    ra.RequisitionAdjustmentID as ID,
                    'Return' as Type,
                    CONCAT('Adjustment #', ra.RequisitionAdjustmentID) as Reference,
                    0 as Quantity,
                    ra.Reason,
                    ra.AdjustmentDate as Date,
                    ra.IssuanceID
                FROM RequisitionAdjustment ra
                WHERE ra.AdjustmentType = 'Return'
                ORDER BY ra.AdjustmentDate DESC
            ");

            foreach ($returns as &$ret) {
                $details = $db->fetchAll("
                    SELECT rad.*, i.ItemName 
                    FROM RequisitionAdjustmentDetail rad
                    JOIN CentralInventoryBatch cib ON rad.BatchID = cib.BatchID
                    JOIN Item i ON cib.ItemID = i.ItemID
                    WHERE rad.RequisitionAdjustmentID = ?
                ", [$ret['ID']]);
                
                $ret['Details'] = $details;
                if (!empty($details)) {
                    $ret['Quantity'] = array_sum(array_column($details, 'QuantityAdjusted'));
                    $ret['Reference'] .= " (" . implode(', ', array_map(fn($d) => $d['ItemName'], $details)) . ")";
                }
            }
            
            // Fetch manual adjustments from InventoryAdjustment
            $manuals = $db->fetchAll("
                SELECT 
                    ia.AdjustmentID as ID,
                    'Manual' as Type,
                    CONCAT(i.ItemName, ' (Batch: ', ia.BatchID, ')') as Reference,
                    ia.AdjustmentQuantity as Quantity,
                    ia.Reason,
                    ia.AdjustmentDate as Date,
                    ia.BatchID
                FROM InventoryAdjustment ia
                LEFT JOIN CentralInventoryBatch cib ON ia.BatchID = cib.BatchID
                LEFT JOIN Item i ON cib.ItemID = i.ItemID
                ORDER BY ia.AdjustmentDate DESC
            ");

            // Combine and sort by date
            $combined = array_merge($disposals, $returns, $manuals);
            usort($combined, function($a, $b) {
                return strtotime($b['Date']) - strtotime($a['Date']);
            });
            
            return $combined;
            
        case 'inventory_adjustments':
            return $db->fetchAll("SELECT * FROM InventoryAdjustment ORDER BY AdjustmentDate DESC");
            
        case 'reports':
            return $db->fetchAll("
                SELECT r.*, CONCAT(u.FName, ' ', u.LName) as GeneratedByFullName 
                FROM Report r
                LEFT JOIN Users u ON r.UserID = u.UserID
                ORDER BY r.GeneratedDate DESC
            ");
            
        default:
            return $db->read($file);
    }
}

function save_data($file, $data) {
    global $db;
    
    switch($file) {
        case 'users':
            foreach ($data as $user) {
                $id = $user['UserID'] ?? null;
                if ($id && is_numeric($id)) {
                     $db->execute(
                        "UPDATE Users SET FName = ?, MName = ?, LName = ?, Role = ?, Username = ?, Password = ?, EmailNotifications = ?, InAppNotifications = ?, ThemePreference = ? WHERE UserID = ?",
                        [
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $user['Username'],
                            $user['Password'],
                            $user['EmailNotifications'] ?? 1,
                            $user['InAppNotifications'] ?? 1,
                            $user['ThemePreference'] ?? 'system',
                            $id
                        ]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO Users (FName, MName, LName, Role, Username, Password, EmailNotifications, InAppNotifications, ThemePreference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $user['Username'],
                            $user['Password'],
                            $user['EmailNotifications'] ?? 1,
                            $user['InAppNotifications'] ?? 1,
                            $user['ThemePreference'] ?? 'system'
                        ]
                    );
                }
            }
            return true;
            
        case 'items':
            foreach ($data as $item) {
                $id = $item['ItemID'] ?? null;
                
                // Try to find existing item to update
                // The API passes the full list of items every time (legacy behavior).
                // So we must check existence to avoid duplicates.
                
                $exists = false;
                if ($id) {
                    $check = $db->fetchOne("SELECT ItemID FROM Item WHERE ItemID = ?", [$id]);
                    if ($check) {
                         $db->execute(
                            "UPDATE Item SET ItemName = ?, ItemType = ?, UnitOfMeasure = ? WHERE ItemID = ?",
                            [$item['ItemName'], $item['ItemType'], $item['UnitOfMeasure'], $id]
                        );
                        $exists = true;
                    }
                }
                
                // If not found by ID (or ID is generated like 'I0001' but not in DB yet?)
                // Note: If API passes 'I0001', we should trust it if we want to maintain the legacy ID format.
                // But schema has AUTO_INCREMENT on ItemID (INT).
                // API generates string IDs 'I0001'.
                // DB Schema violation: ItemID is INT.
                // This explains why inserts might fail if they pass 'I0001' to an INT column.
                // Or maybe they pass just the numeric part?
                // API code: $newItemId = 'I' . str_pad($maxId + 1 ...);
                // Schema: ItemID INT AUTO_INCREMENT.
                
                // Workaround: We cannot insert 'I0001' into INT. 
                // We should let the DB handle IDs (Auto Inc) or change Schema.
                // But existing API code relies on IXXXX format. 
                // If I change API to use DB IDs, I break frontend maybe?
                // Let's assume for now we just want to update if we can match.
                
                if (!$exists) {
                     // If we are here, it's a new item or one we couldn't match.
                     // Insert without ID (let DB generate) or if we really need custom ID, we'd need to change schema.
                     // Assuming DB AutoInc is the source of truth now.
                     $db->execute(
                        "INSERT INTO Item (ItemName, ItemType, UnitOfMeasure) VALUES (?, ?, ?)",
                        [$item['ItemName'], $item['ItemType'], $item['UnitOfMeasure']]
                    );
                }
            }
            return true;

        case 'warehouses':
            foreach ($data as $wh) {
                $id = $wh['WarehouseID'] ?? null;
                if ($id && is_numeric($id)) {
                     // Update logic if needed
                } else {
                    $existing = $db->fetchOne("SELECT WarehouseID FROM Warehouse WHERE WarehouseName = ?", [$wh['WarehouseName']]);
                    if (!$existing) {
                        $db->execute(
                            "INSERT INTO Warehouse (WarehouseName, Location, WarehouseType) VALUES (?, ?, ?)",
                            [$wh['WarehouseName'], $wh['Location'], $wh['WarehouseType']]
                        );
                    }
                }
            }
            return true;

        case 'suppliers':
             foreach ($data as $sup) {
                $id = $sup['SupplierID'] ?? null;
                if ($id && is_numeric($id)) {
                    $db->execute("UPDATE Supplier SET Name = ?, Address = ?, ContactInfo = ? WHERE SupplierID = ?", [$sup['Name'], $sup['Address'] ?? null, $sup['ContactInfo'] ?? null, $id]);
                } else {
                    $db->execute("INSERT INTO Supplier (Name, Address, ContactInfo) VALUES (?, ?, ?)", [$sup['Name'], $sup['Address'] ?? null, $sup['ContactInfo'] ?? null]);
                }
             }
             return true;
            
        case 'inventory':
            foreach ($data as $batch) {
                $id = $batch['BatchID'] ?? null;
                if ($id && is_numeric($id)) {
                     $db->execute("UPDATE CentralInventoryBatch SET QuantityOnHand = ?, QuantityReleased = ? WHERE BatchID = ?", 
                        [$batch['QuantityOnHand'], $batch['QuantityReleased'] ?? 0, $id]);
                } else {
                     // Resolve ItemID (Handle 'I0001' string format vs INT database ID)
                     $resolvedItemId = $batch['ItemID'];
                     if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
                         $resolvedItemId = (int)$matches[1];
                     }
                     
                     $db->execute(
                         "INSERT INTO CentralInventoryBatch (ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)",
                         [
                             $resolvedItemId, $batch['WarehouseID'] ?? 1, $batch['ExpiryDate'] ?? null,
                             $batch['QuantityOnHand'], $batch['QuantityReleased'] ?? 0, $batch['UnitCost'] ?? 0,
                             $batch['DateReceived'] ?? date('Y-m-d')
                         ]
                     );
                }
            }
            return true;
            
        case 'procurement_orders':
            foreach ($data as $po) {
                $id = $po['POID'] ?? null;
                if ($id && is_numeric($id)) {
                    $db->execute("UPDATE ProcurementOrder SET StatusType = ? WHERE POID = ?", [$po['StatusType'], $id]);
                } else {
                    // Resolve SupplierID
                    $supplierId = $po['SupplierID'] ?? null;
                    if (empty($supplierId) && !empty($po['SupplierName'])) {
                        // Try to find existing supplier by name
                        $existingSupplier = $db->fetchOne("SELECT SupplierID FROM Supplier WHERE Name = ?", [$po['SupplierName']]);
                        if ($existingSupplier) {
                            $supplierId = $existingSupplier['SupplierID'];
                        } else {
                            // Create new supplier
                            $db->execute("INSERT INTO Supplier (Name, Address) VALUES (?, ?)", [$po['SupplierName'], $po['SupplierAddress'] ?? '']);
                            $supplierId = $db->lastInsertId();
                        }
                    }

                    // Resolve ContractID and auto-generate if needed
                    $contractId = null;
                    if (!empty($po['ContractNumber'])) {
                        $existingContract = $db->fetchOne("SELECT ContractID FROM Contract WHERE ContractNumber = ?", [$po['ContractNumber']]);
                        if ($existingContract) {
                            $contractId = $existingContract['ContractID'];
                            // Update existing contract if dates/amount are provided
                            if (!empty($po['ContractStartDate']) || !empty($po['ContractEndDate']) || !empty($po['ContractAmount'])) {
                                $db->execute(
                                    "UPDATE Contract SET StartDate = ?, EndDate = ?, ContractAmount = ? WHERE ContractID = ?",
                                    [
                                        $po['ContractStartDate'] ?: null,
                                        $po['ContractEndDate'] ?: null,
                                        $po['ContractAmount'] ?: null,
                                        $contractId
                                    ]
                                );
                            }
                        } else {
                            // Create new contract
                            $db->execute(
                                "INSERT INTO Contract (SupplierID, ContractNumber, StartDate, EndDate, ContractAmount, StatusType) VALUES (?, ?, ?, ?, ?, ?)",
                                [
                                    $supplierId ?: null, 
                                    $po['ContractNumber'], 
                                    $po['ContractStartDate'] ?? null,
                                    $po['ContractEndDate'] ?? null,
                                    $po['ContractAmount'] ?? null,
                                    'Active'
                                ]
                            );
                            $contractId = $db->lastInsertId();
                        }
                    }

                    // Logic: Insert -> Get ID -> Generate PONumber -> Update
                    $db->execute(
                        "INSERT INTO ProcurementOrder (UserID, SupplierID, HealthCenterID, ContractID, DocumentType, PONumber, PODate, StatusType) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $po['UserID'], 
                            $supplierId ?: null, 
                            $po['HealthCenterID'] ?? null, 
                            $contractId,
                            $po['DocumentType'] ?? 'PO',
                            'TEMP', 
                            $po['PODate'], $po['StatusType']
                        ]
                    );
                    $newId = $db->lastInsertId();
                    $poNum = 'PO-' . date('Y') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
                    $db->execute("UPDATE ProcurementOrder SET PONumber = ? WHERE POID = ?", [$poNum, $newId]);
                    
                    $itemsKey = isset($po['ProcurementOrderItems']) ? 'ProcurementOrderItems' : 'PurchaseOrderItems';
                    if (!empty($po[$itemsKey])) {
                        foreach ($po[$itemsKey] as $item) {
                            // Resolve ItemID (Handle 'I0001' string format vs INT database ID)
                            $resolvedItemId = $item['ItemID'];
                            if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
                                $resolvedItemId = (int)$matches[1];
                            }
                            
                            $db->execute(
                                "INSERT INTO ProcurementOrderItem (POID, ItemID, QuantityOrdered, UnitCost, ExpiryDate) VALUES (?, ?, ?, ?, ?)",
                                [
                                    $newId, 
                                    $resolvedItemId, 
                                    $item['QuantityOrdered'], 
                                    $item['UnitCost'] ?? 0,
                                    $item['ExpiryDate'] ?? null
                                ]
                            );
                        }
                    }
                }
            }
            return true;
            
        case 'requisitions':
            try {
                foreach ($data as $req) {
                    $id = $req['RequisitionID'] ?? null;
                    if ($id && is_numeric($id)) {
                        $db->execute("UPDATE Requisition SET StatusType = ? WHERE RequisitionID = ?", [$req['StatusType'], $id]);
                        if (!empty($req['ApprovalLogs'])) {
                            $lastLog = end($req['ApprovalLogs']);
                            if ($lastLog && !isset($lastLog['ApprovalLogID'])) {
                                $db->execute(
                                    "INSERT INTO ApprovalLog (RequisitionID, UserID, Decision, DecisionDate) VALUES (?, ?, ?, ?)",
                                    [$id, $lastLog['UserID'], $lastLog['Decision'], $lastLog['DecisionDate'] ?? date('Y-m-d H:i:s')]
                                );
                            }
                        }
                    } else {
                        // Resolve HealthCenterID
                        $hcId = $req['HealthCenterID'] ?? null;
                        if (empty($hcId) && !empty($req['HealthCenterName'])) {
                            // Try to find existing by name
                            $existing = $db->fetchOne("SELECT HealthCenterID FROM HealthCenters WHERE Name = ?", [$req['HealthCenterName']]);
                            if ($existing) {
                                $hcId = $existing['HealthCenterID'];
                            } else {
                                // Create new
                                $db->execute("INSERT INTO HealthCenters (Name, Address) VALUES (?, ?)", [$req['HealthCenterName'], $req['HealthCenterAddress'] ?? '']);
                                $hcId = $db->lastInsertId();
                            }
                        }

                        $tempNum = 'TEMP-' . time() . '-' . rand(1000, 9999);
                        $res = $db->execute(
                            "INSERT INTO Requisition (HealthCenterID, UserID, RequisitionNumber, RequestDate, StatusType) VALUES (?, ?, ?, ?, ?)",
                            [
                                $hcId ?: null, $req['UserID'], $tempNum, $req['RequestDate'] ?? $req['RequestedDate'], $req['StatusType']
                            ]
                        );
                        
                        if (!$res) {
                            throw new Exception("Initial Requisition insert failed.");
                        }
                        
                        $newId = $db->lastInsertId();
                        $reqNum = 'REQ-' . date('Y') . '-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
                        $res = $db->execute("UPDATE Requisition SET RequisitionNumber = ? WHERE RequisitionID = ?", [$reqNum, $newId]);
                        
                        if (!$res) {
                            throw new Exception("Requisition number update failed.");
                        }
                        
                        if (!empty($req['RequisitionItems'])) {
                            foreach ($req['RequisitionItems'] as $item) {
                                // Resolve ItemID (Handle 'I0001' string format vs INT database ID)
                                $resolvedItemId = $item['ItemID'];
                                if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
                                    $resolvedItemId = (int)$matches[1];
                                }
                                
                                $res = $db->execute(
                                    "INSERT INTO RequisitionItem (RequisitionID, ItemID, QuantityRequested) VALUES (?, ?, ?)",
                                    [$newId, $resolvedItemId, $item['QuantityRequested']]
                                );
                                
                                if (!$res) {
                                    throw new Exception("Requisition item insert failed for ItemID: $resolvedItemId");
                                }
                            }
                        }
                    }
                }
                return true;
            } catch (PDOException $e) {
                return false;
            }
            
            
        case 'issuances':
            foreach ($data as $iss) {
                 $db->execute(
                     "INSERT INTO Issuance (RequisitionID, UserID, IssueDate, StatusType) VALUES (?, ?, ?, ?)",
                     [$iss['RequisitionID'], $iss['IssuedByUserID'] ?? $iss['UserID'], $iss['DateIssued'] ?? $iss['IssueDate'], $iss['StatusType'] ?? 'Issued']
                 );
                 $newId = $db->lastInsertId();
                 
                 if (!empty($iss['IssuedItems'])) {
                     foreach ($iss['IssuedItems'] as $item) {
                         $db->execute(
                             "INSERT INTO IssuanceItem (IssuanceID, BatchID, RequisitionItemID, QuantityIssued) VALUES (?, ?, ?, ?)",
                             [$newId, $item['BatchID'], $item['RequisitionItemID'] ?? null, $item['QuantityIssued']]
                         );
                     }
                 }
            }
            return true;
            
        case 'receivings':
            foreach ($data as $rcv) {
                 $db->execute(
                     "INSERT INTO Receiving (UserID, POID, ReceivedDate) VALUES (?, ?, ?)",
                     [$rcv['UserID'], $rcv['POID'], $rcv['ReceivedDate']]
                 );
                  $newRcvId = $db->lastInsertId();
                  if (!empty($rcv['ReceivedItems'])) {
                      foreach ($rcv['ReceivedItems'] as $batch) {
                          
                          // Resolve ItemID (Handle 'I0001' string format vs INT database ID)
                          $resolvedItemId = $batch['ItemID'];
                          if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
                              $resolvedItemId = (int)$matches[1];
                          }
                          // echo "DEBUG: Resolved ItemID " . $batch['ItemID'] . " -> " . $resolvedItemId . "\n";
                          
                          try {
                              // Check if batch exists...
                              $existingBatch = $db->fetchOne(
                                  "SELECT BatchID, QuantityOnHand FROM CentralInventoryBatch 
                                   WHERE ItemID = ? AND WarehouseID = ? AND (ExpiryDate = ? OR (ExpiryDate IS NULL AND ? IS NULL)) AND UnitCost = ?",
                                  [
                                      $resolvedItemId, 
                                      $batch['WarehouseID'] ?? 1, 
                                      $batch['ExpiryDate'] ?? null, 
                                      $batch['ExpiryDate'] ?? null,
                                      $batch['UnitCost'] ?? 0
                                  ]
                              );

                              if ($existingBatch) {
                                  // Update
                                  $db->execute("UPDATE CentralInventoryBatch SET QuantityOnHand = QuantityOnHand + ? WHERE BatchID = ?", [$batch['QuantityOnHand'], $existingBatch['BatchID']]);
                                  $newBatchId = $existingBatch['BatchID'];
                              } else {
                                  // Insert
                                  $db->execute(
                                      "INSERT INTO CentralInventoryBatch (ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                      [
                                          $resolvedItemId, $batch['WarehouseID'] ?? 1, $batch['ExpiryDate'] ?? null,
                                          $batch['QuantityOnHand'], 0, $batch['UnitCost'] ?? 0, $batch['DateReceived']
                                      ]
                                  );
                                  $newBatchId = $db->lastInsertId();
                              }
                              
                              $db->execute(
                                  "INSERT INTO ReceivingItem (ReceivingID, BatchID, QuantityReceived) VALUES (?, ?, ?)",
                                  [$newRcvId, $newBatchId, $batch['QuantityOnHand']]
                              );
                              
                          } catch (Exception $e) {
                               error_log("ERROR in receivings loop: " . $e->getMessage());
                               throw $e; // Re-throw to fail cleanly
                          }
                      }
                  }
            }
            return true;
            
        case 'reports':
            foreach($data as $rpt) {
                // If it's a new report (no ID or string ID from legacy), insert it
                if (!isset($rpt['ReportID']) || !is_numeric($rpt['ReportID'])) {
                    $db->execute(
                        "INSERT INTO Report (UserID, ReportType, GeneratedDate, GeneratedForOffice) VALUES (?, ?, ?, ?)",
                        [$rpt['UserID'], $rpt['ReportType'], $rpt['GeneratedDate'], $rpt['GeneratedForOffice']]
                    );
                }
            }
            return true;

        case 'approval_logs':
            foreach($data as $log) {
                if (!isset($log['ApprovalLogID'])) {
                    $db->execute(
                        "INSERT INTO ApprovalLog (RequisitionID, UserID, Decision, DecisionDate) VALUES (?, ?, ?, ?)",
                        [$log['RequisitionID'], $log['UserID'], $log['Decision'], $log['DecisionDate'] ?? date('Y-m-d H:i:s')]
                    );
                }
            }
            return true;

        case 'issuance_items':
            foreach($data as $item) {
                if (!isset($item['IssuanceItemID'])) {
                    $db->execute(
                        "INSERT INTO IssuanceItem (IssuanceID, BatchID, RequisitionItemID, QuantityIssued) VALUES (?, ?, ?, ?)",
                        [$item['IssuanceID'], $item['BatchID'], $item['RequisitionItemID'] ?? null, $item['QuantityIssued']]
                    );
                }
            }
            return true;

        case 'requisition_adjustments':
            foreach($data as $adj) {
                if (!isset($adj['RequisitionAdjustmentID']) || !is_numeric($adj['RequisitionAdjustmentID'])) {
                    $db->execute(
                        "INSERT INTO RequisitionAdjustment (IssuanceID, UserID, AdjustmentType, AdjustmentDate, Reason) VALUES (?, ?, ?, ?, ?)",
                        [$adj['IssuanceID'] ?? null, $adj['UserID'], $adj['AdjustmentType'], $adj['AdjustmentDate'], $adj['Reason']]
                    );
                }
            }
            return true;

        case 'requisition_adjustment_details':
            foreach($data as $det) {
                if (!isset($det['RAD'])) {
                    $db->execute(
                        "INSERT INTO RequisitionAdjustmentDetail (RequisitionAdjustmentID, BatchID, QuantityAdjusted) VALUES (?, ?, ?)",
                        [$det['RequisitionAdjustmentID'], $det['BatchID'], $det['QuantityAdjusted']]
                    );
                }
            }
            return true;

        case 'security_logs':
            foreach($data as $d) {
                $db->execute("INSERT INTO SecurityLog (UserID, ActionType, ActionDescription, IPAddress, ModuleAffected, ActionDate) VALUES (?, ?, ?, ?, ?, ?)",
                [$d['UserID'] === 'unknown' ? null : $d['UserID'], $d['ActionType'], $d['Description'] ?? $d['ActionDescription'], $d['IPAddress'], $d['Status'] ?? 'Info', $d['ActionDate']]);
            }
            return true;

        case 'transaction_logs':
             foreach($data as $d) {
                $db->execute("INSERT INTO TransactionAuditLog (UserID, ReferenceType, ReferenceID, ActionType, ActionDate) VALUES (?, ?, ?, ?, ?)",
                [$d['UserID'], $d['ReferenceType'], isset($d['ReferenceID']) && is_numeric($d['ReferenceID']) ? $d['ReferenceID'] : 0, $d['ActionType'], $d['ActionDate']]);
             }
             return true;
             
        default:
            return true;
    }
}

// Log Security Event
function log_security_event($userId, $actionType, $status, $description) {
    global $db;
    
    $logId = 'SEC-' . time() . '-' . substr(uniqid(), -5);
    $db->execute(
        "INSERT INTO SecurityLog (SecurityLogID, UserID, ActionType, ActionDescription, IPAddress, ModuleAffected, ActionDate) 
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
            $logId,
            $userId,
            $actionType,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $status, // Using ModuleAffected for status
            date('Y-m-d H:i:s')
        ]
    );
}

// Log Transaction
function logTransaction($actionType, $referenceType, $referenceId) {
    global $db;
    if (!isLoggedIn()) return;
    
    $user = $_SESSION['user'];
    $logId = 'AUD-' . time() . '-' . substr(uniqid(), -5);
    
    $db->execute(
        "INSERT INTO TransactionAuditLog (AuditLogID, UserID, ReferenceType, ActionType, ActionDate) 
         VALUES (?, ?, ?, ?, ?)",
        [
            $logId,
            $user['UserID'] ?? '0',
            $referenceType,
            $actionType,
            date('Y-m-d H:i:s')
        ]
    );
}
