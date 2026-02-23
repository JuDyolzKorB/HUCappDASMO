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

    // Get connection to a specific Health Center database
    public static function getHCConnection($healthCenterId) {
        require_once __DIR__ . '/hc_db_manager.php';
        
        $dbName = HCDatabaseManager::ensureHealthCenterDatabase($healthCenterId);
        if (!$dbName) {
            return null;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . $dbName . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("HC Database Connection Error: " . $e->getMessage());
            return null;
        }
    }

    // Rollback transaction
    public function rollback() {
        return $this->conn->rollBack();
    }

    // Broadcast a system-wide update event for real-time syncing
    public function broadcastUpdate($eventType, $data = null) {
        $json = $data ? json_encode($data) : null;
        try {
            $stmt = $this->conn->prepare("INSERT INTO SystemUpdates (EventType, EventData) VALUES (?, ?)");
            return $stmt->execute([$eventType, $json]);
        } catch (PDOException $e) {
            error_log("Broadcast Update Error: " . $e->getMessage());
            return false;
        }
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
            return $db->fetchAll("SELECT UserID, FName as FirstName, MName as MiddleName, LName as LastName, Role, HealthCenterID, Username, Password FROM Users");
            
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
            $pos = $db->fetchAll("SELECT po.*, s.Name as SupplierName, hc.Name as HealthCenterName, c.ContractNumber as LinkedContractNumber FROM ProcurementOrder po LEFT JOIN Supplier s ON po.SupplierID = s.SupplierID LEFT JOIN HealthCenters hc ON po.HealthCenterID = hc.HealthCenterID LEFT JOIN Contract c ON po.ContractID = c.ContractID ORDER BY po.PODate DESC");
            if (empty($pos)) return [];
            $poIds = array_column($pos, 'POID');
            $items = $db->fetchAll("SELECT * FROM ProcurementOrderItem WHERE POID IN (" . implode(',', $poIds) . ")");
            $itemsByPO = [];
            foreach ($items as $item) { $itemsByPO[$item['POID']][] = $item; }
            foreach ($pos as &$po) {
                if (empty($po['ContractNumber']) && !empty($po['LinkedContractNumber'])) $po['ContractNumber'] = $po['LinkedContractNumber'];
                $po['ProcurementOrderItems'] = $itemsByPO[$po['POID']] ?? [];
                $po['ApprovalLogs'] = [];
            }
            return $pos;
            
        case 'requisitions':
            $reqs = $db->fetchAll("SELECT r.*, i.IssuanceID, hc.Name as HealthCenterName, u.FName, u.LName FROM Requisition r LEFT JOIN Issuance i ON r.RequisitionID = i.RequisitionID LEFT JOIN HealthCenters hc ON r.HealthCenterID = hc.HealthCenterID LEFT JOIN Users u ON r.UserID = u.UserID ORDER BY r.RequestDate DESC");
            if (empty($reqs)) return [];
            $reqIds = array_column($reqs, 'RequisitionID');
            $issIds = array_filter(array_column($reqs, 'IssuanceID'));
            
            $items = $db->fetchAll("SELECT ri.*, i.ItemName FROM RequisitionItem ri JOIN Item i ON ri.ItemID = i.ItemID WHERE ri.RequisitionID IN (" . implode(',', $reqIds) . ")");
            $itemsByReq = []; foreach ($items as $item) { $itemsByReq[$item['RequisitionID']][] = $item; }
            
            $issuedByIss = [];
            if (!empty($issIds)) {
                $issued = $db->fetchAll("SELECT ii.*, i.ItemName, cib.ItemID FROM IssuanceItem ii JOIN CentralInventoryBatch cib ON ii.BatchID = cib.BatchID JOIN Item i ON cib.ItemID = i.ItemID WHERE ii.IssuanceID IN (" . implode(',', $issIds) . ")");
                foreach ($issued as $item) { $issuedByIss[$item['IssuanceID']][] = $item; }
            }
            
            $logs = $db->fetchAll("SELECT al.*, CONCAT(u.FName, ' ', u.LName) as ApprovedByFullName FROM ApprovalLog al LEFT JOIN Users u ON al.UserID = u.UserID WHERE al.RequisitionID IN (" . implode(',', $reqIds) . ") ORDER BY al.DecisionDate DESC");
            $logsByReq = []; foreach ($logs as $log) { $logsByReq[$log['RequisitionID']][] = $log; }
            
            $adjs = $db->fetchAll("SELECT ra.*, i.RequisitionID, CONCAT(u.FName, ' ', u.LName) as AdjustedByFullName FROM RequisitionAdjustment ra JOIN Issuance i ON ra.IssuanceID = i.IssuanceID LEFT JOIN Users u ON ra.UserID = u.UserID WHERE i.RequisitionID IN (" . implode(',', $reqIds) . ") ORDER BY ra.AdjustmentDate DESC");
            $adjsByReq = []; foreach ($adjs as $adj) { $adjsByReq[$adj['RequisitionID']][] = $adj; }

            foreach ($reqs as &$req) {
                $rid = $req['RequisitionID'];
                $isid = $req['IssuanceID'];
                $req['RequisitionItems'] = $itemsByReq[$rid] ?? [];
                $req['IssuedItems'] = ($isid && isset($issuedByIss[$isid])) ? $issuedByIss[$isid] : [];
                $req['ApprovalLogs'] = $logsByReq[$rid] ?? [];
                $req['Adjustments'] = $adjsByReq[$rid] ?? [];
                $req['RequestedByFullName'] = ($req['FName'] ?? '') . ' ' . ($req['LName'] ?? '');
                if (empty($req['HealthCenterName'])) $req['HealthCenterName'] = 'Unknown';
            }
            return $reqs;
            
        case 'issuances':
            $issuances = $db->fetchAll("SELECT i.*, u.FName, u.LName FROM Issuance i LEFT JOIN Users u ON i.UserID = u.UserID ORDER BY i.IssueDate DESC");
            if (empty($issuances)) return [];
            $issIds = array_column($issuances, 'IssuanceID');
            $items = $db->fetchAll("SELECT * FROM IssuanceItem WHERE IssuanceID IN (" . implode(',', $issIds) . ")");
            $itemsByIss = []; foreach ($items as $item) { $itemsByIss[$item['IssuanceID']][] = $item; }
            foreach ($issuances as &$iss) {
                $iss['IssuedItems'] = $itemsByIss[$iss['IssuanceID']] ?? [];
                $iss['IssuedByFullName'] = ($iss['FName'] ?? '') . ' ' . ($iss['LName'] ?? '');
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
                     $hcId = $user['HealthCenterID'] ?? null;
                     if ($hcId === '') $hcId = null;

                     if (!$db->execute(
                        "UPDATE Users SET FName = ?, MName = ?, LName = ?, Role = ?, HealthCenterID = ?, Username = ?, Password = ?, EmailNotifications = ?, InAppNotifications = ?, ThemePreference = ? WHERE UserID = ?",
                        [
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $hcId,
                            $user['Username'],
                            $user['Password'],
                            $user['EmailNotifications'] ?? 1,
                            $user['InAppNotifications'] ?? 1,
                            $user['ThemePreference'] ?? 'system',
                            $id
                        ]
                    )) return false;
                } else {
                    $hcId = $user['HealthCenterID'] ?? null;
                    if ($hcId === '') $hcId = null;
                    
                    if (!$db->execute(
                        "INSERT INTO Users (FName, MName, LName, Role, HealthCenterID, Username, Password, EmailNotifications, InAppNotifications, ThemePreference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $hcId,
                            $user['Username'],
                            $user['Password'],
                            $user['EmailNotifications'] ?? 1,
                            $user['InAppNotifications'] ?? 1,
                            $user['ThemePreference'] ?? 'system'
                        ]
                    )) return false;
                }
            }
            $db->broadcastUpdate('users_updated');
            return true;
            
        case 'items':
            foreach ($data as $item) {
                $id = $item['ItemID'] ?? null;
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
                
                if (!$exists) {
                     if (!$db->execute(
                        "INSERT INTO Item (ItemName, ItemType, UnitOfMeasure) VALUES (?, ?, ?)",
                        [$item['ItemName'], $item['ItemType'], $item['UnitOfMeasure']]
                    )) return false;
                }
            }
            $db->broadcastUpdate('items_updated');
            return true;

        case 'warehouses':
            foreach ($data as $wh) {
                $id = $wh['WarehouseID'] ?? null;
                if (!$id || !is_numeric($id)) {
                    $existing = $db->fetchOne("SELECT WarehouseID FROM Warehouse WHERE WarehouseName = ?", [$wh['WarehouseName']]);
                    if (!$existing) {
                        $db->execute("INSERT INTO Warehouse (WarehouseName, Location, WarehouseType) VALUES (?, ?, ?)", [$wh['WarehouseName'], $wh['Location'], $wh['WarehouseType']]);
                    }
                }
            }
            $db->broadcastUpdate('warehouses_updated');
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
             $db->broadcastUpdate('suppliers_updated');
             return true;
            
        case 'inventory':
            foreach ($data as $batch) {
                $id = $batch['BatchID'] ?? null;
                if ($id && is_numeric($id)) {
                     $db->execute("UPDATE CentralInventoryBatch SET QuantityOnHand = ?, QuantityReleased = ? WHERE BatchID = ?", 
                        [$batch['QuantityOnHand'], $batch['QuantityReleased'] ?? 0, $id]);
                } else {
                     $resolvedItemId = $batch['ItemID'];
                     if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
                         $resolvedItemId = (int)$matches[1];
                     }
                     $db->execute(
                         "INSERT INTO CentralInventoryBatch (ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)",
                         [$resolvedItemId, $batch['WarehouseID'] ?? 1, $batch['ExpiryDate'] ?? null, $batch['QuantityOnHand'], $batch['QuantityReleased'] ?? 0, $batch['UnitCost'] ?? 0, $batch['DateReceived'] ?? date('Y-m-d')]
                     );
                }
            }
            $db->broadcastUpdate('inventory_updated');
            return true;
            
        case 'health_centers':
            foreach ($data as $hc) {
                $id = $hc['HealthCenterID'] ?? null;
                if ($id && is_numeric($id)) {
                    $db->execute("UPDATE HealthCenters SET Name = ?, Address = ? WHERE HealthCenterID = ?", [$hc['Name'], $hc['Address'] ?? '', $id]);
                } else {
                    $db->execute("INSERT INTO HealthCenters (Name, Address) VALUES (?, ?)", [$hc['Name'], $hc['Address'] ?? '']);
                }
            }
            $db->broadcastUpdate('health_centers_updated');
            return true;

        case 'procurement_orders':
            foreach ($data as $po) {
                $id = $po['POID'] ?? null;
                if ($id && is_numeric($id)) {
                    $db->execute("UPDATE ProcurementOrder SET StatusType = ? WHERE POID = ?", [$po['StatusType'], $id]);
                } else {
                    $supplierId = $po['SupplierID'] ?? null;
                    if (empty($supplierId) && !empty($po['SupplierName'])) {
                        $existingSupplier = $db->fetchOne("SELECT SupplierID FROM Supplier WHERE Name = ?", [$po['SupplierName']]);
                        if ($existingSupplier) {
                            $supplierId = $existingSupplier['SupplierID'];
                        } else {
                            $db->execute("INSERT INTO Supplier (Name, Address) VALUES (?, ?)", [$po['SupplierName'], $po['SupplierAddress'] ?? '']);
                            $supplierId = $db->lastInsertId();
                        }
                    }
                    $contractId = null;
                    if (!empty($po['ContractNumber'])) {
                        $existingContract = $db->fetchOne("SELECT ContractID FROM Contract WHERE ContractNumber = ?", [$po['ContractNumber']]);
                        if ($existingContract) {
                            $contractId = $existingContract['ContractID'];
                        } else {
                            $db->execute("INSERT INTO Contract (SupplierID, ContractNumber, StartDate, EndDate, ContractAmount, StatusType) VALUES (?, ?, ?, ?, ?, ?)",
                                [$supplierId ?: null, $po['ContractNumber'], $po['ContractStartDate'] ?? null, $po['ContractEndDate'] ?? null, $po['ContractAmount'] ?? null, 'Active']);
                            $contractId = $db->lastInsertId();
                        }
                    }
                    $db->execute("INSERT INTO ProcurementOrder (UserID, SupplierID, HealthCenterID, ContractID, DocumentType, PONumber, PODate, StatusType) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$po['UserID'], $supplierId ?: null, $po['HealthCenterID'] ?? null, $contractId, $po['DocumentType'] ?? 'PO', 'TEMP', $po['PODate'], $po['StatusType']]);
                    $newId = $db->lastInsertId();
                    $poNum = 'PO-' . date('Y') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
                    $db->execute("UPDATE ProcurementOrder SET PONumber = ? WHERE POID = ?", [$poNum, $newId]);
                    if (!empty($po['ProcurementOrderItems'] ?? $po['PurchaseOrderItems'])) {
                        foreach (($po['ProcurementOrderItems'] ?? $po['PurchaseOrderItems']) as $item) {
                            $resolvedItemId = $item['ItemID'];
                            if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) $resolvedItemId = (int)$matches[1];
                            $db->execute("INSERT INTO ProcurementOrderItem (POID, ItemID, QuantityOrdered, UnitCost, ExpiryDate) VALUES (?, ?, ?, ?, ?)",
                                [$newId, $resolvedItemId, $item['QuantityOrdered'], $item['UnitCost'] ?? 0, $item['ExpiryDate'] ?? null]);
                        }
                    }
                }
            }
            $db->broadcastUpdate('procurement_orders_updated');
            return true;
            
        case 'requisitions':
            try {
                foreach ($data as $req) {
                    $id = $req['RequisitionID'] ?? null;
                    if ($id && is_numeric($id)) {
                        $db->execute("UPDATE Requisition SET StatusType = ? WHERE RequisitionID = ?", [$req['StatusType'], $id]);
                    } else {
                        $hcId = $req['HealthCenterID'] ?? null;
                        if (empty($hcId) && !empty($req['HealthCenterName'])) {
                            $existing = $db->fetchOne("SELECT HealthCenterID FROM HealthCenters WHERE Name = ?", [$req['HealthCenterName']]);
                            if ($existing) $hcId = $existing['HealthCenterID'];
                            else {
                                $db->execute("INSERT INTO HealthCenters (Name, Address) VALUES (?, ?)", [$req['HealthCenterName'], $req['HealthCenterAddress'] ?? '']);
                                $hcId = $db->lastInsertId();
                            }
                        }
                        $tempNum = 'TEMP-' . time() . '-' . rand(1000, 9999);
                        $db->execute("INSERT INTO Requisition (HealthCenterID, UserID, RequisitionNumber, RequestDate, StatusType) VALUES (?, ?, ?, ?, ?)",
                            [$hcId ?: null, $req['UserID'], $tempNum, $req['RequestDate'] ?? $req['RequestedDate'], $req['StatusType']]);
                        $newId = $db->lastInsertId();
                        $reqNum = 'REQ-' . date('Y') . '-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
                        $db->execute("UPDATE Requisition SET RequisitionNumber = ? WHERE RequisitionID = ?", [$reqNum, $newId]);
                        if (!empty($req['RequisitionItems'])) {
                            foreach ($req['RequisitionItems'] as $item) {
                                $resolvedItemId = $item['ItemID'];
                                if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) $resolvedItemId = (int)$matches[1];
                                $db->execute("INSERT INTO RequisitionItem (RequisitionID, ItemID, QuantityRequested) VALUES (?, ?, ?)", [$newId, $resolvedItemId, $item['QuantityRequested']]);
                            }
                        }
                    }
                }
                $db->broadcastUpdate('requisitions_updated');
                return true;
            } catch (PDOException $e) { return false; }
            
        case 'issuances':
            foreach ($data as $iss) {
                 $db->execute("INSERT INTO Issuance (RequisitionID, UserID, IssueDate, StatusType) VALUES (?, ?, ?, ?)",
                     [$iss['RequisitionID'], $iss['IssuedByUserID'] ?? $iss['UserID'], $iss['DateIssued'] ?? $iss['IssueDate'], $iss['StatusType'] ?? 'Issued']);
                 $newId = $db->lastInsertId();
                 if (!empty($iss['IssuedItems'])) {
                     foreach ($iss['IssuedItems'] as $item) {
                         $db->execute("INSERT INTO IssuanceItem (IssuanceID, BatchID, RequisitionItemID, QuantityIssued) VALUES (?, ?, ?, ?)", [$newId, $item['BatchID'], $item['RequisitionItemID'] ?? null, $item['QuantityIssued']]);
                     }
                 }
            }
            $db->broadcastUpdate('issuances_updated');
            return true;
            
        case 'receivings':
            foreach ($data as $rcv) {
                 $db->execute("INSERT INTO Receiving (UserID, POID, ReceivedDate) VALUES (?, ?, ?)", [$rcv['UserID'], $rcv['POID'], $rcv['ReceivedDate']]);
                   $newRcvId = $db->lastInsertId();
                   if (!empty($rcv['ReceivedItems'])) {
                       foreach ($rcv['ReceivedItems'] as $batch) {
                           $resolvedItemId = $batch['ItemID'];
                           if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) $resolvedItemId = (int)$matches[1];
                           try {
                               $existingBatch = $db->fetchOne("SELECT BatchID FROM CentralInventoryBatch WHERE ItemID = ? AND WarehouseID = ? AND (ExpiryDate = ? OR (ExpiryDate IS NULL AND ? IS NULL)) AND UnitCost = ?",
                                   [$resolvedItemId, $batch['WarehouseID'] ?? 1, $batch['ExpiryDate'] ?? null, $batch['ExpiryDate'] ?? null, $batch['UnitCost'] ?? 0]);
                               if ($existingBatch) {
                                   $db->execute("UPDATE CentralInventoryBatch SET QuantityOnHand = QuantityOnHand + ? WHERE BatchID = ?", [$batch['QuantityOnHand'], $existingBatch['BatchID']]);
                                   $newBatchId = $existingBatch['BatchID'];
                               } else {
                                   $db->execute("INSERT INTO CentralInventoryBatch (ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                       [$resolvedItemId, $batch['WarehouseID'] ?? 1, $batch['ExpiryDate'] ?? null, $batch['QuantityOnHand'], 0, $batch['UnitCost'] ?? 0, $batch['DateReceived']]);
                                   $newBatchId = $db->lastInsertId();
                               }
                               $db->execute("INSERT INTO ReceivingItem (ReceivingID, BatchID, QuantityReceived) VALUES (?, ?, ?)", [$newRcvId, $newBatchId, $batch['QuantityOnHand']]);
                           } catch (Exception $e) { throw $e; }
                       }
                   }
            }
            $db->broadcastUpdate('receivings_updated');
            $db->broadcastUpdate('inventory_updated');
            return true;
            
        case 'reports':
            foreach($data as $rpt) {
                if (!isset($rpt['ReportID']) || !is_numeric($rpt['ReportID'])) {
                    $db->execute("INSERT INTO Report (UserID, ReportType, GeneratedDate, GeneratedForOffice) VALUES (?, ?, ?, ?)",
                        [$rpt['UserID'], $rpt['ReportType'], $rpt['GeneratedDate'], $rpt['GeneratedForOffice']]);
                }
            }
            $db->broadcastUpdate('reports_updated');
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
            $db->broadcastUpdate($file . '_updated');
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
