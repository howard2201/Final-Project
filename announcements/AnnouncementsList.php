<?php
session_start();
require_once "../config/Database.php";

// Check if user is admin
$isAdmin = isset($_SESSION['admin_id']);

// Get database connection
$pdo = Database::getInstance()->getConnection();
$error = '';
$success = '';

// Handle announcement creation (admin only)
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_announcement'])) {
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);

    if (empty($title)) {
        $error = "Please enter an announcement title.";
    } elseif (empty($body)) {
        $error = "Please enter announcement content.";
    } else {
        try {
            $stmt = $pdo->prepare("CALL createAnnouncement(?, ?)");
            $stmt->execute([$title, $body]);
            $_SESSION['success_message'] = "✓ Announcement created successfully!";
            header("Location: AnnouncementsList.php");
            exit;
        } catch (PDOException $e) {
            $error = "Failed to create announcement. Please try again.";
            error_log("Create announcement error: " . $e->getMessage());
        }
    }
}

// Handle announcement deletion (admin only)
if ($isAdmin && isset($_POST['delete_announcement'])) {
    $announcement_id = intval($_POST['announcement_id']);
    try {
        $stmt = $pdo->prepare("CALL deleteAnnouncement(?)");
        $stmt->execute([$announcement_id]);
        $_SESSION['success_message'] = "Announcement deleted successfully.";
        header("Location: AnnouncementsList.php");
        exit;
    } catch (PDOException $e) {
        $error = "Failed to delete announcement. Please try again.";
        error_log("Delete announcement error: " . $e->getMessage());
    }
}

// Get all announcements
try {
    $stmt = $pdo->prepare("CALL getAllAnnouncements()");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $announcements = [];
    error_log("Get announcements error: " . $e->getMessage());
}

