<?php
// includes/hc_db_manager.php
require_once __DIR__ . '/config.php';

class HCDatabaseManager {
    /**
     * Ensures that a health center has a database and it's properly initialized.
     */
    public static function ensureHealthCenterDatabase($healthCenterId) {
        $db = Database::getInstance();
        
        // 1. Get HC info
        $hc = $db->fetchOne("SELECT Name, DatabaseName FROM HealthCenters WHERE HealthCenterID = ?", [$healthCenterId]);
        if (!$hc) return false;

        $dbName = $hc['DatabaseName'];
        
        // 2. If no DatabaseName assigned, generate one
        if (empty($dbName)) {
            // Convert to lowercase and replace spaces/special chars with underscores
            $slug = strtolower($hc['Name']);
            $slug = preg_replace('/[^a-z0-9]/', '_', $slug);
            $slug = preg_replace('/_+/', '_', $slug); // Remove duplicate underscores
            $slug = trim($slug, '_');   // Trim underscores
            
            $dbName = 'hc_' . $slug;
            
            // Update central table
            $db->execute("UPDATE HealthCenters SET DatabaseName = ? WHERE HealthCenterID = ?", [$dbName, $healthCenterId]);
        }

        // 3. Create database if not exists and run schema
        try {
            // Use main PDO but without DB selected to create the new one
            $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Check if DB exists
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
            if (!$stmt->fetch()) {
                // Create DB
                $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Switch to new DB
                $pdo->exec("USE `$dbName` ");
                
                // Run schema
                $schemaFile = __DIR__ . '/../database/health_center_schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    // Basic split by semicolon. For complex schemas, use a better parser.
                    $sql = preg_replace('/--.*$/m', '', $sql);
                    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    
                    foreach ($statements as $s) {
                        if (!empty($s)) $pdo->exec($s);
                    }
                }
            }
            return $dbName;
        } catch (Exception $e) {
            error_log("HC DB Provisioning Error: " . $e->getMessage());
            return false;
        }
    }
}
