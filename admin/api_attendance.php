<?php
/**
 * API Endpoint for Attendance Data
 * Provides real-time attendance information in JSON format
 * Requires admin authentication
 */

session_start();

// Set proper headers for API
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Verify authentication
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access. Please login first.',
            'data' => []
        ]);
        exit;
    }

    require_once "../config/Database.php";

    // Get database connection
    $database = Database::getInstance();
    $conn = $database->getConnection();

    // Validate and sanitize input
    $searchDate = isset($_GET['search_date']) ? trim($_GET['search_date']) : '';

    // Validate date format if provided
    if (!empty($searchDate)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $searchDate)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date format. Expected YYYY-MM-DD.',
                'data' => []
            ]);
            exit;
        }

        // Validate if date is parseable
        $dateObj = DateTime::createFromFormat('Y-m-d', $searchDate);
        if ($dateObj === false || $dateObj->format('Y-m-d') !== $searchDate) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid date provided.',
                'data' => []
            ]);
            exit;
        }
    }

    // Fetch attendance records using stored procedure
    $stmt = $conn->prepare("CALL getAttendanceForAPI()");
    $stmt->execute();
    $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Close the cursor for the stored procedure
    if (method_exists($stmt, 'closeCursor')) {
        $stmt->closeCursor();
    }

    // Process records to consolidate by person and date
    $uniqueRecords = [];
    $latestTimeInPerPerson = [];

    foreach ($allRecords as $record) {
        if (!empty($record['time_in'])) {
            try {
                $recordDateTime = new DateTime($record['time_in']);
                $recordDate = $recordDateTime->format('Y-m-d');
                $personDate = $record['name'] . '_' . $recordDate;

                // Track the latest time_in for each person per day
                if (!isset($latestTimeInPerPerson[$personDate])) {
                    $latestTimeInPerPerson[$personDate] = $record;
                } else {
                    $existingTimeIn = new DateTime($latestTimeInPerPerson[$personDate]['time_in']);
                    $newTimeIn = new DateTime($record['time_in']);
                    if ($newTimeIn > $existingTimeIn) {
                        $latestTimeInPerPerson[$personDate] = $record;
                    }
                }

                // Consolidate records
                if (!isset($uniqueRecords[$personDate])) {
                    $uniqueRecords[$personDate] = $record;
                } else {
                    // Update with latest time_out if it exists
                    if (!empty($record['time_out']) && empty($uniqueRecords[$personDate]['time_out'])) {
                        $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
                    } elseif (!empty($record['time_out']) && !empty($uniqueRecords[$personDate]['time_out'])) {
                        // Keep the later time_out
                        $existingTimeOut = new DateTime($uniqueRecords[$personDate]['time_out']);
                        $newTimeOut = new DateTime($record['time_out']);
                        if ($newTimeOut > $existingTimeOut) {
                            $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Date parsing error for record ID {$record['id']}: " . $e->getMessage());
                continue;
            }
        }
    }

    // Add latest time_in info to each unique record for status determination
    foreach ($uniqueRecords as $key => $record) {
        if (isset($latestTimeInPerPerson[$key])) {
            $uniqueRecords[$key]['latest_time_in'] = $latestTimeInPerPerson[$key]['time_in'];
            $uniqueRecords[$key]['latest_time_out'] = !empty($latestTimeInPerPerson[$key]['time_out']) ? $latestTimeInPerPerson[$key]['time_out'] : null;
        }
    }

    $filteredRecords = array_values($uniqueRecords);

    // Apply date filter if provided
    if (!empty($searchDate)) {
        $dateFilteredRecords = [];
        foreach ($filteredRecords as $record) {
            if (!empty($record['time_in'])) {
                try {
                    $recordDateTime = new DateTime($record['time_in']);
                    $recordDate = $recordDateTime->format('Y-m-d');
                    if ($recordDate === $searchDate) {
                        $dateFilteredRecords[] = $record;
                    }
                } catch (Exception $e) {
                    error_log("Date filtering error: " . $e->getMessage());
                    continue;
                }
            }
        }
        $filteredRecords = $dateFilteredRecords;
    }

    // Build response array with calculated data
    $response = [];
    foreach ($filteredRecords as $record) {
        try {
            $id = intval($record['id']);
            $name = htmlspecialchars($record['name'], ENT_QUOTES, 'UTF-8');

            // Get date from time_in
            $date = 'N/A';
            if (!empty($record['time_in'])) {
                try {
                    $dateTime = new DateTime($record['time_in']);
                    $date = $dateTime->format('M d, Y');
                } catch (Exception $e) {
                    error_log("Date formatting error: " . $e->getMessage());
                }
            }

            // Determine status based on latest time_in
            $latestTimeIn = (isset($record['latest_time_in']) && !empty($record['latest_time_in'])) ? $record['latest_time_in'] : $record['time_in'];
            $latestTimeOut = (isset($record['latest_time_out']) && !empty($record['latest_time_out'])) ? $record['latest_time_out'] : null;
            $status = (!empty($latestTimeIn) && is_null($latestTimeOut)) ? 'Online' : 'Offline';

            $response[] = [
                'id' => $id,
                'name' => $name,
                'date' => $date,
                'status' => $status
            ];
        } catch (Exception $e) {
            error_log("Record processing error: " . $e->getMessage());
            continue;
        }
    }

    // Return successful response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Attendance records retrieved successfully',
        'count' => count($response),
        'data' => $response
    ]);

} catch (PDOException $e) {
    error_log("Database error in api_attendance.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again later.',
        'data' => []
    ]);
} catch (Exception $e) {
    error_log("General error in api_attendance.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.',
        'data' => []
    ]);
}
?>
