<?php
require_once 'Request.php';
require_once '../residents/Resident.php';
session_start();
if(!isset($_SESSION['resident_id'])) header('Location: ../residents/Login.php');

$resident = new Resident();
$user = $resident->getById($_SESSION['resident_id']);

$request = new Request();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['request_type'];
    $details = $_POST['details'];
    $id_file = $_FILES['id_file']['name'];
    $residency_file = $_FILES['residency_file']['name'];

    move_uploaded_file($_FILES['id_file']['tmp_name'], '../uploads/' . $id_file);
    move_uploaded_file($_FILES['residency_file']['tmp_name'], '../uploads/' . $residency_file);

    $request->create($user['id'], $type, $details, $id_file, $residency_file);
    header('Location: ../residents/Dashboard.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Request Service — Prototype</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<header class="site-header small">
<div class="container header-inner">
<div class="brand">Prototype</div>
<nav class="nav">
<a href="../residents/Dashboard.php">Dashboard</a>
</nav>
</div>
</header>

<main class="container request-page">
<div class="card form-card">
<h2>Request a Document</h2>
<form method="POST" enctype="multipart/form-data">
<label>Type of Request
<select name="request_type" required>
<option value="">Select Request Type</option>
<option>Barangay Certificate</option>
<option>Barangay Clearance</option>
<option>Business Permits and Licenses</option>
<option>Applying for a Passport</option>
<option>Clearance Certificates</option>
<option>Employment</option>
<option>Government Documents Application</option>
<option>Identification</option>
<option>Proof of Address</option>
</select>
</label>

<label>Purpose / Details
<textarea name="details" rows="4" required></textarea>
</label>

<label>Upload Valid ID (Required)
<input type="file" name="id_file" accept=".jpg,.jpeg,.png,.pdf" required>
</label>

<label>Barangay Certificate of Residency (Required)
<input type="file" name="residency_file" accept=".jpg,.jpeg,.png,.pdf" required>
</label>

<div class="actions">
<button type="submit" class="btn">Submit Request</button>
</div>
</form>
</div>
</main>
<script src="../js/app.js"></script>
</body>
</html>
