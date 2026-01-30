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
            'purchase_orders' => 'PurchaseOrder',
            'purchase_order_items' => 'PurchaseOrderItem',
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
            'reports' => 'Report'
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
            
        case 'inventory':
            return $db->fetchAll("SELECT * FROM CentralInventoryBatch");
            
        case 'purchase_orders':
            // Get POs with their items
            $pos = $db->fetchAll("SELECT * FROM PurchaseOrder ORDER BY PODate DESC");
            foreach ($pos as &$po) {
                $po['PurchaseOrderItems'] = $db->fetchAll(
                    "SELECT * FROM PurchaseOrderItem WHERE POID = ?", 
                    [$po['POID']]
                );
                $po['ApprovalLogs'] = [];
            }
            return $pos;
            
        case 'requisitions':
            // Get requisitions with their items and approval logs
            $reqs = $db->fetchAll("SELECT * FROM Requisition ORDER BY RequestDate DESC");
            foreach ($reqs as &$req) {
                $req['RequisitionItems'] = $db->fetchAll(
                    "SELECT * FROM RequisitionItem WHERE RequisitionID = ?", 
                    [$req['RequisitionID']]
                );
                $req['ApprovalLogs'] = $db->fetchAll(
                    "SELECT * FROM ApprovalLog WHERE RequisitionID = ?", 
                    [$req['RequisitionID']]
                );
                
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
                    "SELECT ri.*, cib.* FROM ReceivingItem ri 
                     JOIN CentralInventoryBatch cib ON ri.BatchID = cib.BatchID 
                     WHERE ri.ReceivingID = ?", 
                    [$rcv['ReceivingID']]
                );
            }
            return $receivings;
            
        case 'security_logs':
            return $db->fetchAll("SELECT * FROM SecurityLog ORDER BY ActionDate DESC LIMIT 100");
            
        case 'transaction_logs':
            return $db->fetchAll("SELECT * FROM TransactionAuditLog ORDER BY ActionDate DESC LIMIT 100");
            
        case 'reports':
            // Reports might still be in JSON - keep file-based for now
            $path = __DIR__ . '/../data/reports.json';
            if (file_exists($path)) {
                return json_decode(file_get_contents($path), true) ?? [];
            }
            return [];
            
        default:
            return $db->read($file);
    }
}

