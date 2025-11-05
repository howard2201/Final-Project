<?php
require_once '../config/Database.php';

class Admin {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM admins WHERE email=?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if($admin && password_verify($password, $admin['password'])) return $admin;
        return false;
    }
}
?>
