<?php
session_start();
require_once "Resident.php";

// Check if user is logged in
if(!isset($_SESSION['resident_id'])) {
    header("Location: ../index.php");
    exit;
}

// Validate session - check if user still exists and is approved
require_once '../config/Database.php';
$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->prepare("CALL getResidentById(?)");
    $stmt->execute([$_SESSION['resident_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Dashboard validation error: " . $e->getMessage());
    session_destroy();
    header('Location: Login.php');
    exit;
}

// If user doesn't exist, destroy session and redirect
if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// If user is rejected, redirect to rejection page
if ($user['approval_status'] === 'Rejected') {
    $_SESSION['rejected_resident_id'] = $user['id'];
    $_SESSION['rejected_resident_name'] = $user['full_name'];
    $_SESSION['rejection_date'] = $user['rejection_date'];
    header("Location: Rejected_Page.php");
    exit;
}

// If user is not approved, redirect to home
if ($user['approval_status'] !== 'Approved') {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$resident = new Resident();
$requests = $resident->getRequests($_SESSION['resident_id']);

// Get announcements
try {
    $stmt = $pdo->prepare("CALL getAllAnnouncements()");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $announcements = [];
    error_log("Get announcements error: " . $e->getMessage());
}

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
<script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<?php if(!empty($successMessage)): ?>
  <div data-success-message="<?php echo htmlspecialchars($successMessage); ?>"></div>
<?php endif; ?>

<main class="container dashboard">
<h2>Welcome, <?php echo htmlspecialchars($_SESSION['resident_name']); ?></h2>

<!-- Announcements Section -->
<?php if (count($announcements) > 0): ?>
<section class="announcements-section">
  <h3>📢 Barangay Announcements</h3>
  <div class="announcements-scroll">
    <?php foreach (array_slice($announcements, 0, 3) as $announcement): ?>
      <div class="announcement-card-resident">
        <div class="announcement-header-resident">
          <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
          <span class="announcement-date-resident">
            <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
          </span>
        </div>
        <p><?php echo nl2br(htmlspecialchars($announcement['body'])); ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>
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

<?php include '../includes/chat.php'; ?>
<?php include '../includes/footer.php'; ?>
</body>
</html>