function save_data($file, $data) {
    global $db;
    
    // This function needs to be refactored to use proper INSERT/UPDATE
    // For now, we'll handle specific cases
    
    switch($file) {
        case 'users':
            // Handle user updates
            $success = true;
            foreach ($data as $user) {
                // Determine if updating or inserting
                // Using fetchOne requires the UserID to be of correct type/value
                $existing = $db->fetchOne("SELECT UserID FROM Users WHERE UserID = ?", [$user['UserID']]);
                
                $result = false;
                if ($existing) {
                    // Update
                    $result = $db->execute(
                        "UPDATE Users SET FName = ?, MName = ?, LName = ?, Role = ?, Username = ?, Password = ? WHERE UserID = ?",
                        [
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $user['Username'],
                            $user['Password'],
                            $user['UserID']
                        ]
                    );
                } else {
                    // Insert
                    $result = $db->execute(
                        "INSERT INTO Users (UserID, FName, MName, LName, Role, Username, Password) VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [
                            $user['UserID'],
                            $user['FirstName'] ?? $user['FName'],
                            $user['MiddleName'] ?? $user['MName'],
                            $user['LastName'] ?? $user['LName'],
                            $user['Role'],
                            $user['Username'],
                            $user['Password']
                        ]
                    );
                }
                
                if (!$result) {
                    $success = false;
                    error_log("Failed to save user " . $user['UserID']);
                }
            }
            return $success;
            
        case 'warehouses':
            foreach ($data as $wh) {
                $existing = $db->fetchOne("SELECT WarehouseID FROM Warehouse WHERE WarehouseID = ?", [$wh['WarehouseID']]);
                if (!$existing) {
                    $db->execute(
                        "INSERT INTO Warehouse (WarehouseID, WarehouseName, Location, WarehouseType) VALUES (?, ?, ?, ?)",
                        [$wh['WarehouseID'], $wh['WarehouseName'], $wh['Location'], $wh['WarehouseType']]
                    );
                }
            }
            return true;
            
        case 'inventory':
            // Clear and reinsert (simple approach for now)
            foreach ($data as $batch) {
                $existing = $db->fetchOne("SELECT BatchID FROM CentralInventoryBatch WHERE BatchID = ?", [$batch['BatchID']]);
                if ($existing) {
                    $db->execute(
                        "UPDATE CentralInventoryBatch SET QuantityOnHand = ?, QuantityReleased = ? WHERE BatchID = ?",
                        [$batch['QuantityOnHand'], $batch['QuantityReleased'] ?? 0, $batch['BatchID']]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO CentralInventoryBatch (BatchID, ItemID, WarehouseID, ExpiryDate, QuantityOnHand, QuantityReleased, UnitCost, DateReceived) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $batch['BatchID'],
                            $batch['ItemID'],
                            $batch['WarehouseID'] ?? 'W01',
                            $batch['ExpiryDate'] ?? null,
                            $batch['QuantityOnHand'],
                            $batch['QuantityReleased'] ?? 0,
                            $batch['UnitCost'] ?? 0,
                            $batch['DateReceived'] ?? date('Y-m-d')
                        ]
                    );
                }
            }
            return true;
            
        case 'purchase_orders':
            foreach ($data as $po) {
                $existing = $db->fetchOne("SELECT POID FROM PurchaseOrder WHERE POID = ?", [$po['POID']]);
                if ($existing) {
                    $db->execute(
                        "UPDATE PurchaseOrder SET StatusType = ? WHERE POID = ?",
                        [$po['StatusType'], $po['POID']]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO PurchaseOrder (POID, UserID, SupplierID, HealthCenterID, PONumber, PODate, StatusType) 
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [
                            $po['POID'],
                            $po['UserID'],
                            $po['SupplierID'],
                            $po['HealthCenterID'] ?? null,
                            $po['PONumber'],
                            $po['PODate'],
                            $po['StatusType']
                        ]
                    );
                    
                    // Insert PO items
                    if (isset($po['PurchaseOrderItems'])) {
                        foreach ($po['PurchaseOrderItems'] as $item) {
                            $db->execute(
                                "INSERT INTO PurchaseOrderItem (POItemID, POID, ItemID, QuantityOrdered, UnitCost) 
                                 VALUES (?, ?, ?, ?, ?)",
                                [
                                    $item['POItemID'],
                                    $po['POID'],
                                    $item['ItemID'],
                                    $item['QuantityOrdered'],
                                    $item['UnitCost'] ?? 0
                                ]
                            );
                        }
                    }
                }
            }
            return true;
            
        case 'requisitions':
            foreach ($data as $req) {
                $existing = $db->fetchOne("SELECT RequisitionID FROM Requisition WHERE RequisitionID = ?", [$req['RequisitionID']]);
                if ($existing) {
                    $db->execute(
                        "UPDATE Requisition SET StatusType = ? WHERE RequisitionID = ?",
                        [$req['StatusType'], $req['RequisitionID']]
                    );
                    
                    // Update approval logs
                    if (isset($req['ApprovalLogs'])) {
                        foreach ($req['ApprovalLogs'] as $log) {
                            $logExists = $db->fetchOne("SELECT ApprovalLogID FROM ApprovalLog WHERE ApprovalLogID = ?", [$log['ApprovalLogID']]);
                            if (!$logExists) {
                                $db->execute(
                                    "INSERT INTO ApprovalLog (ApprovalLogID, RequisitionID, UserID, Decision, DecisionDate) 
                                     VALUES (?, ?, ?, ?, ?)",
                                    [
                                        $log['ApprovalLogID'],
                                        $req['RequisitionID'],
                                        $log['UserID'],
                                        $log['Decision'],
                                        $log['DecisionDate']
                                    ]
                                );
                            }
                        }
                    }
                } else {
                    $db->execute(
                        "INSERT INTO Requisition (RequisitionID, HealthCenterID, UserID, RequestDate, StatusType) 
                         VALUES (?, ?, ?, ?, ?)",
                        [
                            $req['RequisitionID'],
                            $req['HealthCenterID'],
                            $req['UserID'],
                            $req['RequestedDate'] ?? $req['RequestDate'],
                            $req['StatusType']
                        ]
                    );
                    
                    // Insert requisition items
                    if (isset($req['RequisitionItems'])) {
                        foreach ($req['RequisitionItems'] as $item) {
                            $db->execute(
                                "INSERT INTO RequisitionItem (RequisitionItemID, RequisitionID, ItemID, QuantityRequested) 
                                 VALUES (?, ?, ?, ?)",
                                [
                                    $item['RequisitionItemID'],
                                    $req['RequisitionID'],
                                    $item['ItemID'],
                                    $item['QuantityRequested']
                                ]
                            );
                        }
                    }
                }
            }
            return true;
            
        case 'issuances':
            foreach ($data as $iss) {
                $existing = $db->fetchOne("SELECT IssuanceID FROM Issuance WHERE IssuanceID = ?", [$iss['IssuanceID']]);
                if (!$existing) {
                    $db->execute(
                        "INSERT INTO Issuance (IssuanceID, RequisitionID, UserID, IssueDate, StatusType) 
                         VALUES (?, ?, ?, ?, ?)",
                        [
                            $iss['IssuanceID'],
                            $iss['RequisitionID'],
                            $iss['IssuedByUserID'] ?? $iss['UserID'],
                            $iss['DateIssued'] ?? $iss['IssueDate'],
                            $iss['StatusType'] ?? 'Issued'
                        ]
                    );
                    
                    // Insert issuance items
                    if (isset($iss['IssuedItems'])) {
                        foreach ($iss['IssuedItems'] as $item) {
                            $db->execute(
                                "INSERT INTO IssuanceItem (IssuanceItemID, IssuanceID, BatchID, RequisitionItemID, QuantityIssued) 
                                 VALUES (?, ?, ?, ?, ?)",
                                [
                                    $item['IssuanceItemID'],
                                    $iss['IssuanceID'],
                                    $item['BatchID'],
                                    $item['RequisitionItemID'] ?? null,
                                    $item['QuantityIssued']
                                ]
                            );
                        }
                    }
                }
            }
            return true;
            
        case 'receivings':
            foreach ($data as $rcv) {
                $existing = $db->fetchOne("SELECT ReceivingID FROM Receiving WHERE ReceivingID = ?", [$rcv['ReceivingID']]);
                if (!$existing) {
                    $db->execute(
                        "INSERT INTO Receiving (ReceivingID, UserID, POID, ReceivedDate) 
                         VALUES (?, ?, ?, ?)",
                        [
                            $rcv['ReceivingID'],
                            $rcv['UserID'],
                            $rcv['POID'],
                            $rcv['ReceivedDate']
                        ]
                    );
                    
                    // Insert receiving items
                    if (isset($rcv['ReceivedItems'])) {
                        foreach ($rcv['ReceivedItems'] as $item) {
                            $riID = 'RCI-' . rand(10000, 99999);
                            $db->execute(
                                "INSERT INTO ReceivingItem (ReceivingItemID, ReceivingID, BatchID, QuantityReceived) 
                                 VALUES (?, ?, ?, ?)",
                                [
                                    $riID,
                                    $rcv['ReceivingID'],
                                    $item['BatchID'],
                                    $item['QuantityOnHand'] ?? $item['QuantityReceived']
                                ]
                            );
                        }
                    }
                }
            }
            return true;
            
        case 'security_logs':
        case 'transaction_logs':
        case 'reports':
            // These are handled by specific functions
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
