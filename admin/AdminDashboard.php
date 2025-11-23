<?php
// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$error = '';
$success = '';

// Get filter parameters
$docFromDate = isset($_GET['doc_from_date']) ? $_GET['doc_from_date'] : '';
$docToDate = isset($_GET['doc_to_date']) ? $_GET['doc_to_date'] : '';
$docSearch = isset($_GET['doc_search']) ? trim($_GET['doc_search']) : '';
$docStatus = isset($_GET['doc_status']) ? $_GET['doc_status'] : '';

$residentFromDate = isset($_GET['resident_from_date']) ? $_GET['resident_from_date'] : '';
$residentToDate = isset($_GET['resident_to_date']) ? $_GET['resident_to_date'] : '';
$residentSearch = isset($_GET['resident_search']) ? trim($_GET['resident_search']) : '';
$residentStatus = isset($_GET['resident_status']) ? $_GET['resident_status'] : '';

// Get all document requests
$allRequests = $admin->getRequests();

// Get all residents
$allResidents = $admin->getAllResidents();

// Count document request statuses
$docPending = $docApproved = $docRejected = 0;
foreach ($allRequests as $r) {
    switch ($r['status']) {
        case 'Pending': $docPending++; break;
        case 'Approved': $docApproved++; break;
        case 'Rejected': $docRejected++; break;
    }
}

// Count resident approval statuses
$residentPending = $residentApproved = $residentRejected = 0;
foreach ($allResidents as $r) {
    switch ($r['approval_status']) {
        case 'Pending': $residentPending++; break;
        case 'Approved': $residentApproved++; break;
        case 'Rejected': $residentRejected++; break;
    }
}

// Filter document requests
$filteredRequests = $allRequests;
if ($docFromDate && $docToDate) {
    $filteredRequests = array_filter($filteredRequests, function($r) use ($docFromDate, $docToDate) {
        $createdDate = date('Y-m-d', strtotime($r['created_at']));
        return $createdDate >= $docFromDate && $createdDate <= $docToDate;
    });
}
if ($docSearch) {
    $filteredRequests = array_filter($filteredRequests, function($r) use ($docSearch) {
        return stripos($r['full_name'], $docSearch) !== false;
    });
}
if ($docStatus) {
    $filteredRequests = array_filter($filteredRequests, function($r) use ($docStatus) {
        return $r['status'] === $docStatus;
    });
}
$filteredRequests = array_values($filteredRequests); // Re-index array

