<?php
require_once "../config/Database.php";

class Admin {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function login($email, $password) {
        try {
            // Use stored procedure for admin login
            $stmt = $this->conn->prepare("CALL loginAdmin(?)");
            $stmt->execute([$email]);
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

    public function checkInAttendance($name, $time_in) {
        try {
            $stmt = $this->conn->prepare("CALL createAttendanceCheckIn(?, ?)");
            return $stmt->execute([$name, $time_in]);
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

    public function updateAttendanceRecord($id, $name, $time_in, $time_out) {
        try {
            // Direct update using prepared statement for flexibility
            $stmt = $this->conn->prepare("UPDATE attendance SET name = ?, time_in = ?, time_out = ? WHERE id = ?");
            return $stmt->execute([$name, $time_in, $time_out, $id]);
        } catch (PDOException $e) {
            error_log("Update attendance record error: " . $e->getMessage());
            throw new Exception("Unable to update attendance record. Please try again.");
        }
    }
}