// Check for success message
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Announcements — Smart Barangay System</title>
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/alerts.js"></script>
</head>
<body <?php if ($success) echo 'data-success-message="' . htmlspecialchars($success) . '"'; ?>>

  <?php if ($isAdmin): ?>
    <!-- Admin Layout with Sidebar -->
    <div class="admin-layout">
      <aside class="sidebar">
        <div class="brand">Admin</div>
        <nav>
          <div class="sidebar-links">
            <a href="../admin/AdminDashboard.php">Dashboard</a>
            <div class="dropdown">
              <button class="dropbtn">Requests ▾</button>
              <div class="dropdown-content">
                <a href="../admin/AdminDashboard.php?type=registration">Registration Requests</a>
                <a href="../admin/AdminDashboard.php?type=document">Document Requests</a>
                <a href="../admin/AdminDashboard.php">All Requests</a>
              </div>
            </div>
            <a href="../admin/ResidentApprovals.php">Resident Approvals</a>
            <a href="AnnouncementsList.php" class="active">Announcements</a>
          </div>
        </nav>
      </aside>

      <main class="admin-main">
        <header class="admin-header">
          <h1>📢 Announcements</h1>
          <div class="admin-user">
            <span>👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
            <a href="../logout.php" class="btn small">Logout</a>
          </div>
        </header>

        <div class="admin-content">
  <?php else: ?>
    <!-- Regular User Layout -->
    <?php include '../includes/headerinner.php'; ?>
    <main class="container">
  <?php endif; ?>
    <?php if (!$isAdmin): ?>
      <div class="header-inner">
        <h2>📢 Barangay Announcements</h2>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Admin Create Announcement Form -->
    <?php if ($isAdmin): ?>
      <section class="announcement-create-section">
        <h3>Create New Announcement</h3>
        <form method="POST" class="announcement-form">
          <div class="form-group">
            <label for="title">Announcement Title *</label>
            <input
              type="text"
              id="title"
              name="title"
              class="form-input"
              placeholder="Enter announcement title..."
              required
              maxlength="255"
            >
          </div>

          <div class="form-group">
            <label for="body">Announcement Content *</label>
            <textarea
              id="body"
              name="body"
              class="form-textarea"
              rows="6"
              placeholder="Enter announcement details..."
              required
            ></textarea>
          </div>

          <button type="submit" name="create_announcement" class="btn btn-primary">
            ✓ Create Announcement
          </button>
        </form>
      </section>
    <?php endif; ?>

    <!-- Announcements List -->
    <section class="announcements-public-section">
      <h3>All Announcements (<?php echo count($announcements); ?>)</h3>

      <?php if (count($announcements) > 0): ?>
        <div class="announcements-list">
          <?php foreach ($announcements as $announcement): ?>
            <div class="announcement-card">
              <div class="announcement-header">
                <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                <span class="announcement-date">
                  📅 <?php echo date('F j, Y \a\t g:i A', strtotime($announcement['created_at'])); ?>
                </span>
              </div>
              <div class="announcement-body">
                <p><?php echo nl2br(htmlspecialchars($announcement['body'])); ?></p>
              </div>

              <?php if ($isAdmin): ?>
                <div class="announcement-actions">
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                    <button
                      type="submit"
                      name="delete_announcement"
                      class="btn small btn-danger"
                      onclick="return confirm('Are you sure you want to delete this announcement?')"
                    >
                      🗑️ Delete
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <p>📢 No announcements yet. <?php echo $isAdmin ? 'Create your first announcement above!' : 'Check back later for updates.'; ?></p>
        </div>
      <?php endif; ?>
    </section>

  <?php if ($isAdmin): ?>
        </div> <!-- .admin-content -->
      </main> <!-- .admin-main -->
    </div> <!-- .admin-layout -->
  <?php else: ?>
    </main> <!-- .container -->
    <?php include '../includes/footer.php'; ?>
  <?php endif; ?>

  <script src="../js/appear.js"></script>

  <style>
    /* Admin content wrapper */
    .admin-content {
      padding: 2rem;
      overflow-y: auto;
      max-height: calc(100vh - 80px);
    }

    /* Public view container - centered and same width as admin */
    body:not(.admin-layout) .container {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem;
    }

    .header-inner {
      text-align: center;
      margin-bottom: 2rem;
    }

    .header-inner h2 {
      font-size: 2rem;
      color: #111;
    }

    .announcement-create-section {
      background: var(--card);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      margin-bottom: 2rem;
    }

    .announcement-create-section h3 {
      margin: 0 0 1.5rem 0;
      color: #111;
    }

    .announcement-form {
      max-width: 100%;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #333;
    }

    .form-input,
    .form-textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 1rem;
      font-family: inherit;
      transition: border-color 0.2s;
    }

    .form-input:focus,
    .form-textarea:focus {
      outline: none;
      border-color: var(--primary);
    }

    .form-textarea {
      resize: vertical;
      min-height: 120px;
    }

    .announcements-public-section {
      margin-top: 2rem;
      margin-bottom: 2rem;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }

    .announcements-public-section h3 {
      margin-bottom: 1.5rem;
      color: #111;
      position: sticky;
      top: 0;
      background: white;
      padding: 1rem 0;
      z-index: 10;
    }

    .announcements-list {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
      padding-bottom: 2rem;
      width: 100%;
    }

    .announcement-card {
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      overflow: hidden;
      border-left: 4px solid var(--primary);
      min-height: 200px;
      display: flex;
      flex-direction: column;
    }

    .announcement-header {
      background: #f8f9fa;
      padding: 1.5rem;
      border-bottom: 1px solid #e9ecef;
    }

    .announcement-header h3 {
      margin: 0 0 0.5rem 0;
      color: #111;
      font-size: 1.3rem;
    }

    .announcement-date {
      color: #6c757d;
      font-size: 0.9rem;
    }

    .announcement-body {
      padding: 2rem;
      flex: 1;
      min-height: 120px;
    }

    .announcement-body p {
      margin: 0;
      color: #495057;
      line-height: 1.8;
      font-size: 1rem;
    }

    .announcement-actions {
      padding: 1rem 1.5rem;
      background: #f8f9fa;
      border-top: 1px solid #e9ecef;
      display: flex;
      justify-content: flex-end;
    }

    .btn-danger {
      background: #dc3545;
      color: white;
    }

    .btn-danger:hover {
      background: #c82333;
    }

    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #6c757d;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .empty-state p {
      margin: 0;
      font-size: 1.1rem;
    }

    /* Active sidebar link */
    .sidebar-links a.active {
      background: rgba(255, 255, 255, 0.2);
      border-left: 4px solid white;
      padding-left: calc(1rem - 4px);
    }

    /* Custom scrollbar for admin content */
    .admin-content::-webkit-scrollbar {
      width: 8px;
    }

    .admin-content::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .admin-content::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
    }

    .admin-content::-webkit-scrollbar-thumb:hover {
      background: #555;
    }

    @media (max-width: 800px) {
      .announcement-create-section {
        padding: 1.5rem;
      }

      .announcement-header h3 {
        font-size: 1.1rem;
      }

      .admin-content {
        padding: 1rem;
      }
    }
  </style>
</body>
</html>
