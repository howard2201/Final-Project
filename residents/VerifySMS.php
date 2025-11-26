<?php
session_start();
require_once '../config/Database.php';
require_once '../config/SMSService.php';

$error = '';
$success = '';

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
 
// Ensure there is a pending registration session
if (!isset($_SESSION['pending_registration'], $_SESSION['registration_username'])) {
    clearPendingRegistrationSession();
    header('Location: Register.php');
    exit;
}

$pending = $_SESSION['pending_registration'];
$isProduction = isset($pending['mode']) ? ($pending['mode'] === 'production') : true;
$displayCode = !$isProduction ? ($pending['otp_display'] ?? null) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle resend action
    if (isset($_POST['resend'])) {
        if ($pending['resend_count'] >= 3) {
            $error = "You have reached the maximum number of resend attempts. Please restart the registration process.";
        } elseif (time() < $pending['can_resend_at']) {
            $wait = $pending['can_resend_at'] - time();
            $error = "Please wait {$wait} more seconds before requesting a new code.";
        } else {
            $smsService = new SMSService();
            $newCode = SMSService::generateCode();
            if ($smsService->sendVerificationCode($pending['phone_number'], $newCode)) {
                $pending['otp_hash'] = password_hash($newCode, PASSWORD_DEFAULT);
                $pending['expires_at'] = time() + 600;
                $pending['resend_count'] += 1;
                $pending['can_resend_at'] = time() + 60;
                $pending['otp_display'] = $smsService->isProduction() ? null : $newCode;
                $_SESSION['pending_registration'] = $pending;
                $pending = $_SESSION['pending_registration'];
                $isProduction = $smsService->isProduction();
                $displayCode = !$isProduction ? $pending['otp_display'] : null;
                $success = "A new verification code has been sent to your phone number.";
            } else {
                $error = "Failed to send a new verification code. Please try again later.";
            }
        }
    } else {
        // Verify code input
        $code = trim($_POST['verification_code']);

        if (!preg_match('/^[0-9]{6}$/', $code)) {
            $error = "Please enter the 6-digit verification code.";
        } elseif (time() > $pending['expires_at']) {
            clearPendingRegistrationSession();
            $_SESSION['error_message'] = "Your verification code has expired. Please register again.";
            header('Location: Register.php');
            exit;
        } elseif (!password_verify($code, $pending['otp_hash'])) {
            $error = "Invalid verification code. Please try again or request a new code.";
        } else {
            // Create the account now that OTP has been verified
            try {
                $pdo = Database::getInstance()->getConnection();

                // Re-check username availability
                $checkUsernameStmt = $pdo->prepare('CALL checkResidentUsernameExists(?)');
                $checkUsernameStmt->execute([$pending['username']]);
                $usernameExists = $checkUsernameStmt->rowCount() > 0;
                if (method_exists($checkUsernameStmt, 'closeCursor')) {
                    $checkUsernameStmt->closeCursor();
                }

                if ($usernameExists) {
                    $error = "This username has already been registered. Please start over with a different username.";
                } else {
                    $idFileContent = file_exists($pending['id_file_path']) ? file_get_contents($pending['id_file_path']) : null;
                    $proofFileContent = file_exists($pending['proof_file_path']) ? file_get_contents($pending['proof_file_path']) : null;

                    if ($idFileContent === null || $proofFileContent === null) {
                        $error = "We could not read your uploaded documents. Please restart the registration process.";
                        clearPendingRegistrationSession();
                    } else {
                        $stmt = $pdo->prepare('CALL registerResident(?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([
                            $pending['full_name'],
                            $pending['username'],
                            NULL,
                            $pending['phone_number'],
                            $pending['password_hash'],
                            $idFileContent,
                            $proofFileContent
                        ]);

                        if ($stmt->rowCount() > 0) {
                            if (method_exists($stmt, 'closeCursor')) {
                                $stmt->closeCursor();
                            }
                            clearPendingRegistrationSession();
                            $_SESSION['success_message'] = "Account created successfully! Your account is pending approval. You'll be able to log in once approved.";
                            header('Location: Login.php');
                            exit;
                        } else {
                            if (method_exists($stmt, 'closeCursor')) {
                                $stmt->closeCursor();
                            }
                            $error = "We couldn't finalize your registration. Please try again.";
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Finalize registration error: " . $e->getMessage());
                $error = "We encountered a technical issue while creating your account. Please try again.";
            } catch (Exception $e) {
                error_log("Finalize registration general error: " . $e->getMessage());
                $error = "Something went wrong while completing your registration. Please try again.";
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
  <title>Verify Phone Number — Smart Barangay System</title>
  <link rel="stylesheet" href="../css/residents.css">
  <script src="../js/alerts.js"></script>
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<?php if(isset($error) && !empty($error)): ?>
  <div data-error-message="<?php echo htmlspecialchars($error); ?>"></div>
<?php endif; ?>

<?php if(isset($success) && !empty($success)): ?>
  <div data-success-message="<?php echo htmlspecialchars($success); ?>"></div>
<?php endif; ?>

<main class="container auth-page">
<div class="backlogin">
  <img src="../assets/img/logo.png" alt="logo">
  <div class="auth-card">
    <h2>Verify Phone Number</h2>
    <p>We've sent a verification code to your phone number. Please enter it below to complete your registration.</p>
    <?php if(!$isProduction && !empty($displayCode)): ?>
      <div style="background:#f0f4ff;border:1px solid #a3bffa;border-radius:8px;padding:12px;margin-bottom:15px;">
        <strong>Offline OTP:</strong> <?php echo htmlspecialchars($displayCode); ?><br>
        <small>Send this code manually to the resident if SMS delivery is offline.</small>
      </div>
    <?php endif; ?>
    
    <form method="POST">
      <label>Verification Code
        <input type="text" name="verification_code" maxlength="6" pattern="[0-9]{6}" placeholder="000000" required autofocus>
      </label>
      <div class="auth-actions">
        <button type="submit" class="btn">Verify</button>
        <button type="submit" name="resend" value="1" class="btn outline" formnovalidate>Resend Code</button>
      </div>
      <div style="margin-top: 10px; text-align: center;">
        <a href="Register.php" style="color: #007bff; text-decoration: none; font-size: 0.9em;">Back to Registration</a>
      </div>
    </form>
  </div>
</div>
</main>
<script src="../js/responsive.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>

