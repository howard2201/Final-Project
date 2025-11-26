<?php
/**
 * Attendance Logs View Page
 * Admin panel for viewing past attendance records from attendance_logs table
 */

session_start();

// Set timezone to Asia/Manila (Philippines)
date_default_timezone_set('Asia/Manila');

// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

try {
    $admin = new Admin();
    $error = null;
    
    // Get filter parameters
    $fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : null;
    $toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : null;
    $searchName = isset($_GET['search_name']) ? trim($_GET['search_name']) : null;
    
    // Validate date formats if provided
    if ($fromDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        $error = "Invalid from date format. Please use YYYY-MM-DD format.";
        $fromDate = null;
    }
    
    if ($toDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
        $error = "Invalid to date format. Please use YYYY-MM-DD format.";
        $toDate = null;
    }
    
    // Get attendance logs
    $attendanceLogs = $admin->getAttendanceLogs($fromDate, $toDate, $searchName);
    
    // Archive old records on page load (maintenance)
    $admin->archiveOldAttendanceRecords();
    
} catch (Exception $e) {
    error_log("AttendanceLogsView error: " . $e->getMessage());
    $error = "An error occurred while retrieving attendance logs.";
    $attendanceLogs = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Logs</title>
  <link rel="stylesheet" href="../css/admin.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <script src="../js/alerts.js"></script>
  <style>
    .section-header { margin-bottom: 1.5rem; }
    .section-header h2 { color: #111; margin-bottom: 0.5rem; }
    .empty-state { text-align: center; padding: 3rem; background: var(--card); border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); color: var(--muted); }
    .error-message { background-color: #fee; color: #c33; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #c33; }
    .profile-pic { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    .filter-wrapper { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; margin-bottom: 1.5rem; }
    .filter-wrapper input { padding: 0.4rem 0.6rem; border-radius: 4px; border: 1px solid #ccc; }
    .filter-wrapper label { display: block; font-size: 0.9rem; margin-bottom: 0.2rem; }
    .filter-item { display: flex; flex-direction: column; }
    .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .summary-card { background: var(--card); padding: 1.5rem; border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); }
    .summary-card h3 { font-size: 0.9rem; color: var(--muted); margin-bottom: 0.5rem; }
    .summary-card .value { font-size: 2rem; font-weight: bold; color: var(--primary); }
  </style>
</head>

<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
      <header class="admin-top">
        <h1>Attendance Logs</h1>
        <p style="color: var(--muted); margin-top: 0.5rem;">View past attendance records</p>
      </header>

      <?php if (!is_null($error)): ?>
        <div class="error-message">
          <strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section id="attendanceLogsTable">
        <div class="section-header">
          <h2>Past Attendance Records</h2>
        </div>

        <div class="summary-cards">
          <div class="summary-card">
            <h3>Total Logs</h3>
            <div class="value" id="totalLogs"><?php echo count($attendanceLogs); ?></div>
          </div>
        </div>

        <!-- Search Filters -->
        <div class="search-section" style="margin-top: 1.5rem;">
          <form method="GET">
            <div class="filter-wrapper">
              <!-- From Date -->
              <div class="filter-item">
                <label for="from_date">From Date:</label>
                <input type="date" id="from_date" name="from_date" class="form-control" 
                       value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date'], ENT_QUOTES, 'UTF-8') : ''; ?>">
              </div>

              <!-- To Date -->
              <div class="filter-item">
                <label for="to_date">To Date:</label>
                <input type="date" id="to_date" name="to_date" class="form-control"
                       value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date'], ENT_QUOTES, 'UTF-8') : ''; ?>">
              </div>

              <!-- Name Search -->
              <div class="filter-item">
                <label for="search_name">Search Name:</label>
                <input type="text" id="search_name" name="search_name" class="form-control"
                       placeholder="Enter name"
                       value="<?php echo isset($_GET['search_name']) ? htmlspecialchars($_GET['search_name'], ENT_QUOTES, 'UTF-8') : ''; ?>">
              </div>

              <div style="display: flex; gap: 0.5rem; align-items: flex-end;">
                <button type="submit" class="btn">Search</button>
                <?php if (!empty($_GET['from_date']) || !empty($_GET['to_date']) || !empty($_GET['search_name'])): ?>
                  <a href="AttendanceLogsView.php" class="btn outline small">Clear</a>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>

        <!-- Table Display -->
        <?php if (!empty($attendanceLogs)): ?>
          <div class="table-container">
            <table id="attendanceLogsDataTable">
              <thead>
                <tr>
                  <th>Employee #</th>
                  <th>Profile</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                  <th>Duration</th>
                  <th>Archived At</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($attendanceLogs as $log) {
                    try {
                        $employeeNumber = isset($log['employee_number']) && !empty($log['employee_number']) 
                            ? htmlspecialchars($log['employee_number'], ENT_QUOTES, 'UTF-8') 
                            : 'N/A';
                        $name = htmlspecialchars($log['name'], ENT_QUOTES, 'UTF-8');
                        $date = !empty($log['time_in']) ? date('M d, Y', strtotime($log['time_in'])) : 'N/A';
                        $timeIn = !empty($log['time_in']) ? date('h:i:s A', strtotime($log['time_in'])) : 'N/A';
                        $timeOut = !empty($log['time_out']) ? date('h:i:s A', strtotime($log['time_out'])) : 'N/A';
                        $archivedAt = !empty($log['archived_at']) ? date('M d, Y h:i:s A', strtotime($log['archived_at'])) : 'N/A';
                        
                        // Calculate duration
                        $duration = 'N/A';
                        if (!empty($log['time_in']) && !empty($log['time_out'])) {
                            $timeInObj = new DateTime($log['time_in']);
                            $timeOutObj = new DateTime($log['time_out']);
                            $diff = $timeInObj->diff($timeOutObj);
                            $duration = sprintf('%02d:%02d:%02d', 
                                $diff->h + ($diff->days * 24), 
                                $diff->i, 
                                $diff->s
                            );
                        }
                        
                        $profilePic = '../assets/img/profile.png';

                        echo "<tr>
                            <td>{$employeeNumber}</td>
                            <td><img src='{$profilePic}' alt='Profile' class='profile-pic'></td>
                            <td>{$name}</td>
                            <td>{$date}</td>
                            <td>{$timeIn}</td>
                            <td>{$timeOut}</td>
                            <td>{$duration}</td>
                            <td>{$archivedAt}</td>
                        </tr>";
                    } catch (Exception $e) {
                        error_log("Log rendering error: " . $e->getMessage());
                        continue;
                    }
                }
                ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-container">
            <div class="empty-state">
              <p><?php echo !is_null($error) ? "Unable to load logs due to an error." : "No attendance logs found."; ?></p>
            </div>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <script>
    $(document).ready(function(){
      if($.fn.DataTable.isDataTable('#attendanceLogsDataTable')){
        $('#attendanceLogsDataTable').DataTable().destroy();
      }
      $('#attendanceLogsDataTable').DataTable({
        "pageLength": 25,
        "order": [[0, "desc"]],
        "language": {
          "emptyTable": "No attendance logs found",
          "zeroRecords": "No matching records found"
        },
        "retrieve": true,
        "destroy": true
      });
    });
  </script>
</body>
</html>

