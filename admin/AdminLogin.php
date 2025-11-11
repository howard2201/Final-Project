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
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (empty($password)) {
        $error = "Please enter your password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $user = $admin->login($email, $password);

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
                $error = "The email or password you entered is incorrect.";
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
        <h2>🔐 Admin Login</h2>
        <p class="auth-subtitle">Administrator Access Only</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <strong>⚠️ Login Failed</strong>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Email Address
                <input type="email" name="email" placeholder="admin@gmail.com" required>
            </label>
            <label>Password
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>
            <div class="auth-actions">
                <button type="submit" class="btn">Login as Admin</button>
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
</main>
<?php include '../includes/footer.php'; ?>
</body>
</html>
