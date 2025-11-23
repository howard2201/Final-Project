<?php
session_start();
require_once "../config/Database.php";
require_once "Resident.php";

// Check if user is logged in
if (!isset($_SESSION['resident_id'])) {
    header("Location: Login.php");
    exit;
}

// Validate session - check if user still exists and is approved
$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->prepare("CALL getResidentById(?)");
    $stmt->execute([$_SESSION['resident_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Landing validation error: " . $e->getMessage());
    session_destroy();
    header('Location: Login.php');
    exit;
}

// If user doesn't exist, destroy session and redirect
if (!$user) {
    session_destroy();
    header("Location: Login.php");
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

// If user is not approved, destroy session and redirect
if ($user['approval_status'] !== 'Approved') {
    session_destroy();
    header("Location: Login.php");
    exit;
}

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome — Bagong Pook Community Service</title>
<link rel="stylesheet" href="../css/landing.css">
<script src="../js/alerts.js"></script>
</head>
<body>

<?php include '../includes/headerinner.php'; ?>

<?php if(!empty($successMessage)): ?>
  <div data-success-message="<?php echo htmlspecialchars($successMessage); ?>"></div>
<?php endif; ?>

<main class="container landing-page">

  <div class="landing-container">
    <img src="../assets/img/logo.png" alt="Bagong Pook Logo">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['resident_name']); ?>!</h1>
    <p>
      Thank you for logging in. This platform allows residents of Bagong Pook 
      to easily request community services, stay updated with announcements, 
      and access important local information. Click below to proceed to your homepage.
    </p>
    <a href="Dashboard.php" class="btn">Go to Dashboard</a>
  </div>

  <?php if (count($announcements) > 0): ?>
  <section class="announcements-section">
    <h3>📢 Latest Announcements</h3>
    <div class="announcements-scroll">
      <?php foreach(array_slice($announcements, 0, 3) as $announcement): ?>
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

</main>

<?php include '../includes/footer.php'; ?>

</body>
</html>
