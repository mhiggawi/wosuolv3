<?php
session_start();
require_once 'db_config.php';

// Security & Permission Check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'checkin_user', 'viewer'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

$user_role = $_SESSION['role'];
$user_event_access = $_SESSION['event_id_access'] ?? $_SESSION['event_id'] ?? null;

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['event_id']) || !isset($input['guests'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'بيانات غير صالحة']);
    exit;
}

$event_id = intval($input['event_id']);
$guests_data = $input['guests'];

// Check event access permissions
if ($user_role !== 'admin' && $event_id != $user_event_access) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول لهذا الحدث']);
    exit;
}

// Verify event exists
$event_check = $mysqli->prepare("SELECT id FROM events WHERE id = ?");
$event_check->bind_param("i", $event_id);
$event_check->execute();
$event_result = $event_check->get_result();

if ($event_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'الحدث غير موجود']);
    exit;
}
$event_check->close();

$imported_count = 0;
$errors = [];

try {
    // Start transaction
    $mysqli->autocommit(false);
    
    // Prepare insert statement
    $insert_stmt = $mysqli->prepare("INSERT INTO guests (event_id, guest_id, name_ar, phone_number, guests_count, table_number, assigned_location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($guests_data as $index => $guest) {
        try {
            // Validate required fields
            if (empty($guest['name_ar'])) {
                $errors[] = "الصف " . ($index + 2) . ": اسم الضيف مطلوب";
                continue;
            }
            
            // Clean and validate data
            $name_ar = trim($guest['name_ar']);
            $phone_number = isset($guest['phone_number']) ? trim($guest['phone_number']) : '';
            $guests_count = isset($guest['guests_count']) ? max(1, intval($guest['guests_count'])) : 1;
            $table_number = isset($guest['table_number']) ? trim($guest['table_number']) : '';
            $assigned_location = isset($guest['assigned_location']) ? trim($guest['assigned_location']) : '';
            $notes = isset($guest['notes']) ? trim($guest['notes']) : '';
            
            // Validate phone number format if provided
            if (!empty($phone_number) && !preg_match('/^[\d\s\+\-\(\)]+$/', $phone_number)) {
                $errors[] = "الصف " . ($index + 2) . ": رقم الهاتف غير صحيح";
                continue;
            }
            
            // Generate unique guest ID
            do {
                $guest_id = substr(str_shuffle('0123456789abcdef'), 0, 4);
                $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM guests WHERE guest_id = ?");
                $check_stmt->bind_param("s", $guest_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $exists = $check_result->fetch_row()[0];
                $check_stmt->close();
            } while ($exists > 0);
            
            // Insert guest
            $insert_stmt->bind_param("isssssss", $event_id, $guest_id, $name_ar, $phone_number, $guests_count, $table_number, $assigned_location, $notes);
            
            if ($insert_stmt->execute()) {
                $imported_count++;
            } else {
                $errors[] = "الصف " . ($index + 2) . ": فشل في إدراج البيانات - " . $mysqli->error;
            }
            
        } catch (Exception $e) {
            $errors[] = "الصف " . ($index + 2) . ": " . $e->getMessage();
        }
    }
    
    $insert_stmt->close();
    
    // Commit transaction if we have successful imports
    if ($imported_count > 0) {
        $mysqli->commit();
    } else {
        $mysqli->rollback();
    }
    
    $mysqli->autocommit(true);
    
    // Prepare response
    $response = [
        'success' => $imported_count > 0,
        'imported_count' => $imported_count,
        'total_rows' => count($guests_data),
        'errors' => $errors
    ];
    
    if ($imported_count > 0) {
        $response['message'] = "تم استيراد {$imported_count} ضيف بنجاح";
        if (!empty($errors)) {
            $response['message'] .= " مع " . count($errors) . " أخطاء";
        }
    } else {
        $response['message'] = "لم يتم استيراد أي ضيف";
        if (!empty($errors)) {
            $response['message'] .= ". الأخطاء: " . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $response['message'] .= " وأخطاء أخرى...";
            }
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $mysqli->rollback();
    $mysqli->autocommit(true);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في الخادم: ' . $e->getMessage(),
        'imported_count' => 0,
        'errors' => []
    ]);
}

$mysqli->close();
?>