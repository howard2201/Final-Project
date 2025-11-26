<?php
/**
 * Automatic Attendance Archiving Script
 * 
 * This script archives old attendance records (from previous days) 
 * to the attendance_logs table.
 * 
 * This can be run:
 * 1. Manually via browser: http://your-domain/admin/archive_attendance.php
 * 2. Via cron job: Add to crontab to run daily (e.g., at 1:00 AM)
 *    Example: 0 1 * * * /usr/bin/php /path/to/admin/archive_attendance.php
 * 3. Via Windows Task Scheduler (for XAMPP on Windows)
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Include database connection
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Admin.php';

// Set headers for JSON response (if accessed via browser)
header('Content-Type: application/json');

try {
    $admin = new Admin();
    
    // Archive old records
    $result = $admin->archiveOldAttendanceRecords();
    
    $response = [
        'success' => true,
        'message' => 'Attendance records archived successfully',
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => 'Asia/Manila'
    ];
    
    // Log the operation
    error_log("Attendance archiving completed at " . date('Y-m-d H:i:s'));
    
    // If accessed via CLI, output to console
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] " . $response['message'] . "\n";
    } else {
        // If accessed via browser, output JSON
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    $errorMessage = "Error archiving attendance records: " . $e->getMessage();
    error_log($errorMessage);
    
    $response = [
        'success' => false,
        'message' => $errorMessage,
        'timestamp' => date('Y-m-d H:i:s'),
        'timezone' => 'Asia/Manila'
    ];
    
    // If accessed via CLI, output to console
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $errorMessage . "\n";
        exit(1);
    } else {
        // If accessed via browser, output JSON
        http_response_code(500);
        echo json_encode($response, JSON_PRETTY_PRINT);
    }
    exit(1);
}

