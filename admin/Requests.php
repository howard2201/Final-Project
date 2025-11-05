<?php
require_once "Admin.php";
$admin = new Admin();

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])){
    $admin->updateRequestStatus($_POST['request_id'], $_POST['status']);
    header("Location: AdminDashboard.php");
    exit;
}
