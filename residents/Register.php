<?php
session_start();
require_once '../config/Database.php';
require_once 'Resident.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim($_POST['fullName']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  // Validate inputs
  if (empty($fullName)) {
    $error = "Please enter your full name.";
  } elseif (strlen($fullName) < 3) {
    $error = "Your name must be at least 3 characters long.";
  } elseif (empty($email)) {
    $error = "Please enter your email address.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Please enter a valid email address (e.g., yourname@example.com).";
  } elseif (empty($password)) {
    $error = "Please enter a password.";
  } elseif (strlen($password) < 6 || strlen($password) > 16) {
    $error = "Your password must be at least 6 characters long and a maximum of 16 characters for security.";
  } else {
    // Handle file uploads
    $registerId = $_FILES['registerId'];
    $registerProof = $_FILES['registerProof'];

    // Validate file uploads with detailed messages
    if ($registerId['error'] !== UPLOAD_ERR_OK) {
      switch ($registerId['error']) {
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
    } elseif ($registerProof['error'] !== UPLOAD_ERR_OK) {
      switch ($registerProof['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $error = "Your proof of residency file is too large. Please upload a file smaller than 5MB.";
          break;
        case UPLOAD_ERR_NO_FILE:
          $error = "Please upload your proof of residency.";
          break;
        default:
          $error = "There was a problem uploading your proof of residency. Please try again.";
      }
    } else {
      // Validate file types
      $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
      $idFileType = mime_content_type($registerId['tmp_name']);
      $proofFileType = mime_content_type($registerProof['tmp_name']);

      if (!in_array($idFileType, $allowedTypes)) {
        $error = "Your ID must be a JPG, PNG, or PDF file.";
      } elseif (!in_array($proofFileType, $allowedTypes)) {
        $error = "Your proof of residency must be a JPG, PNG, or PDF file.";
      } else {
        // Read file contents as binary data
        $idFileContent = file_get_contents($registerId['tmp_name']);
        $proofFileContent = file_get_contents($registerProof['tmp_name']);

        // Hash the password using SHA-256
        $hashedPassword = hash('sha256', $password);

        try {
          // Get database connection
          $pdo = Database::getInstance()->getConnection();

          // Check if email already exists
          $checkStmt = $pdo->prepare('CALL checkResidentEmailExists(?)');
          $checkStmt->execute([$email]);

          if ($checkStmt->rowCount() > 0) {
            $error = "This email is already registered. Please use a different email or try logging in.";
          } else {
            // Close the previous result set before calling another procedure
            $checkStmt->closeCursor();

            // Insert using stored procedure
            $stmt = $pdo->prepare('CALL registerResident(?, ?, ?, ?, ?)');
            $stmt->execute([$fullName, $email, $hashedPassword, $idFileContent, $proofFileContent]);

            if ($stmt->rowCount() > 0) {
              $_SESSION['success_message'] = "Registration successful! Your account is being reviewed by barangay officials. You'll be able to log in once approved.";
              header("Location: Login.php");
              exit;
            } else {
              $error = "We couldn't create your account right now. Please try again or contact the barangay office for help.";
            }
          }
        } catch (PDOException $e) {
          $error = "We're experiencing technical difficulties. Please try again later or contact the barangay office for assistance.";
          // Log the actual error for developers (in production, log to file)
          error_log("Registration error: " . $e->getMessage());
        }
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register — Prototype</title>
  <link rel="stylesheet" href="../css/header.css">
  <link rel="stylesheet" href="../css/footer.css">
  <link rel="stylesheet" href="../css/register.css">
  <script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<?php if(isset($_SESSION['success_message'])): ?>
  <div data-success-message="<?php echo htmlspecialchars($_SESSION['success_message']); ?>"></div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<main class="container auth-page">
  <div class="auth-card">
    <h2>Create an Account</h2>
    <?php if(!empty($error)): ?>
      <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <?php if(!empty($success)): ?>
      <p class="success-text"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
      <label>Full Name
        <input type="text" name="fullName" required>
      </label>
      <label>Email
        <input type="email" name="email" required>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <label>Upload Valid ID
        <input type="file" name="registerId" accept=".jpg,.jpeg,.png,.pdf" required>
      </label>
      <label>Upload Proof of Residency
        <input type="file" name="registerProof" accept=".jpg,.jpeg,.png,.pdf" required>
      </label>
      <div class="auth-actions">
        <button type="submit" class="btn">Register</button>
        <a href="Login.php" class="btn outline">Login</a>
      </div>
    </form>
  </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
