<?php
require_once '../config/Database.php';

class Request {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create($resident_id, $type, $details, $id_file, $residency_file) {
        $stmt = $this->conn->prepare("INSERT INTO requests (resident_id,type,details,id_file,residency_file,status) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$resident_id, $type, $details, $id_file, $residency_file, 'Pending']);
        return $this->conn->lastInsertId();
    }

    public function getByResident($resident_id) {
        $stmt = $this->conn->prepare("SELECT * FROM requests WHERE resident_id=? ORDER BY id DESC");
        $stmt->execute([$resident_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $stmt = $this->conn->query("SELECT r.*, res.full_name FROM requests r JOIN residents res ON r.resident_id=res.id ORDER BY r.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("UPDATE requests SET status=? WHERE id=?");
        $stmt->execute([$status, $id]);
    }
}
?>
