<?php
session_start();
require_once '../config/Database.php';
require_once 'Resident.php';
require_once '../config/SMSService.php';

$residentClass = new Resident();
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
                $result = $residentClass->initiatePasswordReset($username);
                if ($result) {
                    $_SESSION['reset_token'] = $result['token'];
                    $_SESSION['reset_username'] = $result['username'];
                    $_SESSION['reset_step'] = 2;
                    $_SESSION['reset_mode'] = $result['mode'] ?? 'production';
                    $_SESSION['reset_otp_display'] = $result['otp_code'] ?? null;
                    $success = "A verification code has been sent to your registered phone number. Please check your SMS.";
                } else {
                    $error = "If the username exists, a verification code has been sent to your registered phone number.";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === '2') {
        if (isset($_POST['resend_code'])) {
            if (isset($_SESSION['reset_username'])) {
                $result = $residentClass->resendPasswordResetCode($_SESSION['reset_username']);
                if ($result) {
                    $_SESSION['reset_mode'] = $result['mode'] ?? $_SESSION['reset_mode'] ?? 'production';
                    $_SESSION['reset_otp_display'] = $result['otp_code'] ?? null;
                    $success = "A new verification code has been sent to your phone.";
                } else {
                    $error = "Unable to resend verification code at the moment. Please try again later.";
                }
            } else {
                $error = "Session expired. Please start over.";
                unset($_SESSION['reset_token'], $_SESSION['reset_username'], $_SESSION['reset_step'], $_SESSION['reset_mode'], $_SESSION['reset_otp_display']);
            }
        } else {
        // Step 2: Verify SMS code
        $code = trim($_POST['verification_code']);
        
        if (empty($code)) {
            $error = "Please enter the verification code.";
        } elseif (isset($_SESSION['reset_username'])) {
            $verified = $residentClass->verifyResetCode($_SESSION['reset_username'], $code);
            if ($verified) {
                $_SESSION['reset_step'] = 3;
                unset($_SESSION['reset_otp_display']);
                $success = "Code verified successfully. Please enter your new password.";
            } else {
                $error = "Invalid or expired verification code. Please try again.";
            }
        } else {
            $error = "Session expired. Please start over.";
            unset($_SESSION['reset_token'], $_SESSION['reset_username'], $_SESSION['reset_step'], $_SESSION['reset_mode'], $_SESSION['reset_otp_display']);
        }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] === '3') {
        // Step 3: Reset password
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($newPassword)) {
            $error = "Please enter a new password.";
        } elseif (strlen($newPassword) < 6 || strlen($newPassword) > 16) {
            $error = "Password must be between 6 and 16 characters.";
        } elseif (!preg_match('/[0-9]/', $newPassword) || !preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
            $error = "Password must include at least one number and one special character.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "Passwords do not match. Please try again.";
        } elseif (isset($_SESSION['reset_username']) && isset($_SESSION['reset_token'])) {
            try {
                $success = $residentClass->resetPassword($_SESSION['reset_username'], $_SESSION['reset_token'], $newPassword);
                if ($success) {
                    unset($_SESSION['reset_token'], $_SESSION['reset_username'], $_SESSION['reset_step'], $_SESSION['reset_mode'], $_SESSION['reset_otp_display']);
                    $_SESSION['success_message'] = "Password reset successfully! You can now login with your new password.";
                    header('Location: Login.php');
                    exit;
                } else {
                    $error = "Invalid or expired reset token. Please start over.";
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = "Session expired. Please start over.";
            unset($_SESSION['reset_token'], $_SESSION['reset_username'], $_SESSION['reset_step'], $_SESSION['reset_mode'], $_SESSION['reset_otp_display']);
        }
    }
}

$currentStep = isset($_SESSION['reset_step']) ? $_SESSION['reset_step'] : 1;
$otpMode = $_SESSION['reset_mode'] ?? 'production';
$otpDisplay = $_SESSION['reset_otp_display'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Forgot Password — Smart Barangay System</title>
  <link rel="stylesheet" href="../css/residents.css">
  <script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<?php if(isset($error) && !empty($error)): ?>
  <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
<?php endif; ?>

<?php if(isset($success) && !empty($success)): ?>
  <div data-success-message="<?php echo htmlspecialchars($success); ?>"></div>
<?php endif; ?>

<main class="container auth-page">
<div class="backlogin">
  <img src="../assets/img/logo.png" alt="logo">
  <div class="auth-card">
    <h2>Reset Password</h2>
    
    <?php if ($currentStep === 1): ?>
      <p>Enter your username to receive a verification code via SMS.</p>
      <form method="POST">
        <input type="hidden" name="step" value="1">
        <label>Username
          <input type="text" name="username" required autofocus>
        </label>
        <div class="auth-actions">
          <button type="submit" class="btn">Send Verification Code</button>
          <a href="Login.php" class="btn outline">Back to Login</a>
        </div>
      </form>
    <?php elseif ($currentStep === 2): ?>
      <p>Enter the verification code sent to your phone number.</p>
      <?php if ($otpMode !== 'production' && !empty($otpDisplay)): ?>
        <div style="background:#f0f4ff;border:1px solid #a3bffa;border-radius:8px;padding:12px;margin-bottom:15px;">
          <strong>Offline OTP:</strong> <?php echo htmlspecialchars($otpDisplay); ?><br>
          <small>Send this code manually to the resident if SMS delivery is offline.</small>
        </div>
      <?php endif; ?>
      <form method="POST">
        <input type="hidden" name="step" value="2">
        <label>Verification Code
          <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>
        </label>
        <div class="auth-actions">
          <button type="submit" class="btn">Verify Code</button>
          <button type="submit" name="resend_code" value="1" class="btn outline" formnovalidate>Resend Code</button>
          <a href="ForgotPassword.php" class="btn outline">Start Over</a>
        </div>
      </form>
    <?php elseif ($currentStep === 3): ?>
      <p>Enter your new password.</p>
      <form method="POST">
        <input type="hidden" name="step" value="3">
        <label>New Password
          <input type="password" name="new_password" minlength="6" maxlength="16" pattern="(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{6,16}" title="6-16 characters with at least one number and one special character" required autofocus>
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
<script src="../js/responsive.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>

