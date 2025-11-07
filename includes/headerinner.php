<header class="site-header">
    <div class="container header-inner">
        <div class="brand">Smart Brgy System</div>
        <nav class="nav">
            <a href="../index.php">Home</a>
            <a href="../requests/RequestForm.php">Services</a>
            <a href="../announcements/AnnouncementsList.php">Announcements</a>

            <?php
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            $current_page = basename($_SERVER['PHP_SELF']);


            if (isset($_SESSION['resident_id'])) {
                echo '<a href="../Logout.php" class="btn small outline">Logout</a>';
            } else {

                if (!in_array($current_page, ['Login.php', 'Register.php'])) {
                    echo '<a href="../residents/Login.php" class="btn small">Login</a>';
                }
            }
            ?>
        </nav>
    </div>
</header>
