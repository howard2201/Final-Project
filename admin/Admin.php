<?php
require_once "../config/Database.php";

class Admin {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM admins WHERE email=:email");
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user && password_verify($password, $user['password'])){
            return $user;
        }
        return false;
    }

    public function getRequests() {
        $stmt = $this->conn->prepare("SELECT r.*, res.full_name FROM requests r JOIN residents res ON r.resident_id = res.id ORDER BY r.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRequestStatus($request_id, $status) {
        $stmt = $this->conn->prepare("UPDATE requests SET status=:status WHERE id=:id");
        return $stmt->execute(['status'=>$status, 'id'=>$request_id]);
    }
}
