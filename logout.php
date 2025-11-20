<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user has confirmed logout via GET
if (!isset($_GET['confirm'])) {
    // Show HTML + JS confirmation
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Logout Confirmation</title>
        <!-- Link your existing CSS -->
        <link rel="stylesheet" href="css/residents.css">
    </head>
    <body>
        <div class="custom-alert-logout show" id="logout-popup">
            <div class="custom-alert-content">
                <div class="custom-alert-header"><strong>Logout Confirmation</strong></div>
                <div class="custom-alert-body" style="margin-top:1rem;">Are you sure you want to logout?</div>
                <div style="margin-top:1.5rem;">
                    <button id="logout-yes">Yes</button>
                    <button id="logout-no">No</button>
                </div>
            </div>
        </div>


        <script>
            document.getElementById('logout-yes').addEventListener('click', function(){
                // Redirect with confirmation
                window.location.href = "logout.php?confirm=yes";
            });

            document.getElementById('logout-no').addEventListener('click', function(){
                // Close popup and go back to homepage
                window.location.href = "index.php";
            });
        </script>
    </body>
    </html>
    <?php
    exit; // Stop PHP execution until user confirms
}

// If confirmed, continue logout process

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
?>
