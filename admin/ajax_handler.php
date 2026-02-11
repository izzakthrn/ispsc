<?php
// ajax_handler.php - Handle AJAX requests for staff interface

require_once '../backend/configure/config.php';
require_once 'functions.php';

header('Content-Type: application/json');

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'toggle_service':
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $windowId = (int)($_POST['window_id'] ?? 0);
        if ($serviceId <= 0 || $windowId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid service or window ID']);
            exit;
        }
        $result = toggleService($conn, $serviceId, $windowId);
        echo json_encode(['success' => $result]);
        break;

    case 'mark_done':
        $queueId = (int)($_POST['queue_id'] ?? 0);
        $windowId = (int)($_POST['window_id'] ?? 0);
        if ($queueId <= 0 || $windowId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        $result = markCustomerDone($conn, $queueId);
        echo json_encode(['success' => $result]);
        break;

    case 'call_next':
        $queueId = (int)($_POST['queue_id'] ?? 0);
        $windowId = (int)($_POST['window_id'] ?? 0);
        if ($queueId <= 0 || $windowId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        $result = callNextCustomer($conn, $windowId, $queueId);
        echo json_encode(['success' => $result]);
        break;

    case 'get_status':
        $windowId = (int)($_POST['window_id'] ?? 0);
        if ($windowId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid window ID']);
            exit;
        }
        $enabledCount = getEnabledServicesCount($conn, $windowId);
        $totalCount = getTotalServicesCount($conn, $windowId);
        $currentServing = getCurrentlyServing($conn, $windowId);
        $nextEligible = getNextEligible($conn, $windowId);
        $eligibleCount = countEligible($conn, $windowId);

        echo json_encode([
            'success' => true,
            'enabled_services' => $enabledCount,
            'total_services' => $totalCount,
            'current_serving' => $currentServing,
            'next_eligible' => $nextEligible,
            'eligible_count' => $eligibleCount
        ]);
        break;

    case 'get_dashboard_queue':
        $on_queue = [];
        $staff_result = $conn->query("SELECT id FROM staff LIMIT 7");
        while ($row = $staff_result->fetch_assoc()) {
            $staff_id = $row['id'];
            $stmt = $conn->prepare("SELECT q.queue_number, s.name FROM queue q JOIN services s ON q.service_id = s.id WHERE q.window_id = ? AND q.status = 'serving' ORDER BY q.called_at DESC LIMIT 1");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $on_queue[$staff_id] = $result->fetch_assoc();
            $stmt->close();
        }
        echo json_encode(['success' => true, 'on_queue' => $on_queue]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        break;
}

$conn->close();
?>
