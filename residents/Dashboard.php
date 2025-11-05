<?php
session_start();
require_once "Resident.php";
if(!isset($_SESSION['resident_id'])) header("Location: ../index.php");

$resident = new Resident();
$requests = $resident->getRequests($_SESSION['resident_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
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
<a href="../logout.php" class="btn small outline">Logout</a>
</nav>
</div>
</header>

<main class="container dashboard">
<h2>Welcome, <?php echo $_SESSION['resident_name']; ?></h2>
<div class="grid cols-3">
<div class="stat card"><h3><?php echo count(array_filter($requests, fn($r)=>$r['status']=='Pending')); ?></h3><p>Pending</p></div>
<div class="stat card"><h3><?php echo count(array_filter($requests, fn($r)=>$r['status']=='Approved')); ?></h3><p>Approved</p></div>
<div class="stat card"><h3><?php echo count(array_filter($requests, fn($r)=>$r['status']=='Rejected')); ?></h3><p>Rejected</p></div>
</div>

<h3>Your Requests</h3>
<div id="requestsList">
<?php
if(count($requests)){
    foreach($requests as $r){
        echo "<div class='card'><strong>{$r['type']}</strong><p>{$r['details']} — <em>{$r['status']}</em></p></div>";
    }
}else{
    echo "<p class='muted'>No requests submitted.</p>";
}
?>
</div>
</main>
</body>
</html>
