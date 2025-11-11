<?php
// Include your database class
include '../config/Database.php'; // Adjust path according to your folder structure

// Get PDO connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Check if Arduino sent a name
if (isset($_POST['name'])) {
    $name = trim($_POST['name']);

    // Check the last record for this user
    $stmt = $conn->prepare("SELECT id, time_in, time_out FROM attendance WHERE name = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $now = date("Y-m-d H:i:s");
    $response = "";

    if ($row) {
        if (is_null($row['time_out'])) {
            // Check-Out
            $update = $conn->prepare("UPDATE attendance SET time_out = ? WHERE id = ?");
            $success = $update->execute([$now, $row['id']]);
            $response = $success ? "Check-Out" : "Database Error";
        } else {
            // New Check-In
            $insert = $conn->prepare("INSERT INTO attendance (name, time_in) VALUES (?, ?)");
            $success = $insert->execute([$name, $now]);
            $response = $success ? "Check-In" : "Database Error";
        }
    } else {
        // First Check-In
        $insert = $conn->prepare("INSERT INTO attendance (name, time_in) VALUES (?, ?)");
        $success = $insert->execute([$name, $now]);
        $response = $success ? "Check-In" : "Database Error";
    }

    echo $response;
} else {
    echo "No name provided";
}
?>