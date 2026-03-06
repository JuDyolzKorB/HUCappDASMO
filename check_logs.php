<?php
$logPath = ini_get('error_log');
echo "Log Path: $logPath\n";
if (file_exists($logPath)) {
    $content = file($logPath);
    $lastLines = array_slice($content, -20);
    echo "--- Last 20 lines of log ---\n";
    echo implode("", $lastLines);
} else {
    echo "Log file does not exist.\n";
}
?>
