<header class="site-header">
    <div class="container header-inner">
        <div class="brand">Bagong Pook Community Service Request</div>
        <nav class="nav">
            <a href="../index.php">Home</a>
            <a href="../index.php#services" class="services-link">Services</a>
            <a href="../announcements/AnnouncementsList.php">Announcements</a>

            <?php
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            $current_page = basename($_SERVER['PHP_SELF']);


            if (isset($_SESSION['resident_id'])) {
                echo '<a href="../logout.php" class="btn small outline">Logout</a>';
            } elseif (isset($_SESSION['admin_id'])) {
                echo '<a href="../logout.php" class="btn small outline">Logout</a>';
            } else {
                if (!in_array($current_page, ['Login.php', 'Register.php', 'AdminLogin.php'])) {
                    echo '<a href="../residents/Login.php" class="btn small">Login</a>';
                }
            }
            ?>
        </nav>
    </div>
</header>