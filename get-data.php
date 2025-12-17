<?php
header('Content-Type: application/json');

// Include the connection script
include 'db_connect.php';

// 1. Get Latest Reading (and calculate seconds_ago using UNIX_TIMESTAMP)
$result = $conn->query("SELECT *, UNIX_TIMESTAMP() - UNIX_TIMESTAMP(reading_time) AS seconds_ago FROM sensor_logs ORDER BY id DESC LIMIT 1");
$latest = $result->fetch_assoc();

// 2. Get Chart Data (Last 20 readings)
$historyQuery = $conn->query("SELECT * FROM (SELECT * FROM sensor_logs ORDER BY id DESC LIMIT 20) Var1 ORDER BY id ASC");

$labels = [];
$data = [];

while ($row = $historyQuery->fetch_assoc()) {
    $labels[] = date("H:i:s", strtotime($row['reading_time']));
    $data[] = $row['gas_raw'];
}

// 3. Send JSON Response
echo json_encode([
    'latest' => $latest,
    'chart' => [
        'labels' => $labels,
        'data' => $data
    ]
]);

$conn->close();
?>