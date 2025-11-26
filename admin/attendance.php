<?php
/**
 * Attendance Check-In/Out System
 * Handles real-time attendance logging via Arduino or manual input
 */

session_start();

// Set timezone to Asia/Manila (Philippines)
date_default_timezone_set('Asia/Manila');

// Set proper headers
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    require_once '../config/Database.php';

    // Get PDO connection
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // Validate database connection
    if ($conn === null) {
        error_log("Attendance system error: Database connection is null");
        http_response_code(500);
        echo "System Error";
        exit;
    }

    // Handle employee number backfill request (GET request with special parameter)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['backfill_employee_numbers']) && $_GET['backfill_employee_numbers'] === '1') {
        try {
            // Get all unique names from attendance table with NULL employee_number
            $stmt = $conn->prepare("
                SELECT DISTINCT name 
                FROM attendance 
                WHERE (employee_number IS NULL OR employee_number = '')
                ORDER BY name
            ");
            $stmt->execute();
            $names = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Also check attendance_logs
            $logStmt = $conn->prepare("
                SELECT DISTINCT name 
                FROM attendance_logs 
                WHERE (employee_number IS NULL OR employee_number = '')
                ORDER BY name
            ");
            $logStmt->execute();
            $logNames = $logStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine and get unique names
            $allNames = [];
            foreach ($names as $nameRow) {
                $allNames[$nameRow['name']] = true;
            }
            foreach ($logNames as $nameRow) {
                $allNames[$nameRow['name']] = true;
            }
            
            $count = 0;
            foreach (array_keys($allNames) as $name) {
                // Generate unique employee number
                $genStmt = $conn->prepare("CALL generateUniqueEmployeeNumber(?, @emp_num)");
                $genStmt->execute([$name]);
                $genStmt->closeCursor();
                
                // Get the generated employee number
                $resultStmt = $conn->query("SELECT @emp_num as employee_number");
                $result = $resultStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result && isset($result['employee_number']) && !empty($result['employee_number'])) {
                    $employeeNumber = $result['employee_number'];
                    
                    // Update all attendance records for this name
                    $updateStmt = $conn->prepare("
                        UPDATE attendance 
                        SET employee_number = ? 
                        WHERE name = ? AND (employee_number IS NULL OR employee_number = '')
                    ");
                    $updateStmt->execute([$employeeNumber, $name]);
                    
                    // Update all attendance_logs records for this name
                    $updateLogsStmt = $conn->prepare("
                        UPDATE attendance_logs 
                        SET employee_number = ? 
                        WHERE name = ? AND (employee_number IS NULL OR employee_number = '')
                    ");
                    $updateLogsStmt->execute([$employeeNumber, $name]);
                    
                    $count++;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Assigned employee numbers to {$count} employees.",
                'count' => $count
            ]);
            exit;
        } catch (Exception $e) {
            error_log("Employee number backfill error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error assigning employee numbers: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Validate request method for normal attendance operations
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
    $cleanName = iconv('UTF-8', 'UTF-8//IGNORE', $name);
    if ($cleanName === false) {
        $cleanName = '';
    }
    $name = preg_replace('/[\x00-\x1F\x7F]/', '', $cleanName);
    $name = preg_replace('/[^\p{L}\s\-\'\.]/u', '', $name);

    if ($name === '') {
        http_response_code(400);
        echo "No name provided";
        exit;
    }

    // Validate name length using multibyte-safe check
    $nameLength = mb_strlen($name, 'UTF-8');
    if ($nameLength < 2 || $nameLength > 255) {
        http_response_code(400);
        echo "Invalid name length";
        exit;
    }

    // Validate name contains only letters, spaces, and basic characters
    if (!preg_match('/^[\p{L}\s\-\'\.]+$/u', $name)) {
        http_response_code(400);
        echo "Invalid name format";
        exit;
    }

    $now = date("Y-m-d H:i:s");
    $today = date("Y-m-d");
    $response = "";

    try {
        // Check the last record for this user using stored procedure
        $stmt = $conn->prepare("CALL getLastAttendance(?)");
        
        if ($stmt === false) {
            $errorInfo = $conn->errorInfo();
            error_log("Attendance system error: Failed to prepare getLastAttendance statement. Error: " . print_r($errorInfo, true));
            throw new Exception("Failed to prepare statement: " . ($errorInfo[2] ?? 'Unknown error'));
        }

        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        // Get employee_number and position from last record if available
        $employeeNumber = null;
        $position = null;
        if ($row && isset($row['employee_number']) && !empty($row['employee_number'])) {
            $employeeNumber = $row['employee_number'];
        }
        if ($row && isset($row['position']) && !empty($row['position'])) {
            $position = $row['position'];
        }

        // If employee_number not found in current attendance, check attendance_logs
        if (empty($employeeNumber)) {
            try {
                $logStmt = $conn->prepare("CALL getEmployeeInfoFromLogs(?)");
                if ($logStmt !== false) {
                    $logStmt->execute([$name]);
                    $logRow = $logStmt->fetch(PDO::FETCH_ASSOC);
                    $logStmt->closeCursor();
                    
                    if ($logRow) {
                        if (isset($logRow['employee_number']) && !empty($logRow['employee_number'])) {
                            $employeeNumber = $logRow['employee_number'];
                        }
                        if (isset($logRow['position']) && !empty($logRow['position'])) {
                            $position = $logRow['position'];
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error but don't fail the check-in if logs lookup fails
                error_log("Error fetching employee info from logs: " . $e->getMessage());
            }
        }

        // If still no employee_number, generate a unique one
        if (empty($employeeNumber)) {
            try {
                $genStmt = $conn->prepare("CALL generateUniqueEmployeeNumber(?, @emp_num)");
                if ($genStmt !== false) {
                    $genStmt->execute([$name]);
                    $genStmt->closeCursor();
                    
                    // Get the generated employee number
                    $resultStmt = $conn->query("SELECT @emp_num as employee_number");
                    $result = $resultStmt->fetch(PDO::FETCH_ASSOC);
                    if ($result && isset($result['employee_number']) && !empty($result['employee_number'])) {
                        $employeeNumber = $result['employee_number'];
                        
                        // Update all existing records for this person with the new employee number
                        try {
                            $updateStmt = $conn->prepare("
                                UPDATE attendance 
                                SET employee_number = ? 
                                WHERE name = ? AND (employee_number IS NULL OR employee_number = '')
                            ");
                            $updateStmt->execute([$employeeNumber, $name]);
                            
                            $updateLogsStmt = $conn->prepare("
                                UPDATE attendance_logs 
                                SET employee_number = ? 
                                WHERE name = ? AND (employee_number IS NULL OR employee_number = '')
                            ");
                            $updateLogsStmt->execute([$employeeNumber, $name]);
                        } catch (Exception $e) {
                            // Log but don't fail - the new record will still have the employee number
                            error_log("Error updating existing records with employee number: " . $e->getMessage());
                        }
                    }
                }
            } catch (Exception $e) {
                // Log error but don't fail the check-in if generation fails
                error_log("Error generating employee number: " . $e->getMessage());
            }
        }

        if ($row) {
            // Validate the row has required fields
            // Use array_key_exists instead of isset because time_out can be NULL
            if (
                !array_key_exists('id', $row) ||
                !array_key_exists('time_out', $row) ||
                !array_key_exists('time_in', $row)
            ) {
                throw new Exception("Invalid record structure from database");
            }

            $lastAttendanceDate = $row['time_in'] ? date("Y-m-d", strtotime($row['time_in'])) : null;

            if (!is_null($row['time_out']) && $lastAttendanceDate === $today) {
                http_response_code(429);
                echo "Daily limit reached";
                exit;
            }

            if (is_null($row['time_out'])) {
                // Check-Out
                $update = $conn->prepare("CALL updateAttendanceCheckOut(?, ?)");
                
                if ($update === false) {
                    $errorInfo = $conn->errorInfo();
                    error_log("Attendance system error: Failed to prepare updateAttendanceCheckOut statement. Error: " . print_r($errorInfo, true));
                    throw new Exception("Failed to prepare checkout statement: " . ($errorInfo[2] ?? 'Unknown error'));
                }

                $success = $update->execute([$row['id'], $now]);
                $update->closeCursor();
                
                if ($success) {
                    http_response_code(200);
                    $response = "Check-Out";
                } else {
                    $errorInfo = $update->errorInfo();
                    error_log("Attendance system error: Failed to execute checkout update. Error: " . print_r($errorInfo, true));
                    throw new Exception("Failed to execute checkout update: " . ($errorInfo[2] ?? 'Unknown error'));
                }
            } else {
                // New Check-In
                $insert = $conn->prepare("CALL createAttendanceCheckIn(?, ?, ?, ?)");
                
                if ($insert === false) {
                    $errorInfo = $conn->errorInfo();
                    error_log("Attendance system error: Failed to prepare createAttendanceCheckIn statement. Error: " . print_r($errorInfo, true));
                    throw new Exception("Failed to prepare checkin statement: " . ($errorInfo[2] ?? 'Unknown error'));
                }

                $success = $insert->execute([$name, $now, $employeeNumber, $position]);
                $insert->closeCursor();
                
                if ($success) {
                    http_response_code(201);
                    $response = "Check-In";
                } else {
                    $errorInfo = $insert->errorInfo();
                    error_log("Attendance system error: Failed to execute checkin insert. Error: " . print_r($errorInfo, true));
                    throw new Exception("Failed to execute checkin insert: " . ($errorInfo[2] ?? 'Unknown error'));
                }
            }
        } else {
            // First Check-In
            $insert = $conn->prepare("CALL createAttendanceCheckIn(?, ?, ?, ?)");
            
            if ($insert === false) {
                $errorInfo = $conn->errorInfo();
                error_log("Attendance system error: Failed to prepare createAttendanceCheckIn statement (first check-in). Error: " . print_r($errorInfo, true));
                throw new Exception("Failed to prepare checkin statement: " . ($errorInfo[2] ?? 'Unknown error'));
            }

            $success = $insert->execute([$name, $now, $employeeNumber, $position]);
            $insert->closeCursor();
            
            if ($success) {
                http_response_code(201);
                $response = "Check-In";
            } else {
                $errorInfo = $insert->errorInfo();
                error_log("Attendance system error: Failed to execute checkin insert (first check-in). Error: " . print_r($errorInfo, true));
                throw new Exception("Failed to execute checkin insert: " . ($errorInfo[2] ?? 'Unknown error'));
            }
        }

    } catch (PDOException $e) {
        error_log("Attendance PDO error: " . $e->getMessage() . " | Code: " . $e->getCode() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
        error_log("PDO Error Info: " . print_r($conn->errorInfo(), true));
        http_response_code(500);
        $response = "Database Error";
    } catch (Exception $e) {
        error_log("Attendance system error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        $response = "System Error";
    }

    echo $response;

} catch (Exception $e) {
    error_log("Critical attendance system error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo "System Error";
}
?>