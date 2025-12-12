<?php
require_once '../../includes/db_connection.php';

// Exit if no connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Array of SQL statements to create tables
$sqlStatements = [
    // Contact form submissions table
    "CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firstName VARCHAR(50) NOT NULL,
        lastName VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        company VARCHAR(100) DEFAULT NULL,
        subject VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        subscribe TINYINT(1) DEFAULT 0,
        ip_address VARCHAR(45) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Resume history table (from your original SQL)
    "CREATE TABLE IF NOT EXISTS resume_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(100) NOT NULL,
        user_email VARCHAR(100) NOT NULL,
        job_role VARCHAR(100) NOT NULL,
        template_used VARCHAR(50) NOT NULL,
        action_type ENUM('created', 'viewed', 'downloaded') NOT NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Admin users table (from your original SQL)
    "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    // Insert default admin user (password: admin123)
    "INSERT IGNORE INTO admin_users (username, email, password, full_name, role, status) 
    VALUES ('admin', 'admin@smartresume.com', '$2y$10$W9EB7GRX2R6XBGomcwJGxudtmYGJbQjx9717c79GN74Z3V1sEm7Im', 'System Administrator', 'super_admin', 'active')"
];

// Execute each SQL statement
$success = true;
$errors = [];

foreach ($sqlStatements as $sql) {
    if (!$conn->query($sql)) {
        $success = false;
        $errors[] = $conn->error;
    }
}

// Output results
if ($success) {
    echo "Database setup completed successfully! All tables have been created.";
} else {
    echo "Database setup encountered errors:<br>";
    foreach ($errors as $error) {
        echo "- " . $error . "<br>";
    }
}

$conn->close();
?>
