<?php
session_start();
require_once "../config/Database.php";
require_once "../residents/Resident.php";

// Check if user is logged in
if(!isset($_SESSION['resident_id'])){
    header("Location: ../index.php");
    exit;
}

// Get database connection
$conn = Database::getInstance()->getConnection();

// Validate session - check if user still exists and is approved using stored procedure
try {
    $stmt = $conn->prepare("CALL getResidentById(?)");
    $stmt->execute([$_SESSION['resident_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("RequestForm validation error: " . $e->getMessage());
    session_destroy();
    header('Location: Login.php');
    exit;
}

// If user doesn't exist or is not approved, destroy session and redirect
if (!$user || $user['approval_status'] !== 'Approved') {
    session_destroy();
    header("Location: ../index.php");
    exit;
}

$resident = new Resident();
$residentId = $_SESSION['resident_id'];
$error = '';
$success = '';
$requestTypes = [
    'Barangay Clearance',
    'Cedula',
    'Certificate of Indigency',
    'Certificate of Residency'
];
$selectedType = $_POST['requestType'] ?? '';
$detailsValue = $_POST['details'] ?? '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $type = trim($_POST['requestType'] ?? '');
    $details = trim($_POST['details']);

    // Validate inputs
    if (empty($type) || !in_array($type, $requestTypes, true)) {
        $error = "Please select the document you need.";
    } elseif (empty($details)) {
        $error = "Please tell us the purpose of your request.";
    } elseif (strlen($details) < 10) {
        $error = "Please provide more details about your request (at least 10 characters).";
    } else {
        // Validate file uploads
        $uploadId = $_FILES['uploadId'];

        if ($uploadId['error'] !== UPLOAD_ERR_OK) {
            switch ($uploadId['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "Your ID file is too large. Please upload a file smaller than 5MB.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "Please upload your valid ID.";
                    break;
                default:
                    $error = "There was a problem uploading your ID. Please try again.";
            }
        } else {
            // Validate file types
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            $idFileType = mime_content_type($uploadId['tmp_name']);

            if (!in_array($idFileType, $allowedTypes)) {
                $error = "Your ID must be a JPG, PNG, or PDF file.";
            } else {
                // Create uploads directory if it doesn't exist
                $uploadDir = "../uploads/";
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Generate unique filenames
                $idFileName = uniqid() . '_' . basename($uploadId['name']);
                $residencyFileName = '';

                // Move uploaded files
                if (!move_uploaded_file($uploadId['tmp_name'], $uploadDir . $idFileName)) {
                    $error = "Failed to save your ID file. Please try again.";
                } else {
                    try {
                        $stmt = $conn->prepare("CALL createRequest(?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $residentId,
                            $type,
                            $details,
                            $idFileName,
                            $residencyFileName
                        ]);

                        $_SESSION['success_message'] = "Your request has been submitted successfully! You can track its status on your dashboard.";
                        header("Location: ../residents/Dashboard.php");
                        exit;
                    } catch (PDOException $e) {
                        $error = "We couldn't submit your request right now. Please try again or contact the barangay office.";
                        error_log("Request submission error: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Request Service</title>
<link rel="stylesheet" href="../css/residents.css">
<script src="../js/alerts.js"></script>
</head>
<body>

<?php include '../includes/headerinner.php'; ?>

<?php if(!empty($error)): ?>
  <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
<?php endif; ?>

<?php if(!empty($success)): ?>
  <div data-success-message="<?php echo htmlspecialchars($success); ?>"></div>
<?php endif; ?>

<main class="container request-page">
<div class="card form-card">
<h2>Request a Document</h2>
<form method="POST" enctype="multipart/form-data" id="requestForm">

<label>Full Name
<input type="text" value="<?= $_SESSION['resident_name'] ?>" readonly>
</label>

<label>Type of Document Needed
<select name="requestType" required>
<option value="">Select Request Type</option>
<?php foreach ($requestTypes as $typeOption): ?>
  <option value="<?php echo htmlspecialchars($typeOption); ?>" <?php echo ($selectedType === $typeOption) ? 'selected' : ''; ?>>
    <?php echo htmlspecialchars($typeOption); ?>
  </option>
<?php endforeach; ?>
</select>
</label>

<label>Purpose / Details
<textarea name="details" rows="4" required><?php echo htmlspecialchars($detailsValue); ?></textarea>
</label>

<label>Upload Valid ID (Required)
<input type="file" name="uploadId" accept=".jpg,.jpeg,.png,.pdf" required>
</label>

<div class="actions">
<button type="submit" class="btn">Submit Request</button>
</div>

</form>
</div>
</main>
    
<div class="hero-actions">
<!-- Back Button aligned right -->
<div style="margin-bottom: 2rem; text-align: right;">
    <a href="../residents/Dashboard.php" class="btn outline">← Back to Dashboard</a>
</div>

</div>

<script src="../js/appear.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
