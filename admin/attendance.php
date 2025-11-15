<?php
/**
 * Attendance Check-In/Out System
 * Handles real-time attendance logging via Arduino or manual input
 */

session_start();

// Set proper headers
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    require_once '../config/Database.php';

    // Get PDO connection
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo "Method Not Allowed";
        exit;
    }

    // Check if name is provided
    if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
        http_response_code(400);
        echo "No name provided";
        exit;
    }

    // Sanitize and validate input
    $name = trim($_POST['name']);
    
    // Validate name length
    if (strlen($name) < 2 || strlen($name) > 255) {
        http_response_code(400);
        echo "Invalid name length";
        exit;
    }

    // Validate name contains only letters, spaces, and basic characters
    if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/u', $name)) {
        http_response_code(400);
        echo "Invalid name format";
        exit;
    }

    $now = date("Y-m-d H:i:s");
    $response = "";

    try {
        // Check the last record for this user using stored procedure
        $stmt = $conn->prepare("CALL getLastAttendance(?)");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row) {
            // Validate the row has required fields
            if (!isset($row['id']) || !isset($row['time_out'])) {
                throw new Exception("Invalid record structure from database");
            }

            if (is_null($row['time_out'])) {
                // Check-Out
                $update = $conn->prepare("CALL updateAttendanceCheckOut(?, ?)");
                
                if (!$update) {
                    throw new Exception("Failed to prepare checkout statement");
                }

                $success = $update->execute([$row['id'], $now]);
                $update->closeCursor();
                
                if ($success) {
                    http_response_code(200);
                    $response = "Check-Out";
                } else {
                    throw new Exception("Failed to execute checkout update");
                }
            } else {
                // New Check-In
                $insert = $conn->prepare("CALL createAttendanceCheckIn(?, ?)");
                
                if (!$insert) {
                    throw new Exception("Failed to prepare checkin statement");
                }

                $success = $insert->execute([$name, $now]);
                $insert->closeCursor();
                
                if ($success) {
                    http_response_code(201);
                    $response = "Check-In";
                } else {
                    throw new Exception("Failed to execute checkin insert");
                }
            }
        } else {
            // First Check-In
            $insert = $conn->prepare("CALL createAttendanceCheckIn(?, ?)");
            
            if (!$insert) {
                throw new Exception("Failed to prepare checkin statement");
            }

            $success = $insert->execute([$name, $now]);
            $insert->closeCursor();
            
            if ($success) {
                http_response_code(201);
                $response = "Check-In";
            } else {
                throw new Exception("Failed to execute checkin insert");
            }
        }

    } catch (PDOException $e) {
        error_log("Attendance PDO error: " . $e->getMessage());
        http_response_code(500);
        $response = "Database Error";
    } catch (Exception $e) {
        error_log("Attendance system error: " . $e->getMessage());
        http_response_code(500);
        $response = "System Error";
    }

    echo $response;

} catch (Exception $e) {
    error_log("Critical attendance system error: " . $e->getMessage());
    http_response_code(500);
    echo "System Error";
}
?>