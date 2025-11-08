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
            if($user && password_verify($password, $user['password'])){
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
}
