<?php
session_start();
require_once '../config/Database.php';
require_once 'Resident.php';
require_once '../config/SMSService.php';

$residentClass = new Resident();
$error = '';
$success = '';

// Check if user is in registration process
if (!isset($_SESSION['registration_username'])) {
    header('Location: Register.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        // Resend verification code
        $sent = $residentClass->sendVerificationCode($_SESSION['registration_username']);
        if ($sent) {
            $success = "Verification code has been resent to your phone number.";
        } else {
            $error = "Failed to send verification code. Please try again.";
        }
    } else {
        // Verify code
        $code = trim($_POST['verification_code']);
        
        if (empty($code)) {
            $error = "Please enter the verification code.";
        } else {
            $verified = $residentClass->verifyCode($_SESSION['registration_username'], $code);
            if ($verified) {
                // Clear verification session and redirect to login
                unset($_SESSION['registration_username']);
                $_SESSION['success_message'] = "Account created successfully! Your account is pending approval. You'll be able to log in once approved.";
                header('Location: Login.php');
                exit;
            } else {
                $error = "Invalid or expired verification code. Please try again or request a new code.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verify Phone Number — Smart Barangay System</title>
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
    <h2>Verify Phone Number</h2>
    <p>We've sent a verification code to your phone number. Please enter it below to complete your registration.</p>
    
    <form method="POST">
      <label>Verification Code
        <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>
      </label>
      <div class="auth-actions">
        <button type="submit" class="btn">Verify</button>
        <button type="submit" name="resend" value="1" class="btn outline">Resend Code</button>
      </div>
      <div style="margin-top: 10px; text-align: center;">
        <a href="Register.php" style="color: #007bff; text-decoration: none; font-size: 0.9em;">Back to Registration</a>
      </div>
    </form>
  </div>
</div>
</main>
<script src="../js/responsive.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>

