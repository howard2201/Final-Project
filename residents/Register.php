<?php
session_start();
require_once '../config/Database.php';
require_once 'Resident.php';
$residentClass = new Resident();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $idFile = $_FILES['registerId']['name'];
    $proofFile = $_FILES['registerProof']['name'];
    move_uploaded_file($_FILES['registerId']['tmp_name'], '../uploads/'.$idFile);
    move_uploaded_file($_FILES['registerProof']['tmp_name'], '../uploads/'.$proofFile);

    $residentClass->register($fullName, $email, $password, $idFile, $proofFile);
    header('Location: Login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register — Prototype</title>
  <link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container auth-page">
  <div class="auth-card">
    <h2>Create an Account</h2>
    <form method="POST" enctype="multipart/form-data">
      <label>Full Name
        <input type="text" name="fullName" required>
      </label>
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <label>Upload Valid ID
        <input type="file" name="registerId" accept=".jpg,.jpeg,.png,.pdf" required>
      </label>
      <label>Upload Proof of Residency
        <input type="file" name="registerProof" accept=".jpg,.jpeg,.png,.pdf" required>
      </label>
      <div class="auth-actions">
        <button type="submit" class="btn">Register</button>
        <a href="Login.php" class="btn outline">Login</a>
      </div>
    </form>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
