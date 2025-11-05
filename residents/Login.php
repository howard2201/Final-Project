<?php
require_once 'Resident.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $resident = new Resident();
    $res = $resident->login($email, $password);

    if ($res) {
        $_SESSION['resident_id'] = $res['id'];
        header('Location: Dashboard.php');
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login — Prototype</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<div class="auth-card container">
<h2>Resident Login</h2>
<form method="POST">
<label>Email<input type="email" name="email" required></label>
<label>Password<input type="password" name="password" required></label>
<div class="auth-actions">
<button type="submit" class="btn">Login</button>
</div>
</form>
<?php if(isset($error)) echo "<p class='muted'>$error</p>"; ?>
<p class="muted">Don't have an account? <a href="Register.php" class="link">Register here</a></p>
</div>
</body>
</html>
