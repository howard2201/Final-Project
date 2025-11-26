<?php
// Shared admin sidebar include
// Detect if we're being included from chatting folder or admin folder
$callerFile = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'] ?? '';
$isFromChatting = strpos($callerFile, DIRECTORY_SEPARATOR . 'chatting' . DIRECTORY_SEPARATOR) !== false;
$isFromAnnouncements = strpos($callerFile, DIRECTORY_SEPARATOR . 'announcements' . DIRECTORY_SEPARATOR) !== false;

// Set base path based on location
if ($isFromChatting) {
    // From chatting folder, go up two levels then into admin
    $basePath = '../../admin/';
    $announcementsPath = '../../announcements/';
    $logoutPath = '../../logoutadmin.php';
    $messagesPath = 'Messages.php';
} elseif ($isFromAnnouncements) {
    // From announcements folder, go up one level into admin
    $basePath = '../admin/';
    $announcementsPath = './';
    $logoutPath = '../logoutadmin.php';
    $messagesPath = '../chatting/admin/Messages.php';
} else {
    // From admin folder
    $basePath = '';
    $announcementsPath = '../announcements/';
    $logoutPath = '../logoutadmin.php';
    $messagesPath = '../chatting/admin/Messages.php';
}
?>
<aside class="sidebar">
  <div class="brand">Admin</div>
  <nav>
    <div class="sidebar-links">
      <a href="<?php echo $basePath; ?>AdminDashboard.php">Dashboard</a>

      <a href="<?php echo $basePath; ?>DocumentRequests.php">Document Requests</a>

      <a href="<?php echo $basePath; ?>ResidentApprovals.php">Resident Approvals</a>
      <a href="<?php echo $basePath; ?>AttendanceView.php">Attendance</a>
      <a href="<?php echo $basePath; ?>AttendanceLogsView.php">Attendance Logs</a>
      <a href="<?php echo $basePath; ?>EmployeeProfiles.php">Employee Profiles</a>
      <a href="<?php echo $announcementsPath; ?>AnnouncementsList.php">Announcements</a>
      <a href="<?php echo $messagesPath; ?>">Messages</a>
      <a href="<?php echo $logoutPath; ?>" class="btn outline small">Logout</a>
    </div>
  </nav>
</aside>

<!-- Dropdown script removed -->
