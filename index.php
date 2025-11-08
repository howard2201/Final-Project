<?php
session_start();

// Get logout message if any
$logoutMessage = '';
if (isset($_SESSION['logout_message'])) {
    $logoutMessage = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Smart Brgy System — Home</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<?php if(!empty($logoutMessage)): ?>
<div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px auto; border-radius: 5px; max-width: 1200px;">
  <strong style="color: #28a745;">✓ Logged Out</strong>
  <p style="color: #155724; margin: 5px 0 0 0;"><?php echo htmlspecialchars($logoutMessage); ?></p>
</div>
<?php endif; ?>

<main>
    <section class="hero">
      <div class="container hero-inner">
        <div class="hero-copy">
          <h1>Faster, easier barangay services</h1>
          <p>Request certificates, track submissions, and get announcements — all online.</p>
          <div class="hero-actions">
            <?php
              if (isset($_SESSION['resident_id'])) {
                echo '<a href="requests/RequestForm.php" class="btn">Request a Document</a>';
              }
              if (!isset($_SESSION['resident_id'])) {
                echo '<a href="residents/Login.php" class="btn outline">Resident Login</a>';
              }
            ?>
          </div>
        </div>
        <div class="hero-image">
          <img src="assets/img/logo.png" alt="logo">
        </div>
      </div>
    </section>

    <section class="features container">
      <h2>Services</h2>
      <div class="scroll-cards">
        <div class="card">
          <h3>Barangay Certificate</h3>
          <p>Request an official document verifying your residency within the barangay.</p>
        </div>
        <div class="card">
          <h3>Barangay Clearance</h3>
          <p>Apply for a barangay clearance for employment, business, or personal use.</p>
        </div>
        <div class="card">
          <h3>Business Permits and Licenses</h3>
          <p>Submit requirements for business permits and renew existing licenses.</p>
        </div>
        <div class="card">
          <h3>Applying for a Passport</h3>
          <p>Get barangay certification support for passport application requirements.</p>
        </div>
        <div class="card">
          <h3>Clearance Certificates</h3>
          <p>Request barangay-issued clearances for various official purposes.</p>
        </div>
        <div class="card">
          <h3>Employment</h3>
          <p>Obtain barangay documents needed for job applications or verification.</p>
        </div>
        <div class="card">
          <h3>Government Documents Application</h3>
          <p>Assistance in securing NBI, Police, or Birth Certificates at the barangay level.</p>
        </div>
        <div class="card">
          <h3>Identification</h3>
          <p>Apply for a barangay ID or request verification for lost or new IDs.</p>
        </div>
        <div class="card">
          <h3>Proof of Address</h3>
          <p>Request documents proving your official address within the barangay.</p>
        </div>
      </div>
    </section>

    <section class="history-section container">
      <h2>About Our Barangay</h2>
      <button id="openHistory" class="btn outline">View Barangay History</button>
    </section>
</main>

<div id="historyModal" class="modal">
    <div class="modal-content">
      <span id="closeHistory" class="close">&times;</span>
      <h2>Barangay History</h2>
      <p>
        “Banaba” was established in 1883, a small barrio, 1/2 miles away from Taal Lake and the enthralling 
        “Ramandan Falls”. In ancient times, natives here built a “pandayan” – 
        a craft with expertise in metal carvings and furniture making using irons as their source of livelihood. 
        In 1867 – 1869, there was an increase in the number of coffee growers as they opted to plant coffee as their major source of living. 
        However, in dry seasons, natives were forced to evacuate to the nearby plains called 
        “Nueva Villa” which was later renamed to “Bagong Pook” and currently houses Nestle Philippines, Inc., 
        the first food industry in Lipa City.
        <img src="assets/img/brgy-info.png" alt="info" width="500">
        <img src="assets/img/brgy-info2.png" alt="info" width="500">
      </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="js/appear.js"></script>
</body>
</html>
