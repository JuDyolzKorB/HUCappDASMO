<?php

class Database {
    private $basePath;

    public function __construct() {
        $this->basePath = __DIR__ . '/../data/';
        if (!file_exists($this->basePath)) {
            mkdir($this->basePath, 0777, true);
        }
    }

    public function read($filename) {
        $path = $this->basePath . $filename . '.json';
        if (!file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        return json_decode($json, true) ?? [];
    }

    public function write($filename, $data) {
        $path = $this->basePath . $filename . '.json';
        return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    public function find($filename, $key, $value) {
        $data = $this->read($filename);
        foreach ($data as $item) {
            if (isset($item[$key]) && $item[$key] == $value) {
                return $item;
            }
        }
        return null;
    }
}

// Global DB instance
$db = new Database();

// Initialize data helper function
function get_data($file) {
    global $db;
    return $db->read($file);
}

function save_data($file, $data) {
    global $db;
    return $db->write($file, $data);
}

// Log Security Event
function log_security_event($userId, $actionType, $status, $description) {
    global $db;
    $logs = $db->read('security_logs');
    $newLog = [
        'SecurityLogID' => 'SEC-' . time() . '-' . substr(uniqid(), -5),
        'UserID' => $userId,
        'ActionType' => $actionType, // Login, Logout, etc
        'Status' => $status,         // Success / Failure
        'Description' => $description,
        'IPAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'ActionDate' => date('c')
    ];
    array_unshift($logs, $newLog);
    $db->write('security_logs', $logs);
}

// Log Transaction
function logTransaction($actionType, $referenceType, $referenceId) {
    global $db;
    if (!isLoggedIn()) return;
    
    $user = $_SESSION['user'];
    $logs = $db->read('transaction_logs');
    $newLog = [
        'AuditLogID' => 'AUD-' . time() . '-' . substr(uniqid(), -5),
        'UserID' => $user['UserID'],
        'UserFullName' => $user['FirstName'] . ' ' . $user['LastName'],
        'ActionType' => $actionType,
        'ReferenceType' => $referenceType,
        'ReferenceID' => $referenceId,
        'ActionDate' => date('c')
    ];
    array_unshift($logs, $newLog);
    $db->write('transaction_logs', $logs);
}

