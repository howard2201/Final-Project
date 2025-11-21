<?php
// Shared admin sidebar include
?>
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
      <a href="../admin/DocumentRequests.php">Document Requests</a>
      <a href="../admin/ResidentApprovals.php">Resident Approvals</a>
      <a href="../admin/AttendanceView.php">Attendance</a>
      <a href="../announcements/AnnouncementsList.php">Announcements</a>
      <a href="../logoutadmin.php" class="btn outline small">Logout</a>
    </div>
  </nav>
</aside>
