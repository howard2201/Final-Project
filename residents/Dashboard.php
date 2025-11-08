<?php
session_start();
require_once "Resident.php";
if(!isset($_SESSION['resident_id'])) header("Location: ../index.php");

$resident = new Resident();
$requests = $resident->getRequests($_SESSION['resident_id']);

// Get success message if any
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container dashboard">
<h2>Welcome, <?php echo htmlspecialchars($_SESSION['resident_name']); ?></h2>
<?php if(!empty($successMessage)): ?>
  <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
    <strong style="color: #28a745;">✓ Success</strong>
    <p style="color: #155724; margin: 5px 0 0 0;"><?php echo htmlspecialchars($successMessage); ?></p>
  </div>
<?php endif; ?>
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
        $type = htmlspecialchars($r['type'], ENT_QUOTES, 'UTF-8');
        $details = htmlspecialchars($r['details'], ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8');

        echo "<div class='card'><strong>{$type}</strong><p>{$details} — <em>{$status}</em></p></div>";
    }
}else{
    echo "<p class='muted'>No requests submitted.</p>";
}
?>
</div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
