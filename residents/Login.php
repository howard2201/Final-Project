<?php
session_start();
require_once '../config/Database.php';
require_once 'Resident.php';
$residentClass = new Resident();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $resident = $residentClass->login($email, $password);
            if ($resident) {
                // Check approval status
                if ($resident['approval_status'] === 'Pending') {
                    // Store resident info in session for temporary page
                    $_SESSION['pending_resident_id'] = $resident['id'];
                    $_SESSION['pending_resident_name'] = $resident['full_name'];
                    $_SESSION['pending_resident_email'] = $resident['email'];
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
                    header('Location: Dashboard.php');
                    exit;
                } else {
                    $error = "There is an issue with your account status. Please contact the barangay office.";
                }
            } else {
                $error = "The email or password you entered is incorrect. Please try again.";
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
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<?php if(isset($_SESSION['success_message'])): ?>
  <div data-success-message="<?php echo htmlspecialchars($_SESSION['success_message']); ?>"></div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<main class="container auth-page">
  <div class="auth-card">
    <h2>Resident Login</h2>
    <?php if(isset($error)): ?>
      <div class="alert-error">
        <strong>⚠️ Login Failed</strong>
        <p><?php echo htmlspecialchars($error); ?></p>
      </div>
    <?php endif; ?>
    <form method="POST">
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <div class="auth-actions">
        <button type="submit" class="btn">Login</button>
        <a href="Register.php" class="btn outline">Register</a>
      </div>
    </form>

    <div class="login-divider">
      <p>Are you an administrator?</p>
      <a href="../admin/AdminLogin.php">🔐 Admin Login</a>
    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
