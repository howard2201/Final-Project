<?php
/**
 * Attendance View Page
 * Admin panel for viewing and managing attendance records
 */

session_start();

// Set timezone to Asia/Manila (Philippines)
date_default_timezone_set('Asia/Manila');

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
    /* Latest Check-In Placeholder */
    .checkin-placeholder {
      background: var(--card);
      border: 2px solid var(--border);
      border-radius: 8px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }
    .checkin-placeholder.has-checkin {
      border-color: #667eea;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    }
    .checkin-content {
      display: flex;
      align-items: center;
      gap: 1.25rem;
    }
    .checkin-profile-section {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      flex-shrink: 0;
    }
    .checkin-id {
      font-size: 0.9rem;
      font-weight: bold;
      color: var(--muted);
      background: var(--bg);
      padding: 0.25rem 0.75rem;
      border-radius: 4px;
      border: 1px solid var(--border);
      min-width: 60px;
      text-align: center;
      transition: all 0.3s ease;
    }
    .checkin-placeholder.has-checkin .checkin-id {
      color: #667eea;
      border-color: #667eea;
      background: rgba(102, 126, 234, 0.1);
    }
    .checkin-profile-pic {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 50%;
      border: 3px solid var(--border);
      transition: all 0.3s ease;
    }
    .checkin-placeholder.has-checkin .checkin-profile-pic {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .checkin-placeholder:not(.has-checkin) .checkin-profile-pic {
      opacity: 0.5;
      filter: grayscale(100%);
    }
    .checkin-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.75rem;
      font-weight: bold;
      flex-shrink: 0;
    }
    .checkin-placeholder:not(.has-checkin) .checkin-icon {
      background: var(--muted);
      opacity: 0.5;
    }
    .checkin-info {
      flex: 1;
    }
    .checkin-label {
      font-size: 0.85rem;
      color: var(--muted);
      margin-bottom: 0.5rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .checkin-name {
      font-size: 1.5rem;
      font-weight: bold;
      color: var(--text);
      margin-bottom: 0.25rem;
      transition: color 0.3s ease;
    }
    .checkin-placeholder.has-checkin .checkin-name {
      color: #667eea;
    }
    .checkin-position {
      font-size: 0.9rem;
      color: var(--muted);
      margin-bottom: 0.25rem;
      font-style: italic;
    }
    .checkin-placeholder.has-checkin .checkin-position {
      color: var(--text);
      font-weight: 400;
    }
    .checkin-time {
      font-size: 1rem;
      color: var(--muted);
    }
    .checkin-placeholder.has-checkin .checkin-time {
      color: var(--text);
      font-weight: 500;
    }
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

      <!-- Latest Check-In Placeholder -->
      <div id="latestCheckInPlaceholder" class="checkin-placeholder">
        <div class="checkin-content">
          <div class="checkin-profile-section">
            <div class="checkin-id" id="latestCheckInId">---</div>
            <img src="../assets/img/profile.png" alt="Profile" class="checkin-profile-pic" id="latestCheckInProfile">
          </div>
          <div class="checkin-icon">✓</div>
          <div class="checkin-info">
            <div class="checkin-label">Latest Check-In</div>
            <div class="checkin-name" id="latestCheckInName">No check-ins yet today</div>
            <div class="checkin-position" id="latestCheckInPosition"></div>
            <div class="checkin-time" id="latestCheckInTime"></div>
          </div>
        </div>
      </div>

      <section id="attendanceTable">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center;">
          <h2>Attendance Records (Today)</h2>
          <a href="AttendanceLogsView.php" class="btn outline">View Past Logs</a>
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
                  <th>Employee #</th>
                  <th>Name</th>
                  <th>Position</th>
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
                        $employeeNumber = isset($record['employee_number']) && !empty($record['employee_number']) 
                            ? htmlspecialchars($record['employee_number'], ENT_QUOTES, 'UTF-8') 
                            : 'N/A';
                        $position = isset($record['position']) && !empty($record['position']) 
                            ? htmlspecialchars($record['position'], ENT_QUOTES, 'UTF-8') 
                            : 'N/A';
                        $date = !empty($record['time_in']) ? date('M d, Y', strtotime($record['time_in'])) : 'N/A';
                        $latestTimeIn = isset($record['latest_time_in']) && !empty($record['latest_time_in']) ? $record['latest_time_in'] : $record['time_in'];
                        $latestTimeOut = isset($record['latest_time_out']) && !empty($record['latest_time_out']) ? $record['latest_time_out'] : null;
                        $status = (!empty($latestTimeIn) && is_null($latestTimeOut)) ? 'Online' : 'Offline';
                        $statusClass = $status === 'Online' ? 'status-online' : 'status-offline';
                        $profilePic = '../assets/img/profile.png';

                        echo "<tr class='clickable-row' data-name='{$name}'>
                            <td>{$id}</td>
                            <td><img src='{$profilePic}' alt='Profile' class='profile-pic'></td>
                            <td>{$employeeNumber}</td>
                            <td>{$name}</td>
                            <td>{$position}</td>
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
    let lastCheckInId = null; // Track the latest check-in ID
    let lastCheckInTime = null; // Track the latest check-in time

    closeBtn.addEventListener('click', function() { modal.style.display = 'none'; });
    window.addEventListener('click', function(event) { if (event.target === modal) { modal.style.display = 'none'; }});

    // Handle clicks on existing rows (for rows loaded on page load)
    document.addEventListener('click', function(event) {
      const row = event.target.closest('.clickable-row');
      if (row && event.target.tagName !== 'BUTTON' && event.target.tagName !== 'INPUT') {
        const name = row.getAttribute('data-name');
        if (name) showDetailedRecords(name);
      }
    });

    function showDetailedRecords(name) {
      // Fetch fresh full records for this person when modal opens
      fetch('api_attendance.php?get_full_records=1')
        .then(res => res.json())
        .then(fullData => {
          if (fullData.success && fullData.fullRecords) {
            // Update allRecords with latest data
            allRecords = fullData.fullRecords;
            displayPersonRecords(name);
          } else {
            // Fallback: use current allRecords
            displayPersonRecords(name);
          }
        })
        .catch(() => {
          // Fallback: use current allRecords if API fails
          displayPersonRecords(name);
        });
    }

    function displayPersonRecords(name) {
      // Filter records for this person
      const personRecords = allRecords.filter(r => r.name === name);
      
      // Group by date and consolidate (same logic as main table)
      const recordsByDate = {};
      const latestTimeInPerDate = {};
      
      personRecords.forEach(record => {
        if (record.time_in) {
          const recordDate = new Date(record.time_in).toISOString().split('T')[0];
          const dateKey = name + '_' + recordDate;
          
          // Track latest time_in for each date
          if (!latestTimeInPerDate[dateKey] || new Date(record.time_in) > new Date(latestTimeInPerDate[dateKey].time_in)) {
            latestTimeInPerDate[dateKey] = record;
          }
          
          // Consolidate records by date
          if (!recordsByDate[dateKey]) {
            recordsByDate[dateKey] = {
              date: recordDate,
              time_in: record.time_in,
              time_out: record.time_out || null,
              latest_time_in: record.time_in,
              latest_time_out: record.time_out || null
            };
          } else {
            // Update with latest time_out if it exists
            if (record.time_out && !recordsByDate[dateKey].time_out) {
              recordsByDate[dateKey].time_out = record.time_out;
              recordsByDate[dateKey].latest_time_out = record.time_out;
            } else if (record.time_out && recordsByDate[dateKey].time_out) {
              // Keep the later time_out
              if (new Date(record.time_out) > new Date(recordsByDate[dateKey].time_out)) {
                recordsByDate[dateKey].time_out = record.time_out;
                recordsByDate[dateKey].latest_time_out = record.time_out;
              }
            }
            // Update latest time_in
            if (new Date(record.time_in) > new Date(recordsByDate[dateKey].latest_time_in)) {
              recordsByDate[dateKey].latest_time_in = record.time_in;
            }
          }
        }
      });
      
      // Update with latest time_in/time_out info
      Object.keys(recordsByDate).forEach(key => {
        if (latestTimeInPerDate[key]) {
          recordsByDate[key].latest_time_in = latestTimeInPerDate[key].time_in;
          recordsByDate[key].latest_time_out = latestTimeInPerDate[key].time_out || null;
        }
      });
      
      // Convert to array and sort by date (newest first)
      const consolidatedRecords = Object.values(recordsByDate);
      consolidatedRecords.sort((a, b) => new Date(b.date) - new Date(a.date));
      
      // Display records
      const tableBody = document.getElementById('detailsTableBody');
      tableBody.innerHTML = '';
      
      consolidatedRecords.forEach(record => {
        const date = new Date(record.date).toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric', timeZone: 'Asia/Manila'});
        const timeIn = new Date(record.latest_time_in).toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit', second:'2-digit', timeZone: 'Asia/Manila'});
        const timeOut = record.latest_time_out ? new Date(record.latest_time_out).toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit', second:'2-digit', timeZone: 'Asia/Manila'}) : 'Ongoing';
        
        // Calculate status using same logic as main table
        const latestTimeIn = record.latest_time_in;
        const latestTimeOut = record.latest_time_out;
        const status = (latestTimeIn && !latestTimeOut) ? 'Online' : 'Offline';
        
        // Calculate duration
        let duration = 'N/A';
        if (record.latest_time_in && record.latest_time_out) {
          const diffMs = new Date(record.latest_time_out) - new Date(record.latest_time_in);
          const hours = Math.floor(diffMs / 3600000);
          const minutes = Math.floor((diffMs % 3600000) / 60000);
          const seconds = Math.floor((diffMs % 60000) / 1000);
          duration = `${String(hours).padStart(2,'0')}:${String(minutes).padStart(2,'0')}:${String(seconds).padStart(2,'0')}`;
        }
        
        tableBody.innerHTML += `<tr>
          <td>${date}</td>
          <td>${timeIn}</td>
          <td>${timeOut}</td>
          <td>${duration}</td>
          <td><span class="status-${status.toLowerCase()}">${status}</span></td>
        </tr>`;
      });
      
      document.getElementById('selectedName').textContent = name;
      modal.style.display='block';
    }

    function updateSummaryStats() {
      const rows = document.querySelectorAll('#attendanceDataTable tbody tr');
      let onlineCount = 0, offlineCount = 0;
      rows.forEach(r => {
        const statusCell = r.querySelector('td:nth-child(7) span');
        if (statusCell) {
          const status = statusCell.textContent.trim();
          if(status==='Online') onlineCount++; else offlineCount++;
        }
      });
      document.getElementById('totalRecords').textContent = rows.length;
      document.getElementById('onlineCount').textContent = onlineCount;
      document.getElementById('offlineCount').textContent = offlineCount;
    }

    function updateStatusRealtime() {
      const searchDate = new URLSearchParams(window.location.search).get('search_date') || '';
      const fromDate = new URLSearchParams(window.location.search).get('from_date') || '';
      const toDate = new URLSearchParams(window.location.search).get('to_date') || '';
      const empNumber = new URLSearchParams(window.location.search).get('emp_number') || '';
      
      let url = 'api_attendance.php';
      const params = new URLSearchParams();
      if (searchDate) params.append('search_date', searchDate);
      if (fromDate) params.append('from_date', fromDate);
      if (toDate) params.append('to_date', toDate);
      if (empNumber) params.append('emp_number', empNumber);
      params.append('get_latest_checkin', '1'); // Request latest check-in info
      if (params.toString()) url += '?' + params.toString();
      
      fetch(url).then(res => res.json()).then(data => {
        // Check for new check-in
        if (data.success && data.latestCheckIn) {
          const latestCheckIn = data.latestCheckIn;
          
          // Compare time_in to detect new check-ins (more reliable than ID)
          if (latestCheckIn.time_in) {
            const latestTime = new Date(latestCheckIn.time_in).getTime();
            const lastTime = lastCheckInTime ? new Date(lastCheckInTime).getTime() : 0;
            
            // Show notification if this is a newer check-in time
            if (latestTime > lastTime) {
              console.log('New check-in detected:', latestCheckIn.name, latestCheckIn.time_in);
              lastCheckInId = latestCheckIn.id;
              lastCheckInTime = latestCheckIn.time_in;
              showLatestCheckIn(latestCheckIn.name, latestCheckIn.time_in, latestCheckIn.id, latestCheckIn.employee_number, latestCheckIn.position);
            }
          }
        }
        
        if(data.success && data.data){
          const table = $('#attendanceDataTable').DataTable();
          const tbody = $('#attendanceDataTable tbody');
          const existingRows = new Map();
          
          // Get all existing rows
          table.rows().every(function() {
            const row = this.node();
            const name = row.getAttribute('data-name');
            if (name) {
              existingRows.set(name, {
                row: row,
                data: this.data()
              });
            }
          });
          
          // Track which records we've processed
          const processedNames = new Set();
          let hasChanges = false;
          
          // Process each record from API
          data.data.forEach(record => {
            processedNames.add(record.name);
            const existing = existingRows.get(record.name);
            
            if (existing) {
              // Update existing row
              const row = existing.row;
              const statusSpan = row.querySelector('td:nth-child(7) span');
              const currentStatus = statusSpan ? statusSpan.textContent.trim() : '';
              
              if (statusSpan && currentStatus !== record.status) {
                statusSpan.className = record.status === 'Online' ? 'status-online' : 'status-offline';
                statusSpan.textContent = record.status;
                hasChanges = true;
              }
              
              // Update date if needed
              const dateCell = row.querySelector('td:nth-child(6)');
              if (dateCell && dateCell.textContent.trim() !== record.date) {
                dateCell.textContent = record.date;
                hasChanges = true;
              }
              
              // Update employee number if needed
              const empNumCell = row.querySelector('td:nth-child(3)');
              if (empNumCell && record.employee_number && empNumCell.textContent.trim() !== record.employee_number) {
                empNumCell.textContent = record.employee_number;
                hasChanges = true;
              }
              
              // Update position if needed
              const positionCell = row.querySelector('td:nth-child(5)');
              if (positionCell && record.position && positionCell.textContent.trim() !== record.position) {
                positionCell.textContent = record.position;
                hasChanges = true;
              }
            } else {
              // Add new row
              hasChanges = true;
              const profilePic = '../assets/img/profile.png';
              const statusClass = record.status === 'Online' ? 'status-online' : 'status-offline';
              const employeeNumber = record.employee_number || 'N/A';
              const position = record.position || 'N/A';
              const newRow = `
                <tr class="clickable-row" data-name="${record.name}">
                  <td>${record.id}</td>
                  <td><img src="${profilePic}" alt="Profile" class="profile-pic"></td>
                  <td>${employeeNumber}</td>
                  <td>${record.name}</td>
                  <td>${position}</td>
                  <td>${record.date}</td>
                  <td><span class="${statusClass}">${record.status}</span></td>
                  <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this record?');">
                      <input type="hidden" name="delete_id" value="${record.id}">
                      <button type="submit" class="btn-delete">Delete</button>
                    </form>
                  </td>
                </tr>
              `;
              
              // Add row to DataTable
              table.row.add($(newRow)).draw(false);
              
              // Update allRecords for modal - we'll refresh this when modal opens
              // For now, just ensure the record exists
              const recordExists = allRecords.some(r => r.id === record.id && r.name === record.name);
              if (!recordExists) {
                // Add placeholder - will be updated when modal opens
                allRecords.push({
                  id: record.id,
                  name: record.name,
                  time_in: new Date().toISOString(),
                  time_out: record.status === 'Offline' ? new Date().toISOString() : null
                });
              }
            }
          });
          
          // Remove rows that are no longer in the API response (if not using filters)
          if (!searchDate && !fromDate && !toDate && !empNumber) {
            existingRows.forEach((value, name) => {
              if (!processedNames.has(name)) {
                const rowNode = value.row;
                table.row(rowNode).remove().draw(false);
                hasChanges = true;
              }
            });
          }
          
          if (hasChanges) {
            // Re-draw table to ensure proper ordering
            table.draw(false);
            updateSummaryStats();
          } else {
            updateSummaryStats();
          }
          
          // Update allRecords for modal (fetch full records every 5 refreshes to reduce load)
          if (!window.refreshCount) window.refreshCount = 0;
          window.refreshCount++;
          if (window.refreshCount % 5 === 0 || hasChanges) {
            fetch('api_attendance.php?get_full_records=1')
              .then(res => res.json())
              .then(fullData => {
                if (fullData.success && fullData.fullRecords) {
                  allRecords = fullData.fullRecords;
                }
              })
              .catch(e => console.error('Error updating full records:', e));
          }
        }
      }).catch(e => {
        console.error('Error updating attendance:', e);
      });
    }

    $(document).ready(function(){
      setTimeout(function(){
        if($.fn.DataTable.isDataTable('#attendanceDataTable')){
          $('#attendanceDataTable').DataTable().destroy();
        }
        const dataTable = $('#attendanceDataTable').DataTable({
          "pageLength":15,
          "order":[[0,"desc"]],
          "language":{"emptyTable":"No attendance records found","zeroRecords":"No matching records found"},
          "retrieve":true,"destroy":true,
          "drawCallback":function(){
            document.querySelectorAll('#attendanceDataTable tbody tr').forEach(row=>{
              const nameCell = row.querySelector('td:nth-child(4)');
              if (nameCell) {
                row.setAttribute('data-name', nameCell.textContent.trim());
              }
            });
            // Re-attach click handlers for new rows
            document.querySelectorAll('#attendanceDataTable tbody tr.clickable-row').forEach(row => {
              if (!row.hasAttribute('data-click-handled')) {
                row.setAttribute('data-click-handled', 'true');
                row.addEventListener('click', function(e) {
                  if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'INPUT') {
                    const name = this.getAttribute('data-name');
                    if (name) showDetailedRecords(name);
                  }
                });
              }
            });
          }
        });
        updateSummaryStats();
        updateStatusRealtime();
        updateInterval=setInterval(updateStatusRealtime,3000);
      },100);
    });

    function generateUnique5DigitId(recordId) {
      // Generate a unique 5-digit number from the record ID
      // Using a combination of record ID and a hash-like function
      const base = recordId || Date.now();
      // Create a 5-digit number (10000-99999)
      const uniqueId = ((base * 7919) % 90000) + 10000; // 7919 is a prime number for better distribution
      return String(Math.floor(uniqueId)).padStart(5, '0');
    }

    function showLatestCheckIn(name, timeIn, recordId, employeeNumber, position) {
      const placeholder = document.getElementById('latestCheckInPlaceholder');
      const nameElement = document.getElementById('latestCheckInName');
      const timeElement = document.getElementById('latestCheckInTime');
      const positionElement = document.getElementById('latestCheckInPosition');
      const idElement = document.getElementById('latestCheckInId');
      const profilePic = document.getElementById('latestCheckInProfile');
      
      if (!placeholder || !nameElement || !timeElement || !idElement) {
        console.error('Placeholder elements not found');
        return;
      }
      
      nameElement.textContent = name;
      
      // Display position if available
      if (positionElement) {
        if (position && position !== 'null' && position !== '') {
          positionElement.textContent = position;
          positionElement.style.display = 'block';
        } else {
          positionElement.textContent = '';
          positionElement.style.display = 'none';
        }
      }
      
      // Display employee_number from database, or 'N/A' if not available
      if (employeeNumber && employeeNumber !== 'null' && employeeNumber !== '') {
        idElement.textContent = employeeNumber;
      } else {
        idElement.textContent = 'N/A';
      }
      
      // Format time (using Asia/Manila timezone)
      try {
        const time = new Date(timeIn);
        const timeString = time.toLocaleTimeString('en-US', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: true,
          timeZone: 'Asia/Manila'
        });
        timeElement.textContent = timeString;
      } catch (e) {
        console.error('Error formatting time:', e);
        timeElement.textContent = 'Just now';
      }
      
      // Show profile picture
      if (profilePic) {
        profilePic.style.display = 'block';
        profilePic.src = '../assets/img/profile.png';
      }
      
      // Add has-checkin class for styling
      placeholder.classList.add('has-checkin');
      
      // Add a subtle animation when updated
      placeholder.style.animation = 'pulse 0.5s ease-out';
      setTimeout(() => {
        placeholder.style.animation = '';
      }, 500);
    }

    // Initialize: Get the latest check-in on page load and display it
    $(document).ready(function() {
      fetch('api_attendance.php?get_latest_checkin=1')
        .then(res => res.json())
        .then(data => {
          if (data.success && data.latestCheckIn) {
            lastCheckInId = data.latestCheckIn.id;
            lastCheckInTime = data.latestCheckIn.time_in;
            // Display the current latest check-in
            showLatestCheckIn(data.latestCheckIn.name, data.latestCheckIn.time_in, data.latestCheckIn.id, data.latestCheckIn.employee_number, data.latestCheckIn.position);
          }
        })
        .catch(e => console.error('Error fetching latest check-in:', e));
    });

    window.addEventListener('beforeunload',function(){ if(updateInterval){clearInterval(updateInterval);} });
  </script>
  <style>
    @keyframes pulse {
      0% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.02);
      }
      100% {
        transform: scale(1);
      }
    }
  </style>
</body>
</html>
