<?php
// API endpoint for real-time attendance updates
require_once "auth_check.php";
require_once "Admin.php";

$admin = new Admin();
$attendanceRecords = $admin->getAttendanceRecords();

// Process records to get consolidated view with latest status
$uniqueRecords = [];
$latestTimeInPerPerson = [];

foreach ($attendanceRecords as $record) {
  if ($record['time_in']) {
    $recordDate = date('Y-m-d', strtotime($record['time_in']));
    $personDate = $record['name'] . '_' . $recordDate;
    
    // Track the latest time_in for each person per day
    if (!isset($latestTimeInPerPerson[$personDate]) || 
        strtotime($record['time_in']) > strtotime($latestTimeInPerPerson[$personDate]['time_in'])) {
      $latestTimeInPerPerson[$personDate] = $record;
    }
    
    if (!isset($uniqueRecords[$personDate])) {
      $uniqueRecords[$personDate] = $record;
    } else {
      // Update with latest time_out if it exists
      if ($record['time_out'] && !$uniqueRecords[$personDate]['time_out']) {
        $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
      } elseif ($record['time_out'] && $uniqueRecords[$personDate]['time_out']) {
        // Keep the later time_out
        $existingTimeOut = strtotime($uniqueRecords[$personDate]['time_out']);
        $newTimeOut = strtotime($record['time_out']);
        if ($newTimeOut > $existingTimeOut) {
          $uniqueRecords[$personDate]['time_out'] = $record['time_out'];
        }
      }
    }
  }
}

// Add latest time_in info to each unique record for status determination
foreach ($uniqueRecords as $key => $record) {
  if (isset($latestTimeInPerPerson[$key])) {
    $uniqueRecords[$key]['latest_time_in'] = $latestTimeInPerPerson[$key]['time_in'];
    $uniqueRecords[$key]['latest_time_out'] = isset($latestTimeInPerPerson[$key]['time_out']) ? $latestTimeInPerPerson[$key]['time_out'] : null;
  }
}

$filteredRecords = array_values($uniqueRecords);

// Apply date filter if provided
$searchDate = isset($_GET['search_date']) ? $_GET['search_date'] : '';
if ($searchDate) {
  $dateFilteredRecords = [];
  foreach ($filteredRecords as $record) {
    if ($record['time_in']) {
      $recordDate = date('Y-m-d', strtotime($record['time_in']));
      if ($recordDate === $searchDate) {
        $dateFilteredRecords[] = $record;
      }
    }
  }
  $filteredRecords = $dateFilteredRecords;
}

// Build response array with calculated data
$response = [];
foreach ($filteredRecords as $record) {
  $id = $record['id'];
  $name = $record['name'];
  
  // Get date from time_in
  $date = $record['time_in'] ? date('M d, Y', strtotime($record['time_in'])) : 'N/A';

  // Determine status based on latest time_in
  $latestTimeIn = isset($record['latest_time_in']) && $record['latest_time_in'] ? $record['latest_time_in'] : $record['time_in'];
  $latestTimeOut = isset($record['latest_time_out']) && $record['latest_time_out'] ? $record['latest_time_out'] : null;
  $status = ($latestTimeIn && is_null($latestTimeOut)) ? 'Online' : 'Offline';

  $response[] = [
    'id' => intval($id),
    'name' => $name,
    'date' => $date,
    'status' => $status
  ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
