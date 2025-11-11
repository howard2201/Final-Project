<?php
// Include your database class
include '../config/Database.php'; // Adjust path according to your folder structure

// Get PDO connection
$database = Database::getInstance();
$conn = $database->getConnection();

// Check if Arduino sent a name
if (isset($_POST['name'])) {
    $name = trim($_POST['name']);
    $now = date("Y-m-d H:i:s");
    $response = "";

    try {
        // Check the last record for this user using stored procedure
        $stmt = $conn->prepare("CALL getLastAttendance(?)");
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            if (is_null($row['time_out'])) {
                // Check-Out
                $update = $conn->prepare("CALL updateAttendanceCheckOut(?, ?)");
                $success = $update->execute([$row['id'], $now]);
                $update->closeCursor();
                $response = $success ? "Check-Out" : "Database Error";
            } else {
                // New Check-In
                $insert = $conn->prepare("CALL createAttendanceCheckIn(?, ?)");
                $success = $insert->execute([$name, $now]);
                $insert->closeCursor();
                $response = $success ? "Check-In" : "Database Error";
            }
        } else {
            // First Check-In
            $insert = $conn->prepare("CALL createAttendanceCheckIn(?, ?)");
            $success = $insert->execute([$name, $now]);
            $insert->closeCursor();
            $response = $success ? "Check-In" : "Database Error";
        }
    } catch (Exception $e) {
        error_log("Attendance system error: " . $e->getMessage());
        $response = "System Error";
    }

    echo $response;
} else {
    echo "No name provided";
}
?>