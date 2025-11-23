<?php 
session_start();
require_once "Admin.php";

if (isset($_SESSION['admin_id'])) {
    header("Location: AdminDashboard.php");
    exit;
}

$admin = new Admin();
$error = '';

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username)) {
        $error = "Please enter your username.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } else {
        try {
            $user = $admin->login($username, $password);

            if ($user) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_last_activity'] = time();
                $_SESSION['admin_session_created'] = time();
                $_SESSION['success_message'] = "Welcome back, " . htmlspecialchars($user['full_name']) . "! You have successfully logged in as administrator.";

                header("Location: AdminDashboard.php");
                exit;
            } else {
                $error = "Invalid username or password. The credentials you entered do not exist in our system. Please check your username and password, or use the 'Forgot Password' option if you've forgotten your credentials.";
            }
        } catch (Exception $e) {
            $error = "We're experiencing technical difficulties. Please try again later.";
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — Smart Barangay System</title>
    <link rel="stylesheet" href="../css/AdminLogin.css">
    <script src="../js/alerts.js"></script>
</head>
<body>

<?php include '../includes/headerinner.php'; ?>

<?php if(isset($_SESSION['success_message'])): ?>
  <div data-success-message="<?php echo htmlspecialchars($_SESSION['success_message']); ?>"></div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if(!empty($error)): ?>
    <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
<?php endif; ?>

<main class="container auth-page">
<div class="backlogin">
<img src="../assets/img/logo.png" alt="logo">
    <div class="auth-card">
        <h2>🔐 Admin Login</h2>
        <p class="auth-subtitle">Administrator Access Only</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error" style="margin-bottom: 1.5rem; padding: 1rem; background-color: #fee; border-left: 4px solid #e63946; color: #b71c1c; border-radius: 4px;">
                <strong>⚠️ Login Failed</strong>
                <p style="margin-top: 0.5rem; margin-bottom: 0;"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Username
                <input type="text" name="username" placeholder="Enter your username" required>
            </label>
            <label>Password
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>
            <div class="auth-actions">
                <button type="submit" class="btn">Login as Admin</button>
            </div>
            <div style="margin-top: 10px; text-align: center;">
                <a href="ForgotPassword.php" style="color: #007bff; text-decoration: none; font-size: 0.9em;">Forgot Password?</a>
            </div>
        </form>

        <div class="login-divider">
            <p>Not a resident?</p>
            <a href="../residents/Login.php">👤 Resident Login</a>
        </div>

        <div class="login-divider">
            <a href="../index.php" class="back-link">← Back to Home</a>
        </div>
    </div>
</div>
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
