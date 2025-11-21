<?php
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$requests = $admin->getRequests();
$error = '';
$success = '';

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

    header("Location: DocumentRequests.php");
    exit;
  } catch (Exception $e) {
    $error = "Failed to update request status. Please try again.";
  }
}

if (isset($_SESSION['success_message'])) {
  $success = $_SESSION['success_message'];
  unset($_SESSION['success_message']);
}

$pending = $approved = $rejected = 0;
foreach ($requests as $r) {
  switch ($r['status']) {
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
  <title>Document Requests — Admin</title>
  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
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
          <p class="muted">Document Services</p>
          <h1>Resident Requests</h1>
        </div>
      </header>

      <div class="stat-card highlight-card">
        <h3><?php echo count($requests); ?></h3>
        <p>Total Requests</p>
      </div>

      <?php if (!empty($success)): ?>
        <div class="success-message">
          <strong>✓ Success</strong>
          <p><?php echo htmlspecialchars($success); ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="alert-error">
          <strong>⚠️ Error</strong>
          <p><?php echo htmlspecialchars($error); ?></p>
        </div>
      <?php endif; ?>

      <section class="stats-grid">
        <div class="stat-card">
          <h3><?php echo $pending; ?></h3>
          <p>Pending</p>
        </div>
        <div class="stat-card">
          <h3><?php echo $approved; ?></h3>
          <p>Approved</p>
        </div>
        <div class="stat-card">
          <h3><?php echo $rejected; ?></h3>
          <p>Rejected</p>
        </div>
      </section>

      <section id="requestsTable">
        <div class="search-container">
          <input type="text" id="requestSearch" class="search-input" placeholder="🔍 Search by resident name...">
        </div>
        <table id="requestsDataTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Details</th>
              <th>Status</th>
              <th>Documents</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($requests) {
              foreach ($requests as $r) {
                $id = htmlspecialchars($r['id'], ENT_QUOTES, 'UTF-8');
                $fullName = htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8');
                $fullNameAttr = htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8');
                $status = htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8');
                $type = htmlspecialchars($r['type'] ?? 'Document Request', ENT_QUOTES, 'UTF-8');
                $details = htmlspecialchars($r['details'] ?? 'No details provided.', ENT_QUOTES, 'UTF-8');
                $idFile = !empty($r['id_file']);
                $residencyFile = !empty($r['residency_file']);

                echo "<tr>
                  <td>{$id}</td>
                  <td>{$fullName}</td>
                  <td>{$type}</td>
                  <td class='request-details'>".nl2br($details)."</td>
                  <td><span class='status-badge status-".strtolower($status)."'>${status}</span></td>
                  <td class='documents-cell'>
                    " . ($idFile ? "<button type='button' class='btn small outline' onclick=\"viewRequestFile({$id}, 'id', '{$fullNameAttr}')\">📄 ID</button>" : "<span class='muted'>No ID File</span>") . "
                  </td>
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
              echo "<tr><td colspan='7' class='muted'>No requests found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </section>

    </main>
  </div>

  <div id="requestFileModal" class="file-modal">
    <div class="file-modal-content">
      <div class="file-modal-header">
        <h3 id="requestFileTitle">View Document</h3>
        <div class="file-modal-controls">
          <button class="file-modal-close" onclick="closeRequestFileModal()">&times;</button>
        </div>
      </div>
      <div class="file-modal-body">
        <iframe id="requestFileFrame" title="Request Document Viewer"></iframe>
      </div>
    </div>
  </div>

  <script>
    $.fn.dataTable.ext.errMode = 'none';
    $(document).ready(function() {
      setTimeout(function() {
        if ($.fn.DataTable.isDataTable('#requestsDataTable')) {
          $('#requestsDataTable').DataTable().destroy();
        }

        try {
          const table = $('#requestsDataTable').DataTable({
            "pageLength": 10,
            "columnDefs": [
              { "orderable": false, "targets": [5, 6] }
            ],
            "order": [[0, "desc"]],
            "language": {
              "emptyTable": "No requests found",
              "zeroRecords": "No matching requests found"
            },
            "retrieve": true,
            "destroy": true
          });

          $('#requestSearch').on('keyup', function() {
            table.column(1).search(this.value).draw();
          });
        } catch (e) {
          console.error('DataTable initialization error:', e);
        }
      }, 100);
    });

    function viewRequestFile(requestId, fileType, residentName) {
      const modal = document.getElementById('requestFileModal');
      const frame = document.getElementById('requestFileFrame');
      const title = document.getElementById('requestFileTitle');
      const label = fileType === 'id' ? 'Valid ID' : 'Residency Proof';

      title.textContent = `${residentName} — ${label}`;
      frame.src = `ViewRequestFile.php?id=${requestId}&type=${fileType}`;
      modal.style.display = 'block';
    }

    function closeRequestFileModal() {
      const modal = document.getElementById('requestFileModal');
      const frame = document.getElementById('requestFileFrame');
      frame.src = '';
      modal.style.display = 'none';
    }

    window.addEventListener('click', (event) => {
      const modal = document.getElementById('requestFileModal');
      if (event.target === modal) {
        closeRequestFileModal();
      }
    });
  </script>
</body>
</html>

