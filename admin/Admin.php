<?php
require_once "../config/Database.php";

class Admin {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("CALL loginAdmin(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check both SHA-256 and bcrypt for backward compatibility
            $passwordHash = hash('sha256', $password);
            if($user && ($user['password'] === $passwordHash || password_verify($password, $user['password']))){
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
}
