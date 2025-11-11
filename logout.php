<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Store a message before destroying session
$logoutMessage = "You have been successfully logged out.";

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = $logoutMessage;

// Redirect to home page
header("Location: index.php");
exit;
