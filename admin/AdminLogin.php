<?php
require_once 'Admin.php';
session_start();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $admin = new Admin();
    $res = $admin->login($email, $password);
    if($res) {
        $_SESSION['admin_id'] = $res['id'];
        header('Location: AdminDashboard.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Login — Prototype</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<div class="auth-card container">
<h2>Admin Login</h2>
<form method="POST">
<label>Email<input type="email" name="email" required></label>
<label>Password<input type="password" name="password" required></label>
<div class="auth-actions">
<button type="submit" class="btn">Login</button>
</div>
</form>
<?php if(isset($error)) echo "<p class='muted'>$error</p>"; ?>
</div>
</body>
</html>
