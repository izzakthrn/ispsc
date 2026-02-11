<?php
// functions.php - Database Functions

require_once __DIR__ . '/../backend/configure/config.php';

/**
 * Get all services with their enabled status
 */
function getServices($conn) {
    $sql = "SELECT * FROM services ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get services for a specific window with their enabled status
 */
function getServicesForWindow($conn, $windowId) {
    $sql = "SELECT s.*, COALESCE(ts.is_enabled, 1) as is_enabled
            FROM services s
            LEFT JOIN window_services ws ON s.id = ws.service_id AND ws.window_id = ?
            LEFT JOIN toggle_services ts ON s.id = ts.service_id AND ts.window_id = ?
            WHERE ws.window_id IS NOT NULL
            ORDER BY s.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $windowId, $windowId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get enabled services count for a window
 */
function getEnabledServicesCount($conn, $windowId) {
    $sql = "SELECT COUNT(*) as count
            FROM window_services ws
            LEFT JOIN toggle_services ts ON ws.service_id = ts.service_id AND ws.window_id = ts.window_id
            WHERE ws.window_id = ? AND COALESCE(ts.is_enabled, 1) = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $windowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get total services count for a window
 */
function getTotalServicesCount($conn, $windowId) {
    $sql = "SELECT COUNT(*) as count
            FROM window_services
            WHERE window_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $windowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Get currently serving customers for a window (up to 5 most recent)
 */
function getCurrentlyServing($conn, $windowId) {
    $sql = "SELECT q.*, s.name as service_name
            FROM queue q
            INNER JOIN services s ON q.service_id = s.id
            WHERE q.window_id = ? AND q.status = 'serving'
            ORDER BY q.called_at DESC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $windowId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get next eligible customers for a window (from enabled services for this window)
 */
function getNextEligible($conn, $windowId) {
    $sql = "SELECT q.*, s.name as service_name
            FROM queue q
            INNER JOIN services s ON q.service_id = s.id
            INNER JOIN window_services ws ON ws.service_id = q.service_id AND ws.window_id = ?
            LEFT JOIN toggle_services ts ON ts.service_id = q.service_id AND ts.window_id = ?
            WHERE q.status = 'waiting'
            AND COALESCE(ts.is_enabled, 1) = 1
            ORDER BY q.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $windowId, $windowId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Count eligible customers
 */
function countEligible($conn, $windowId) {
    $sql = "SELECT COUNT(*) as count
            FROM queue q
            INNER JOIN services s ON q.service_id = s.id
            INNER JOIN window_services ws ON ws.service_id = q.service_id AND ws.window_id = ?
            LEFT JOIN toggle_services ts ON ts.service_id = q.service_id AND ts.window_id = ?
            WHERE q.status = 'waiting'
            AND COALESCE(ts.is_enabled, 1) = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $windowId, $windowId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

/**
 * Mark current customer as done
 */
function markCustomerDone($conn, $queueId) {
    $sql = "UPDATE queue
            SET status = 'done', completed_at = NOW()
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $queueId);
    return $stmt->execute();
}

/**
 * Call next customer
 */
function callNextCustomer($conn, $windowId, $queueId) {
    // Call the next customer to serving status (don't mark previous as done)
    $sql = "UPDATE queue
            SET status = 'serving', window_id = ?, called_at = NOW()
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $windowId, $queueId);
    return $stmt->execute();
}

/**
 * Toggle service status for a specific window
 */
function toggleService($conn, $serviceId, $windowId) {
    // First, check if the toggle entry exists
    $checkSql = "SELECT id FROM toggle_services WHERE service_id = ? AND window_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('ii', $serviceId, $windowId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows == 0) {
        // Insert new entry with default enabled
        $insertSql = "INSERT INTO toggle_services (service_id, window_id, is_enabled) VALUES (?, ?, 1)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('ii', $serviceId, $windowId);
        $insertStmt->execute();
        $insertStmt->close();
    }

    // Now toggle the status
    $sql = "UPDATE toggle_services
            SET is_enabled = NOT is_enabled
            WHERE service_id = ? AND window_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $serviceId, $windowId);
    $result = $stmt->execute();
    $checkStmt->close();
    return $result;
}

/**
 * Get window details
 */
function getWindow($conn, $windowId) {
    $sql = "SELECT * FROM windows WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $windowId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>