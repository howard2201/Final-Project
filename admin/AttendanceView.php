<?php
// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();

// Handle delete request
if (isset($_POST['delete_id'])) {
  try {
    $admin->deleteAttendanceRecord($_POST['delete_id']);
    $deleteSuccess = "Record deleted successfully!";
  } catch (Exception $e) {
    $deleteError = $e->getMessage();
  }
}

$attendanceRecords = $admin->getAttendanceRecords();
$filteredRecords = $attendanceRecords;
$searchDate = '';

// Remove redundancies - keep only first check-in and last check-out per person per day
// But also track the latest time_in for status determination
$uniqueRecords = [];
$latestTimeInPerPerson = [];

foreach ($attendanceRecords as $record) {
  if ($record['time_in']) {
    $recordDate = date('Y-m-d', strtotime($record['time_in']));
    $personDate = $record['name'] . '_' . $recordDate;
    
    // Track the latest time_in for each person per day
    if (!isset($latestTimeInPerPerson[$personDate]) || 
        strtotime($record['time_in']) > strtotime($latestTimeInPerPerson[$personDate]['time_in'])) {
      $latestTimeInPerPerson[$personDate] = $record;
    }
    
    if (!isset($uniqueRecords[$personDate])) {
      $uniqueRecords[$personDate] = $record;
    } else {
      // Update with latest time_out if it exists
      if ($record['time_out'] && !$uniqueRecords[$personDate]['time_out']) {
        $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
      } elseif ($record['time_out'] && $uniqueRecords[$personDate]['time_out']) {
        // Keep the later time_out
        $existingTimeOut = strtotime($uniqueRecords[$personDate]['time_out']);
        $newTimeOut = strtotime($record['time_out']);
        if ($newTimeOut > $existingTimeOut) {
          $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
        }
      }
    }
  }
}

// Add latest time_in info to each unique record for status determination
foreach ($uniqueRecords as $key => $record) {
  if (isset($latestTimeInPerPerson[$key])) {
    $uniqueRecords[$key]['latest_time_in'] = $latestTimeInPerPerson[$key]['time_in'];
    $uniqueRecords[$key]['latest_time_out'] = isset($latestTimeInPerPerson[$key]['time_out']) ? $latestTimeInPerPerson[$key]['time_out'] : null;
  }
}

$filteredRecords = array_values($uniqueRecords);

