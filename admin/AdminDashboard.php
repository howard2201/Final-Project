<?php
session_start();
require_once "Admin.php";
if(!isset($_SESSION['admin_id'])) header("Location: ../index.php");

$admin = new Admin();
$requests = $admin->getRequests();

if(isset($_POST['update_status'])){
    $admin->updateRequestStatus($_POST['request_id'], $_POST['status']);
    header("Location: AdminDashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<div class="admin-layout">
<aside class="sidebar">
<div class="brand">Admin</div>
<nav>
<div class="sidebar-links">
<a href="AdminDashboard.php">Dashboard</a>
<a href="#requests">Requests</a>
</div>
</nav>
</aside>

<main class="admin-main">
<header class="admin-top">
<h1>Requests</h1>
<div class="admin-actions">
<a href="../logout.php" class="btn outline small">Logout</a>
</div>
</header>

<section id="requestsTable">
<table>
<thead>
<tr><th>ID</th><th>Name</th><th>Type</th><th>Status</th><th>Action</th></tr>
</thead>
<tbody>
<?php
if($requests){
    foreach($requests as $r){
        echo "<tr>
        <td>{$r['id']}</td>
        <td>{$r['full_name']}</td>
        <td>{$r['type']}</td>
        <td>{$r['status']}</td>
        <td>
        <form method='POST' style='display:inline'>
            <input type='hidden' name='request_id' value='{$r['id']}'>
            <button name='status' value='Approved' class='btn'>Approve</button>
            <button name='status' value='Rejected' class='btn outline'>Reject</button>
        </form>
        </td>
        </tr>";
    }
} else {
    echo "<tr><td colspan='5' class='muted'>No requests found.</td></tr>";
}
?>
</tbody>
</table>
</section>
</main>
</div>
</body>
</html>
