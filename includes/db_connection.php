<?php
// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartresume_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
$conn = null;

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to ensure proper handling of special characters
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log the error instead of displaying it (for production)
    error_log("Database connection error: " . $e->getMessage());
    
    // For development, you can uncomment the line below
    // die("Database connection failed: " . $e->getMessage());
}

/**
 * Get PDO database connection (alternative connection method)
 * @return PDO database connection
 */
function getPDOConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("PDO connection error: " . $e->getMessage());
        return null;
    }
}
?>
