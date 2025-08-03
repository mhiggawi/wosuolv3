<?php
// api_rsvp_handler.php
// This file acts as a secure API endpoint to handle RSVP responses.

header('Content-Type: application/json');
require_once 'db_config.php';

// Get the posted data
$data = json_decode(file_get_contents('php://input'), true);

$guest_id = $data['guest_id'] ?? '';
$status = $data['status'] ?? '';

// --- Validation ---
if (empty($guest_id) || !in_array($status, ['confirmed', 'canceled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// --- Database Update ---
$sql = "UPDATE guests SET status = ? WHERE guest_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("ss", $status, $guest_id);
    
    if ($stmt->execute()) {
        // If the update was successful, we can now call the n8n webhook
        
        // --- n8n Webhook Call (Only if confirmed) ---
        if ($status === 'confirmed') {
            // First, get the event's webhook URL and guest phone number
            $sql_info = "SELECT e.n8n_confirm_webhook, g.phone_number 
                         FROM guests g 
                         JOIN events e ON g.event_id = e.id 
                         WHERE g.guest_id = ?";
            
            $stmt_info = $mysqli->prepare($sql_info);
            $stmt_info->bind_param("s", $guest_id);
            $stmt_info->execute();
            $result_info = $stmt_info->get_result()->fetch_assoc();
            $stmt_info->close();

            $webhook_url = $result_info['n8n_confirm_webhook'] ?? null;
            $phone_number = $result_info['phone_number'] ?? null;

            if ($webhook_url && $phone_number) {
                // Prepare data to send to n8n
                $n8n_payload = json_encode([
                    'guest_id' => $guest_id,
                    'phone_number' => $phone_number
                ]);

                // Use cURL to send the request to n8n
                $ch = curl_init($webhook_url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $n8n_payload);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($n8n_payload)
                ]);
                
                // It's a "fire and forget" request, we don't need the response from n8n
                curl_exec($ch);
                curl_close($ch);
            }
        }
        
        // Send success response back to the rsvp.php page
        echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed.']);
}

$mysqli->close();
?>
