<?php
header('Content-Type: application/json');
// Include the connection script
include 'db_connect.php';

// 1. Get Parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
// --- NEW DATE RANGE PARAMETER ---
$dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : '';
// --------------------------------

$limit = 10;
$offset = ($page - 1) * $limit;

// 2. Build Query Conditions
$conditions = [];

// Search Logic (Generic)
if($search != '') {
    $safeSearch = $conn->real_escape_string($search);
    $conditions[] = "(device_name LIKE '%$safeSearch%' OR reading_time LIKE '%$safeSearch%')";
}

// Status Filter
if($statusFilter != '' && $statusFilter != 'All') {
    $safeStatus = $conn->real_escape_string($statusFilter);
    $conditions[] = "status = '$safeStatus'";
}

// --- DATE RANGE LOGIC ---
if($dateRange != '') {
    // Expected format: "YYYY-MM-DD to YYYY-MM-DD" or just "YYYY-MM-DD"
    $dates = explode(" to ", $dateRange);
    
    if (count($dates) == 2) {
        $startDate = $conn->real_escape_string($dates[0]);
        $endDate = $conn->real_escape_string($dates[1]);
        // Filter from start date (inclusive) to end date (inclusive)
        $conditions[] = "DATE(reading_time) BETWEEN '$startDate' AND '$endDate'";
    } elseif (count($dates) == 1 && !empty($dates[0])) {
        // Handle case where only a single date is selected
        $singleDate = $conn->real_escape_string($dates[0]);
        $conditions[] = "DATE(reading_time) = '$singleDate'";
    }
}
// ----------------------------

// Combine Conditions
$whereSQL = "";
if (count($conditions) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $conditions);
}

// 3. Get Total Count
$countQuery = $conn->query("SELECT COUNT(*) as total FROM sensor_logs $whereSQL");
$totalRows = $countQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// 4. Get Data
$sql = "SELECT * FROM sensor_logs $whereSQL ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'data' => $data,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => $totalRows
    ]
]);

$conn->close();
?>