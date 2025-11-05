<?php
require_once 'Resident.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $id_file = $_FILES['id_file']['name'];
    $proof_file = $_FILES['proof_file']['name'];

    move_uploaded_file($_FILES['id_file']['tmp_name'], '../uploads/' . $id_file);
    move_uploaded_file($_FILES['proof_file']['tmp_name'], '../uploads/' . $proof_file);

    $resident = new Resident();
    $resident->register($full_name, $email, $password, $id_file, $proof_file);

    header('Location: Login.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Register — Prototype</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<div class="auth-card container">
<h2>Create an Account</h2>
<form method="POST" enctype="multipart/form-data">
<label>Full Name<input type="text" name="full_name" required></label>
<label>Email<input type="email" name="email" required></label>
<label>Password<input type="password" name="password" required></label>
<label>Upload Valid ID<input type="file" name="id_file" accept=".jpg,.jpeg,.png,.pdf" required></label>
<label>Upload Proof of Residency<input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" required></label>
<div class="auth-actions">
<button type="submit" class="btn">Register</button>
<a href="Login.php" class="btn outline">Cancel</a>
</div>
</form>
<p class="muted">Already have an account? <a href="Login.php" class="link">Login here</a></p>
</div>
</body>
</html>
