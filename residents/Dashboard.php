<?php
require_once 'Resident.php';
require_once '../requests/Request.php';
session_start();
if(!isset($_SESSION['resident_id'])) header('Location: Login.php');

$resident = new Resident();
$user = $resident->getById($_SESSION['resident_id']);

$request = new Request();
$requests = $request->getByResident($user['id']);

$pending = count(array_filter($requests, fn($r)=> $r['status']=='Pending'));
$approved = count(array_filter($requests, fn($r)=> $r['status']=='Approved'));
$rejected = count(array_filter($requests, fn($r)=> $r['status']=='Rejected'));
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Dashboard — Prototype</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<header class="site-header">
<div class="container header-inner">
<div class="brand">Prototype</div>
<nav class="nav">
<a href="Dashboard.php">Dashboard</a>
<a href="../requests/RequestForm.php">Request</a>
<a href="../announcements/AnnouncementsList.php">Announcements</a>
<a href="Logout.php" class="btn small outline">Logout</a>
</nav>
</div>
</header>

<main class="container dashboard">
<section class="welcome">
<h2>Welcome, <?= $user['full_name'] ?></h2>
<div class="grid cols-3">
<div class="stat card"><h3><?= $pending ?></h3><p>Pending Requests</p></div>
<div class="stat card"><h3><?= $approved ?></h3><p>Approved</p></div>
<div class="stat card"><h3><?= $rejected ?></h3><p>Rejected</p></div>
</div>
</section>

<section class="requests">
<h3>Your Requests</h3>
<div id="requestsList">
<?php foreach($requests as $r): ?>
<div class="card">
<strong><?= $r['type'] ?></strong>
<p><?= $r['details'] ?> — <em><?= $r['status'] ?></em></p>
</div>
<?php endforeach; ?>
</div>
</section>
</main>
<footer class="site-footer small">
<div class="container">
<p class="copyright">© <span id="year2"></span> Prototype</p>
</div>
</footer>
<script src="../js/app.js"></script>
</body>
</html>
