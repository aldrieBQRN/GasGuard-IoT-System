<?php
// Include the connection script
include 'db_connect.php';

// Truncate empties the table completely and resets the ID counter
if ($conn->query("TRUNCATE TABLE sensor_logs") === TRUE) {
    echo "Database successfully cleared.";
} else {
    echo "Error clearing database: " . $conn->error;
}

$conn->close();
?>