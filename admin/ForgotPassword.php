<?php
session_start();
require_once "Admin.php";
require_once '../config/SMSService.php';

$admin = new Admin();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] === '1') {
        // Step 1: Enter username
        $username = trim($_POST['username']);
        
        if (empty($username)) {
            $error = "Please enter your username.";
        } else {
            try {
                $result = $admin->initiatePasswordReset($username);
                if ($result) {
                    $_SESSION['reset_token'] = $result['token'];
                    $_SESSION['reset_username'] = $result['username'];
                    $_SESSION['reset_step'] = 2;
                    $success = "A verification code has been sent to your registered phone number. Please check your SMS.";
                } else {
                    $error = "If the username exists, a verification code has been sent to your registered phone number.";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === '2') {
        // Step 2: Verify SMS code
        $code = trim($_POST['verification_code']);
        
        if (empty($code)) {
            $error = "Please enter the verification code.";
        } elseif (isset($_SESSION['reset_username'])) {
            $verified = $admin->verifyResetCode($_SESSION['reset_username'], $code);
            if ($verified) {
                $_SESSION['reset_step'] = 3;
                $success = "Code verified successfully. Please enter your new password.";
            } else {
                $error = "Invalid or expired verification code. Please try again.";
            }
        } else {
            $error = "Session expired. Please start over.";
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_step']);
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === '3') {
        // Step 3: Reset password
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($newPassword)) {
            $error = "Please enter a new password.";
        } elseif (strlen($newPassword) < 6 || strlen($newPassword) > 16) {
            $error = "Password must be between 6 and 16 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match. Please try again.";
        } elseif (isset($_SESSION['reset_username']) && isset($_SESSION['reset_token'])) {
            try {
                $success = $admin->resetPassword($_SESSION['reset_username'], $_SESSION['reset_token'], $newPassword);
                if ($success) {
                    unset($_SESSION['reset_token']);
                    unset($_SESSION['reset_username']);
                    unset($_SESSION['reset_step']);
                    $_SESSION['success_message'] = "Password reset successfully! You can now login with your new password.";
                    header("Location: AdminLogin.php");
                    exit;
                } else {
                    $error = "Invalid or expired reset token. Please start over.";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Session expired. Please start over.";
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_step']);
        }
    }
}

$currentStep = isset($_SESSION['reset_step']) ? $_SESSION['reset_step'] : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password — Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
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
<div class="backlogin">
<img src="../assets/img/logo.png" alt="logo">
    <div class="auth-card">
        <h2>🔐 Admin Password Reset</h2>
        
        <?php if ($currentStep === 1): ?>
          <p>Enter your username to receive a verification code via SMS.</p>
          <form method="POST">
            <input type="hidden" name="step" value="1">
            <label>Username
              <input type="text" name="username" required autofocus>
            </label>
            <div class="auth-actions">
              <button type="submit" class="btn">Send Verification Code</button>
              <a href="AdminLogin.php" class="btn outline">Back to Login</a>
            </div>
          </form>
        <?php elseif ($currentStep === 2): ?>
          <p>Enter the verification code sent to your phone number.</p>
          <form method="POST">
            <input type="hidden" name="step" value="2">
            <label>Verification Code
              <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>
            </label>
            <div class="auth-actions">
              <button type="submit" class="btn">Verify Code</button>
              <a href="ForgotPassword.php" class="btn outline">Start Over</a>
            </div>
          </form>
        <?php elseif ($currentStep === 3): ?>
          <p>Enter your new password.</p>
          <form method="POST">
            <input type="hidden" name="step" value="3">
            <label>New Password
              <input type="password" name="new_password" minlength="6" maxlength="16" required autofocus>
            </label>
            <label>Confirm New Password
              <input type="password" name="confirm_password" minlength="6" maxlength="16" required>
            </label>
            <div class="auth-actions">
              <button type="submit" class="btn">Reset Password</button>
              <a href="ForgotPassword.php" class="btn outline">Cancel</a>
            </div>
          </form>
        <?php endif; ?>
    </div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>

