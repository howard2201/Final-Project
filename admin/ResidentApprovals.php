<?php
// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$pendingResidents = $admin->getPendingResidents();
$allResidents = $admin->getAllResidents();
$error = '';
$success = '';

// Handle approval/rejection
if (isset($_POST['update_approval'])) {
  try {
    $status = $_POST['approval_status'];
    $admin->updateResidentApprovalStatus($_POST['resident_id'], $status);

    if ($status === 'Approved') {
      $_SESSION['success_message'] = "✓ Resident account has been approved successfully! The resident can now log in.";
    } elseif ($status === 'Rejected') {
      $_SESSION['success_message'] = "Resident account has been rejected.";
    } else {
      $_SESSION['success_message'] = "Resident account status updated successfully!";
    }

    header("Location: ResidentApprovals.php");
    exit;
  } catch (Exception $e) {
    $error = "Failed to update resident approval status. Please try again.";
  }
}

// Get success message if any
if (isset($_SESSION['success_message'])) {
  $success = $_SESSION['success_message'];
  unset($_SESSION['success_message']);
}

// Count statuses
$pending = 0;
$approved = 0;
$rejected = 0;
foreach ($allResidents as $r) {
  switch ($r['approval_status']) {
    case 'Pending': $pending++; break;
    case 'Approved': $approved++; break;
    case 'Rejected': $rejected++; break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resident Approvals — Admin</title>
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
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
          <a href="../logout.php" class="btn outline small">Logout</a>
        </div>
      </nav>
    </aside>
        
    <main class="main-content">
      <h1>Resident Account Approvals</h1>

      <?php if(!empty($success)): ?>
        <div class="alert-success">
          <strong>✓ Success</strong>
          <p><?php echo htmlspecialchars($success); ?></p>
        </div>
      <?php endif; ?>

      <?php if(!empty($error)): ?>
        <div class="alert-error">
          <strong>⚠️ Error</strong>
          <p><?php echo htmlspecialchars($error); ?></p>
        </div>
      <?php endif; ?>

      <!-- Statistics -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3><?php echo $pending; ?></h3>
          <p>Pending Approval</p>
        </div>
        <div class="stat-card">
          <h3><?php echo $approved; ?></h3>
          <p>Approved</p>
        </div>
        <div class="stat-card">
          <h3><?php echo $rejected; ?></h3>
          <p>Rejected</p>
        </div>
      </div>
        
      <!-- Pending Residents Table -->
      <section>
        <h2>Pending Resident Registrations</h2>
        <div class="search-container">
          <input type="text" id="pendingSearch" class="search-input" placeholder="🔍 Search by name or email..." onkeyup="filterTable('pendingResidentsTable', 'pendingSearch')">
        </div>
        <table id="pendingResidentsTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Registration Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (count($pendingResidents) > 0) {
              foreach ($pendingResidents as $resident) {
                $id = htmlspecialchars($resident['id'], ENT_QUOTES, 'UTF-8');
                $fullName = htmlspecialchars($resident['full_name'], ENT_QUOTES, 'UTF-8');
                $email = htmlspecialchars($resident['email'], ENT_QUOTES, 'UTF-8');
                $createdAt = htmlspecialchars($resident['created_at'], ENT_QUOTES, 'UTF-8');
                $status = htmlspecialchars($resident['approval_status'], ENT_QUOTES, 'UTF-8');

                echo "<tr>
                  <td>{$id}</td>
                  <td>{$fullName}</td>
                  <td>{$email}</td>
                  <td>{$createdAt}</td>
                  <td><span class='status-badge status-pending'>{$status}</span></td>
                  <td>
                    <button onclick='viewFile({$id}, \"id\", \"{$fullName}\")' class='btn small outline'>📄 ID</button>
                    <button onclick='viewFile({$id}, \"proof\", \"{$fullName}\")' class='btn small outline'>📄 Proof</button>
                    <form method='POST' class='inline-form'>
                      <input type='hidden' name='resident_id' value='{$id}'>
                      <button type='submit' name='update_approval' value='1' onclick=\"return confirm('Approve this resident account?')\" class='btn small btn-approve'>
                        <input type='hidden' name='approval_status' value='Approved'>
                        ✓ Approve
                      </button>
                    </form>
                    <form method='POST' class='inline-form'>
                      <input type='hidden' name='resident_id' value='{$id}'>
                      <button type='submit' name='update_approval' value='1' onclick=\"return confirm('Reject this resident account?')\" class='btn small outline'>
                        <input type='hidden' name='approval_status' value='Rejected'>
                        ✗ Reject
                      </button>
                    </form>
                  </td>
                </tr>";
              }
            } else {
              echo "<tr><td colspan='6' class='muted'>No pending resident registrations.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </section>

      <!-- All Residents Table -->
      <section class="chart-section">
        <h2>All Residents</h2>
        <div class="search-container">
          <input type="text" id="allResidentsSearch" class="search-input" placeholder="🔍 Search by name or email..." onkeyup="filterTable('allResidentsTable', 'allResidentsSearch')">
        </div>
        <table id="allResidentsTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Registration Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (count($allResidents) > 0) {
              foreach ($allResidents as $resident) {
                $id = htmlspecialchars($resident['id'], ENT_QUOTES, 'UTF-8');
                $fullName = htmlspecialchars($resident['full_name'], ENT_QUOTES, 'UTF-8');
                $email = htmlspecialchars($resident['email'], ENT_QUOTES, 'UTF-8');
                $createdAt = htmlspecialchars($resident['created_at'], ENT_QUOTES, 'UTF-8');
                $status = htmlspecialchars($resident['approval_status'], ENT_QUOTES, 'UTF-8');
                
                $statusClass = 'status-pending';
                if ($status === 'Approved') $statusClass = 'status-approved';
                if ($status === 'Rejected') $statusClass = 'status-rejected';

                echo "<tr>
                  <td>{$id}</td>
                  <td>{$fullName}</td>
                  <td>{$email}</td>
                  <td>{$createdAt}</td>
                  <td><span class='status-badge {$statusClass}'>{$status}</span></td>
                </tr>";
              }
            } else {
              echo "<tr><td colspan='5' class='muted'>No residents found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </section>

    </main>
  </div>


  <!-- File Viewer Modal -->
  <div id="fileViewerModal" class="file-modal">
    <div class="file-modal-content">
      <div class="file-modal-header">
        <h3 id="fileModalTitle">View Document</h3>
        <div class="file-modal-controls">
          <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">🔍+</button>
          <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">🔍−</button>
          <button class="zoom-btn" onclick="resetZoom()" title="Reset View">↺</button>
          <button class="file-modal-close" onclick="closeFileModal()">&times;</button>
        </div>
      </div>
      <div class="file-modal-body" id="imageViewerContainer">
        <img id="fileViewerImage" src="" alt="Document" />
      </div>
    </div>
  </div>

  <script>
    // Suppress DataTables warnings (show in console instead of alert)
    $.fn.dataTable.ext.errMode = 'none';

    // Initialize DataTables
    $(document).ready(function() {
      // Small delay to ensure DOM is fully ready
      setTimeout(function() {
        // Destroy existing DataTables if they exist
        if ($.fn.DataTable.isDataTable('#pendingResidentsTable')) {
          $('#pendingResidentsTable').DataTable().destroy();
        }
        if ($.fn.DataTable.isDataTable('#allResidentsTable')) {
          $('#allResidentsTable').DataTable().destroy();
        }

        // Initialize DataTables with error handling
        try {
          $('#pendingResidentsTable').DataTable({
            "pageLength": 10,
            "order": [[3, "desc"]],
            "searching": false,  // Disable default search since we're using custom
            "columnDefs": [
              { "orderable": false, "targets": 5 } // Disable sorting on Action column
            ],
            "language": {
              "emptyTable": "No pending residents",
              "zeroRecords": "No matching residents found"
            },
            "retrieve": true, // Allow re-initialization
            "destroy": true // Destroy existing instance before creating new one
          });

          $('#allResidentsTable').DataTable({
            "pageLength": 10,
            "order": [[3, "desc"]],
            "searching": false,  // Disable default search since we're using custom
            "columnDefs": [
              { "orderable": false, "targets": 5 } // Disable sorting on Action column
            ],
            "language": {
              "emptyTable": "No residents found",
              "zeroRecords": "No matching residents found"
            },
            "retrieve": true, // Allow re-initialization
            "destroy": true // Destroy existing instance before creating new one
          });
        } catch (e) {
          console.error('DataTable initialization error:', e);
        }
      }, 100);
    });

    // Live search function
    function filterTable(tableId, searchInputId) {
      const input = document.getElementById(searchInputId);
      const filter = input.value.toLowerCase();
      const table = document.getElementById(tableId);
      const tbody = table.getElementsByTagName('tbody')[0];
      const rows = tbody.getElementsByTagName('tr');

      let visibleCount = 0;

      for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');

        if (cells.length > 0) {
          // Get name (column 1) and email (column 2)
          const name = cells[1] ? cells[1].textContent.toLowerCase() : '';
          const email = cells[2] ? cells[2].textContent.toLowerCase() : '';

          // Check if filter matches name or email
          if (name.includes(filter) || email.includes(filter)) {
            row.style.display = '';
            visibleCount++;
          } else {
            row.style.display = 'none';
          }
        }
      }

      // Update "no results" message if needed
      updateNoResultsMessage(tbody, visibleCount, filter);
    }

    function updateNoResultsMessage(tbody, visibleCount, filter) {
      // Remove existing "no results" row if any
      const existingNoResults = tbody.querySelector('.no-results-row');
      if (existingNoResults) {
        existingNoResults.remove();
      }

      // Add "no results" message if no rows are visible and filter is not empty
      if (visibleCount === 0 && filter !== '') {
        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.innerHTML = '<td colspan="6" class="muted" style="text-align: center; padding: 20px;">No results found for "' + filter + '"</td>';
        tbody.appendChild(noResultsRow);
      }
    }

    // File viewer functions
    let currentZoom = 1;
    let isDragging = false;
    let startX, startY, scrollLeft, scrollTop;

    function viewFile(residentId, fileType, residentName) {
      const modal = document.getElementById('fileViewerModal');
      const img = document.getElementById('fileViewerImage');
      const title = document.getElementById('fileModalTitle');

      const fileTypeLabel = fileType === 'id' ? 'ID Document' : 'Proof of Residency';
      title.textContent = `${residentName} - ${fileTypeLabel}`;

      img.src = `ViewResidentFiles.php?id=${residentId}&type=${fileType}`;
      modal.style.display = 'block';
      resetZoom();
    }

    function closeFileModal() {
      const modal = document.getElementById('fileViewerModal');
      const img = document.getElementById('fileViewerImage');

      modal.style.display = 'none';
      img.src = '';
      currentZoom = 1;
    }

    function zoomIn() {
      currentZoom += 0.25;
      if (currentZoom > 5) currentZoom = 5; // Max zoom 500%
      applyZoom();
    }

    function zoomOut() {
      currentZoom -= 0.25;
      if (currentZoom < 0.5) currentZoom = 0.5; // Min zoom 50%
      applyZoom();
    }

    function resetZoom() {
      currentZoom = 1;
      const container = document.getElementById('imageViewerContainer');
      const img = document.getElementById('fileViewerImage');

      img.style.transform = 'scale(1)';
      img.style.cursor = 'default';
      container.scrollLeft = 0;
      container.scrollTop = 0;
    }

    function applyZoom() {
      const img = document.getElementById('fileViewerImage');
      img.style.transform = `scale(${currentZoom})`;
      img.style.cursor = currentZoom > 1 ? 'grab' : 'default';
    }

    // Enable dragging when zoomed in
    const container = document.getElementById('imageViewerContainer');
    const img = document.getElementById('fileViewerImage');

    img.addEventListener('mousedown', (e) => {
      if (currentZoom > 1) {
        isDragging = true;
        img.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        startY = e.pageY - container.offsetTop;
        scrollLeft = container.scrollLeft;
        scrollTop = container.scrollTop;
      }
    });

    container.addEventListener('mouseleave', () => {
      isDragging = false;
      if (currentZoom > 1) img.style.cursor = 'grab';
    });

    container.addEventListener('mouseup', () => {
      isDragging = false;
      if (currentZoom > 1) img.style.cursor = 'grab';
    });

    container.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      e.preventDefault();
      const x = e.pageX - container.offsetLeft;
      const y = e.pageY - container.offsetTop;
      const walkX = (x - startX) * 2;
      const walkY = (y - startY) * 2;
      container.scrollLeft = scrollLeft - walkX;
      container.scrollTop = scrollTop - walkY;
    });

    // Mouse wheel zoom
    container.addEventListener('wheel', (e) => {
      e.preventDefault();
      if (e.deltaY < 0) {
        zoomIn();
      } else {
        zoomOut();
      }
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('fileViewerModal');
      if (event.target === modal) {
        closeFileModal();
      }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        closeFileModal();
      }
    });
  </script>
</body>
</html>

