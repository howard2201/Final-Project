<?php
require_once __DIR__ . '/../config/Database.php';

class Resident {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function register($full_name, $email, $password, $id_file, $proof_file) {
        $stmt = $this->conn->prepare("INSERT INTO residents (full_name,email,password,id_file,proof_file) VALUES (?,?,?,?,?)");
        $stmt->execute([$full_name, $email, password_hash($password, PASSWORD_DEFAULT), $id_file, $proof_file]);
        return $this->conn->lastInsertId();
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM residents WHERE email=?");
        $stmt->execute([$email]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($res && password_verify($password, $res['password'])) return $res;
        return false;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM residents WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
