<?php
/**
 * Attendance View Page
 * Admin panel for viewing and managing attendance records
 */

session_start();

// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

try {
    $admin = new Admin();
    $attendanceRecords = $admin->getAttendanceRecords();
    $filteredRecords = $attendanceRecords;
    $searchDate = '';
    $error = null;
    $deleteSuccess = null;

    // Handle delete request
    if (isset($_POST['delete_id'])) {
        try {
            $admin->deleteAttendanceRecord($_POST['delete_id']);
            $deleteSuccess = "Record deleted successfully!";
            // Refresh records
            $attendanceRecords = $admin->getAttendanceRecords();
            $filteredRecords = $attendanceRecords;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    // Process records to consolidate by person and date
    $uniqueRecords = [];
    $latestTimeInPerPerson = [];

    foreach ($attendanceRecords as $record) {
        if (!empty($record['time_in'])) {
            try {
                $recordDate = date('Y-m-d', strtotime($record['time_in']));
                $personDate = $record['name'] . '_' . $recordDate;

                if (!isset($latestTimeInPerPerson[$personDate]) || 
                    strtotime($record['time_in']) > strtotime($latestTimeInPerPerson[$personDate]['time_in'])) {
                    $latestTimeInPerPerson[$personDate] = $record;
                }

                if (!isset($uniqueRecords[$personDate])) {
                    $uniqueRecords[$personDate] = $record;
                } else {
                    if (!empty($record['time_out']) && empty($uniqueRecords[$personDate]['time_out'])) {
                        $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
                    } elseif (!empty($record['time_out']) && !empty($uniqueRecords[$personDate]['time_out'])) {
                        $existingTimeOut = strtotime($uniqueRecords[$personDate]['time_out']);
                        $newTimeOut = strtotime($record['time_out']);
                        if ($newTimeOut > $existingTimeOut) {
                            $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Record consolidation error: " . $e->getMessage());
                continue;
            }
        }
    }

    foreach ($uniqueRecords as $key => $record) {
        if (isset($latestTimeInPerPerson[$key])) {
            $uniqueRecords[$key]['latest_time_in'] = $latestTimeInPerPerson[$key]['time_in'];
            $uniqueRecords[$key]['latest_time_out'] = !empty($latestTimeInPerPerson[$key]['time_out']) ? $latestTimeInPerPerson[$key]['time_out'] : null;
        }
    }

    $filteredRecords = array_values($uniqueRecords);

    // Existing single date search
    if (isset($_GET['search_date']) && !empty($_GET['search_date'])) {
        $searchDate = trim($_GET['search_date']);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchDate)) {
            $error = "Invalid date format. Please use YYYY-MM-DD format.";
        } else {
            $dateObj = DateTime::createFromFormat('Y-m-d', $searchDate);
            if ($dateObj === false || $dateObj->format('Y-m-d') !== $searchDate) {
                $error = "Invalid date provided. Please enter a valid date.";
                $searchDate = '';
            } else {
                $dateFilteredRecords = [];
                foreach ($filteredRecords as $record) {
                    if (!empty($record['time_in'])) {
                        try {
                            $recordDate = date('Y-m-d', strtotime($record['time_in']));
                            if ($recordDate === $searchDate) {
                                $dateFilteredRecords[] = $record;
                            }
                        } catch (Exception $e) {
                            error_log("Date filtering error: " . $e->getMessage());
                            continue;
                        }
                    }
                }
                $filteredRecords = $dateFilteredRecords;
            }
        }
    }

    // ============================
    // NEW: Additional Filter Logic
    // ============================
    $fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
    $toDate = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';
    $empNumber = isset($_GET['emp_number']) ? trim($_GET['emp_number']) : '';

    if ($fromDate || $toDate || $empNumber) {
        $filteredRecords = array_filter($filteredRecords, function($record) use ($fromDate, $toDate, $empNumber) {
            $recordDate = !empty($record['time_in']) ? date('Y-m-d', strtotime($record['time_in'])) : '';
            $match = true;

            if ($fromDate && $recordDate < $fromDate) $match = false;
            if ($toDate && $recordDate > $toDate) $match = false;
            if ($empNumber && stripos($record['name'], $empNumber) === false && stripos($recordDate, $empNumber) === false) $match = false;

            return $match;
        });
    }
} catch (Exception $e) {
    error_log("AttendanceView error: " . $e->getMessage());
    $error = "An error occurred while retrieving attendance records.";
    $filteredRecords = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Management</title>
  <link rel="stylesheet" href="../css/admin.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>

  <script src="../js/alerts.js"></script>
  <style>
    /* Existing CSS */
    .section-header { margin-bottom: 1.5rem; }
    .section-header h2 { color: #111; margin-bottom: 0.5rem; }
    .empty-state { text-align: center; padding: 3rem; background: var(--card); border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.05); color: var(--muted); }
    .error-message { background-color: #fee; color: #c33; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #c33; }
    .success-message { background-color: #efe; color: #3c3; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #3c3; }
    .profile-pic { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    /* New filter styling */
    .filter-wrapper input { padding: 0.4rem 0.6rem; border-radius: 4px; border:1px solid #ccc; }
    .filter-wrapper label { display:block; font-size:0.9rem; margin-bottom:0.2rem; }
  </style>
</head>

<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
      <header class="admin-top">
        <h1>Attendance Management</h1>
      </header>

      <?php if (!is_null($error)): ?>
        <div class="error-message">
          <strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!is_null($deleteSuccess)): ?>
        <div class="success-message">
          <?php echo htmlspecialchars($deleteSuccess, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section id="attendanceTable">
        <div class="section-header">
          <h2>Attendance Records</h2>
        </div>

        <div class="summary-cards">
          <div class="summary-card">
            <h3>Total Records</h3>
            <div class="value" id="totalRecords"><?php echo count($filteredRecords); ?></div>
          </div>
          <div class="summary-card online">
            <h3>Online Now</h3>
            <div class="value" id="onlineCount">0</div>
          </div>
          <div class="summary-card offline">
            <h3>Offline</h3>
            <div class="value" id="offlineCount">0</div>
          </div>
        </div>


        <!-- ============================
             NEW: Additional Search Filters
             ============================ -->
        <div class="search-section" style="margin-top:1.5rem;">
          <form method="GET">
            <div class="filter-wrapper" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">

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

              <!-- Employee Number / Date -->
              <div class="filter-item">
                <label for="emp_number">Employee Number:</label>
                <input type="text" id="emp_number" name="emp_number" class="form-control"
                       placeholder="Enter Employee Number"
                       value="<?php echo isset($_GET['emp_number']) ? htmlspecialchars($_GET['emp_number'], ENT_QUOTES, 'UTF-8') : ''; ?>">
              </div>

              <button type="submit" class="btn">Search</button>
              <?php if (!empty($_GET['from_date']) || !empty($_GET['to_date']) || !empty($_GET['emp_number'])): ?>
                <a href="AttendanceView.php" class="btn outline small">Clear</a>
              <?php endif; ?>
            </div>
          </form>
        </div>

        <!-- ============================
             Table Display
             ============================ -->
        <?php if (!empty($filteredRecords)): ?>
          <div class="table-container">
            <table id="attendanceDataTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Profile</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($filteredRecords as $record) {
                    try {
                        $id = intval($record['id']);
                        $name = htmlspecialchars($record['name'], ENT_QUOTES, 'UTF-8');
                        $date = !empty($record['time_in']) ? date('M d, Y', strtotime($record['time_in'])) : 'N/A';
                        $latestTimeIn = isset($record['latest_time_in']) && !empty($record['latest_time_in']) ? $record['latest_time_in'] : $record['time_in'];
                        $latestTimeOut = isset($record['latest_time_out']) && !empty($record['latest_time_out']) ? $record['latest_time_out'] : null;
                        $status = (!empty($latestTimeIn) && is_null($latestTimeOut)) ? 'Online' : 'Offline';
                        $statusClass = $status === 'Online' ? 'status-online' : 'status-offline';
                        $profilePic = '../assets/img/profile.png';

                        echo "<tr class='clickable-row' data-name='{$name}'>
                            <td>{$id}</td>
                            <td><img src='{$profilePic}' alt='Profile' class='profile-pic'></td>
                            <td>{$name}</td>
                            <td>{$date}</td>
                            <td><span class=\"{$statusClass}\">{$status}</span></td>
                            <td>
                              <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this record?\");'>
                                <input type='hidden' name='delete_id' value='{$id}'>
                                <button type='submit' class='btn-delete'>Delete</button>
                              </form>
                            </td>
                        </tr>";
                    } catch (Exception $e) {
                        error_log("Record rendering error: " . $e->getMessage());
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
              <p><?php echo !is_null($error) ? "Unable to load records due to an error." : "No attendance records found."; ?></p>
            </div>
          </div>
        <?php endif; ?>

        <!-- ============================
             Details Modal
             ============================ -->
        <div id="detailsModal" class="modal">
          <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Time In/Out Details for <span id="selectedName"></span></h2>
            <table id="detailsTable">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                  <th>Duration</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="detailsTableBody">
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    // Existing JS remains completely intact
    $.fn.dataTable.ext.errMode = 'none';
    const modal = document.getElementById('detailsModal');
    const closeBtn = document.querySelector('.close');
    const allRecords = <?php echo json_encode($admin->getAttendanceRecords()); ?>;
    let updateInterval = null;

    closeBtn.addEventListener('click', function() { modal.style.display = 'none'; });
    window.addEventListener('click', function(event) { if (event.target === modal) { modal.style.display = 'none'; }});

    document.addEventListener('click', function(event) {
      const row = event.target.closest('.clickable-row');
      if (row) { showDetailedRecords(row.getAttribute('data-name')); }
    });

    function showDetailedRecords(name) {
      const personRecords = allRecords.filter(r => r.name === name);
      personRecords.sort((a,b) => new Date(a.time_in) - new Date(b.time_in));
      const tableBody = document.getElementById('detailsTableBody');
      tableBody.innerHTML = '';
      personRecords.forEach(record => {
        if (record.time_in) {
          const date = new Date(record.time_in).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'});
          const timeIn = new Date(record.time_in).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
          const timeOut = record.time_out ? new Date(record.time_out).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit'}) : 'Ongoing';
          let duration = 'N/A'; let status = 'Online';
          if(record.time_in && record.time_out){
            status='Offline';
            const diffMs = new Date(record.time_out) - new Date(record.time_in);
            const hours = Math.floor(diffMs / 3600000);
            const minutes = Math.floor((diffMs % 3600000)/60000);
            const seconds = Math.floor((diffMs % 60000)/1000);
            duration = `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
          }
          tableBody.innerHTML += `<tr>
            <td>${date}</td><td>${timeIn}</td><td>${timeOut}</td><td>${duration}</td><td><span class="status-${status.toLowerCase()}">${status}</span></td>
          </tr>`;
        }
      });
      document.getElementById('selectedName').textContent = name;
      modal.style.display='block';
    }

    function updateSummaryStats() {
      const rows = document.querySelectorAll('#attendanceDataTable tbody tr');
      let onlineCount = 0, offlineCount = 0;
      rows.forEach(r => {
        const status = r.querySelector('td:nth-child(5) span').textContent.trim();
        if(status==='Online') onlineCount++; else offlineCount++;
      });
      document.getElementById('totalRecords').textContent = rows.length;
      document.getElementById('onlineCount').textContent = onlineCount;
      document.getElementById('offlineCount').textContent = offlineCount;
    }

    function updateStatusRealtime() {
      const searchDate = new URLSearchParams(window.location.search).get('search_date') || '';
      const url = 'api_attendance.php' + (searchDate ? '?search_date=' + searchDate : '');
      fetch(url).then(res => res.json()).then(data => {
        if(data.success && data.data){
          data.data.forEach(record => {
            let row = document.querySelector(`tr[data-name="${record.name}"]`);
            if(row){
              const statusSpan = row.querySelector('td:nth-child(5) span');
              if(statusSpan && statusSpan.textContent.trim() !== record.status){
                statusSpan.className = record.status === 'Online' ? 'status-online' : 'status-offline';
                statusSpan.textContent = record.status;
              }
            }
          });
          updateSummaryStats();
        }
      }).catch(e=>console.error('Error updating status:',e));
    }

    $(document).ready(function(){
      setTimeout(function(){
        if($.fn.DataTable.isDataTable('#attendanceDataTable')){
          $('#attendanceDataTable').DataTable().destroy();
        }
        $('#attendanceDataTable').DataTable({
          "pageLength":15,
          "order":[[0,"desc"]],
          "language":{"emptyTable":"No attendance records found","zeroRecords":"No matching records found"},
          "retrieve":true,"destroy":true,
          "drawCallback":function(){
            document.querySelectorAll('#attendanceDataTable tbody tr').forEach(row=>{
              row.setAttribute('data-name',row.querySelector('td:nth-child(3)').textContent.trim());
            });
          }
        });
        updateSummaryStats();
        updateStatusRealtime();
        updateInterval=setInterval(updateStatusRealtime,3000);
      },100);
    });

    window.addEventListener('beforeunload',function(){ if(updateInterval){clearInterval(updateInterval);} });
  </script>
</body>
</html>
