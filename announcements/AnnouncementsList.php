<?php
require_once "../config/Database.php";

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT * FROM announcements ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements</title>
<link rel="stylesheet" href="../css/styling.css">
</head>
<body>
<main class="container">
<h2>Announcements</h2>
<div class="cards">
<?php
if($announcements){
    foreach($announcements as $a){
        echo "<div class='card'>
                <h4>{$a['title']}</h4>
                <p>{$a['body']}</p>
              </div>";
    }
}else{
    echo "<p class='muted'>No announcements available.</p>";
}
?>
</div>
</main>
<script src="../js/app.js"></script>
</body>
</html>