// Filter by date if search is submitted
if (isset($_GET['search_date']) && !empty($_GET['search_date'])) {
  $searchDate = $_GET['search_date'];
  $dateFilteredRecords = [];
  
  foreach ($filteredRecords as $record) {
    if ($record['time_in']) {
      $recordDate = date('Y-m-d', strtotime($record['time_in']));
      if ($recordDate === $searchDate) {
        $dateFilteredRecords[] = $record;
      }
    }
  }
  $filteredRecords = $dateFilteredRecords;
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

    .breadcrumb {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 1.5rem;
    }

    .breadcrumb a {
      color: #007bff;
      text-decoration: none;
    }

    .breadcrumb a:hover {
      text-decoration: underline;
    }

    .breadcrumb span {
      margin: 0 0.5rem;
      color: #999;
    }

    .summary-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .summary-card {
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
      border-left: 4px solid #007bff;
    }

    .summary-card h3 {
      margin: 0;
      font-size: 0.9rem;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }

    .summary-card .value {
      font-size: 2rem;
      font-weight: bold;
      color: #111;
      margin-top: 0.5rem;
    }

    .summary-card.online {
      border-left-color: #28a745;
    }

    .summary-card.offline {
      border-left-color: #dc3545;
    }

    .filters-section {
      background: white;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
      margin-bottom: 2rem;
    }

    .filters-section h3 {
      margin: 0 0 1rem 0;
      font-size: 1rem;
      color: #111;
      font-weight: 600;
    }

    .filters-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      align-items: end;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
    }

    .filter-group label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 0.5rem;
    }

    .filter-group input,
    .filter-group select {
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.9rem;
      transition: border-color 0.2s;
    }

    .filter-group input:focus,
    .filter-group select:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    .filter-actions {
      display: flex;
      gap: 0.5rem;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.9rem;
    }

    .btn.primary {
      background-color: #007bff;
      color: white;
    }

    .btn.primary:hover {
      background-color: #0056b3;
    }

    .btn.outline {
      background-color: white;
      color: #007bff;
      border: 1px solid #007bff;
    }

    .btn.outline:hover {
      background-color: #f0f7ff;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      background: var(--card);
      border-radius: 8px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.05);
      color: var(--muted);
    }

    .status-online {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      background-color: #d4edda;
      color: #155724;
      border-radius: 4px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .status-offline {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      background-color: #f8d7da;
      color: #721c24;
      border-radius: 4px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .clickable-row {
      cursor: pointer;
    }

    .clickable-row:hover {
      background-color: #f5f5f5;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
      background-color: #fefefe;
      margin: 5% auto;
      padding: 2rem;
      border: 1px solid #888;
      border-radius: 8px;
      width: 80%;
      max-width: 900px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }

    .close:hover,
    .close:focus {
      color: black;
    }

    #detailsTable {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    #detailsTable th,
    #detailsTable td {
      padding: 0.8rem;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    #detailsTable th {
      background-color: #007bff;
      color: white;
    }

    #detailsTable tbody tr:hover {
      background-color: #f5f5f5;
    }

    .btn-delete {
      padding: 0.5rem 1rem;
      background-color: #dc3545;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.9rem;
      font-weight: 600;
      transition: background-color 0.3s ease;
    }

    .btn-delete:hover {
      background-color: #c82333;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      padding: 1rem;
      border-radius: 4px;
      margin-bottom: 1rem;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      padding: 1rem;
      border-radius: 4px;
      margin-bottom: 1rem;
      border: 1px solid #f5c6cb;
    }

    #attendanceDataTable {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    #attendanceDataTable thead {
      background-color: #f8f9fa;
      border-bottom: 2px solid #dee2e6;
    }

    #attendanceDataTable th {
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: #333;
      font-size: 0.9rem;
    }

    #attendanceDataTable td {
      padding: 1rem;
      border-bottom: 1px solid #dee2e6;
      text-align: center;
    }

    #attendanceDataTable tbody tr:last-child td {
      border-bottom: none;
    }

    #attendanceDataTable tbody tr:hover {
      background-color: #f8f9fa;
    }

    .table-container {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }

    .dataTables_wrapper {
      padding: 0;
    }

    .dataTables_bottom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background: #f8f9fa;
      border-top: 1px solid #dee2e6;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .dataTables_info {
      margin: 0;
    }

    .dataTables_paginate {
      margin: 0;
    }

    .date-filter-group {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-left: auto;
    }

    .date-filter-group label {
      margin: 0;
      font-weight: 600;
      white-space: nowrap;
      font-size: 0.9rem;
    }

    .date-filter-group input {
      padding: 0.5rem 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.9rem;
      cursor: pointer;
      min-width: 150px;
      background: white;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23007bff' stroke-width='2'%3E%3Crect x='3' y='4' width='18' height='18' rx='2' ry='2'%3E%3C/rect%3E%3Cline x1='16' y1='2' x2='16' y2='6'%3E%3C/line%3E%3Cline x1='8' y1='2' x2='8' y2='6'%3E%3C/line%3E%3Cline x1='3' y1='10' x2='21' y2='10'%3E%3C/line%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.5rem center;
      background-size: 18px;
      padding-right: 2.5rem;
    }

    .date-filter-group input:hover {
      border-color: #007bff;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }

    /* Litepicker custom styling */
    .litepicker {
      border-radius: 4px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    }

    .litepicker .month-item {
      padding: 12px;
    }

    .litepicker .day {
      border-radius: 4px;
    }

    .litepicker .day.is-today {
      color: #007bff;
      font-weight: 600;
    }

    .litepicker .day.is-selected {
      background-color: #007bff;
      color: white;
    }

    .litepicker .day:hover:not(.is-disabled) {
      background-color: #e9f0ff;
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
        <!-- Breadcrumb -->
        <div class="breadcrumb">
          <a href="AdminDashboard.php">Dashboard</a>
          <span>›</span>
          <strong>Attendance Records</strong>
        </div>

        <!-- Summary Cards -->
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

        <?php if (isset($deleteSuccess)): ?>
          <div class="alert-success"><?php echo $deleteSuccess; ?></div>
        <?php endif; ?>
        <?php if (isset($deleteError)): ?>
          <div class="alert-error"><?php echo $deleteError; ?></div>
        <?php endif; ?>

        <!-- Attendance Records Table -->
        <?php if (!empty($filteredRecords)): ?>
          <div class="table-container">
            <table id="attendanceDataTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($filteredRecords as $record) {
                  $id = htmlspecialchars($record['id'], ENT_QUOTES, 'UTF-8');
                  $name = htmlspecialchars($record['name'], ENT_QUOTES, 'UTF-8');
                  
                  // Get date from time_in
                  $date = $record['time_in'] ? date('M d, Y', strtotime($record['time_in'])) : 'N/A';

                  // Determine status based on latest time_in: Online if latest time_in exists but latest time_out is null
                  $latestTimeIn = isset($record['latest_time_in']) && $record['latest_time_in'] ? $record['latest_time_in'] : $record['time_in'];
                  $latestTimeOut = isset($record['latest_time_out']) && $record['latest_time_out'] ? $record['latest_time_out'] : null;
                  $status = ($latestTimeIn && is_null($latestTimeOut)) ? 'Online' : 'Offline';
                  $statusClass = $status === 'Online' ? 'status-online' : 'status-offline';

                  echo "<tr class='clickable-row' data-name='{$name}'>
                    <td>{$id}</td>
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
                }
                ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="table-container">
            <div class="empty-state">
              <p>No attendance records found.</p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Date Filter Section (Always Visible) -->
        <div style="padding: 1rem; background: #f8f9fa; border-top: 1px solid #dee2e6; border-radius: 0 0 8px 8px; display: flex; justify-content: flex-end; align-items: center; gap: 1rem;">
          <label for="simple_date_picker" style="margin: 0; font-weight: 600;"> Filter by Date:</label>
          <form method="GET" style="display: flex; gap: 0.5rem;">
            <input type="date" id="simple_date_picker" name="search_date" value="<?php echo htmlspecialchars($searchDate); ?>" style="padding: 0.5rem 0.75rem; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
            <button type="submit" class="btn primary" style="padding: 0.5rem 1rem; margin: 0;">Search</button>
            <?php if (!empty($searchDate)): ?>
              <a href="AttendanceView.php" style="padding: 0.5rem 1rem; background: white; color: #007bff; border: 1px solid #007bff; border-radius: 4px; text-decoration: none; font-weight: 600; display: flex; align-items: center;">Clear</a>
            <?php endif; ?>
          </form>
        </div>

        <!-- Details Modal -->
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

  <?php include 'adminchat.php'; ?>

  <script>
    // Suppress DataTables warnings
    $.fn.dataTable.ext.errMode = 'none';

    // Get modal elements
    const modal = document.getElementById('detailsModal');
    const closeBtn = document.querySelector('.close');
    const allRecords = <?php echo json_encode($admin->getAttendanceRecords()); ?>;
    let updateInterval = null;

    // Close modal when X is clicked
    closeBtn.addEventListener('click', function() {
      modal.style.display = 'none';
    });

    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });

    // Handle row click to show details
    document.addEventListener('click', function(event) {
      const row = event.target.closest('.clickable-row');
      if (row) {
        const selectedName = row.getAttribute('data-name');
        showDetailedRecords(selectedName);
      }
    });

    function showDetailedRecords(name) {
      // Filter all records for the selected person
      const personRecords = allRecords.filter(record => record.name === name);
      
      // Sort by time_in
      personRecords.sort((a, b) => new Date(a.time_in) - new Date(b.time_in));

      // Populate the details table
      const tableBody = document.getElementById('detailsTableBody');
      tableBody.innerHTML = '';
      
      personRecords.forEach(record => {
        if (record.time_in) {
          const date = new Date(record.time_in).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
          });
          const timeIn = new Date(record.time_in).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
          });
          const timeOut = record.time_out ? 
            new Date(record.time_out).toLocaleTimeString('en-US', {
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit'
            }) : 'Ongoing';

          // Calculate duration
          let duration = 'N/A';
          if (record.time_in && record.time_out) {
            const inTime = new Date(record.time_in);
            const outTime = new Date(record.time_out);
            const diffMs = outTime - inTime;
            const hours = Math.floor(diffMs / 3600000);
            const minutes = Math.floor((diffMs % 3600000) / 60000);
            const seconds = Math.floor((diffMs % 60000) / 1000);
            duration = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
          }

          const row = `<tr>
            <td>${date}</td>
            <td>${timeIn}</td>
            <td>${timeOut}</td>
            <td>${duration}</td>
          </tr>`;
          tableBody.innerHTML += row;
        }
      });

      // Update modal header and show it
      document.getElementById('selectedName').textContent = name;
      modal.style.display = 'block';
    }

    // Real-time status update function
    function updateStatusRealtime() {
      const searchDate = new URLSearchParams(window.location.search).get('search_date') || '';
      const url = 'api_attendance.php' + (searchDate ? '?search_date=' + searchDate : '');
      
      fetch(url)
        .then(response => response.json())
        .then(data => {
          // Check for new records or updates
          data.forEach(record => {
            let row = document.querySelector(`tr[data-id="${record.id}"]`);
            
            if (!row) {
              // New record - add it to the table
              addNewRowToTable(record);
            } else {
              // Existing record - update it
              updateExistingRow(row, record);
            }
          });
          
          // Update summary statistics
          updateSummaryStats();
        })
        .catch(error => console.error('Error updating status:', error));
    }

    // Check if it's a new day and refresh the table
    function checkAndRefreshForNewDay() {
      const lastCheckTime = sessionStorage.getItem('lastDayCheck');
      const currentDate = new Date().toDateString();
      
      if (lastCheckTime !== currentDate) {
        // It's a new day, refresh the page
        sessionStorage.setItem('lastDayCheck', currentDate);
        location.reload();
      }
    }

    // Update summary statistics
    function updateSummaryStats() {
      const rows = document.querySelectorAll('#attendanceDataTable tbody tr');
      let onlineCount = 0;
      let offlineCount = 0;

      rows.forEach(row => {
        const statusSpan = row.querySelector('td:nth-child(4) span');
        if (statusSpan) {
          if (statusSpan.textContent.trim() === 'Online') {
            onlineCount++;
          } else {
            offlineCount++;
          }
        }
      });

      document.getElementById('totalRecords').textContent = rows.length;
      document.getElementById('onlineCount').textContent = onlineCount;
      document.getElementById('offlineCount').textContent = offlineCount;
    }

    function addNewRowToTable(record) {
      const tbody = document.querySelector('#attendanceDataTable tbody');
      if (!tbody) return;

      // Check if a row with the same name and date already exists
      const existingRows = Array.from(tbody.querySelectorAll('tr')).filter(row => {
        const nameCell = row.querySelector('td:nth-child(2)');
        const dateCell = row.querySelector('td:nth-child(3)');
        return nameCell && dateCell && 
               nameCell.textContent.trim() === record.name && 
               dateCell.textContent.trim() === record.date;
      });

      if (existingRows.length > 0) {
        // Row for this person and date already exists, just update it
        updateExistingRow(existingRows[0], record);
        return;
      }

      const statusClass = record.status === 'Online' ? 'status-online' : 'status-offline';
      const newRow = document.createElement('tr');
      newRow.setAttribute('data-id', record.id);
      newRow.className = 'clickable-row';
      newRow.setAttribute('data-name', record.name);
      
      newRow.innerHTML = `
        <td>${record.id}</td>
        <td>${record.name}</td>
        <td>${record.date}</td>
        <td><span class="${statusClass}">${record.status}</span></td>
        <td>
          <form method='POST' style='display:inline;' onsubmit='return confirm("Are you sure you want to delete this record?");'>
            <input type='hidden' name='delete_id' value='${record.id}'>
            <button type='submit' class='btn-delete'>Delete</button>
          </form>
        </td>
      `;
      
      // Add click handler for the new row
      newRow.addEventListener('click', function(event) {
        if (!event.target.closest('form')) {
          const selectedName = newRow.getAttribute('data-name');
          showDetailedRecords(selectedName);
        }
      });
      
      tbody.insertBefore(newRow, tbody.firstChild);
      
      // Update record count
      const totalRecordsElement = document.querySelector('.section-header p strong');
      if (totalRecordsElement) {
        const currentCount = parseInt(totalRecordsElement.textContent);
        totalRecordsElement.textContent = currentCount + 1;
      }
    }

    function updateExistingRow(row, record) {
      // Update date cell (3rd column)
      const dateCell = row.querySelector('td:nth-child(3)');
      if (dateCell) {
        const currentDate = dateCell.textContent.trim();
        if (record.date !== currentDate) {
          dateCell.textContent = record.date;
        }
      }

      // Update status cell (4th column)
      const statusCell = row.querySelector('td:nth-child(4)');
      if (statusCell) {
        const statusSpan = statusCell.querySelector('span');
        if (statusSpan) {
          const oldStatus = statusSpan.textContent.trim();
          const newStatus = record.status;
          
          if (oldStatus !== newStatus) {
            const statusClass = newStatus === 'Online' ? 'status-online' : 'status-offline';
            statusSpan.className = statusClass;
            statusSpan.textContent = newStatus;
          }
        }
      }
    }

    // Initialize DataTable
    $(document).ready(function() {
      setTimeout(function() {
        if ($.fn.DataTable.isDataTable('#attendanceDataTable')) {
          $('#attendanceDataTable').DataTable().destroy();
        }

        try {
          const table = $('#attendanceDataTable').DataTable({
            "pageLength": 15,
            "order": [[0, "desc"]],
            "language": {
              "emptyTable": "No attendance records found",
              "zeroRecords": "No matching records found"
            },
            "retrieve": true,
            "destroy": true,
            "drawCallback": function() {
              // Add data-id attribute to rows after each draw
              document.querySelectorAll('#attendanceDataTable tbody tr').forEach(row => {
                const idCell = row.querySelector('td:first-child');
                if (idCell) {
                  row.setAttribute('data-id', idCell.textContent.trim());
                }
              });
            }
          });
        } catch (e) {
          console.error('DataTable initialization error:', e);
        }

        // Add data-id attribute to rows initially
        document.querySelectorAll('#attendanceDataTable tbody tr').forEach(row => {
          const idCell = row.querySelector('td:first-child');
          if (idCell) {
            row.setAttribute('data-id', idCell.textContent.trim());
          }
        });

        // Start real-time updates (every 3 seconds)
        updateStatusRealtime();
        updateInterval = setInterval(updateStatusRealtime, 3000);

        // Update summary stats initially
        updateSummaryStats();

        // Check for new day every 60 seconds
        setInterval(checkAndRefreshForNewDay, 60000);
      }, 100);
    });

    // Stop updates when page unloads
    window.addEventListener('beforeunload', function() {
      if (updateInterval) {
        clearInterval(updateInterval);
      }
    });
  </script>
</body>

</html>
