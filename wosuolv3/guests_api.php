<?php
// guests_api.php - API endpoint for fetching guests data for offline caching
session_start();
require_once 'db_config.php';

// Security & Permission Check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'checkin_user', 'viewer'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$user_role = $_SESSION['role'];
$user_event_access = $_SESSION['event_id_access'] ?? null;

if (!$event_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Event ID is required']);
    exit;
}

if ($user_role !== 'admin' && $event_id != $user_event_access) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied for this event']);
    exit;
}

header('Content-Type: application/json');

if (isset($_GET['fetch_guests'])) {
    // Fetch all guests for the event
    $guests = [];
    $stmt = $mysqli->prepare("SELECT guest_id, name_ar, phone_number, status, guests_count, checkin_status, table_number, assigned_location, notes FROM guests WHERE event_id = ? ORDER BY name_ar ASC");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $guests = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
    
    echo json_encode($guests);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}

$mysqli->close();
?>