// Filter residents
$filteredResidents = $allResidents;
if ($residentFromDate && $residentToDate) {
    $filteredResidents = array_filter($filteredResidents, function($r) use ($residentFromDate, $residentToDate) {
        $createdDate = date('Y-m-d', strtotime($r['created_at']));
        return $createdDate >= $residentFromDate && $createdDate <= $residentToDate;
    });
}
if ($residentSearch) {
    $filteredResidents = array_filter($filteredResidents, function($r) use ($residentSearch) {
        return stripos($r['username'] ?? '', $residentSearch) !== false;
    });
}
if ($residentStatus) {
    $filteredResidents = array_filter($filteredResidents, function($r) use ($residentStatus) {
        return $r['approval_status'] === $residentStatus;
    });
}
$filteredResidents = array_values($filteredResidents); // Re-index array

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
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
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
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
      <header class="admin-top">
        <div>
          <p class="muted">Welcome back, Admin</p>
          <h1>Dashboard Overview</h1>
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

      <!-- Document Requests Section -->
      <section id="document-requests" class="dashboard-section">
        <h2>📄 Document Requests</h2>
        
        <!-- Document Requests Statistics -->
        <div class="stat-card highlight-card">
          <h3><?php echo count($allRequests); ?></h3>
          <p>Total Document Requests</p>
        </div>

        <section class="stats-grid">
          <div class="stat-card">
            <h3><?php echo $docPending; ?></h3>
            <p>Pending</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $docApproved; ?></h3>
            <p>Approved</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $docRejected; ?></h3>
            <p>Rejected</p>
          </div>
        </section>
        
        <!-- Filter Form for Document Requests -->
        <form method="GET" class="filter-form" id="docFilterForm">
          <input type="hidden" name="resident_from_date" value="<?php echo htmlspecialchars($residentFromDate); ?>">
          <input type="hidden" name="resident_to_date" value="<?php echo htmlspecialchars($residentToDate); ?>">
          <input type="hidden" name="resident_search" value="<?php echo htmlspecialchars($residentSearch); ?>">
          <input type="hidden" name="resident_status" value="<?php echo htmlspecialchars($residentStatus); ?>">
          <div class="filter-row">
            <div class="filter-group">
              <label>From Date:</label>
              <input type="date" name="doc_from_date" value="<?php echo htmlspecialchars($docFromDate); ?>" class="form-input">
            </div>
            <div class="filter-group">
              <label>To Date:</label>
              <input type="date" name="doc_to_date" value="<?php echo htmlspecialchars($docToDate); ?>" class="form-input">
            </div>
            <div class="filter-group">
              <label>Search by Name:</label>
              <input type="text" name="doc_search" value="<?php echo htmlspecialchars($docSearch); ?>" placeholder="Enter name..." class="form-input">
            </div>
            <div class="filter-group">
              <label>Status:</label>
              <select name="doc_status" class="form-input">
                <option value="">All</option>
                <option value="Pending" <?php echo $docStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="Approved" <?php echo $docStatus === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="Rejected" <?php echo $docStatus === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
              </select>
            </div>
            <div class="filter-group">
              <button type="submit" class="btn btn-primary">Filter</button>
              <a href="AdminDashboard.php" class="btn outline">Reset</a>
            </div>
          </div>
        </form>

        <!-- Document Requests Table -->
        <div class="table-container">
          <table id="documentRequestsTable" class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Resident Name</th>
                <th>Request Type</th>
                <th>Status</th>
                <th>Date Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($filteredRequests) > 0): ?>
                <?php foreach ($filteredRequests as $request): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                    <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['type']); ?></td>
                    <td>
                      <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                        <?php echo htmlspecialchars($request['status']); ?>
                      </span>
                    </td>
                    <td><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td>No data</td>
                  <td>No data</td>
                  <td>No data</td>
                  <td>No data</td>
                  <td>No data</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Resident Approvals Section -->
      <section id="resident-approvals" class="dashboard-section">
        <h2>👥 Resident Account Approvals</h2>
        
        <!-- Resident Approvals Statistics -->
        <div class="stat-card highlight-card">
          <h3><?php echo count($allResidents); ?></h3>
          <p>Total Resident Accounts</p>
        </div>

        <section class="stats-grid">
          <div class="stat-card">
            <h3><?php echo $residentPending; ?></h3>
            <p>Pending</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $residentApproved; ?></h3>
            <p>Approved</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $residentRejected; ?></h3>
            <p>Rejected</p>
          </div>
        </section>
        
        <!-- Filter Form for Resident Approvals -->
        <form method="GET" class="filter-form" id="residentFilterForm">
          <input type="hidden" name="doc_from_date" value="<?php echo htmlspecialchars($docFromDate); ?>">
          <input type="hidden" name="doc_to_date" value="<?php echo htmlspecialchars($docToDate); ?>">
          <input type="hidden" name="doc_search" value="<?php echo htmlspecialchars($docSearch); ?>">
          <input type="hidden" name="doc_status" value="<?php echo htmlspecialchars($docStatus); ?>">
          <div class="filter-row">
            <div class="filter-group">
              <label>From Date:</label>
              <input type="date" name="resident_from_date" value="<?php echo htmlspecialchars($residentFromDate); ?>" class="form-input">
            </div>
            <div class="filter-group">
              <label>To Date:</label>
              <input type="date" name="resident_to_date" value="<?php echo htmlspecialchars($residentToDate); ?>" class="form-input">
            </div>
            <div class="filter-group">
              <label>Search by Username:</label>
              <input type="text" name="resident_search" value="<?php echo htmlspecialchars($residentSearch); ?>" placeholder="Enter username..." class="form-input">
            </div>
            <div class="filter-group">
              <label>Status:</label>
              <select name="resident_status" class="form-input">
                <option value="">All</option>
                <option value="Pending" <?php echo $residentStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="Approved" <?php echo $residentStatus === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="Rejected" <?php echo $residentStatus === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
              </select>
            </div>
            <div class="filter-group">
              <button type="submit" class="btn btn-primary">Filter</button>
              <a href="AdminDashboard.php" class="btn outline">Reset</a>
            </div>
          </div>
        </form>

        <!-- Resident Approvals Table -->
        <div class="table-container">
          <table id="residentApprovalsTable" class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Phone Number</th>
                <th>Status</th>
                <th>Date Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($filteredResidents) > 0): ?>
                <?php foreach ($filteredResidents as $resident): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($resident['id']); ?></td>
                    <td><?php echo htmlspecialchars($resident['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($resident['username'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($resident['phone_number'] ?? 'N/A'); ?></td>
                    <td>
                      <span class="status-badge status-<?php echo strtolower($resident['approval_status']); ?>">
                        <?php echo htmlspecialchars($resident['approval_status']); ?>
                      </span>
                    </td>
                    <td><?php echo date('M j, Y g:i A', strtotime($resident['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td>No data</td>
                  <td>No data</td>
                  <td>No data</td>
                  <td>No data</td>
                  <td>No data</td>
                  <td>No data</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
  <script>
    $(document).ready(function() {
      // Check if we have filter parameters to determine which section to scroll to
      var urlParams = new URLSearchParams(window.location.search);
      var hasDocFilters = urlParams.has('doc_from_date') || urlParams.has('doc_to_date') || urlParams.has('doc_search') || urlParams.has('doc_status');
      var hasResidentFilters = urlParams.has('resident_from_date') || urlParams.has('resident_to_date') || urlParams.has('resident_search') || urlParams.has('resident_status');
      
      // Scroll to the appropriate section after page load if filters are applied
      setTimeout(function() {
        if (hasDocFilters) {
          var docSection = document.querySelector('.dashboard-section:first-of-type');
          if (docSection) {
            docSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Add a small offset to account for sticky header
            window.scrollBy(0, -20);
          }
        } else if (hasResidentFilters) {
          var residentSection = document.querySelectorAll('.dashboard-section')[1];
          if (residentSection) {
            residentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // Add a small offset to account for sticky header
            window.scrollBy(0, -20);
          }
        }
      }, 100);

      // Initialize Document Requests Table
      var docTable = $('#documentRequestsTable');
      if (docTable.length && docTable.find('thead th').length === 5) {
        docTable.DataTable({
          pageLength: 10,
          order: [[4, 'desc']], // Sort by date descending
          searching: false, // Disable DataTables search since we have custom filter
          columnDefs: [
            { orderable: true, targets: [0, 1, 2, 3, 4] }
          ]
        });
      }

      // Initialize Resident Approvals Table
      var residentTable = $('#residentApprovalsTable');
      if (residentTable.length && residentTable.find('thead th').length === 6) {
        residentTable.DataTable({
          pageLength: 10,
          order: [[5, 'desc']], // Sort by date descending
          searching: false, // Disable DataTables search since we have custom filter
          columnDefs: [
            { orderable: true, targets: [0, 1, 2, 3, 4, 5] }
          ]
        });
      }

      // Handle form submissions to add anchor and prevent scroll to top
      $('#docFilterForm, #residentFilterForm').on('submit', function(e) {
        var formId = $(this).attr('id');
        var anchor = formId === 'docFilterForm' ? '#document-requests' : '#resident-approvals';
        
        // Store scroll position before submit
        sessionStorage.setItem('scrollTo', anchor);
        
        // Add anchor to form action or use a hidden field
        if (!window.location.href.includes(anchor)) {
          // The form will submit normally, but we'll handle scroll after page load
        }
      });
    });

    // After page load, check if we need to scroll to a specific section
    window.addEventListener('load', function() {
      var scrollTo = sessionStorage.getItem('scrollTo');
      if (scrollTo) {
        setTimeout(function() {
          var element = document.querySelector(scrollTo);
          if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.scrollBy(0, -20);
          }
          sessionStorage.removeItem('scrollTo');
        }, 200);
      }
    });
  </script>
</body>
</html>
