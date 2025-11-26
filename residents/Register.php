<?php
session_start();
require_once '../config/Database.php';
require_once '../config/SMSService.php';

$error = '';
$success = '';

/**
 * Remove any pending registration artifacts (temp files + session values)
 */
function clearPendingRegistrationSession()
{
    if (isset($_SESSION['pending_registration'])) {
        $pending = $_SESSION['pending_registration'];
        if (!empty($pending['id_file_path']) && file_exists($pending['id_file_path'])) {
            @unlink($pending['id_file_path']);
        }
        if (!empty($pending['proof_file_path']) && file_exists($pending['proof_file_path'])) {
            @unlink($pending['proof_file_path']);
        }
        unset($_SESSION['pending_registration']);
    }
    if (isset($_SESSION['registration_username'])) {
        unset($_SESSION['registration_username']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim($_POST['fullName']);
  $username = trim($_POST['username']);
  $phoneNumber = trim($_POST['phone_number']);
  $password = $_POST['password'];

  // Validate inputs
  if (empty($fullName)) {
    $error = "Please enter your full name.";
  } elseif (strlen($fullName) < 3) {
    $error = "Your name must be at least 3 characters long.";
  } elseif (empty($username)) {
    $error = "Please enter a username.";
  } elseif (strlen($username) < 3 || strlen($username) > 20) {
    $error = "Username must be between 3 and 20 characters.";
  } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $error = "Username can only contain letters, numbers, and underscores.";
  } elseif (empty($phoneNumber)) {
    $error = "Please enter your phone number.";
  } elseif (!preg_match('/^(\+63|0)?9\d{9}$/', $phoneNumber)) {
    $error = "Please enter a valid Philippine phone number (e.g., 09123456789 or +639123456789).";
  } elseif (empty($password)) {
    $error = "Please enter a password.";
  } elseif (strlen($password) < 6 || strlen($password) > 16) {
    $error = "Your password must be between 6 and 16 characters long.";
  } elseif (!preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
    $error = "Your password must include at least one number and one special character.";
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
        try {
          // Get database connection
          $pdo = Database::getInstance()->getConnection();

          // Check if username already exists
          $checkUsernameStmt = $pdo->prepare('CALL checkResidentUsernameExists(?)');
          $checkUsernameStmt->execute([$username]);
          $usernameExists = $checkUsernameStmt->rowCount() > 0;
          if (method_exists($checkUsernameStmt, 'closeCursor')) {
            $checkUsernameStmt->closeCursor();
          }

          if ($usernameExists) {
            $error = "This username is already taken. Please choose a different username.";
          } else {

            // Format phone number
            $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
            if (substr($phoneNumber, 0, 1) === '0') {
              $phoneNumber = '+63' . substr($phoneNumber, 1);
            } elseif (substr($phoneNumber, 0, 2) === '63') {
              $phoneNumber = '+' . $phoneNumber;
            } elseif (substr($phoneNumber, 0, 1) !== '+') {
              $phoneNumber = '+63' . $phoneNumber;
            }

            // Temporarily store files until OTP verification succeeds
            $pendingDir = realpath(__DIR__ . '/../uploads');
            if ($pendingDir === false) {
              $pendingDir = __DIR__ . '/../uploads';
            }
            $pendingDir .= '/pending';
            if (!is_dir($pendingDir)) {
              mkdir($pendingDir, 0777, true);
            }

            $storeTempFile = function ($file, $prefix) use ($pendingDir) {
              $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
              $tempName = $pendingDir . '/' . uniqid($prefix . '_', true) . ($extension ? '.' . strtolower($extension) : '');
              if (!move_uploaded_file($file['tmp_name'], $tempName)) {
                throw new Exception("We couldn't store your {$prefix} file. Please try again.");
              }
              return $tempName;
            };
            $idTempPath = $proofTempPath = null;
            try {
              $idTempPath = $storeTempFile($registerId, 'id');
              $proofTempPath = $storeTempFile($registerProof, 'proof');
            } catch (Exception $e) {
              if ($idTempPath && file_exists($idTempPath)) {
                @unlink($idTempPath);
              }
              if ($proofTempPath && file_exists($proofTempPath)) {
                @unlink($proofTempPath);
              }
              throw $e;
            }

            // Generate and send OTP before creating the account
            $smsService = new SMSService();
            $otpCode = SMSService::generateCode();
            $sent = $smsService->sendVerificationCode($phoneNumber, $otpCode);

            if ($sent) {
              // Clear any previous pending registration data
              clearPendingRegistrationSession();

              $_SESSION['pending_registration'] = [
                'full_name' => $fullName,
                'username' => $username,
                'phone_number' => $phoneNumber,
                'password_hash' => hash('sha256', $password),
                'id_file_path' => $idTempPath,
                'proof_file_path' => $proofTempPath,
                'created_at' => time(),
                'expires_at' => time() + 600, // 10 minutes validity
                'otp_hash' => password_hash($otpCode, PASSWORD_DEFAULT),
                'resend_count' => 0,
                'can_resend_at' => time() + 60,
                'otp_display' => $smsService->isProduction() ? null : $otpCode,
                'mode' => $smsService->getMode()
              ];
              $_SESSION['registration_username'] = $username;

              header("Location: VerifySMS.php");
              exit;
            } else {
              $error = "We couldn't send the verification code. Please double-check your phone number or try again later.";
              if (isset($idTempPath) && file_exists($idTempPath)) {
                @unlink($idTempPath);
              }
              if (isset($proofTempPath) && file_exists($proofTempPath)) {
                @unlink($proofTempPath);
              }
            }
          }
        } catch (PDOException $e) {
          if (isset($idTempPath) && file_exists($idTempPath)) {
            @unlink($idTempPath);
          }
          if (isset($proofTempPath) && file_exists($proofTempPath)) {
            @unlink($proofTempPath);
          }
          $error = "We're experiencing technical difficulties. Please try again later or contact the barangay office for assistance.";
          // Log the actual error for developers (in production, log to file)
          error_log("Registration error: " . $e->getMessage());
        } catch (Exception $e) {
          if (isset($idTempPath) && file_exists($idTempPath)) {
            @unlink($idTempPath);
          }
          if (isset($proofTempPath) && file_exists($proofTempPath)) {
            @unlink($proofTempPath);
          }
          if (empty($error)) {
            $error = $e->getMessage();
          }
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
  <link rel="stylesheet" href="../css/Register.css">
  <script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<?php if(isset($_SESSION['success_message'])): ?>
  <div data-success-message="<?php echo htmlspecialchars($_SESSION['success_message']); ?>"></div>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
  <div data-error-message="<?php echo htmlspecialchars($_SESSION['error_message']); ?>"></div>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if(!empty($error)): ?>
  <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
<?php endif; ?>

<main class="container auth-page">
  <div class="backlogin">
  <img src="../assets/img/logo.png" alt="logo">
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
      <label>Username
        <input type="text" name="username" pattern="[a-zA-Z0-9_]{3,20}" title="3-20 characters, letters, numbers, and underscores only" required>
        <small style="color: #666; font-size: 0.85em;">3-20 characters, letters, numbers, and underscores only</small>
      </label>
      <label>Phone Number
        <input type="tel" name="phone_number" placeholder="09123456789 or +639123456789" pattern="(\+63|0)?9\d{9}" required>
        <small style="color: #666; font-size: 0.85em;">Philippine mobile number (e.g., 09123456789)</small>
      </label>
      <label>Password
        <input type="password" name="password" minlength="6" maxlength="16" pattern="(?=.*[0-9])(?=.*[^a-zA-Z0-9]).{6,16}" title="6-16 characters with at least one number and one special character" required>
        <small style="color: #666; font-size: 0.85em;">6-16 characters, include at least one number and one special character</small>
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
  </div>
</main>
<script src="../js/responsive.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
