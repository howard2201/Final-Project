<?php
require_once "../config/Database.php";

// Get database connection
$conn = Database::getInstance()->getConnection();

$stmt = $conn->prepare("CALL getAllAnnouncements()");
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/headerinner.php'; ?>

<main class="container">
<h2>Announcements</h2>
<div class="cards">
<?php
if($announcements){
    foreach($announcements as $a){
        $title = htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8');
        $body = htmlspecialchars($a['body'], ENT_QUOTES, 'UTF-8');

        echo "<div class='card'>
                <h4>{$title}</h4>
                <p>{$body}</p>
              </div>";
    }
}else{
    echo "<p class='muted'>No announcements available.</p>";
}
?>
</div>
</main>
<script src="../js/appear.js"></script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
