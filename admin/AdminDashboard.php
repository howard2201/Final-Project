<?php
// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$requests = $admin->getRequests();
$error = '';
$success = '';

// Filter type from dropdown
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';
$filteredRequests = [];

if ($requests) {
    foreach ($requests as $r) {
        if ($filterType === 'registration' && $r['type'] === 'Registration') {
            $filteredRequests[] = $r;
        } elseif ($filterType === 'document' && $r['type'] === 'Document') {
            $filteredRequests[] = $r;
        } elseif ($filterType === 'all') {
            $filteredRequests[] = $r;
        }
    }
}

// Count statuses for chart
$pending = $approved = $rejected = 0;
foreach ($filteredRequests as $r) {
    switch ($r['status']) {
        case 'Pending': $pending++; break;
        case 'Approved': $approved++; break;
        case 'Rejected': $rejected++; break;
    }
}

if (isset($_POST['update_status'])) {
  try {
    $admin->updateRequestStatus($_POST['request_id'], $_POST['status']);
    $_SESSION['success_message'] = "Request status updated successfully!";
    header("Location: AdminDashboard.php" . ($filterType !== 'all' ? "?type=$filterType" : ""));
    exit;
  } catch (Exception $e) {
    $error = "Failed to update request status. Please try again.";
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
  <link rel="stylesheet" href="../css/style.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="brand">Admin</div>
      <nav>
        <div class="sidebar-links">
          <a href="AdminDashboard.php">Dashboard</a>
          <div class="dropdown">
            <button class="dropbtn">Requests ▾</button>
            <div class="dropdown-content">
              <a href="AdminDashboard.php?type=registration">Registration Requests</a>
              <a href="AdminDashboard.php?type=document">Document Requests</a>
              <a href="AdminDashboard.php">All Requests</a>
            </div>
          </div>
        </div>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-top">
        <h1>
          <?php
          if ($filterType === 'registration') echo "Registration Requests";
          elseif ($filterType === 'document') echo "Document Requests";
          else echo "All Requests";
          ?>
        </h1>
        <div class="admin-actions">
          <a href="../logout.php" class="btn outline small">Logout</a>
        </div>
      </header>

      <?php if (!empty($error)): ?>
        <div style="background: #ffe6e6; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <strong style="color: #dc3545;">⚠️ Error</strong>
          <p style="color: #721c24; margin: 5px 0 0 0;"><?php echo htmlspecialchars($error); ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <strong style="color: #28a745;">✓ Success</strong>
          <p style="color: #155724; margin: 5px 0 0 0;"><?php echo htmlspecialchars($success); ?></p>
        </div>
      <?php endif; ?>

      <!-- Requests Table -->
      <section id="requestsTable">
        <table id="requestsDataTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($filteredRequests) {
              foreach ($filteredRequests as $r) {
                $id = htmlspecialchars($r['id'], ENT_QUOTES, 'UTF-8');
                $fullName = htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8');
                $type = htmlspecialchars($r['type'], ENT_QUOTES, 'UTF-8');
                $status = htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8');

                echo "<tr>
                  <td>{$id}</td>
                  <td>{$fullName}</td>
                  <td>{$type}</td>
                  <td>{$status}</td>
                  <td>
                    <form method='POST' style='display:inline'>
                      <input type='hidden' name='request_id' value='{$id}'>
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

      <!-- Bar Chart -->
      <div style="margin-top: 40px;">
        <h2>Status Overview</h2>
        <canvas id="statusChart" width="400" height="200"></canvas>
      </div>

    </main>
  </div>

  <?php include 'adminchat.php'; ?>

  <script>
    // Initialize DataTable
    $(document).ready(function() {
      $('#requestsDataTable').DataTable({
        "pageLength": 10
      });
    });

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
