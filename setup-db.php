<?php
// Setup database
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "MariaDB connection successful\n";
    
    $pdo->exec('CREATE DATABASE IF NOT EXISTS smartresume_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "Database smartresume_db created or already exists\n";
    
    $pdo->exec('USE smartresume_db');
    echo "Using smartresume_db database\n";
    
    // Create admin_users table with the correct structure
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_users (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Insert default admin user (password: admin123)
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO admin_users (username, email, password, full_name, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@smartresume.com', $hashedPassword, 'System Administrator', 'super_admin', 'active']);
    echo "Default admin user created (username: admin, password: admin123)\n";
    
    echo "Database setup completed successfully!\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
