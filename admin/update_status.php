<?php
session_start();
if(!isset($_SESSION['admin'])) header("Location: ../auth/login.php");
require_once '../classes/Request.php';
$req = new Request();

if(isset($_GET['id']) && isset($_GET['status'])){
    $id = $_GET['id'];
    $status = $_GET['status'];
    $req->updateStatus($id,$status);
}
header("Location: admin.php");
exit;
