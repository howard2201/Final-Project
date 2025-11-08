<?php
// Check admin authentication
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$requests = $admin->getRequests();
$error = '';
$success = '';

if (isset($_POST['update_status'])) {
  try {
    $admin->updateRequestStatus($_POST['request_id'], $_POST['status']);
    $_SESSION['success_message'] = "Request status updated successfully!";
    header("Location: AdminDashboard.php");
    exit;
  } catch (Exception $e) {
    $error = "Failed to update request status. Please try again.";
  }
}

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
  <link rel="stylesheet" href="../css/style.css">
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="brand">Admin</div>
      <nav>
        <div class="sidebar-links">
          <a href="AdminDashboard.php">Dashboard</a>
          <a href="#requests">Requests</a>
        </div>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-top">
        <h1>Requests</h1>
        <div class="admin-actions">
          <a href="../logout.php" class="btn outline small">Logout</a>
        </div>
      </header>

      <?php if (!empty($error)): ?>
        <div style="background: #ffe6e6; border-left: 4px solid #dc3545; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <strong style="color: #dc3545;">⚠️ Error</strong>
          <p style="color: #721c24; margin: 5px 0 0 0;"><?php echo htmlspecialchars($error); ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
          <strong style="color: #28a745;">✓ Success</strong>
          <p style="color: #155724; margin: 5px 0 0 0;"><?php echo htmlspecialchars($success); ?></p>
        </div>
      <?php endif; ?>

      <section id="requestsTable">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Type</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if ($requests) {
              foreach ($requests as $r) {
                $id = htmlspecialchars($r['id'], ENT_QUOTES, 'UTF-8');
                $fullName = htmlspecialchars($r['full_name'], ENT_QUOTES, 'UTF-8');
                $type = htmlspecialchars($r['type'], ENT_QUOTES, 'UTF-8');
                $status = htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8');

                echo "<tr>
        <td>{$id}</td>
        <td>{$fullName}</td>
        <td>{$type}</td>
        <td>{$status}</td>
        <td>
        <form method='POST' style='display:inline'>
            <input type='hidden' name='request_id' value='{$id}'>
            <button name='status' value='Approved' class='btn'>Approve</button>
            <button name='status' value='Rejected' class='btn outline'>Reject</button>
        </form>
        </td>
        </tr>";
              }
            } else {
              echo "<tr><td colspan='5' class='muted'>No requests found.</td></tr>";
            }
            ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>

</html>