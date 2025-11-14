<?php
require_once "../config/Database.php";

class Admin {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function login($email, $password) {
        // Try stored procedure first (if present). If it fails or returns nothing,
        // fall back to a direct SELECT for compatibility across environments.
        $user = false;
        try {
            $stmt = $this->conn->prepare("CALL loginAdmin(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Some PDO drivers require closing the cursor after calling a stored procedure
            if (method_exists($stmt, 'closeCursor')) {
                $stmt->closeCursor();
            }
        } catch (PDOException $e) {
            error_log("Admin login CALL error: " . $e->getMessage());
            // continue to fallback below
            $user = false;
        }

        // Fallback to direct SELECT if stored procedure not available or returned nothing
        if (!$user) {
            try {
                $stmt = $this->conn->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Admin login SELECT error: " . $e->getMessage());
                throw new Exception("Unable to process login. Please try again.");
            }
        }

        if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
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

    // Fetch all attendance records from the attendance table
    public function getAttendanceRecords() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM attendance ORDER BY time_in DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get attendance records error: " . $e->getMessage());
            return [];
        }
    }

    // Delete an attendance record by ID
    public function deleteAttendanceRecord($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM attendance WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Delete attendance record error: " . $e->getMessage());
            throw new Exception("Unable to delete attendance record. Please try again.");
        }
    }
}
