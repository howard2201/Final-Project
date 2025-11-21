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

// Get filter dates
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Get announcements (filtered if dates are set)
try {
    if ($from_date && $to_date) {
        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
        $stmt->execute([$from_date, $to_date]);
    } else {
        $stmt = $pdo->prepare("CALL getAllAnnouncements()");
        $stmt->execute();
    }
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
  <link rel="stylesheet" href="../css/announcements.css">
  <link rel="stylesheet" href="../css/admin.css">
  <script src="../js/alerts.js"></script>
</head>
<body <?php if ($success) echo 'data-success-message="' . htmlspecialchars($success) . '"'; ?>>

<?php if ($isAdmin): ?>
  <!-- Admin Layout with Sidebar -->
  <div class="admin-layout">
    <?php include __DIR__ . '/../admin/sidebar.php'; ?>

    <main class="admin-main">
      <header class="admin-header">
        <h1>📢 Announcements</h1>
        <div class="admin-user">
          <span>👤 <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
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

<!-- Filter Announcements by Date -->
<section class="announcement-filter-section">
  <form method="GET" class="announcement-filter-form">
    <div class="form-group">
      <label for="from_date">From Date:</label>
      <input type="date" id="from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="form-input">
    </div>

    <div class="form-group">
      <label for="to_date">To Date:</label>
      <input type="date" id="to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="form-input">
    </div>

    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="AnnouncementsList.php" class="btn outline">Reset</a>
  </form>
</section>

<!-- Announcements List -->
<section class="announcements-public-section">
  <h3>All Announcements (<?php echo count($announcements); ?>)</h3>

  <?php if (count($announcements) > 0): ?>
    <div class="announcements-list">
      <?php foreach ($announcements as $announcement): ?>
        <div class="announcement-card">
          <div class="announcement-header">
            <!-- Profile photo like Facebook -->
            <img src="../assets/img/logo.png" alt="logo">
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
</body>
</html>
