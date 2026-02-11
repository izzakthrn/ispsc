<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_verified']) || $_SESSION['is_verified'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include DB config
require_once '../../backend/configure/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'])) {
    $service_id = (int)$_POST['service_id'];

    $stmt = $conn->prepare("SELECT description FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();

    if ($service) {
        echo json_encode(['success' => true, 'description' => $service['description']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
