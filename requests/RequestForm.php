<?php
session_start();
require_once "../config/Database.php";
require_once "../residents/Resident.php";

if(!isset($_SESSION['resident_id'])){
    header("Location: ../index.php");
    exit;
}

$resident = new Resident();
$residentId = $_SESSION['resident_id'];

$db = new Database();
$conn = $db->connect();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $type = $_POST['requestType'];
    $details = $_POST['details'];

    $idFile = $_FILES['uploadId']['name'];
    $residencyFile = $_FILES['uploadResidency']['name'];

    move_uploaded_file($_FILES['uploadId']['tmp_name'], "../uploads/$idFile");
    move_uploaded_file($_FILES['uploadResidency']['tmp_name'], "../uploads/$residencyFile");

    $stmt = $conn->prepare("INSERT INTO requests (resident_id, type, details, id_file, residency_file, status, created_at) VALUES (:resident_id, :type, :details, :id_file, :residency_file, 'Pending', NOW())");
    $stmt->execute([
        'resident_id'=>$residentId,
        'type'=>$type,
        'details'=>$details,
        'id_file'=>$idFile,
        'residency_file'=>$residencyFile
    ]);

    header("Location: ../residents/Dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Request Service</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<main class="container request-page">
<div class="card form-card">
<h2>Request a Document</h2>
<form method="POST" enctype="multipart/form-data" id="requestForm">

<div class="step" data-step="1">
<label>Full Name
<input type="text" value="<?= $_SESSION['resident_name'] ?>" readonly>
</label>

<label>Type of Request
<select name="requestType" required>
<option value="">Select Request Type</option>
<option>Barangay Certificate</option>
<option>Barangay Clearance</option>
<option>Business Permits and Licenses</option>
<option>Applying for a Passport</option>
<option>Clearance Certificates</option>
<option>Employment</option>
<option>Government Documents Application</option>
<option>Identification</option>
<option>Proof of Address</option>
</select>
</label>

<div class="actions">
<button type="button" id="next1" class="btn">Next</button>
</div>
</div>

<div class="step" data-step="2" style="display:none">
<label>Purpose / Details
<textarea name="details" rows="4" required></textarea>
</label>

<label>Upload Valid ID (Required)
<input type="file" name="uploadId" accept=".jpg,.jpeg,.png,.pdf" required>
</label>

<label>Barangay Certificate of Residency (Required)
<input type="file" name="uploadResidency" accept=".jpg,.jpeg,.png,.pdf" required>
</label>

<div class="actions">
<button type="button" id="back2" class="btn outline">Back</button>
<button type="submit" class="btn">Submit Request</button>
</div>
</div>

</form>
</div>
</main>

<script src="../js/app.js"></script>
<script>
let step = 1;
const steps = document.querySelectorAll('.step');
const showStep = n => {
    steps.forEach(s=>s.style.display = s.dataset.step == n ? 'block' : 'none');
}
document.getElementById('next1').onclick = ()=>{step=2; showStep(step);}
document.getElementById('back2').onclick = ()=>{step=1; showStep(step);}
</script>
</body>
</html>
