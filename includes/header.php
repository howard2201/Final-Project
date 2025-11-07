<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand">Smart Brgy System</div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="requests/RequestForm.php">Services</a>
            <a href="announcements/AnnouncementsList.php">Announcements</a>

            <?php

            if (isset($_SESSION['resident_id'])) {
                echo '<a href="residents/Logout.php" class="btn small outline">Logout</a>';
            } else {
                echo '<a href="residents/Login.php" class="btn small">Login</a>';
            }
            ?>
        </nav>
    </div>
</header>
