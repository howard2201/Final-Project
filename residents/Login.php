<?php
session_start();
require_once '../config/Database.php';
require_once 'Resident.php';
$residentClass = new Resident();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $resident = $residentClass->login($email, $password);
    if ($resident) {
        $_SESSION['resident_id'] = $resident['id'];
        $_SESSION['resident_name'] = $resident['full_name'];
        header('Location: Dashboard.php');
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — Prototype</title>
  <link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container auth-page">
  <div class="auth-card">
    <h2>Resident Login</h2>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
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
  </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
