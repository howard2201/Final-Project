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

    // Handle manual attendance addition
    if (isset($_POST['add_manual_attendance'])) {
        try {
            $manualName = trim($_POST['manual_name']);
            $manualTimeIn = trim($_POST['manual_time_in']);
            $manualTimeOut = !empty(trim($_POST['manual_time_out'])) ? trim($_POST['manual_time_out']) : null;

            // Validate inputs
            if (empty($manualName)) {
                throw new Exception("Name cannot be empty.");
            }
            if (empty($manualTimeIn)) {
                throw new Exception("Time In cannot be empty.");
            }

            // Convert datetime-local format to database format if needed
            $timeInFormatted = str_replace('T', ' ', $manualTimeIn);
            $timeOutFormatted = $manualTimeOut ? str_replace('T', ' ', $manualTimeOut) : null;

            // Add the manual attendance record
            $admin->addManualAttendance($manualName, $timeInFormatted, $timeOutFormatted);
            $deleteSuccess = "Manual attendance record added successfully!";
            
            // Refresh records
            $attendanceRecords = $admin->getAttendanceRecords();
            $filteredRecords = $attendanceRecords;
        } catch (Exception $e) {
            $error = "Failed to add attendance: " . $e->getMessage();
        }
    }

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

    // Handle update request
    if (isset($_POST['update_id']) && isset($_POST['editName']) && isset($_POST['editTimeIn'])) {
        try {
            $updateId = intval($_POST['update_id']);
            $updateName = trim($_POST['editName']);
            $updateTimeIn = trim($_POST['editTimeIn']);
            $updateTimeOut = !empty(trim($_POST['editTimeOut'])) ? trim($_POST['editTimeOut']) : null;

            // Validate inputs
            if (empty($updateName)) {
                throw new Exception("Name cannot be empty.");
            }
            if (empty($updateTimeIn)) {
                throw new Exception("Time In cannot be empty.");
            }

            // Convert datetime-local format to database format if needed
            $timeInFormatted = str_replace('T', ' ', $updateTimeIn);
            $timeOutFormatted = $updateTimeOut ? str_replace('T', ' ', $updateTimeOut) : null;

            // Update the record
            $admin->updateAttendanceRecord($updateId, $updateName, $timeInFormatted, $timeOutFormatted);
            $deleteSuccess = "Record updated successfully!";
            
            // Refresh records
            $attendanceRecords = $admin->getAttendanceRecords();
            $filteredRecords = $attendanceRecords;
        } catch (Exception $e) {
            $error = "Update failed: " . $e->getMessage();
        }
    }

    // Process records to consolidate by person and date
    // Keep only first check-in and last check-out per person per day
    $uniqueRecords = [];
    $latestTimeInPerPerson = [];

    foreach ($attendanceRecords as $record) {
        if (!empty($record['time_in'])) {
            try {
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
                    if (!empty($record['time_out']) && empty($uniqueRecords[$personDate]['time_out'])) {
                        $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
                    } elseif (!empty($record['time_out']) && !empty($uniqueRecords[$personDate]['time_out'])) {
                        // Keep the later time_out
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

    // Add latest time_in info to each unique record for status determination
    foreach ($uniqueRecords as $key => $record) {
        if (isset($latestTimeInPerPerson[$key])) {
            $uniqueRecords[$key]['latest_time_in'] = $latestTimeInPerPerson[$key]['time_in'];
            $uniqueRecords[$key]['latest_time_out'] = !empty($latestTimeInPerPerson[$key]['time_out']) ? $latestTimeInPerPerson[$key]['time_out'] : null;
        }
    }

    $filteredRecords = array_values($uniqueRecords);

    // Filter by date if search is submitted
    if (isset($_GET['search_date']) && !empty($_GET['search_date'])) {
        $searchDate = trim($_GET['search_date']);
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchDate)) {
            $error = "Invalid date format. Please use YYYY-MM-DD format.";
        } else {
            // Validate if date is valid
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

    .error-message {
      background-color: #fee;
      color: #c33;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      border-left: 4px solid #c33;
    }

    .success-message {
      background-color: #efe;
      color: #3c3;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      border-left: 4px solid #3c3;
    }
  </style>
</head>

<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
      <header class="admin-top">
        <h1>Attendance Management</h1>
        <div class="admin-actions">
          <a href="../logout.php" class="btn outline small">Logout</a>
        </div>
      </header>

      <!-- Error Message Display -->
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

      <!-- Attendance Table -->
      <section id="attendanceTable">
        <div class="section-header">
          <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h2>Attendance Records</h2>
            <button type="button" class="btn" onclick="openAddManualModal()" title="Add Manual Attendance">
              ➕ Add Manual Attendance
            </button>
          </div>
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

        <!-- Search Bar -->
        <div class="search-section">
          <form method="GET">
            <div class="date-input-wrapper">
              <label for="search_date">Search by Date</label>
              <input type="date" id="search_date" name="search_date" value="<?php echo htmlspecialchars($searchDate, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <button type="submit" class="btn">Search</button>
            <?php if (!empty($searchDate)): ?>
              <a href="AttendanceView.php" class="btn outline small">Clear</a>
            <?php endif; ?>
          </form>
        </div>

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
                  try {
                      $id = intval($record['id']);
                      $name = htmlspecialchars($record['name'], ENT_QUOTES, 'UTF-8');
                      
                      // Get date from time_in
                      $date = !empty($record['time_in']) ? date('M d, Y', strtotime($record['time_in'])) : 'N/A';

                      // Determine status based on latest time_in: Online if latest time_in exists but latest time_out is null
                      $latestTimeIn = isset($record['latest_time_in']) && !empty($record['latest_time_in']) ? $record['latest_time_in'] : $record['time_in'];
                      $latestTimeOut = isset($record['latest_time_out']) && !empty($record['latest_time_out']) ? $record['latest_time_out'] : null;
                      $status = (!empty($latestTimeIn) && is_null($latestTimeOut)) ? 'Online' : 'Offline';
                      $statusClass = $status === 'Online' ? 'status-online' : 'status-offline';

                      echo "<tr class='clickable-row' data-name='{$name}' data-id='{$id}'>
                        <td>{$id}</td>
                        <td>{$name}</td>
                        <td>{$date}</td>
                        <td><span class=\"{$statusClass}\">{$status}</span></td>
                        <td>
                          <div class='action-buttons'>
                            <button type='button' class='btn-view' onclick='viewRecord({$id}, \"{$name}\")' title='View Details'>View</button>
                            <button type='button' class='btn-edit' onclick='editRecord({$id}, \"{$name}\")' title='Edit Record'>Edit</button>
                            <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this record?\");'>
                              <input type='hidden' name='delete_id' value='{$id}'>
                              <button type='submit' class='btn-delete' title='Delete Record'>Delete</button>
                            </form>
                          </div>
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
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="detailsTableBody">
              </tbody>
            </table>
          </div>
        </div>

        <!-- Manual Attendance Modal -->
        <div id="manualAttendanceModal" class="modal">
          <div class="modal-content">
            <span class="close" onclick="closeAddManualModal()">&times;</span>
            <h2>Add Manual Attendance Record</h2>
            <form method="POST" id="manualAttendanceForm">
              <input type="hidden" name="add_manual_attendance" value="1">
              <div style="margin-bottom: 1rem;">
                <label for="manual_name" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Name</label>
                <input type="text" id="manual_name" name="manual_name" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
              </div>
              <div style="margin-bottom: 1rem;">
                <label for="manual_time_in" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Time In</label>
                <input type="datetime-local" id="manual_time_in" name="manual_time_in" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
              </div>
              <div style="margin-bottom: 1rem;">
                <label for="manual_time_out" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Time Out (Optional)</label>
                <input type="datetime-local" id="manual_time_out" name="manual_time_out" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
              </div>
              <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn outline" onclick="closeAddManualModal()">Cancel</button>
                <button type="submit" class="btn">Add Attendance</button>
              </div>
            </form>
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

    // Open manual attendance modal
    function openAddManualModal() {
      const manualModal = document.getElementById('manualAttendanceModal');
      manualModal.style.display = 'block';
    }

    // Close manual attendance modal
    function closeAddManualModal() {
      const manualModal = document.getElementById('manualAttendanceModal');
      manualModal.style.display = 'none';
      // Reset form
      document.getElementById('manualAttendanceForm').reset();
    }

    // Close manual modal when clicking outside of it
    window.addEventListener('click', function(event) {
      const manualModal = document.getElementById('manualAttendanceModal');
      if (event.target === manualModal) {
        manualModal.style.display = 'none';
      }
    });

    // Handle row click to show details
    document.addEventListener('click', function(event) {
      const row = event.target.closest('.clickable-row');
      if (row && !event.target.closest('button') && !event.target.closest('form')) {
        const selectedName = row.getAttribute('data-name');
        showDetailedRecords(selectedName);
      }
    });

    // View record function
    function viewRecord(id, name) {
      showDetailedRecords(name);
    }

    // Edit record function
    function editRecord(id, name) {
      const modal = document.getElementById('detailsModal');
      const editForm = document.getElementById('editForm') || createEditForm();
      
      // Populate the edit form with record data
      const allRecordsFiltered = allRecords.filter(record => record.id == id);
      if (allRecordsFiltered.length > 0) {
        const record = allRecordsFiltered[0];
        document.getElementById('updateId').value = record.id;
        document.getElementById('editId').value = record.id;
        document.getElementById('editName').value = record.name;
        
        // Convert database datetime format to datetime-local format
        if (record.time_in) {
          const timeInDate = new Date(record.time_in);
          document.getElementById('editTimeIn').value = timeInDate.toISOString().slice(0, 16);
        }
        
        if (record.time_out) {
          const timeOutDate = new Date(record.time_out);
          document.getElementById('editTimeOut').value = timeOutDate.toISOString().slice(0, 16);
        }
        
        // Show edit modal
        showEditModal(record);
      }
    }

    // Create edit form if it doesn't exist
    function createEditForm() {
      const form = document.createElement('div');
      form.id = 'editForm';
      form.innerHTML = `
        <div class="modal" id="editModal">
          <div class="modal-content">
            <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
            <h2>Edit Attendance Record</h2>
            <form method="POST" id="editFormContent">
              <input type="hidden" name="update_id" id="updateId">
              <div style="margin-bottom: 1rem;">
                <label for="editId" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Record ID</label>
                <input type="text" id="editId" readonly style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5;">
              </div>
              <div style="margin-bottom: 1rem;">
                <label for="editName" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Name</label>
                <input type="text" id="editName" name="editName" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
              </div>
              <div style="margin-bottom: 1rem;">
                <label for="editTimeIn" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Time In</label>
                <input type="datetime-local" id="editTimeIn" name="editTimeIn" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
              </div>
              <div style="margin-bottom: 1rem;">
                <label for="editTimeOut" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Time Out (Optional)</label>
                <input type="datetime-local" id="editTimeOut" name="editTimeOut" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
              </div>
              <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn outline" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      `;
      document.body.appendChild(form);
      return form;
    }

    // Show edit modal
    function showEditModal(record) {
      let editModal = document.getElementById('editModal');
      if (!editModal) {
        createEditForm();
        editModal = document.getElementById('editModal');
      }
      editModal.style.display = 'block';
    }

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
          let status = 'Online';
          if (record.time_in && record.time_out) {
            status = 'Offline';
            const inTime = new Date(record.time_in);
            const outTime = new Date(record.time_out);
            const diffMs = outTime - inTime;
            const hours = Math.floor(diffMs / 3600000);
            const minutes = Math.floor((diffMs % 3600000) / 60000);
            const seconds = Math.floor((diffMs % 60000) / 1000);
            duration = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
          }

          const statusSpan = `<span class="status-${status.toLowerCase()}">${status}</span>`;

          const row = `<tr>
            <td>${date}</td>
            <td>${timeIn}</td>
            <td>${timeOut}</td>
            <td>${duration}</td>
            <td>${statusSpan}</td>
          </tr>`;
          tableBody.innerHTML += row;
        }
      });

      // Update modal header and show it
      document.getElementById('selectedName').textContent = name;
      modal.style.display = 'block';
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

    // Real-time status update function
    function updateStatusRealtime() {
      const searchDate = new URLSearchParams(window.location.search).get('search_date') || '';
      const url = 'api_attendance.php' + (searchDate ? '?search_date=' + searchDate : '');
      
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.data) {
            data.data.forEach(record => {
              let row = document.querySelector(`tr[data-name="${record.name}"]`);
              
              if (row) {
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
            });
            updateSummaryStats();
          }
        })
        .catch(error => console.error('Error updating status:', error));
    }

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
            "destroy": true,
            "drawCallback": function() {
              // Add data-name attribute to rows after each draw
              document.querySelectorAll('#attendanceDataTable tbody tr').forEach(row => {
                const nameCell = row.querySelector('td:nth-child(2)');
                if (nameCell) {
                  row.setAttribute('data-name', nameCell.textContent.trim());
                }
              });
            }
          });
        } catch (e) {
          console.error('DataTable initialization error:', e);
        }

        // Update summary stats initially
        updateSummaryStats();

        // Start real-time updates (every 3 seconds)
        updateStatusRealtime();
        updateInterval = setInterval(updateStatusRealtime, 3000);
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