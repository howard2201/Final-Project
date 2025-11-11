<?php
require_once "../config/Database.php";

class Resident {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function register($full_name, $email, $password, $id_file, $proof_file) {
        try {
            $stmt = $this->conn->prepare("CALL checkResidentEmailExists(?)");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) return false;

            // Use SHA-256 hashing
            $hash = hash('sha256', $password);

            // Close cursor before next procedure call
            $stmt->closeCursor();

            $stmt = $this->conn->prepare("CALL registerResident(?, ?, ?, ?, ?)");
            return $stmt->execute([
                $full_name,
                $email,
                $hash,
                $id_file,
                $proof_file
            ]);
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            throw new Exception("Unable to complete registration. Please try again.");
        }
    }

    public function login($email, $password) {
        try {
            $stmt = $this->conn->prepare("CALL loginResident(?)");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check both SHA-256 and bcrypt for backward compatibility
            $passwordHash = hash('sha256', $password);
            if ($user && ($user['resident_password'] === $passwordHash || password_verify($password, $user['resident_password']))) {
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            throw new Exception("Unable to process login. Please try again.");
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
