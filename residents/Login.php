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
                $_SESSION['resident_id'] = $resident['id'];
                $_SESSION['resident_name'] = $resident['full_name'];
                header('Location: Dashboard.php');
                exit;
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
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container auth-page">
  <div class="auth-card">
    <h2>Resident Login</h2>
    <?php if(isset($error)): ?>
      <div style="background: #ffe6e6; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
        <strong style="color: #dc3545;">⚠️ Login Failed</strong>
        <p style="color: #721c24; margin: 5px 0 0 0;"><?php echo htmlspecialchars($error); ?></p>
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

    <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
      <p style="color: #666; margin-bottom: 10px; font-size: 14px;">Are you an administrator?</p>
      <a href="../admin/AdminLogin.php" style="color: #667eea; text-decoration: none; font-weight: 500;">
        🔐 Admin Login
      </a>
    </div>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
