<?php
session_start();
require_once '../config/Database.php';

$pdo = Database::getInstance()->getConnection();
$error = '';
$success = '';
$showPasswordForm = false;
$userEmail = '';
$userID = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 1: Verify email
    if (!isset($_POST['step']) || $_POST['step'] === '1') {
        $email = trim($_POST['email']);

        // Validate email
        if (empty($email)) {
            $error = "Please enter your email address.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            try {
                // Check if admin exists
                $stmt = $pdo->prepare("CALL getAdminByEmail(?)");
                $stmt->execute([$email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($admin) {
                    $showPasswordForm = true;
                    $userEmail = $email;
                    $userID = $admin['id'];
                } else {
                    $error = "No admin account found with this email address.";
                }
            } catch (PDOException $e) {
                $error = "An error occurred. Please try again later.";
                error_log("Admin password reset error: " . $e->getMessage());
            }
        }
    }
    // Step 2: Set new password
    elseif ($_POST['step'] === '2') {
        $email = trim($_POST['email']);
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        $userID = $_POST['user_id'];

        // Validate inputs
        if (empty($newPassword)) {
            $error = "Please enter a new password.";
            $showPasswordForm = true;
            $userEmail = $email;
        } elseif (strlen($newPassword) < 6) {
            $error = "Password must be at least 6 characters long.";
            $showPasswordForm = true;
            $userEmail = $email;
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match. Please try again.";
            $showPasswordForm = true;
            $userEmail = $email;
        } else {
            try {
                // Hash and update password using stored procedure
                $hashedPassword = hash('sha256', $newPassword);
                $updateStmt = $pdo->prepare("CALL updateAdminPassword(?, ?)");
                $updateStmt->execute([$userID, $hashedPassword]);

                $success = "Password changed successfully! You can now log in with your new password.";
            } catch (PDOException $e) {
                $error = "An error occurred. Please try again later.";
                error_log("Admin password reset error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password — Admin</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/footer.css">
    <link rel="stylesheet" href="../css/admin_login.css">
    <link rel="stylesheet" href="../css/forgotpass.css">
    <script src="../js/alerts.js"></script>
</head>
<body>

<?php include '../includes/headerinner.php'; ?>

<?php if(!empty($error)): ?>
    <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
<?php endif; ?>

<?php if(!empty($success)): ?>
    <div data-success-message="<?php echo htmlspecialchars($success); ?>"></div>
<?php endif; ?>

<main class="container auth-page">
    <div class="auth-card">
        <h2>🔐 Forgot Password?</h2>
        <p style="color: #666; margin-bottom: 1.5rem;">Enter your email address and create a new password.</p>
        
        <?php if(!$showPasswordForm): ?>
          <!-- Step 1: Email Verification -->
          <form method="POST">
            <input type="hidden" name="step" value="1">
            <label>Email Address
              <input type="email" name="email" placeholder="admin@gmail.com" required autofocus>
            </label>
            <div class="auth-actions">
              <button type="submit" class="btn">Next</button>
              <a href="AdminLogin.php" class="btn outline">Back to Login</a>
            </div>
          </form>
        <?php elseif(empty($success)): ?>
          <!-- Step 2: New Password Form -->
          <form method="POST">
            <input type="hidden" name="step" value="2">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($userEmail); ?>">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userID); ?>">
            
            <label>New Password
              <input type="password" name="new_password" required minlength="6" placeholder="At least 6 characters">
            </label>
            <label>Confirm Password
              <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter your password">
            </label>
            
            <div class="auth-actions">
              <button type="submit" class="btn">Change Password</button>
              <a href="AdminLogin.php" class="btn outline">Cancel</a>
            </div>
          </form>
        <?php else: ?>
          <!-- Success Message -->
          <div style="text-align: center; padding: 1.5rem 0;">
            <p style="color: #28a745; margin-bottom: 1rem; font-size: 18px;">✓ Password Changed Successfully!</p>
            <p style="color: #666; font-size: 14px;">Your password has been updated. You can now log in with your new password.</p>
            
            <div class="auth-actions" style="margin-top: 2rem;">
              <a href="AdminLogin.php" class="btn">Login Now</a>
            </div>
          </div>
        <?php endif; ?>

        <div class="login-divider">
            <p>Remember your password?</p>
            <a href="AdminLogin.php">← Back to Admin Login</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
