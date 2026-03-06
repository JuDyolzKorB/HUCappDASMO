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
            'users' => 'users',
            'health_centers' => 'healthcenters',
            'warehouses' => 'warehouse',
            'items' => 'item',
            'suppliers' => 'supplier',
            'procurement_orders' => 'procurementorder',
            'procurement_order_items' => 'procurementorderitem',
            'receivings' => 'receiving',
            'receiving_items' => 'receivingitem',
            'inventory' => 'centralinventorybatch',
            'requisitions' => 'requisition',
            'requisition_items' => 'requisitionitem',
            'approval_logs' => 'approvallog',
            'issuances' => 'issuance',
            'issuance_items' => 'issuanceitem',
            'requisition_adjustments' => 'requisitionadjustment',
            'requisition_adjustment_details' => 'requisitionadjustmentdetail',
            'notice_of_issues' => 'noticeofissue',
            'transaction_logs' => 'transactionauditlog',
            'security_logs' => 'securitylog',
            'reports' => 'report',
            'notifications' => 'notifications',
            'hc_inventory_batches' => 'hcinventorybatch',
            'patients' => 'hcpatient',
            'patient_requisitions' => 'hcpatientrequisition',
            'patient_requisition_items' => 'hcpatientrequisitionitem'
        ];
        
        return $mapping[$filename] ?? $filename;
    }

    // Execute a prepared statement
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Execute Error: " . $e->getMessage() . " | SQL: " . $sql . " | Params: " . json_encode($params));
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

    // Check if in transaction
    public function inTransaction() {
        return $this->conn->inTransaction();
    }

    // Resolve ItemID (Handle 'I0001' string format vs INT database ID)
    public function resolveItemId($itemId) {
        if (is_string($itemId) && preg_match('/^I(\d+)$/', $itemId, $matches)) {
            return (int)$matches[1];
        }
        return (int)$itemId;
    }

    // Broadcast a real-time update event to clients
    public function broadcastUpdate($eventType, $eventData = null) {
        try {
            $dataStr = $eventData ? json_encode($eventData) : '';
            return $this->execute(
                "INSERT INTO SystemUpdates (EventType, EventData) VALUES (?, ?)",
                [$eventType, $dataStr]
            );
        } catch (Exception $e) {
            error_log("Failed to broadcast update ($eventType): " . $e->getMessage());
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
            return $db->fetchAll("SELECT UserID, FName as FirstName, MName as MiddleName, LName as LastName, Role, Username, Password, HealthCenterID, EmailNotifications, InAppNotifications, ThemePreference FROM Users");
            
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
                // Ensure StartDate/EndDate/Amount are also mapped if missing from PO but present in Contract
                if ($po['ContractID']) {
                    $linkedC = $db->fetchOne("SELECT * FROM Contract WHERE ContractID = ?", [$po['ContractID']]);
                    if ($linkedC) {
                        if (empty($po['ContractStartDate'])) $po['ContractStartDate'] = $linkedC['StartDate'];
                        if (empty($po['ContractEndDate'])) $po['ContractEndDate'] = $linkedC['EndDate'];
                        if (empty($po['ContractAmount'])) $po['ContractAmount'] = $linkedC['ContractAmount'];
                    }
                }
                // Map to the names expected by details modal
                $po['StartDate'] = $po['ContractStartDate'];
                $po['EndDate'] = $po['ContractEndDate'];
                
                $po['ProcurementOrderItems'] = $itemsByPO[$po['POID']] ?? [];
                $po['ApprovalLogs'] = $db->fetchAll("
                    SELECT al.*, CONCAT(u.FName, ' ', u.LName) as ApproverFullName 
                    FROM ApprovalLog al 
                    JOIN Users u ON al.UserID = u.UserID 
                    WHERE al.ProcurementOrderID = ?
                ", [$po['POID']]);
            }
            return $pos;
            
        case 'requisitions':
            // Get requisitions with their items and approval logs
            // Join Issuance to get IssuanceID for adjustments
            $user = $_SESSION['user'] ?? null;
            $userRole = $user['Role'] ?? '';
            $userHcId = $user['HealthCenterID'] ?? null;
            $isHCUser = ($userRole === 'Health Center Staff' || $userRole === 'Health Center User');

            $sql = "SELECT r.*, i.IssuanceID, u.FName, u.LName, hc.Name as HealthCenterName
                    FROM Requisition r 
                    LEFT JOIN Issuance i ON r.RequisitionID = i.RequisitionID
                    LEFT JOIN Users u ON r.UserID = u.UserID
                    LEFT JOIN HealthCenters hc ON r.HealthCenterID = hc.HealthCenterID";
            $params = [];
            
            if ($isHCUser && $userHcId) {
                $sql .= " WHERE r.HealthCenterID = ?";
                $params[] = $userHcId;
            } else if (isset($_GET['hc_id'])) {
                 $sql .= " WHERE r.HealthCenterID = ?";
                 $params[] = $_GET['hc_id'];
            }
            
            $sql .= " ORDER BY r.RequestDate DESC";
            $reqs = $db->fetchAll($sql, $params);
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

                $req['RequestedByFullName'] = trim(($req['FName'] ?? '') . ' ' . ($req['LName'] ?? ''));
                if (empty($req['HealthCenterName'])) $req['HealthCenterName'] = 'Unknown';
            }
            return $reqs;
            break;
            
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
            
        case 'hc_inventory':
            $user = $_SESSION['user'] ?? null;
            $userRole = $user['Role'] ?? '';
            $userHcId = $user['HealthCenterID'] ?? null;
            
            // Handle both role naming variations
            $isHCUser = ($userRole === 'Health Center Staff' || $userRole === 'Health Center User');

            // If user is Health Center personnel, they can ONLY see their own HC inventory
            if ($isHCUser && $userHcId) {
                $hcId = $userHcId;
            } else {
                $hcId = $_GET['hc_id'] ?? $userHcId;
            }
            
            if ($hcId) {
                return $db->fetchAll("
                    SELECT hci.*, i.ItemName, i.ItemType, i.UnitOfMeasure 
                    FROM HCInventoryBatch hci
                    JOIN Item i ON hci.ItemID = i.ItemID
                    WHERE hci.HealthCenterID = ?
                ", [$hcId]);
            }
            return $db->fetchAll("
                SELECT hci.*, i.ItemName, i.ItemType, i.UnitOfMeasure, hc.Name as HealthCenterName
                FROM HCInventoryBatch hci
                JOIN Item i ON hci.ItemID = i.ItemID
                JOIN HealthCenters hc ON hci.HealthCenterID = hc.HealthCenterID
            ");

        case 'reports':
            return $db->fetchAll("
                SELECT r.*, CONCAT(u.FName, ' ', u.LName) as GeneratedByFullName 
                FROM Report r
                LEFT JOIN Users u ON r.UserID = u.UserID
                ORDER BY r.GeneratedDate DESC
            ");

        case 'patients':
            $user = $_SESSION['user'] ?? null;
            $userRole = $user['Role'] ?? '';
            $userHcId = $user['HealthCenterID'] ?? null;

            $isHCUser = ($userRole === 'Health Center Staff' || $userRole === 'Health Center User');

            if ($isHCUser && $userHcId) {
                $hcId = $userHcId;
            } elseif ($userRole === 'Administrator' || $userRole === 'Head Pharmacist') {
                $hcId = $_GET['hc_id'] ?? null;
            } else {
                $hcId = $_GET['hc_id'] ?? $userHcId;
            }

            if ($hcId) {
                return $db->fetchAll("SELECT * FROM HCPatient WHERE HealthCenterID = ?", [$hcId]);
            }
            return $db->fetchAll("SELECT * FROM HCPatient");

        case 'patient_requisitions':
            $user = $_SESSION['user'] ?? null;
            $userRole = $user['Role'] ?? '';
            $userHcId = $user['HealthCenterID'] ?? null;

            $isHCUser = ($userRole === 'Health Center Staff' || $userRole === 'Health Center User');

            if ($isHCUser && $userHcId) {
                $hcId = $userHcId;
            } elseif ($userRole === 'Administrator' || $userRole === 'Head Pharmacist') {
                $hcId = $_GET['hc_id'] ?? null;
            } else {
                $hcId = $_GET['hc_id'] ?? $userHcId;
            }

            $sql = "SELECT pr.*, p.FName, p.LName, p.Age, p.Gender, pr.Diagnosis, pr.Notes, pr.ContactInfo, pr.IDProof
                    FROM HCPatientRequisition pr
                    JOIN HCPatient p ON pr.PatientID = p.PatientID";
            $params = [];
            if ($hcId) {
                $sql .= " WHERE pr.HealthCenterID = ?";
                $params[] = $hcId;
            }
            $sql .= " ORDER BY pr.RequestDate DESC";
            $reqs = $db->fetchAll($sql, $params);
            foreach ($reqs as &$req) {
                $req['PatientFullName'] = $req['FName'] . ' ' . $req['LName'];
                $req['Items'] = $db->fetchAll(
                    "SELECT pri.*, i.ItemName FROM HCPatientRequisitionItem pri
                     JOIN Item i ON pri.ItemID = i.ItemID
                     WHERE pri.PatientReqID = ?",
                    [$req['PatientReqID']]
                );
            }
            return $reqs;
            
        default:
            return $db->read($file);
    }
}

function save_data($file, $data) {
    global $db;
    
    switch($file) {
        case 'users':
            $allSuccess = true;
            foreach ($data as $user) {
                $id = $user['UserID'] ?? null;
                if ($id && is_numeric($id)) {
                    $res = $db->execute(
                        "UPDATE Users SET FName = ?, MName = ?, LName = ?, Role = ?, Username = ?, Password = ?, HealthCenterID = ?, EmailNotifications = ?, InAppNotifications = ?, ThemePreference = ? WHERE UserID = ?",
                        [
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $user['Username'],
                            $user['Password'],
                            $user['HealthCenterID'] ?? null,
                            $user['EmailNotifications'] ?? 1,
                            $user['InAppNotifications'] ?? 1,
                            $user['ThemePreference'] ?? 'system',
                            $id
                        ]
                    );
                    if (!$res) $allSuccess = false;
                } else {
                    $res = $db->execute(
                        "INSERT INTO Users (FName, MName, LName, Role, Username, Password, HealthCenterID, EmailNotifications, InAppNotifications, ThemePreference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $user['Username'],
                            $user['Password'],
                            $user['HealthCenterID'] ?? null,
                            $user['EmailNotifications'] ?? 1,
                            $user['InAppNotifications'] ?? 1,
                            $user['ThemePreference'] ?? 'system'
                        ]
                    );
                    if (!$res) $allSuccess = false;
                }
            }
            return $allSuccess;
            
        case 'items':
            $allSuccess = true;
            foreach ($data as $item) {
                $id = $item['ItemID'] ?? null;
                $resolvedItemId = $db->resolveItemId($id);
                
                $check = $db->fetchOne("SELECT ItemID FROM Item WHERE ItemID = ?", [$resolvedItemId]);
                if ($check) {
                     $res = $db->execute(
                        "UPDATE Item SET ItemName = ?, ItemType = ?, UnitOfMeasure = ? WHERE ItemID = ?",
                        [$item['ItemName'], $item['ItemType'], $item['UnitOfMeasure'], $resolvedItemId]
                    );
                    if (!$res) $allSuccess = false;
                } else {
                     $res = $db->execute(
                        "INSERT INTO Item (ItemName, ItemType, UnitOfMeasure) VALUES (?, ?, ?)",
                        [$item['ItemName'], $item['ItemType'], $item['UnitOfMeasure']]
                    );
                    if (!$res) $allSuccess = false;
                }
            }
            return $allSuccess;

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
             $allSuccess = true;
             foreach ($data as $sup) {
                $id = $sup['SupplierID'] ?? null;
                if ($id && is_numeric($id)) {
                    $res = $db->execute("UPDATE Supplier SET Name = ?, Address = ?, ContactInfo = ? WHERE SupplierID = ?", [$sup['Name'], $sup['Address'] ?? null, $sup['ContactInfo'] ?? null, $id]);
                } else {
                    $res = $db->execute("INSERT INTO Supplier (Name, Address, ContactInfo) VALUES (?, ?, ?)", [$sup['Name'], $sup['Address'] ?? null, $sup['ContactInfo'] ?? null]);
                }
                if (!$res) $allSuccess = false;
             }
             return $allSuccess;
            
        case 'inventory':
            $allSuccess = true;
            foreach ($data as $batch) {
                $id = $batch['BatchID'] ?? null;
                if ($id && is_numeric($id)) {
                     $res = $db->execute("UPDATE CentralInventoryBatch SET QuantityOnHand = ?, QuantityReleased = ? WHERE BatchID = ?", 
                        [$batch['QuantityOnHand'], $batch['QuantityReleased'] ?? 0, $id]);
                     if (!$res) $allSuccess = false;
                } else {
                     $resolvedItemId = $batch['ItemID'];
                     if (is_string($resolvedItemId) && preg_match('/^I(\d+)$/', $resolvedItemId, $matches)) {
                         $resolvedItemId = (int)$matches[1];
                     }
                     
                     $res = $db->execute(
                         "INSERT INTO CentralInventoryBatch (ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)",
                         [$resolvedItemId, $batch['WarehouseID'] ?? 1, $batch['ExpiryDate'] ?? null, $batch['QuantityOnHand'], $batch['QuantityReleased'] ?? 0, $batch['UnitCost'] ?? 0, $batch['DateReceived'] ?? date('Y-m-d')]
                     );
                     if (!$res) $allSuccess = false;
                }
            }
            return $allSuccess;
            
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
            $allSuccess = true;
            foreach ($data as $po) {
                $id = $po['POID'] ?? null;
                if ($id && is_numeric($id)) {
                    $res = $db->execute("UPDATE ProcurementOrder SET StatusType = ? WHERE POID = ?", [$po['StatusType'], $id]);
                    if (!$res) $allSuccess = false;
                } else {
                    $supplierId = $po['SupplierID'] ?? null;
                    if (empty($supplierId) && !empty($po['SupplierName'])) {
                        $existingSupplier = $db->fetchOne("SELECT SupplierID FROM Supplier WHERE Name = ?", [$po['SupplierName']]);
                        if ($existingSupplier) {
                            $supplierId = $existingSupplier['SupplierID'];
                        } else {
                            // Create new supplier
                            $res = $db->execute("INSERT INTO Supplier (Name, Address) VALUES (?, ?)", [$po['SupplierName'], $po['SupplierAddress'] ?? '']);
                            if (!$res) { $allSuccess = false; continue; }
                            $supplierId = $db->lastInsertId();
                        }
                    }
                    $contractId = null;
                    if (!empty($po['ContractNumber'])) {
                        $existingContract = $db->fetchOne("SELECT ContractID FROM Contract WHERE ContractNumber = ?", [$po['ContractNumber']]);
                        if ($existingContract) {
                            $contractId = $existingContract['ContractID'];
                            // Update existing contract if dates/amount are provided
                            if (!empty($po['ContractStartDate']) || !empty($po['ContractEndDate']) || !empty($po['ContractAmount'])) {
                                $res = $db->execute(
                                    "UPDATE Contract SET StartDate = ?, EndDate = ?, ContractAmount = ? WHERE ContractID = ?",
                                    [
                                        $po['ContractStartDate'] ?: null,
                                        $po['ContractEndDate'] ?: null,
                                        $po['ContractAmount'] ?: null,
                                        $contractId
                                    ]
                                );
                                if (!$res) $allSuccess = false;
                            }
                        } else {
                            // Create new contract
                            $res = $db->execute(
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
                            if (!$res) { $allSuccess = false; continue; }
                            $contractId = $db->lastInsertId();
                        }
                    }

                    // Logic: Insert -> Get ID -> Generate PONumber -> Update
                    $res = $db->execute(
                        "INSERT INTO ProcurementOrder (UserID, SupplierID, HealthCenterID, ContractID, DocumentType, PONumber, PODate, StatusType, ContractNumber, ContractStartDate, ContractEndDate, ContractAmount, RefFilePath, RefFileType) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $po['UserID'], 
                            $supplierId ?: null, 
                            $po['HealthCenterID'] ?? null, 
                            $contractId,
                            $po['DocumentType'] ?? 'PO',
                            'TEMP', 
                            $po['PODate'], 
                            $po['StatusType'],
                            $po['ContractNumber'] ?? null,
                            $po['ContractStartDate'] ?? null,
                            $po['ContractEndDate'] ?? null,
                            $po['ContractAmount'] ?? null,
                            $po['RefFilePath'] ?? null,
                            $po['RefFileType'] ?? null
                        ]
                    );
                    if (!$res) { $allSuccess = false; continue; }
                    $newId = $db->lastInsertId();
                    $poNum = 'PO-' . date('Y') . '-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
                    $res = $db->execute("UPDATE ProcurementOrder SET PONumber = ? WHERE POID = ?", [$poNum, $newId]);
                    if (!$res) $allSuccess = false;
                    
                    $itemsKey = isset($po['ProcurementOrderItems']) ? 'ProcurementOrderItems' : 'PurchaseOrderItems';
                    if (!empty($po[$itemsKey])) {
                        foreach ($po[$itemsKey] as $item) {
                            $resolvedItemId = $db->resolveItemId($item['ItemID']);
                            
                            $res = $db->execute(
                                "INSERT INTO ProcurementOrderItem (POID, ItemID, QuantityOrdered, UnitCost, ExpiryDate) VALUES (?, ?, ?, ?, ?)",
                                [
                                    $newId, 
                                    $resolvedItemId, 
                                    $item['QuantityOrdered'], 
                                    $item['UnitCost'] ?? 0,
                                    $item['ExpiryDate'] ?? null
                                ]
                            );
                            if (!$res) $allSuccess = false;
                        }
                    }
                }
            }
            return $allSuccess;
            
        case 'requisitions':
            $startedTransaction = false;
            if (!$db->inTransaction()) {
                $db->beginTransaction();
                $startedTransaction = true;
            }
            try {
                foreach ($data as $req) {
                    $id = $req['RequisitionID'] ?? null;
                    if ($id && is_numeric($id)) {
                        $res = $db->execute("UPDATE Requisition SET StatusType = ? WHERE RequisitionID = ?", [$req['StatusType'] ?? 'Pending', $id]);
                        if (!$res) throw new Exception("Failed to update requisition status for ID: $id");
                    } else {
                        $hcId = $req['HealthCenterID'] ?? null;
                        if (empty($hcId) && !empty($req['HealthCenterName'])) {
                            $existing = $db->fetchOne("SELECT HealthCenterID FROM HealthCenters WHERE Name = ?", [$req['HealthCenterName']]);
                            if ($existing) {
                                $hcId = $existing['HealthCenterID'];
                            } else {
                                $res = $db->execute("INSERT INTO HealthCenters (Name, Address) VALUES (?, ?)", [$req['HealthCenterName'], $req['HealthCenterAddress'] ?? '']);
                                if (!$res) throw new Exception("Failed to create new health center: " . ($req['HealthCenterName'] ?? 'Unknown'));
                                $hcId = $db->lastInsertId();
                            }
                        }
                        
                        $tempNum = 'TEMP-' . time() . '-' . rand(1000, 9999);
                        $res = $db->execute("INSERT INTO Requisition (HealthCenterID, UserID, RequisitionNumber, RequestDate, StatusType) VALUES (?, ?, ?, ?, ?)",
                            [$hcId ?: null, $req['UserID'], $tempNum, $req['RequestDate'] ?? $req['RequestedDate'] ?? date('Y-m-d H:i:s'), $req['StatusType'] ?? 'Pending']);
                        
                        if (!$res) throw new Exception("Failed to insert new requisition record.");
                        
                        $newId = $db->lastInsertId();
                        $reqNum = 'REQ-' . date('Y') . '-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
                        $res = $db->execute("UPDATE Requisition SET RequisitionNumber = ? WHERE RequisitionID = ?", [$reqNum, $newId]);
                        if (!$res) throw new Exception("Failed to update requisition number for ID: $newId");

                        if (!empty($req['RequisitionItems'])) {
                            foreach ($req['RequisitionItems'] as $item) {
                                $resolvedItemId = $db->resolveItemId($item['ItemID']);
                                if (!$resolvedItemId) throw new Exception("Could not resolve ItemID: " . $item['ItemID']);
                                
                                $res = $db->execute(
                                    "INSERT INTO RequisitionItem (RequisitionID, ItemID, QuantityRequested) VALUES (?, ?, ?)",
                                    [$newId, $resolvedItemId, $item['QuantityRequested']]
                                );
                                
                                if (!$res) {
                                    throw new Exception("Requisition item insert failed for ItemID: $resolvedItemId on Requisition: $newId");
                                }
                            }
                        }
                    }
                }
                if ($startedTransaction) {
                    $db->commit();
                    $db->broadcastUpdate('requisitions_updated');
                }
                return true;
            } catch (Exception $e) {
                if ($startedTransaction && $db->inTransaction()) $db->rollback();
                error_log("Save Requisitions Error: " . $e->getMessage());
                return false;
            }
            
        case 'issuances':
            $allSuccess = true;
            foreach ($data as $iss) {
                 $res = $db->execute(
                     "INSERT INTO Issuance (RequisitionID, UserID, IssueDate, StatusType) VALUES (?, ?, ?, ?)",
                     [$iss['RequisitionID'], $iss['IssuedByUserID'] ?? $iss['UserID'], $iss['DateIssued'] ?? $iss['IssueDate'], $iss['StatusType'] ?? 'Issued']
                 );
                 if (!$res) {
                     $allSuccess = false;
                     continue;
                 }
                 $newId = $db->lastInsertId();
                 if (!empty($iss['IssuedItems'])) {
                     foreach ($iss['IssuedItems'] as $item) {
                         $res = $db->execute(
                             "INSERT INTO IssuanceItem (IssuanceID, BatchID, RequisitionItemID, QuantityIssued) VALUES (?, ?, ?, ?)",
                             [$newId, $item['BatchID'], $item['RequisitionItemID'] ?? null, $item['QuantityIssued']]
                         );
                         if (!$res) $allSuccess = false;
                     }
                 }
            }
            return $allSuccess;
            
        case 'receivings':
            $startedTransaction = false;
            if (!$db->inTransaction()) {
                $db->beginTransaction();
                $startedTransaction = true;
            }
            try {
                foreach ($data as $rcv) {
                     $res = $db->execute(
                         "INSERT INTO Receiving (UserID, POID, ReceivedDate) VALUES (?, ?, ?)",
                         [$rcv['UserID'], $rcv['POID'], $rcv['ReceivedDate']]
                     );
                     if (!$res) throw new Exception("Failed to create receiving header for PO: " . $rcv['POID']);
                     
                     $newRcvId = $db->lastInsertId();
                     
                     if (!empty($rcv['ReceivedItems'])) {
                         foreach ($rcv['ReceivedItems'] as $batch) {
                             $resolvedItemId = $db->resolveItemId($batch['ItemID']);
                             
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
                                 $res = $db->execute("UPDATE CentralInventoryBatch SET QuantityOnHand = QuantityOnHand + ? WHERE BatchID = ?", [$batch['QuantityOnHand'], $existingBatch['BatchID']]);
                                 if (!$res) throw new Exception("Failed to update inventory for BatchID: " . $existingBatch['BatchID']);
                                 $newBatchId = $existingBatch['BatchID'];
                             } else {
                                 // Insert
                                 $res = $db->execute(
                                     "INSERT INTO CentralInventoryBatch (ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) VALUES (?, ?, ?, ?, ?, ?, ?)",
                                     [
                                         $resolvedItemId, $batch['WarehouseID'] ?? 1, $batch['ExpiryDate'] ?? null,
                                         $batch['QuantityOnHand'], 0, $batch['UnitCost'] ?? 0, $batch['DateReceived']
                                     ]
                                 );
                                 if (!$res) throw new Exception("Failed to insert new inventory batch for ItemID: " . $resolvedItemId);
                                 $newBatchId = $db->lastInsertId();
                             }
                             
                             $res = $db->execute(
                                 "INSERT INTO ReceivingItem (ReceivingID, BatchID, QuantityReceived) VALUES (?, ?, ?)",
                                 [$newRcvId, $newBatchId, $batch['QuantityOnHand']]
                             );
                             if (!$res) throw new Exception("Failed to link item to receiving record. ItemID: " . $resolvedItemId);
                         }
                     }
                }
                if ($startedTransaction) {
                    $db->commit();
                    $db->broadcastUpdate('receivings_updated');
                    $db->broadcastUpdate('inventory_updated');
                }
                return true;
            } catch (Exception $e) {
                if ($startedTransaction && $db->inTransaction()) $db->rollback();
                error_log("Save Receivings Error: " . $e->getMessage());
                return false;
            }
            
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

        case 'patients':
            $allSuccess = true;
            foreach ($data as $p) {
                $id = $p['PatientID'] ?? null;
                if ($id && is_numeric($id)) {
                    $res = $db->execute(
                        "UPDATE HCPatient SET HealthCenterID = ?, FName = ?, MName = ?, LName = ?, Age = ?, Gender = ?, Address = ?, ContactNumber = ?, IDProof = ? WHERE PatientID = ?",
                        [$p['HealthCenterID'], $p['FName'], $p['MName'], $p['LName'], $p['Age'], $p['Gender'], $p['Address'], $p['ContactNumber'], $p['IDProof'], $id]
                    );
                    if (!$res) $allSuccess = false;
                } else {
                    $res = $db->execute(
                        "INSERT INTO HCPatient (HealthCenterID, FName, MName, LName, Age, Gender, Address, ContactNumber, IDProof) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $p['HealthCenterID'] ?? null,
                            $p['FName'] ?? '',
                            $p['MName'] ?? null,
                            $p['LName'] ?? '',
                            $p['Age'] ?? null,
                            $p['Gender'] ?? 'Other',
                            $p['Address'] ?? null,
                            $p['ContactNumber'] ?? '',
                            $p['IDProof'] ?? ''
                        ]
                    );
                    if (!$res) $allSuccess = false;
                }
            }
            return $allSuccess;

        case 'patient_requisitions':
            $allSuccess = true;
            foreach ($data as $pr) {
                $id = $pr['PatientReqID'] ?? null;
                if ($id && is_numeric($id)) {
                    $res = $db->execute("UPDATE HCPatientRequisition SET StatusType = ?, Diagnosis = ?, Notes = ? WHERE PatientReqID = ?",
                        [$pr['StatusType'], $pr['Diagnosis'], $pr['Notes'], $id]);
                    if (!$res) $allSuccess = false;
                } else {
                    $tempNum = 'PR-' . time() . '-' . rand(1000, 9999);
                    $res = $db->execute(
                        "INSERT INTO HCPatientRequisition (PatientID, UserID, HealthCenterID, RequisitionNumber, RequestDate, StatusType, Diagnosis, Notes, ContactInfo, IDProof) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $pr['PatientID'] ?? 0,
                            $pr['UserID'] ?? 0,
                            $pr['HealthCenterID'] ?? null,
                            $tempNum,
                            $pr['RequestDate'] ?? date('Y-m-d H:i:s'),
                            $pr['StatusType'] ?? 'Pending',
                            $pr['Diagnosis'] ?? null,
                            $pr['Notes'] ?? null,
                            $pr['ContactInfo'] ?? null,
                            $pr['IDProof'] ?? null
                        ]
                    );
                    if (!$res) { $allSuccess = false; continue; }
                    $newId = $db->lastInsertId();
                    $reqNum = 'PR-' . date('Y') . '-' . str_pad($newId, 5, '0', STR_PAD_LEFT);
                    $res = $db->execute("UPDATE HCPatientRequisition SET RequisitionNumber = ? WHERE PatientReqID = ?", [$reqNum, $newId]);
                    if (!$res) $allSuccess = false;

                    if (!empty($pr['Items'])) {
                        foreach ($pr['Items'] as $item) {
                            $res = $db->execute(
                                "INSERT INTO HCPatientRequisitionItem (PatientReqID, ItemID, QuantityRequested) VALUES (?, ?, ?)",
                                [$newId, $item['ItemID'], $item['QuantityRequested']]
                            );
                            if (!$res) $allSuccess = false;
                        }
                    }
                }
            }
            return $allSuccess;
             
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
