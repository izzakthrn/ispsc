<?php
require_once 'backend/configure/config.php';

$conn = getDBConnection();

$sql = "INSERT IGNORE INTO toggle_services (window_id, service_id, is_enabled)
        SELECT ws.window_id, ws.service_id, 1
        FROM window_services ws";

if ($conn->query($sql) === TRUE) {
    echo "Toggle services initialized with defaults successfully.";
} else {
    echo "Error initializing toggle services: " . $conn->error;
}

$conn->close();
?>
