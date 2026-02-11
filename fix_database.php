<?php
// fix_database.php - Add missing is_enabled column to window_services table

require_once 'backend/configure/config.php';

$conn = getDBConnection();

// Add is_enabled column to window_services table if it doesn't exist
$sql = "ALTER TABLE window_services ADD COLUMN is_enabled TINYINT(1) DEFAULT 1";
if ($conn->query($sql) === TRUE) {
    echo "Column is_enabled added successfully to window_services table.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

// Set all existing records to enabled
$sql = "UPDATE window_services SET is_enabled = 1 WHERE is_enabled IS NULL";
if ($conn->query($sql) === TRUE) {
    echo "All existing window_services records set to enabled.\n";
} else {
    echo "Error updating records: " . $conn->error . "\n";
}

$conn->close();
echo "Database fix completed.\n";
?>
