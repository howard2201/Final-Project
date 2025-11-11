<?php
/**
 * Admin Authentication Guard
 * This file checks if the user is logged in as an admin
 * Include this at the top of any admin-only page
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // Store the attempted page to redirect back after login
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'AdminLogin.php') {
        $_SESSION['redirect_after_login'] = $current_page;
    }

    // Set error message
    $_SESSION['login_error'] = "You must be logged in as an administrator to access this page.";

    // Redirect to admin login page
    header("Location: AdminLogin.php");
    exit;
}

// Validate session - check if admin still exists in database
require_once '../config/Database.php';
$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->prepare("CALL getAdminById(?)");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Admin auth check error: " . $e->getMessage());
    session_destroy();
    header('Location: AdminLogin.php');
    exit;
}

// If admin doesn't exist, destroy session and redirect
if (!$admin) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['login_error'] = "Invalid session. Please log in again.";
    header("Location: AdminLogin.php");
    exit;
}

// Optional: Check if session has expired (30 minutes of inactivity)
$inactive_timeout = 1800; // 30 minutes in seconds

if (isset($_SESSION['admin_last_activity'])) {
    $elapsed_time = time() - $_SESSION['admin_last_activity'];
    
    if ($elapsed_time > $inactive_timeout) {
        // Session expired
        session_unset();
        session_destroy();

        session_start();
        $_SESSION['login_error'] = "Your session has expired due to inactivity. Please log in again.";

        header("Location: AdminLogin.php");
        exit;
    }
}

// Update last activity time
$_SESSION['admin_last_activity'] = time();

// Optional: Regenerate session ID periodically for security
if (!isset($_SESSION['admin_session_created'])) {
    $_SESSION['admin_session_created'] = time();
} else if (time() - $_SESSION['admin_session_created'] > 300) {
    // Regenerate session ID every 5 minutes
    session_regenerate_id(true);
    $_SESSION['admin_session_created'] = time();
}

