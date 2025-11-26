<?php
session_start();
require_once '../config/Database.php';
require_once 'Resident.php';
$residentClass = new Resident();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($username)) {
        $error = "Please enter your username.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } else {
        try {
            $resident = $residentClass->login($username, $password);
            if ($resident) {
                // Check approval status
                if ($resident['approval_status'] === 'Pending') {
                    // Store resident info in session for temporary page
                    $_SESSION['pending_resident_id'] = $resident['id'];
                    $_SESSION['pending_resident_name'] = $resident['full_name'];
                    $_SESSION['pending_resident_phone_number'] = $resident['phone_number'] ?? '';
                    header('Location: Temporary_Page.php');
                    exit;
                } elseif ($resident['approval_status'] === 'Rejected') {
                    // Store rejected resident info in session for rejection page
                    $_SESSION['rejected_resident_id'] = $resident['id'];
                    $_SESSION['rejected_resident_name'] = $resident['full_name'];
                    $_SESSION['rejected_resident_email'] = $resident['email'];
                    $_SESSION['rejection_date'] = $resident['rejection_date'];
                    header('Location: Rejected_Page.php');
                    exit;
                } elseif ($resident['approval_status'] === 'Approved') {
                    // Approved - allow login
                    $_SESSION['resident_id'] = $resident['id'];
                    $_SESSION['resident_name'] = $resident['full_name'];
                    $_SESSION['login_success'] = true;
                    $_SESSION['success_message'] = "Welcome back, " . htmlspecialchars($resident['full_name']) . "! You have successfully logged in.";
                    header('Location: landing.php');
                    exit;
                } else {
                    $error = "There is an issue with your account status. Please contact the barangay office.";
                }
            } else {
                $error = "Invalid username or password. The credentials you entered do not exist in our system. Please check your username and password, or use the 'Forgot Password' option if you've forgotten your credentials.";
            }
        } catch (Exception $e) {
            $error = "We're having trouble logging you in right now. Please try again in a moment.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — Prototype</title>
  <link rel="stylesheet" href="../css/Login.css">
  <script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<?php if(isset($_SESSION['success_message'])): ?>
  <div data-success-message="<?php echo htmlspecialchars($_SESSION['success_message']); ?>"></div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if(isset($error) && !empty($error)): ?>
  <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
<?php endif; ?>

<main class="container auth-page">
<div class="backlogin">
  <img src="../assets/img/logo.png" alt="logo">
  <div class="auth-card">
    <h2>Resident Login</h2>
    <?php if(isset($error) && !empty($error)): ?>
      <div class="alert-error" style="margin-bottom: 1.5rem; padding: 1rem; background-color: #fee; border-left: 4px solid #e63946; color: #b71c1c; border-radius: 4px;">
        <strong>⚠️ Login Failed</strong>
        <p style="margin-top: 0.5rem; margin-bottom: 0;"><?php echo htmlspecialchars($error); ?></p>
      </div>
    <?php endif; ?>
    <form method="POST">
      <label>Username
        <input type="text" name="username" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <div class="auth-actions">
        <button type="submit" class="btn">Login</button>
        <a href="Register.php" class="btn outline">Register</a>
      </div>
      <div style="margin-top: 10px; text-align: center;">
        <a href="ForgotPassword.php" style="color: #007bff; text-decoration: none; font-size: 0.9em;">Forgot Password?</a>
      </div>
    </form>

    <div class="login-divider">
      <p>Are you an administrator?</p>
      <a href="../admin/AdminLogin.php">🔐 Admin Login</a>
    </div>
  </div>
</div>
</main>
<script src="../js/responsive.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
