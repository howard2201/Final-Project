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
    $status = $_POST['status'];
    $admin->updateRequestStatus($_POST['request_id'], $status);

    if ($status === 'Approved') {
      $_SESSION['success_message'] = "✓ Request has been approved successfully!";
    } elseif ($status === 'Rejected') {
      $_SESSION['success_message'] = "Request has been rejected.";
    } else {
      $_SESSION['success_message'] = "Request status updated successfully!";
    }

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
  <link rel="stylesheet" href="../css/admin_dashboard.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <!-- Chart.js -->
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
          <a href="ResidentApprovals.php">Resident Approvals</a>
          <a href="AttendanceView.php">Attendance</a>
          <a href="../announcements/AnnouncementsList.php">Announcements</a>
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
                    <form method='POST' class='inline-form'>
                      <input type='hidden' name='request_id' value='{$id}'>
                      <input type='hidden' name='update_status' value='1'>
                      <button type='submit' name='status' value='Approved' class='btn small'>Approve</button>
                      <button type='submit' name='status' value='Rejected' class='btn small outline'>Reject</button>
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
      <div class="chart-section">
        <h2>Status Overview</h2>
        <canvas id="statusChart" width="400" height="200"></canvas>
      </div>

    </main>
  </div>

  <?php include 'adminchat.php'; ?>

  <script>
    // Suppress DataTables warnings (show in console instead of alert)
    $.fn.dataTable.ext.errMode = 'none';

    // Initialize DataTable
    $(document).ready(function() {
      // Small delay to ensure DOM is fully ready
      setTimeout(function() {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#requestsDataTable')) {
          $('#requestsDataTable').DataTable().destroy();
        }

        // Initialize DataTable with error handling
        try {
          $('#requestsDataTable').DataTable({
            "pageLength": 10,
            "columnDefs": [
              { "orderable": false, "targets": 4 } // Disable sorting on Action column
            ],
            "order": [[0, "desc"]], // Sort by ID descending
            "language": {
              "emptyTable": "No requests found",
              "zeroRecords": "No matching requests found"
            },
            "retrieve": true, // Allow re-initialization
            "destroy": true // Destroy existing instance before creating new one
          });
        } catch (e) {
          console.error('DataTable initialization error:', e);
        }
      }, 100);
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
