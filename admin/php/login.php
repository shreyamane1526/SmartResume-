<?php
session_start();
require_once 'database-config.php';

// Check if already logged in
if (isset($_SESSION['admin_user'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $pdo = DatabaseConfig::getConnection();
            
            $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ? AND status = "active"');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login time
                $updateStmt = $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = ?');
                $updateStmt->execute([$user['id']]);
                
                // Set session variables
                $_SESSION['admin_user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                
                // Generate CSRF token for security
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                header('Location: ../index.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
