<?php
require_once '../config/Database.php';

class Request {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create($resident_id, $type, $details, $id_file, $residency_file) {
        $stmt = $this->conn->prepare("CALL createRequest(?, ?, ?, ?, ?)");
        $stmt->execute([$resident_id, $type, $details, $id_file, $residency_file]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['last_id'];
    }

    public function getByResident($resident_id) {
        $stmt = $this->conn->prepare("CALL getRequestsByResident(?)");
        $stmt->execute([$resident_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $stmt = $this->conn->prepare("CALL getAllRequests()");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->conn->prepare("CALL updateRequestStatus(?, ?)");
        $stmt->execute([$status, $id]);
    }
}
?>
