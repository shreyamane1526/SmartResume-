<?php
session_start();

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('Unauthorized action. Security token mismatch.');
    }
    return true;
}

function requireLogin() {
    if (!isset($_SESSION['admin_user'])) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['admin_user']) && ($_SESSION['admin_user']['role'] === 'admin' || $_SESSION['admin_user']['role'] === 'super_admin');
}

function isSuperAdmin() {
    return isset($_SESSION['admin_user']) && $_SESSION['admin_user']['role'] === 'super_admin';
}
?>
