<?php
session_start();
require_once "../config/Database.php";

// Check if user has pending approval session
if (!isset($_SESSION['pending_resident_id'])) {
    header('Location: Login.php');
    exit;
}

$residentName = isset($_SESSION['pending_resident_name']) ? $_SESSION['pending_resident_name'] : 'Resident';
$residentEmail = isset($_SESSION['pending_resident_email']) ? $_SESSION['pending_resident_email'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Pending Approval — Smart Brgy System</title>
    <link rel="stylesheet" href="../css/headers.css">
    <link rel="stylesheet" href="../css/footers.css">
    <link rel="stylesheet" href="../css/temporary_page.css">
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container pending-container">
    <div class="pending-card">
        <div class="pending-icon">⏳</div>
        <h1>Account Pending Approval</h1>
        <p>Hello, <strong><?php echo htmlspecialchars($residentName); ?></strong>!</p>
        <p>Thank you for registering with the Smart Barangay System.</p>

        <div class="info-box">
            <strong>📋 What's Next?</strong>
            <p>Your account is currently under review by our barangay administrators. This process typically takes 1-2 business days.</p>
        </div>

        <p>You will be able to access the full system once your account has been approved by the barangay office.</p>

        <div class="info-box">
            <strong>📧 Registered Email</strong>
            <p><?php echo htmlspecialchars($residentEmail); ?></p>
        </div>

        <p class="contact-note">
            If you have any questions or concerns, please contact the barangay office directly.
        </p>

        <div class="action-buttons">
            <a href="Login.php" class="logout-link">Back to Login</a>
            <a href="../index.php" class="logout-link">Go to Home</a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>