<?php
require_once 'Admin.php';
require_once '../requests/Request.php';
session_start();
if(!isset($_SESSION['admin_id'])) header('Location: AdminLogin.php');

$request = new Request();
$requests = $request->getAll();

if(isset($_GET['action']) && isset($_GET['id'])) {
    $request->updateStatus($_GET['id'], $_GET['action']);
    header('Location: AdminDashboard.php');
    exit;
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Dashboard — Prototype</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<div class="admin-layout">
<aside class="sidebar">
<div class="brand">Admin</div>
<nav>
<div class="sidebar-links">
<a href="#">Dashboard</a>
<a href="#requests">Requests</a>
</div>
</nav>
</aside>

<main class="admin-main">
<header class="admin-top">
<h1>Requests</h1>
<div class="admin-actions">
<a href="Logout.php" class="btn outline small">Logout</a>
</div>
</header>

<section id="requestsTable">
<table>
<thead>
<tr>
<th>ID</th><th>Name</th><th>Type</th><th>Status</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach($requests as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= $r['full_name'] ?></td>
<td><?= $r['type'] ?></td>
<td><?= $r['status'] ?></td>
<td>
<a class="btn" href="?action=Approved&id=<?= $r['id'] ?>">Approve</a>
<a class="btn outline" href="?action=Rejected&id=<?= $r['id'] ?>">Reject</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>
</main>
</div>
</body>
</html>
