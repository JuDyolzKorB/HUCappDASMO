<?php
// stream.php - SSE endpoint for real-time updates
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx

require_once 'includes/db.php';

// Set infinite time limit
set_time_limit(0);

// Use a session-based approach to get the last update ID
// This allows clients to resume from where they left off if the connection drops briefly
$lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

// Fallback to Last-Event-ID header (sent by browsers on auto-reconnect)
if ($lastId === 0 && isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $lastId = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
}

if ($lastId === 0) {
    // If no ID provided, get the current max ID to start from now
    global $db;
    $res = $db->fetchOne("SELECT MAX(UpdateID) as max_id FROM SystemUpdates");
    $lastId = (int)($res['max_id'] ?? 0);
}

// Send an initial heartbeat
echo "event: connected\ndata: " . json_encode(['lastId' => $lastId]) . "\n\n";
flush();

while (true) {
    // Check for new updates
    global $db;
    $updates = $db->fetchAll("SELECT * FROM SystemUpdates WHERE UpdateID > ? ORDER BY UpdateID ASC", [$lastId]);
    
    if (!empty($updates)) {
        foreach ($updates as $update) {
            echo "id: " . $update['UpdateID'] . "\n";
            echo "event: " . $update['EventType'] . "\ndata: " . ($update['EventData'] ?: '{}') . "\n\n";
            $lastId = $update['UpdateID'];
        }
    } else {
        // Send a heartbeat comment every 30 seconds to keep connection alive
        echo ": heartbeat\n\n";
    }
    
    // Flush the output buffer
    if (ob_get_level() > 0) ob_flush();
    flush();
    
    // Check if client is still connected
    if (connection_aborted()) break;
    
    // Sleep for a bit to avoid hammering the DB
    sleep(2);
}
