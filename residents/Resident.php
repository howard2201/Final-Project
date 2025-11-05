<?php
require_once "../config/Database.php";

class Resident {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function register($full_name, $email, $password, $id_file, $proof_file) {
        $stmt = $this->conn->prepare("SELECT id FROM residents WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->rowCount() > 0) return false;

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->conn->prepare(
            "INSERT INTO residents (full_name, email, password, id_file, proof_file)
             VALUES (:full_name, :email, :password, :id_file, :proof_file)"
        );
        return $stmt->execute([
            'full_name'=>$full_name,
            'email'=>$email,
            'password'=>$hash,
            'id_file'=>$id_file,
            'proof_file'=>$proof_file
        ]);
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM residents WHERE email=:email");
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function getRequests($resident_id) {
        $stmt = $this->conn->prepare("SELECT * FROM requests WHERE resident_id=:id ORDER BY created_at DESC");
        $stmt->execute(['id'=>$resident_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
