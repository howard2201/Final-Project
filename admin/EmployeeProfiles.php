<?php
/**
 * Employee Profiles Management Page
 * Admin panel for viewing and editing employee information from attendance records
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
    $success = null;
    
    // Handle update request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        try {
            $originalName = trim($_POST['original_name']);
            $newName = trim($_POST['name']);
            $employeeNumber = trim($_POST['employee_number']);
            $position = trim($_POST['position']);
            
            // Validate inputs
            if (empty($newName)) {
                $error = "Name cannot be empty.";
            } elseif (mb_strlen($newName) > 255) {
                $error = "Name is too long (maximum 255 characters).";
            } elseif (!empty($employeeNumber) && !preg_match('/^\d{5}$/', $employeeNumber)) {
                $error = "Employee number must be exactly 5 digits.";
            } else {
                // Update the profile
                $admin->updateEmployeeProfile($originalName, $newName, $employeeNumber, $position);
                $success = "Employee profile updated successfully!";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    // Get unique employees
    $employees = $admin->getUniqueEmployees();
    
} catch (Exception $e) {
    error_log("EmployeeProfiles error: " . $e->getMessage());
    $error = "An error occurred while retrieving employee profiles.";
    $employees = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Profiles</title>
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
    .success-message { background-color: #efe; color: #3c3; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #3c3; }
    .profile-pic { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; }
    .editable-input { 
      width: 100%; 
      padding: 0.25rem 0.5rem; 
      border: 1px solid #ddd; 
      border-radius: 4px; 
      font-size: 0.9rem;
    }
    .editable-input:focus { 
      outline: none; 
      border-color: #667eea; 
      box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    }
    .btn-save { 
      background: #667eea; 
      color: white; 
      border: none; 
      padding: 0.4rem 1rem; 
      border-radius: 4px; 
      cursor: pointer;
      font-size: 0.9rem;
    }
    .btn-save:hover { background: #5568d3; }
    .btn-cancel { 
      background: #ccc; 
      color: #333; 
      border: none; 
      padding: 0.4rem 1rem; 
      border-radius: 4px; 
      cursor: pointer;
      font-size: 0.9rem;
      margin-left: 0.5rem;
    }
    .btn-cancel:hover { background: #bbb; }
    .btn-edit { 
      background: #667eea; 
      color: white; 
      border: none; 
      padding: 0.4rem 1rem; 
      border-radius: 4px; 
      cursor: pointer;
      font-size: 0.9rem;
    }
    .btn-edit:hover { background: #5568d3; }
    .edit-mode { background-color: #f8f9ff; }
  </style>
</head>

<body>
  <div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
      <header class="admin-top">
        <h1>Employee Profiles</h1>
        <p style="color: var(--muted); margin-top: 0.5rem;">Manage employee information and details</p>
      </header>

      <?php if (!is_null($error)): ?>
        <div class="error-message">
          <strong>Error:</strong> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!is_null($success)): ?>
        <div class="success-message">
          <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section id="employeeProfilesTable">
        <div class="section-header">
          <h2>Employee Records</h2>
        </div>

        <!-- Table Display -->
        <?php if (!empty($employees)): ?>
          <div class="table-container">
            <table id="employeeProfilesDataTable">
              <thead>
                <tr>
                  <th>Profile</th>
                  <th>Name</th>
                  <th>Employee #</th>
                  <th>Position</th>
                  <th>Total Records</th>
                  <th>Latest Check-In</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                foreach ($employees as $employee) {
                    try {
                        $name = htmlspecialchars($employee['name'], ENT_QUOTES, 'UTF-8');
                        $employeeNumber = isset($employee['employee_number']) && !empty($employee['employee_number']) 
                            ? htmlspecialchars($employee['employee_number'], ENT_QUOTES, 'UTF-8') 
                            : 'N/A';
                        $position = isset($employee['position']) && !empty($employee['position']) 
                            ? htmlspecialchars($employee['position'], ENT_QUOTES, 'UTF-8') 
                            : 'N/A';
                        $totalRecords = isset($employee['total_records']) ? intval($employee['total_records']) : 0;
                        $latestTimeIn = isset($employee['latest_time_in']) && !empty($employee['latest_time_in']) 
                            ? date('M d, Y h:i A', strtotime($employee['latest_time_in'])) 
                            : 'N/A';
                        
                        $profilePic = '../assets/img/profile.png';
                        $rowId = 'employee-' . md5($name);

                        echo "<tr id='{$rowId}' data-original-name='" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "'>
                            <td><img src='{$profilePic}' alt='Profile' class='profile-pic'></td>
                            <td class='editable-name'>{$name}</td>
                            <td class='editable-empnum'>{$employeeNumber}</td>
                            <td class='editable-position'>{$position}</td>
                            <td>{$totalRecords}</td>
                            <td>{$latestTimeIn}</td>
                            <td>
                              <button class='btn-edit' onclick='editEmployee(\"{$rowId}\")'>Edit</button>
                            </td>
                        </tr>";
                    } catch (Exception $e) {
                        error_log("Employee rendering error: " . $e->getMessage());
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
              <p><?php echo !is_null($error) ? "Unable to load profiles due to an error." : "No employee records found."; ?></p>
            </div>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
      <span class="close" onclick="closeEditModal()">&times;</span>
      <h2>Edit Employee Profile</h2>
      <form method="POST" id="editForm">
        <input type="hidden" name="update_profile" value="1">
        <input type="hidden" name="original_name" id="original_name">
        
        <div style="margin-bottom: 1rem;">
          <label for="edit_name" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Name:</label>
          <input type="text" id="edit_name" name="name" class="editable-input" required maxlength="255">
        </div>
        
        <div style="margin-bottom: 1rem;">
          <label for="edit_employee_number" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Employee Number (5 digits):</label>
          <input type="text" id="edit_employee_number" name="employee_number" class="editable-input" pattern="[0-9]{5}" maxlength="5" placeholder="10001">
        </div>
        
        <div style="margin-bottom: 1.5rem;">
          <label for="edit_position" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Position:</label>
          <input type="text" id="edit_position" name="position" class="editable-input" maxlength="255" placeholder="e.g., Manager, Staff">
        </div>
        
        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
          <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
          <button type="submit" class="btn-save">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function(){
      if($.fn.DataTable.isDataTable('#employeeProfilesDataTable')){
        $('#employeeProfilesDataTable').DataTable().destroy();
      }
      $('#employeeProfilesDataTable').DataTable({
        "pageLength": 25,
        "order": [[1, "asc"]], // Sort by name
        "language": {
          "emptyTable": "No employee records found",
          "zeroRecords": "No matching records found"
        },
        "retrieve": true,
        "destroy": true
      });
    });

    function editEmployee(rowId) {
      const row = document.getElementById(rowId);
      if (!row) return;
      
      const originalName = row.getAttribute('data-original-name');
      const nameCell = row.querySelector('.editable-name');
      const empNumCell = row.querySelector('.editable-empnum');
      const positionCell = row.querySelector('.editable-position');
      
      // Populate form
      document.getElementById('original_name').value = originalName;
      document.getElementById('edit_name').value = nameCell.textContent.trim();
      document.getElementById('edit_employee_number').value = empNumCell.textContent.trim() !== 'N/A' ? empNumCell.textContent.trim() : '';
      document.getElementById('edit_position').value = positionCell.textContent.trim() !== 'N/A' ? positionCell.textContent.trim() : '';
      
      // Show modal
      document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
      document.getElementById('editForm').reset();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('editModal');
      if (event.target === modal) {
        closeEditModal();
      }
    }
  </script>
</body>
</html>

