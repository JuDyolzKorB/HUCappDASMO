<?php
/**
 * Create Health Center Database Utility
 * 
 * Usage: Run this script with a ?name=参数 to create a new database.
 * Example: http://localhost/HUCappDASMO/database/create_hc_database.php?name=HC_North
 */

require_once __DIR__ . '/../includes/config.php';

// Only allow in development or for specific authorized roles (if session exists)
// For simplicity in this demo, we check if name is provided.

$hcName = $_GET['name'] ?? null;

if (!$hcName) {
    die("Error: Please provide a health center name slug. Example: ?name=HC_North");
}

// Clean the name to be a valid DB name
$dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $hcName);
$dbName = strtolower($dbName);

if (empty($dbName)) {
    die("Error: Invalid health center name.");
}

try {
    // Connect to MySQL without choosing a database first
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    echo "<h2>Creating Database: $dbName</h2>";
    
    // 1. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✓ Database `$dbName` created or already exists.</p>";
    
    // 2. Select the new database
    $pdo->exec("USE `$dbName` ");
    
    // 3. Read and execute the schema
    $schemaFile = __DIR__ . '/health_center_schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Strip comments to avoid issues with splitting
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $executedCount = 0;
    
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
                $executedCount++;
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>⚠ Warning executing statement: " . htmlspecialchars(substr($stmt, 0, 50)) . "... - " . $e->getMessage() . "</p>";
            }
        }
    }
    echo "<p style='color: green;'>✓ Executed $executedCount SQL statements.</p>";

    // Verify tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($tables)) {
        echo "<p style='color: green;'>✓ Created tables: " . implode(', ', $tables) . "</p>";
    } else {
        echo "<p style='color: red;'>✖ Error: No tables were created in `$dbName`.</p>";
    }

    $mainDb = DB_NAME;
    $pdo->exec("USE `$mainDb` ");
    
    // First, try to find by Name to update existing record
    $checkStmt = $pdo->prepare("SELECT HealthCenterID FROM HealthCenters WHERE Name = ?");
    $checkStmt->execute([$hcName]);
    $existingByName = $checkStmt->fetch();

    if ($existingByName) {
        $updateStmt = $pdo->prepare("UPDATE HealthCenters SET DatabaseName = ? WHERE HealthCenterID = ?");
        $updateStmt->execute([$dbName, $existingByName['HealthCenterID']]);
        $hcId = $existingByName['HealthCenterID'];
        echo "<p style='color: green;'>✓ Updated existing health center `$hcName` with database `$dbName`.</p>";
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO HealthCenters (Name, DatabaseName) VALUES (?, ?)");
        $insertStmt->execute([$hcName, $dbName]);
        $hcId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✓ Registered new health center `$hcName` in central table.</p>";
    }

    // Optional: Auto-assign to current user if administrator and on same session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user']) && $_SESSION['user']['Role'] === 'Administrator') {
        $userUpdate = $pdo->prepare("UPDATE Users SET HealthCenterID = ? WHERE UserID = ?");
        $userUpdate->execute([$hcId, $_SESSION['user']['UserID']]);
        $_SESSION['user']['HealthCenterID'] = $hcId;
        echo "<p style='color: blue;'>ℹ Auto-assigned this health center to your administrator account for testing.</p>";
    }

    echo "<p style='color: green;'>✓ Setup completed successfully.</p>";
    echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<strong>Database Name:</strong> $dbName<br>";
    echo "<strong>Health Center ID:</strong> $hcId<br>";
    echo "<strong>Status:</strong> Ready for use.";
    echo "</div>";
    echo "<p><a href='../index.php?page=hc_inventory'>Go to Health Center Inventory</a></p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>Fatal Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Fatal Error: " . $e->getMessage() . "</p>";
}
