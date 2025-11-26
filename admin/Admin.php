<?php
require_once "../config/Database.php";

class Admin {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function login($username, $password) {
        try {
            // Use stored procedure for admin login
            $stmt = $this->conn->prepare("CALL loginAdmin(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Close cursor to prevent pending resultsets
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }

            // Check both SHA-256 and bcrypt for backward compatibility
            $passwordHash = hash('sha256', $password);
            if ($user && (isset($user['password']) && ($user['password'] === $passwordHash || password_verify($password, $user['password'])))) {
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            throw new Exception("Unable to process login. Please try again.");
        }
    }

    public function initiatePasswordReset($username) {
        try {
            // Check if username exists
            $stmt = $this->conn->prepare("CALL getAdminByUsername(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$user) {
                return false; // Don't reveal if username exists
            }

            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));

            // Store reset token
            $stmt = $this->conn->prepare("CALL initiatePasswordResetAdmin(?, ?)");
            $stmt->execute([$username, $resetToken]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($result && isset($result['phone_number'])) {
                // Generate verification code
                require_once "../config/SMSService.php";
                $code = SMSService::generateCode();
                
                // Store verification code
                $stmt = $this->conn->prepare("CALL storeVerificationCodeAdmin(?, ?)");
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
            $stmt = $this->conn->prepare("CALL verifyCodeAdmin(?, ?)");
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
            $stmt = $this->conn->prepare("CALL verifyPasswordResetTokenAdmin(?, ?)");
            $stmt->execute([$username, $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$user) {
                return false;
            }

            // Hash new password
            $hash = hash('sha256', $newPassword);

            // Reset password
            $stmt = $this->conn->prepare("CALL resetPasswordAdmin(?, ?)");
            return $stmt->execute([$username, $hash]);
        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            throw new Exception("Unable to reset password. Please try again.");
        }
    }

    public function getRequests() {
        try {
            $stmt = $this->conn->prepare("CALL getAllRequestsWithResidents()");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get requests error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    public function updateRequestStatus($request_id, $status) {
        try {
            $stmt = $this->conn->prepare("CALL updateRequestStatus(?, ?)");
            return $stmt->execute([$status, $request_id]);
        } catch (PDOException $e) {
            error_log("Update status error: " . $e->getMessage());
            throw new Exception("Unable to update request status. Please try again.");
        }
    }

    public function getPendingResidents() {
        try {
            $stmt = $this->conn->prepare("CALL getPendingResidents()");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get pending residents error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    public function getAllResidents() {
        try {
            $stmt = $this->conn->prepare("CALL getAllResidentsWithStatus()");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get all residents error: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }

    public function updateResidentApprovalStatus($resident_id, $status) {
        try {
            $stmt = $this->conn->prepare("CALL updateResidentApprovalStatus(?, ?)");
            return $stmt->execute([$resident_id, $status]);
        } catch (PDOException $e) {
            error_log("Update resident approval status error: " . $e->getMessage());
            throw new Exception("Unable to update resident approval status. Please try again.");
        }
    }

    public function getAttendanceRecords() {
        try {
            $stmt = $this->conn->prepare("CALL getAllAttendanceRecords()");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
            return $records;
        } catch (PDOException $e) {
            error_log("Get attendance records error: " . $e->getMessage());
            // Fallback to alternative stored procedure
            try {
                $stmt = $this->conn->prepare("CALL getAttendanceForAPI()");
                $stmt->execute();
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (method_exists($stmt, 'closeCursor')) {
                    $stmt->closeCursor();
                }
                return $records;
            } catch (PDOException $e2) {
                error_log("Fallback attendance query error: " . $e2->getMessage());
                return [];
            }
        }
    }

    public function deleteAttendanceRecord($id) {
        try {
            $stmt = $this->conn->prepare("CALL deleteAttendanceRecord(?)");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Delete attendance record error: " . $e->getMessage());
            throw new Exception("Unable to delete attendance record. Please try again.");
        }
    }

    public function checkInAttendance($name, $time_in, $employee_number = null, $position = null) {
        try {
            $stmt = $this->conn->prepare("CALL createAttendanceCheckIn(?, ?, ?, ?)");
            return $stmt->execute([$name, $time_in, $employee_number, $position]);
        } catch (PDOException $e) {
            error_log("Attendance check-in error: " . $e->getMessage());
            throw new Exception("Unable to record check-in. Please try again.");
        }
    }

    public function checkOutAttendance($attendance_id, $time_out) {
        try {
            $stmt = $this->conn->prepare("CALL updateAttendanceCheckOut(?, ?)");
            return $stmt->execute([$attendance_id, $time_out]);
        } catch (PDOException $e) {
            error_log("Attendance check-out error: " . $e->getMessage());
            throw new Exception("Unable to record check-out. Please try again.");
        }
    }

    public function getLastAttendanceRecord($name) {
        try {
            $stmt = $this->conn->prepare("CALL getLastAttendance(?)");
            $stmt->execute([$name]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
            return $record;
        } catch (PDOException $e) {
            error_log("Get last attendance error: " . $e->getMessage());
            return null;
        }
    }

    public function getAttendanceLogs($fromDate = null, $toDate = null, $name = null) {
        try {
            $stmt = $this->conn->prepare("CALL getAttendanceLogs(?, ?, ?)");
            $stmt->execute([$fromDate, $toDate, $name]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
            return $records;
        } catch (PDOException $e) {
            error_log("Get attendance logs error: " . $e->getMessage());
            // Fallback to getAllAttendanceLogs
            try {
                $stmt = $this->conn->prepare("CALL getAllAttendanceLogs()");
                $stmt->execute();
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (method_exists($stmt, 'closeCursor')) {
                    $stmt->closeCursor();
                }
                // Apply filters manually if stored procedure failed
                if ($fromDate || $toDate || $name) {
                    $filtered = [];
                    foreach ($records as $record) {
                        $recordDate = date('Y-m-d', strtotime($record['time_in']));
                        $match = true;
                        if ($fromDate && $recordDate < $fromDate) $match = false;
                        if ($toDate && $recordDate > $toDate) $match = false;
                        if ($name && stripos($record['name'], $name) === false) $match = false;
                        if ($match) $filtered[] = $record;
                    }
                    return $filtered;
                }
                return $records;
            } catch (PDOException $e2) {
                error_log("Fallback attendance logs query error: " . $e2->getMessage());
                return [];
            }
        }
    }

    public function getAllAttendanceLogs() {
        try {
            $stmt = $this->conn->prepare("CALL getAllAttendanceLogs()");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
            return $records;
        } catch (PDOException $e) {
            error_log("Get all attendance logs error: " . $e->getMessage());
            return [];
        }
    }

    public function archiveOldAttendanceRecords() {
        try {
            $stmt = $this->conn->prepare("CALL archiveOldAttendanceRecords()");
            $stmt->execute();
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Archive old attendance records error: " . $e->getMessage());
            return false;
        }
    }

    public function getUniqueEmployees() {
        try {
            $stmt = $this->conn->prepare("CALL getUniqueEmployees()");
            $stmt->execute();
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
            return $employees;
        } catch (PDOException $e) {
            error_log("Get unique employees error: " . $e->getMessage());
            return [];
        }
    }

    public function updateEmployeeProfile($name, $newName, $employeeNumber, $position) {
        try {
            $stmt = $this->conn->prepare("CALL updateEmployeeProfile(?, ?, ?, ?)");
            $result = $stmt->execute([$name, $newName, $employeeNumber, $position]);
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Update employee profile error: " . $e->getMessage());
            throw new Exception("Unable to update employee profile. Please try again.");
        }
    }
}
