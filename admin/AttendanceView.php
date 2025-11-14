<?php
// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$attendanceRecords = $admin->getAttendanceRecords();
$filteredRecords = $attendanceRecords;
$searchDate = '';

// Filter by date if search is submitted
if (isset($_GET['search_date']) && !empty($_GET['search_date'])) {
  $searchDate = $_GET['search_date'];
  $filteredRecords = [];
  
  foreach ($attendanceRecords as $record) {
    if ($record['time_in']) {
      $recordDate = date('Y-m-d', strtotime($record['time_in']));
      if ($recordDate === $searchDate) {
        $filteredRecords[] = $record;
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Attendance Management</title>
  <link rel="stylesheet" href="../css/admin_dashboard.css">
  <link rel="stylesheet" href="../css/attendance.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <script src="../js/alerts.js"></script>
  <style>
    .section-header {
      margin-bottom: 1.5rem;
    }

    .section-header h2 {
      color: #111;
      margin-bottom: 0.5rem;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      background: var(--card);
      border-radius: 8px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.05);
      color: var(--muted);
    }
  </style>
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
          <a href="ResidentApprovals.php">Resident Approvals</a>
          <a href="AttendanceView.php" style="background: rgba(255,255,255,0.15); font-weight: 600;">Attendance</a>
          <a href="../announcements/AnnouncementsList.php">Announcements</a>
        </div>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-top">
        <h1>Attendance Management</h1>
        <div class="admin-actions">
          <a href="../logout.php" class="btn outline small">Logout</a>
        </div>
      </header>

      <!-- Attendance Table -->
      <section id="attendanceTable">
        <div class="section-header">
          <h2>Attendance Records</h2>
          <p>Total Records: <strong><?php echo count($filteredRecords); ?></strong></p>
        </div>

        <!-- Search Bar -->
        <div class="search-section">
          <form method="GET">
            <div class="date-input-wrapper">
              <label for="search_date">Search by Date</label>
              <input type="date" id="search_date" name="search_date" value="<?php echo htmlspecialchars($searchDate); ?>">
            </div>
            <button type="submit" class="btn">Search</button>
            <?php if (!empty($searchDate)): ?>
              <a href="AttendanceView.php" class="btn outline small">Clear</a>
            <?php endif; ?>
          </form>
        </div>

        <?php if (!empty($filteredRecords)): ?>
          <table id="attendanceDataTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Duration</th>
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($filteredRecords as $record) {
                $id = htmlspecialchars($record['id'], ENT_QUOTES, 'UTF-8');
                $name = htmlspecialchars($record['name'], ENT_QUOTES, 'UTF-8');
                $time_in = $record['time_in'] ? date('M d, Y - H:i:s', strtotime($record['time_in'])) : 'N/A';
                $time_out = $record['time_out'] ? date('M d, Y - H:i:s', strtotime($record['time_out'])) : 'Ongoing';
                
                // Calculate duration if both times exist
                $duration = 'N/A';
                if ($record['time_in'] && $record['time_out']) {
                  $time_in_obj = new DateTime($record['time_in']);
                  $time_out_obj = new DateTime($record['time_out']);
                  $interval = $time_in_obj->diff($time_out_obj);
                  $duration = $interval->format('%h:%i:%s');
                }

                echo "<tr>
                  <td>{$id}</td>
                  <td>{$name}</td>
                  <td>{$time_in}</td>
                  <td>{$time_out}</td>
                  <td>{$duration}</td>
                </tr>";
              }
              ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="empty-state">
            <p>No attendance records found.</p>
          </div>
        <?php endif; ?>
      </section>

    </main>
  </div>

  <?php include 'adminchat.php'; ?>

  <script>
    // Suppress DataTables warnings
    $.fn.dataTable.ext.errMode = 'none';

    // Initialize DataTable
    $(document).ready(function() {
      setTimeout(function() {
        if ($.fn.DataTable.isDataTable('#attendanceDataTable')) {
          $('#attendanceDataTable').DataTable().destroy();
        }

        try {
          $('#attendanceDataTable').DataTable({
            "pageLength": 15,
            "order": [[0, "desc"]],
            "language": {
              "emptyTable": "No attendance records found",
              "zeroRecords": "No matching records found"
            },
            "retrieve": true,
            "destroy": true
          });
        } catch (e) {
          console.error('DataTable initialization error:', e);
        }
      }, 100);
    });
  </script>
</body>

</html>
