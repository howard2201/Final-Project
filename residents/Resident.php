<?php
require_once "../config/Database.php";
require_once "../config/SMSService.php";

class Resident {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function register($full_name, $username, $phone_number, $password, $id_file, $proof_file) {
        try {
            // Check if username exists
            $stmt = $this->conn->prepare("CALL checkResidentUsernameExists(?)");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $stmt->closeCursor();
                throw new Exception("Username already exists. Please choose a different username.");
            }
            $stmt->closeCursor();

            // Use SHA-256 hashing
            $hash = hash('sha256', $password);

            $stmt = $this->conn->prepare("CALL registerResident(?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $full_name,
                $username,
                NULL, // email is now optional
                $phone_number,
                $hash,
                $id_file,
                $proof_file
            ]);
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            throw new Exception("Unable to complete registration. Please try again.");
        }
    }

    public function login($username, $password) {
        try {
            // Use stored procedure for login to centralize SQL logic
            $stmt = $this->conn->prepare("CALL loginResident(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Close cursor to avoid leftover resultsets when calling other procedures
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }

            // Check both SHA-256 and bcrypt for backward compatibility
            $passwordHash = hash('sha256', $password);
            if ($user && (isset($user['resident_password']) && ($user['resident_password'] === $passwordHash || password_verify($password, $user['resident_password'])))) {
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            throw new Exception("Unable to process login. Please try again.");
        }
    }

    public function initiatePasswordReset($username) {
        try {
            // Check if username exists
            $stmt = $this->conn->prepare("CALL getResidentByUsername(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$user) {
                return false; // Don't reveal if username exists
            }

            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));

            // Store reset token
            $stmt = $this->conn->prepare("CALL initiatePasswordResetResident(?, ?)");
            $stmt->execute([$username, $resetToken]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($result && isset($result['phone_number'])) {
                // Generate verification code
                $code = SMSService::generateCode();
                
                // Store verification code
                $stmt = $this->conn->prepare("CALL storeVerificationCodeResident(?, ?)");
                $stmt->execute([$username, $code]);
                $stmt->closeCursor();

                // Send SMS
                $smsService = new SMSService();
                $smsService->sendPasswordResetCode($result['phone_number'], $code);

                return ['token' => $resetToken, 'username' => $username];
            }

            return false;
        } catch (PDOException $e) {
            error_log("Password reset initiation error: " . $e->getMessage());
            throw new Exception("Unable to initiate password reset. Please try again.");
        }
    }

    public function verifyResetCode($username, $code) {
        try {
            $stmt = $this->conn->prepare("CALL verifyCodeResident(?, ?)");
            $stmt->execute([$username, $code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ? true : false;
        } catch (PDOException $e) {
            error_log("Code verification error: " . $e->getMessage());
            return false;
        }
    }

    public function resetPassword($username, $token, $newPassword) {
        try {
            // Verify token
            $stmt = $this->conn->prepare("CALL verifyPasswordResetTokenResident(?, ?)");
            $stmt->execute([$username, $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$user) {
                return false;
            }

            // Hash new password
            $hash = hash('sha256', $newPassword);

            // Reset password
            $stmt = $this->conn->prepare("CALL resetPasswordResident(?, ?)");
            return $stmt->execute([$username, $hash]);
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            throw new Exception("Unable to reset password. Please try again.");
        }
    }

    public function sendVerificationCode($username) {
        try {
            $stmt = $this->conn->prepare("CALL getResidentByUsername(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$user || !$user['phone_number']) {
                return false;
            }

            $code = SMSService::generateCode();
            
            $stmt = $this->conn->prepare("CALL storeVerificationCodeResident(?, ?)");
            $stmt->execute([$username, $code]);
            $stmt->closeCursor();

            $smsService = new SMSService();
            return $smsService->sendVerificationCode($user['phone_number'], $code);
        } catch (PDOException $e) {
            error_log("Send verification code error: " . $e->getMessage());
            return false;
        }
    }

    public function verifyCode($username, $code) {
        try {
            $stmt = $this->conn->prepare("CALL verifyCodeResident(?, ?)");
            $stmt->execute([$username, $code]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            return $result ? true : false;
        } catch (PDOException $e) {
            error_log("Code verification error: " . $e->getMessage());
            return false;
        }
    }

    public function getRequests($resident_id) {
        try {
            $stmt = $this->conn->prepare("CALL getResidentRequests(?)");
            $stmt->execute([$resident_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get requests error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
}
