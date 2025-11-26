<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
    <div class="container header-inner">
        <div class="brand">Bagong Pook Community Service Request</div>

        <!-- Original nav links -->
        <nav class="nav original-nav">
            <a href="../index.php">Home</a>

            <?php if ($current_page !== 'AnnouncementsList.php') : ?>
                <a href="../announcements/AnnouncementsList.php">Announcements</a>
            <?php endif; ?>

            <?php
            if (isset($_SESSION['resident_id']) || isset($_SESSION['admin_id'])) {
                echo '<a href="../logout.php" class="btn small outline">Logout</a>';
            } else {
                if (!in_array($current_page, ['Login.php','Register.php','AdminLogin.php'])) {
                    echo '<a href="../residents/Login.php" class="btn small">Login</a>';
                }
            }
            ?>
        </nav>

        <!-- Hamburger icon -->
        <div class="hamburger" id="hamburger2">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <!-- Dropdown nav for hamburger -->
        <nav class="nav dropdown-nav" id="dropdown2">
            <a href="../index.php">Home</a>

            <?php if ($current_page !== 'AnnouncementsList.php') : ?>
                <a href="../announcements/AnnouncementsList.php">Announcements</a>
            <?php endif; ?>

            <?php
            if (isset($_SESSION['resident_id']) || isset($_SESSION['admin_id'])) {
                echo '<a href="../logout.php" class="btn small outline">Logout</a>';
            } else {
                if (!in_array($current_page, ['Login.php','Register.php','AdminLogin.php'])) {
                    echo '<a href="../residents/Login.php" class="btn small">Login</a>';
                }
            }
            ?>
        </nav>
    </div>
</header>

<link rel="stylesheet" href="../css/include.css">
<link rel="stylesheet" href="css/include.css">
<script src="../js/hamburger.js"></script>
<script src="js/hamburger.js"></script>
