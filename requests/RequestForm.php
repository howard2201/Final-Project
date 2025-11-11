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

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $type = trim($_POST['requestType']);
    $details = trim($_POST['details']);

    // Validate inputs
    if (empty($type)) {
        $error = "Please select the type of document you need.";
    } elseif (empty($details)) {
        $error = "Please tell us the purpose of your request.";
    } elseif (strlen($details) < 10) {
        $error = "Please provide more details about your request (at least 10 characters).";
    } else {
        // Validate file uploads
        $uploadId = $_FILES['uploadId'];
        $uploadResidency = $_FILES['uploadResidency'];

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
        } elseif ($uploadResidency['error'] !== UPLOAD_ERR_OK) {
            switch ($uploadResidency['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "Your residency certificate file is too large. Please upload a file smaller than 5MB.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "Please upload your barangay certificate of residency.";
                    break;
                default:
                    $error = "There was a problem uploading your residency certificate. Please try again.";
            }
        } else {
            // Validate file types
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            $idFileType = mime_content_type($uploadId['tmp_name']);
            $residencyFileType = mime_content_type($uploadResidency['tmp_name']);

            if (!in_array($idFileType, $allowedTypes)) {
                $error = "Your ID must be a JPG, PNG, or PDF file.";
            } elseif (!in_array($residencyFileType, $allowedTypes)) {
                $error = "Your residency certificate must be a JPG, PNG, or PDF file.";
            } else {
                // Create uploads directory if it doesn't exist
                $uploadDir = "../uploads/";
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Generate unique filenames
                $idFileName = uniqid() . '_' . basename($uploadId['name']);
                $residencyFileName = uniqid() . '_' . basename($uploadResidency['name']);

                // Move uploaded files
                if (!move_uploaded_file($uploadId['tmp_name'], $uploadDir . $idFileName)) {
                    $error = "Failed to save your ID file. Please try again.";
                } elseif (!move_uploaded_file($uploadResidency['tmp_name'], $uploadDir . $residencyFileName)) {
                    $error = "Failed to save your residency certificate. Please try again.";
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
<link rel="stylesheet" href="../css/style.css">
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

<div class="step" data-step="1">
<label>Full Name
<input type="text" value="<?= $_SESSION['resident_name'] ?>" readonly>
</label>

<label>Type of Request
<select name="requestType" required>
<option value="">Select Request Type</option>
<option>Barangay Certificate</option>
<option>Barangay Clearance</option>
<option>Business Permits and Licenses</option>
<option>Applying for a Passport</option>
<option>Clearance Certificates</option>
<option>Employment</option>
<option>Government Documents Application</option>
<option>Identification</option>
<option>Proof of Address</option>
</select>
</label>

<div class="actions">
<button type="button" id="next1" class="btn">Next</button>
</div>
</div>

<div class="step step-hidden" data-step="2">
<label>Purpose / Details
<textarea name="details" rows="4" required></textarea>
</label>

<label>Upload Valid ID (Required)
<input type="file" name="uploadId" accept=".jpg,.jpeg,.png,.pdf" required>
</label>

<label>Barangay Certificate of Residency (Required)
<input type="file" name="uploadResidency" accept=".jpg,.jpeg,.png,.pdf" required>
</label>

<div class="actions">
<button type="button" id="back2" class="btn outline">Back</button>
<button type="submit" class="btn">Submit Request</button>
</div>
</div>

</form>
</div>
</main>

<script src="../js/appear.js"></script>
<script>
let step = 1;
const steps = document.querySelectorAll('.step');
const showStep = n => {
    steps.forEach(s=>s.style.display = s.dataset.step == n ? 'block' : 'none');
}
document.getElementById('next1').onclick = ()=>{step=2; showStep(step);}
document.getElementById('back2').onclick = ()=>{step=1; showStep(step);}
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
