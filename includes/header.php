<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand">Bagong Pook Community Service Request</div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="announcements/AnnouncementsList.php">Announcements</a>

            <?php
            if (isset($_SESSION['resident_id'])) {
                echo '<a href="residents/Dashboard.php" class="btn small">Dashboard</a>';
                echo '<a href="logout.php" class="btn small outline">Logout</a>';
            } elseif (isset($_SESSION['admin_id'])) {
                echo '<a href="logout.php" class="btn small outline">Logout</a>';
            } else {
                echo '<a href="residents/Login.php" class="btn small">Login</a>';
            }
            ?>
        </nav>
    </div>
</header>
<link rel="stylesheet" href="../css/include.css">
<link rel="stylesheet" href="css/include.css">