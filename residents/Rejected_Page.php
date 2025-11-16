<?php
session_start();

// Check if rejected resident session exists
if (!isset($_SESSION['rejected_resident_id'])) {
    header('Location: Login.php');
    exit;
}

require_once '../config/Database.php';

// Get rejection details from database using stored procedure
$pdo = Database::getInstance()->getConnection();

try {
    $stmt = $pdo->prepare("CALL getResidentById(?)");
    $stmt->execute([$_SESSION['rejected_resident_id']]);
    $resident = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("Rejected page error: " . $e->getMessage());
    die("Database error. Please contact the administrator.");
}

// If resident doesn't exist or is no longer rejected, redirect
if (!$resident || $resident['approval_status'] !== 'Rejected') {
    session_destroy();
    header('Location: Login.php');
    exit;
}

// Calculate days remaining until deletion
$rejectionDate = new DateTime($resident['rejection_date']);
$deletionDate = clone $rejectionDate;
$deletionDate->modify('+10 days');
$now = new DateTime();
$daysRemaining = $now->diff($deletionDate)->days;

// If already past deletion date
if ($now >= $deletionDate) {
    $daysRemaining = 0;
    $deletionMessage = "Your account is scheduled for deletion today.";
} else {
    $deletionMessage = "Your account will be permanently deleted in <strong>{$daysRemaining} day(s)</strong>.";
}

$residentName = htmlspecialchars($resident['full_name']);
$residentEmail = htmlspecialchars($resident['email']);
$rejectionDateFormatted = $rejectionDate->format('F j, Y \a\t g:i A');
$deletionDateFormatted = $deletionDate->format('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Rejected — Smart Brgy System</title>
    <link rel="stylesheet" href="../css/headers.css">
    <link rel="stylesheet" href="../css/footers.css">
    <link rel="stylesheet" href="../css/rejected_page.css">
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container rejection-container">
    <div class="rejection-card">
            <div class="rejection-header">
                <div class="rejection-icon">⚠️</div>
                <h1>Account Registration Rejected</h1>
            </div>

            <div class="rejection-body">
                <div class="info-box">
                    <h3>Dear <?php echo $residentName; ?>,</h3>
                    <p>We regret to inform you that your account registration has been rejected by the barangay administrator.</p>
                    <p>Your account and all associated data will be permanently deleted after 10 days from the rejection date.</p>
                </div>

                <div class="countdown-box">
                    <div class="label">Days Until Deletion</div>
                    <div class="days"><?php echo $daysRemaining; ?></div>
                    <p style="margin: 0.5rem 0 0 0; color: #6c757d;">
                        <?php echo $deletionMessage; ?>
                    </p>
                </div>

                <ul class="details-list">
                    <li>
                        <span class="label">Account Name:</span>
                        <span class="value"><?php echo $residentName; ?></span>
                    </li>
                    <li>
                        <span class="label">Email:</span>
                        <span class="value"><?php echo $residentEmail; ?></span>
                    </li>
                    <li>
                        <span class="label">Rejection Date:</span>
                        <span class="value"><?php echo $rejectionDateFormatted; ?></span>
                    </li>
                    <li>
                        <span class="label">Deletion Date:</span>
                        <span class="value"><?php echo $deletionDateFormatted; ?></span>
                    </li>
                </ul>

                <div class="warning-text">
                    <strong>⚠️ Important:</strong> If you believe this rejection was made in error, please contact the barangay office immediately before your account is deleted.
                </div>

                <div class="action-buttons">
                    <a href="Login.php" class="logout-link">Back to Login</a>
                    <a href="../index.php" class="logout-link">Go to Home</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>

