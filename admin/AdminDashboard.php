<?php
// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$requests = $admin->getRequests();
$error = '';
$success = '';

// Count statuses for chart
$pending = $approved = $rejected = 0;
foreach ($requests as $r) {
    switch ($r['status']) {
        case 'Pending': $pending++; break;
        case 'Approved': $approved++; break;
        case 'Rejected': $rejected++; break;
    }
}

// Get success message if any
if (isset($_SESSION['success_message'])) {
  $success = $_SESSION['success_message'];
  unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="../js/alerts.js"></script>
</head>

<body>
  <?php if (!empty($success)): ?>
    <div data-success-message="<?php echo htmlspecialchars($success); ?>"></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
  <?php endif; ?>

  <div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
      <header class="admin-top">
        <div>
          <p class="muted">Welcome back, Admin</p>
          <h1>Dashboard Overview</h1>
        </div>
        <a href="DocumentRequests.php" class="btn">Manage Document Requests</a>
      </header>

      <?php if (!empty($error)): ?>
        <div class="alert-error">
          <strong>⚠️ Error</strong>
          <p><?php echo htmlspecialchars($error); ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="success-message">
          <strong>✓ Success</strong>
          <p><?php echo htmlspecialchars($success); ?></p>
        </div>
      <?php endif; ?>

      <section class="stats-grid">
        <div class="stat-card">
          <h3><?php echo $pending; ?></h3>
          <p>Pending Requests</p>
        </div>
        <div class="stat-card">
          <h3><?php echo $approved; ?></h3>
          <p>Approved Requests</p>
        </div>
        <div class="stat-card">
          <h3><?php echo $rejected; ?></h3>
          <p>Rejected Requests</p>
        </div>
      </section>

      <div class="chart-section">
        <h2>Status Overview</h2>
        <canvas id="statusChart" width="400" height="200"></canvas>
      </div>
    </main>
  </div>

  <script>
    // Initialize Chart.js
    const ctx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Pending', 'Approved', 'Rejected'],
        datasets: [{
          label: 'Number of Requests',
          data: [<?php echo $pending; ?>, <?php echo $approved; ?>, <?php echo $rejected; ?>],
          backgroundColor: ['#ffc107', '#28a745', '#dc3545']
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true, stepSize: 1 }
        }
      }
    });
  </script>
</body>
</html>
