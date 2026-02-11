<?php
// setup_database.php - Insert initial data into database

require_once 'backend/configure/config.php';

$conn = getDBConnection();

// Insert windows
$windows = [
    'Window 1',
    'Window 2',
    'Window 3',
    'Window 4',
    'Window 5',
    'Window 6',
    'Window 7'
];

foreach ($windows as $name) {
    $stmt = $conn->prepare("INSERT IGNORE INTO windows (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->close();
}

// Insert staff (registrar codes)
$staff_codes = [
    'REG001',
    'REG002',
    'REG003',
    'REG004',
    'REG005',
    'REG006',
    'REG007'
];

foreach ($staff_codes as $code) {
    $password = password_hash('password', PASSWORD_DEFAULT); // Default password
    $stmt = $conn->prepare("INSERT IGNORE INTO staff (registrar_code, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $password);
    $stmt->execute();
    $stmt->close();
}

// Get all services and windows
$services_result = $conn->query("SELECT id FROM services");
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row['id'];
}

$windows_result = $conn->query("SELECT id FROM windows");
$windows_ids = [];
while ($row = $windows_result->fetch_assoc()) {
    $windows_ids[] = $row['id'];
}

// Insert window_services (all services enabled for all windows)
foreach ($windows_ids as $window_id) {
    foreach ($services as $service_id) {
        $stmt = $conn->prepare("INSERT IGNORE INTO window_services (window_id, service_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $window_id, $service_id);
        $stmt->execute();
        $stmt->close();
    }
}

echo "Database setup completed successfully!";
$conn->close();
?>
