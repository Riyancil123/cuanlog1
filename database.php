<?php
/**
 * CuanLog - Database Connection Configuration (PDO Native)
 * Compatible with XAMPP (MariaDB/MySQL), Docker, and Live Hosting (cPanel/VPS/Render/etc.)
 */

// Database credentials (disesuaikan ke db_lomba)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'db_lomba');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

$db_connection_error = null;

function getDB() {
    static $pdo = null;
    global $db_connection_error;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $db_connection_error = $e->getMessage();
            return null;
        }
    }

    return $pdo;
}

// Function to check if database & tables exist
function checkDatabaseStatus() {
    global $db_connection_error;
    $db = getDB();
    if (!$db) {
        return ['status' => false, 'error' => $db_connection_error ?? 'Cannot connect to MySQL server.'];
    }

    try {
        $stmt = $db->query("SHOW TABLES LIKE 'products'");
        if ($stmt->rowCount() === 0) {
            return ['status' => false, 'error' => 'Database connected but tables are not initialized.'];
        }
        return ['status' => true, 'error' => null];
    } catch (PDOException $e) {
        return ['status' => false, 'error' => $e->getMessage()];
    }
}