<?php
// events.php - محسّن مع إصلاح اللغة الإنجليزية وترتيب نتائج الإرسال
session_start();
require_once 'db_config.php';

// --- Language System ---
$lang = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'ar';
if (isset($_POST['switch_language'])) {
    $lang = $_POST['switch_language'] === 'en' ? 'en' : 'ar';
    $_SESSION['language'] = $lang;
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
}

// Language texts - إصلاح النصوص الإنجليزية المفقودة
$texts = [
    'ar' => [
        'event_management' => 'إدارة الحفلات',
        'logout' => 'تسجيل الخروج',
        'create_new_event' => 'إنشاء حفل جديد',
        'event_name' => 'اسم الحفل',
        'create' => 'إنشاء',
        'current_events' => 'الحفلات الحالية',
        'event_date' => 'تاريخ الحفل',
        'actions' => 'إجراءات',
        'manage_guests' => 'إدارة الضيوف',
        'settings' => 'الإعدادات',
        'dashboard' => 'لوحة المتابعة',
        'send_invitations' => 'إرسال الدعوات',
        'checkin' => 'تسجيل الدخول',
        'registration_link' => 'رابط التسجيل',
        'delete' => 'حذف',
        'send_to_all' => 'إرسال لجميع ضيوف هذا الحفل',
        'send_to_selected' => 'إرسال لمحددين',
        'bulk_messaging' => 'رسائل جماعية',
        'global_send_all' => 'إرسال عام لكل الأحداث',
        'no_events' => 'لا توجد حفلات حالياً.',
        'event_created_success' => 'تم إنشاء الحفل بنجاح!',
        'event_deleted_success' => 'تم حذف الحفل وكل بياناته بنجاح.',
        'event_creation_error' => 'حدث خطأ أثناء إنشاء الحفل.',
        'event_deletion_error' => 'فشل حذف الحفل.',
        'enter_event_name' => 'الرجاء إدخال اسم للحفل.',
        'confirm_delete_event' => 'هل أنت متأكد؟ سيتم حذف الحفل وكل ضيوفه بشكل نهائي.',
        'messages_sent_success' => 'تم إرسال الرسائل بنجاح!',
        'global_messages_sent' => 'تم تشغيل إرسال الرسائل العام لجميع الأحداث.',
        'messaging_error' => 'حدث خطأ في إرسال الرسائل.',
        'select_guests_title' => 'اختيار الضيوف لإرسال الدعوات',
        'select_guests' => 'اختر الضيوف',
        'send_selected' => 'إرسال للمحددين',
        'cancel' => 'إلغاء',
        'close' => 'إغلاق',
        'search_guests' => 'ابحث في الضيوف...',
        'select_all' => 'تحديد الكل',
        'clear_selection' => 'مسح التحديد',
        'guests_selected' => 'ضيف محدد',
        'guest_name' => 'اسم الضيف',
        'phone_number' => 'رقم الهاتف',
        'invitation_status' => 'حالة الدعوة',
        'confirmed' => 'مؤكد',
        'canceled' => 'معتذر',
        'pending' => 'في الانتظار',
        'total_guests' => 'إجمالي الضيوف',
        'confirmed_guests' => 'مؤكدين',
        'pending_guests' => 'في الانتظار',
        'processing' => 'جاري المعالجة...',
        'event_statistics' => 'إحصائيات الحفل',
        'recent_activity' => 'النشاط الأخير',
        'quick_actions' => 'إجراءات سريعة',
        'copy_link' => 'نسخ الرابط',
        'link_copied' => 'تم نسخ الرابط!',
        'event_status' => 'حالة الحفل',
        'active' => 'نشط',
        'draft' => 'مسودة',
        'completed' => 'مكتمل',
        'duplicate_event' => 'نسخ الحفل',
        'archive_event' => 'أرشفة',
        'export_data' => 'تصدير البيانات',
        'sending_messages' => 'جاري إرسال الرسائل...',
        'send_results' => 'نتائج الإرسال',
        'last_send_results' => 'نتائج آخر إرسال',
        'success_count' => 'تم الإرسال بنجاح',
        'failed_count' => 'فشل الإرسال',
        'success_rate' => 'معدل النجاح',
        'send_time' => 'وقت الإرسال',
        'view_send_log' => 'عرض سجل الإرسال',
        'no_send_history' => 'لا يوجد تاريخ إرسال',
        'webhook_not_configured' => 'لم يتم تكوين webhook - يرجى الذهاب للإعدادات',
        'refresh_results' => 'تحديث النتائج',
        'send_global_all' => 'إرسال عام',
        'send_event_all' => 'إرسال للحفل',
        'send_selected_guests' => 'إرسال محدد',
        'go_to_settings' => 'اذهب للإعدادات',
        'bulk_messaging_description' => 'إرسال رسائل دعوة لجميع الضيوف في جميع الأحداث عبر n8n',
        'confirm_global_send' => 'هل أنت متأكد من إرسال الرسائل لجميع الضيوف في كل الأحداث؟',
        'confirm_event_send' => 'إرسال دعوات لجميع ضيوف هذا الحفل؟',
        'create_first_event' => 'ابدأ بإنشاء حفلك الأول من الأعلى'
    ],
    'en' => [
        'event_management' => 'Event Management',
        'logout' => 'Logout',
        'create_new_event' => 'Create New Event',
        'event_name' => 'Event Name',
        'create' => 'Create',
        'current_events' => 'Current Events',
        'event_date' => 'Event Date',
        'actions' => 'Actions',
        'manage_guests' => 'Manage Guests',
        'settings' => 'Settings',
        'dashboard' => 'Dashboard',
        'send_invitations' => 'Send Invitations',
        'checkin' => 'Check-in',
        'registration_link' => 'Registration Link',
        'delete' => 'Delete',
        'send_to_all' => 'Send to All Event Guests',
        'send_to_selected' => 'Send to Selected',
        'bulk_messaging' => 'Bulk Messaging',
        'global_send_all' => 'Global Send to All Events',
        'no_events' => 'No events currently available.',
        'event_created_success' => 'Event created successfully!',
        'event_deleted_success' => 'Event and all its data deleted successfully.',
        'event_creation_error' => 'Error occurred while creating event.',
        'event_deletion_error' => 'Failed to delete event.',
        'enter_event_name' => 'Please enter an event name.',
        'confirm_delete_event' => 'Are you sure? The event and all its guests will be permanently deleted.',
        'messages_sent_success' => 'Messages sent successfully!',
        'global_messages_sent' => 'Global messaging initiated for all events.',
        'messaging_error' => 'Error occurred while sending messages.',
        'select_guests_title' => 'Select Guests to Send Invitations',
        'select_guests' => 'Select Guests',
        'send_selected' => 'Send to Selected',
        'cancel' => 'Cancel',
        'close' => 'Close',
        'search_guests' => 'Search guests...',
        'select_all' => 'Select All',
        'clear_selection' => 'Clear Selection',
        'guests_selected' => 'guests selected',
        'guest_name' => 'Guest Name',
        'phone_number' => 'Phone Number',
        'invitation_status' => 'Invitation Status',
        'confirmed' => 'Confirmed',
        'canceled' => 'Canceled',
        'pending' => 'Pending',
        'total_guests' => 'Total Guests',
        'confirmed_guests' => 'Confirmed',
        'pending_guests' => 'Pending',
        'processing' => 'Processing...',
        'event_statistics' => 'Event Statistics',
        'recent_activity' => 'Recent Activity',
        'quick_actions' => 'Quick Actions',
        'copy_link' => 'Copy Link',
        'link_copied' => 'Link copied!',
        'event_status' => 'Event Status',
        'active' => 'Active',
        'draft' => 'Draft',
        'completed' => 'Completed',
        'duplicate_event' => 'Duplicate Event',
        'archive_event' => 'Archive',
        'export_data' => 'Export Data',
        'sending_messages' => 'Sending messages...',
        'send_results' => 'Send Results',
        'last_send_results' => 'Last Send Results',
        'success_count' => 'Successfully Sent',
        'failed_count' => 'Failed to Send',
        'success_rate' => 'Success Rate',
        'send_time' => 'Send Time',
        'view_send_log' => 'View Send Log',
        'no_send_history' => 'No send history',
        'webhook_not_configured' => 'Webhook not configured - Please go to settings',
        'refresh_results' => 'Refresh Results',
        'send_global_all' => 'Global Send',
        'send_event_all' => 'Event Send',
        'send_selected_guests' => 'Selected Send',
        'go_to_settings' => 'Go to Settings',
        'bulk_messaging_description' => 'Send invitation messages to all guests in all events via n8n',
        'confirm_global_send' => 'Are you sure you want to send messages to all guests in all events?',
        'confirm_event_send' => 'Send invitations to all guests of this event?',
        'create_first_event' => 'Start by creating your first event from above'
    ]
];

$t = $texts[$lang];

// Security check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = '';

// --- Handle messaging requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['messaging_action']) && !isset($_POST['switch_language'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        $action = $_POST['messaging_action'];
        
        switch ($action) {
            case 'send_to_all':
                $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
                if ($event_id) {
                    $result = sendToAllGuestsForEvent($event_id, $mysqli);
                    
                    // حفظ نتائج الإرسال في قاعدة البيانات
                    saveSendResults($event_id, 'send_event_all', $result, $mysqli);
                    
                    $message = $result['success'] ? $t['messages_sent_success'] : $t['messaging_error'];
                    $messageType = $result['success'] ? 'success' : 'error';
                    
                    // إضافة تفاصيل النتائج للرسالة
                    if ($result['success'] && isset($result['response'])) {
                        $response_data = json_decode($result['response'], true);
                        if ($response_data && isset($response_data['summary'])) {
                            $summary = $response_data['summary'];
                            $success_text = $lang === 'ar' ? 'نجح' : 'Success';
                            $failed_text = $lang === 'ar' ? 'فشل' : 'Failed';
                            $message .= " ($success_text: {$summary['successCount']}, $failed_text: {$summary['failureCount']})";
                        }
                    }
                }
                break;
                
            case 'send_to_selected':
                $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
                $selected_guests = json_decode($_POST['selected_guests'] ?? '[]', true);
                if ($event_id && !empty($selected_guests)) {
                    $result = sendToSelectedGuests($event_id, $selected_guests, $mysqli);
                    
                    // حفظ نتائج الإرسال
                    saveSendResults($event_id, 'send_selected', $result, $mysqli, count($selected_guests));
                    
                    $message = $result['success'] ? $t['messages_sent_success'] : $t['messaging_error'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;
                
            case 'global_send_all':
                $result = sendGlobalMessages($mysqli);
                
                // حفظ نتائج الإرسال العام
                saveSendResults(null, 'send_global_all', $result, $mysqli);
                
                $message = $result['success'] ? $t['global_messages_sent'] : $t['messaging_error'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
        header('Location: events.php?message=' . urlencode($message) . '&messageType=' . $messageType);
        exit;
    }
}

// --- Messaging Functions ---
function sendToAllGuestsForEvent($event_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT n8n_initial_invite_webhook FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    
    if ($event && !empty($event['n8n_initial_invite_webhook'])) {
        $webhook_url = $event['n8n_initial_invite_webhook'];
        $payload = json_encode([
            'action' => 'send_event_all',
            'event_id' => (int)$event_id,
            'timestamp' => time()
        ]);
        
        return callWebhook($webhook_url, $payload);
    }
    return ['success' => false, 'message' => 'Webhook URL not configured'];
}

function sendToSelectedGuests($event_id, $guest_ids, $mysqli) {
    $guest_ids = array_map('intval', $guest_ids);
    
    $stmt = $mysqli->prepare("SELECT n8n_initial_invite_webhook FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    
    if ($event && !empty($event['n8n_initial_invite_webhook'])) {
        $webhook_url = $event['n8n_initial_invite_webhook'];
        $payload = json_encode([
            'action' => 'send_selected',
            'event_id' => (int)$event_id,
            'guest_ids' => $guest_ids,
            'timestamp' => time()
        ]);
        
        return callWebhook($webhook_url, $payload);
    }
    return ['success' => false, 'message' => 'Webhook URL not configured'];
}

function sendGlobalMessages($mysqli) {
    $stmt = $mysqli->prepare("SELECT n8n_initial_invite_webhook FROM events WHERE n8n_initial_invite_webhook IS NOT NULL AND n8n_initial_invite_webhook != '' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    
    if ($event && !empty($event['n8n_initial_invite_webhook'])) {
        $webhook_url = $event['n8n_initial_invite_webhook'];
        $payload = json_encode([
            'action' => 'send_global_all',
            'timestamp' => time()
        ]);
        
        return callWebhook($webhook_url, $payload);
    }
    return ['success' => false, 'message' => 'No webhook URL configured'];
}

function callWebhook($url, $payload) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // تسجيل للتشخيص
    error_log("Webhook call to: $url");
    error_log("Payload: $payload");
    error_log("Response: $response");
    error_log("HTTP Code: $httpCode");
    if ($error) error_log("CURL Error: $error");
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300), 
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// دالة حفظ نتائج الإرسال
function saveSendResults($event_id, $action_type, $result, $mysqli, $target_count = null) {
    $response_data = null;
    $success_count = 0;
    $failed_count = 0;
    $total_processed = 0;
    
    if ($result['success'] && !empty($result['response'])) {
        $response_data = json_decode($result['response'], true);
        if ($response_data && isset($response_data['summary'])) {
            $summary = $response_data['summary'];
            $success_count = $summary['successCount'] ?? 0;
            $failed_count = $summary['failureCount'] ?? 0;
            $total_processed = $summary['totalProcessed'] ?? 0;
        }
    }
    
    $stmt = $mysqli->prepare("
        INSERT INTO send_results (event_id, action_type, success_count, failed_count, total_processed, 
                                 target_count, response_data, http_code, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $response_json = json_encode($response_data);
    $http_code = $result['http_code'] ?? 0;
    
    $stmt->bind_param("isiiiisi", $event_id, $action_type, $success_count, $failed_count, 
                     $total_processed, $target_count, $response_json, $http_code);
    $stmt->execute();
    $stmt->close();
}

// دالة جلب آخر نتائج الإرسال لحدث معين مع ترتيب محسّن
function getLastSendResults($event_id, $mysqli) {
    $stmt = $mysqli->prepare("
        SELECT * FROM send_results 
        WHERE event_id = ? OR (event_id IS NULL AND action_type = 'send_global_all')
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $results;
}

// --- API Endpoints ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    // Get event guests for selection modal
    if (isset($_GET['get_guests'])) {
        $event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
        if ($event_id) {
            $stmt = $mysqli->prepare("SELECT id, guest_id, name_ar, phone_number, status FROM guests WHERE event_id = ? ORDER BY name_ar ASC");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $guests = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode($guests);
        }
        exit;
    }
    
    // Get event statistics
    if (isset($_GET['get_stats'])) {
        $event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
        if ($event_id) {
            $stmt = $mysqli->prepare("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status NOT IN ('confirmed', 'canceled') THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN checkin_status = 'checked_in' THEN 1 ELSE 0 END) as checked_in
                FROM guests WHERE event_id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
            echo json_encode($stats);
        }
        exit;
    }
    
    // Get send results
    if (isset($_GET['get_send_results'])) {
        $event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
        if ($event_id) {
            $results = getLastSendResults($event_id, $mysqli);
            echo json_encode($results);
        }
        exit;
    }
}

// --- Handle Delete Event ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event']) && !isset($_POST['switch_language'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        $delete_id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($delete_id) {
            $stmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            if ($stmt->execute()) {
                $message = $t['event_deleted_success']; $messageType = 'success';
            } else {
                $message = $t['event_deletion_error']; $messageType = 'error';
            }
            $stmt->close();
        }
        header('Location: events.php?message=' . urlencode($message) . '&messageType=' . $messageType);
        exit;
    }
}

// --- Handle Create Event ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event']) && !isset($_POST['switch_language'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        $eventName = trim($_POST['event_name']);
        if (!empty($eventName)) {
            $default_ar = $lang === 'ar' ? 'يرجى التحديث من لوحة التحكم' : 'Please update from control panel';
            $default_settings = [
                'qr_card_title_ar' => 'دعوة حفل زفاف',
                'qr_card_title_en' => 'Wedding Invitation',
                'qr_show_code_instruction_ar' => 'يرجى إظهار هذا الرمز عند الدخول',
                'qr_show_code_instruction_en' => 'Please show this code at entrance',
                'qr_brand_text_ar' => 'wosuol.com',
                'qr_brand_text_en' => 'wosuol.com',
                'qr_website' => 'wosuol.com.com',
                'n8n_confirm_webhook' => 'https://your-n8n-instance.com/webhook/confirm',
                'n8n_initial_invite_webhook' => 'https://your-n8n-instance.com/webhook/invite'
            ];
            
            $stmt = $mysqli->prepare("INSERT INTO events (event_name, bride_name_ar, groom_name_ar, event_date_ar, venue_ar, qr_card_title_ar, qr_card_title_en, qr_show_code_instruction_ar, qr_show_code_instruction_en, qr_brand_text_ar, qr_brand_text_en, qr_website, n8n_confirm_webhook, n8n_initial_invite_webhook) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssss", 
                $eventName, $default_ar, $default_ar, $default_ar, $default_ar,
                $default_settings['qr_card_title_ar'], $default_settings['qr_card_title_en'],
                $default_settings['qr_show_code_instruction_ar'], $default_settings['qr_show_code_instruction_en'],
                $default_settings['qr_brand_text_ar'], $default_settings['qr_brand_text_en'],
                $default_settings['qr_website'], $default_settings['n8n_confirm_webhook'], $default_settings['n8n_initial_invite_webhook']
            );
            if ($stmt->execute()) { 
                $message = $t['event_created_success']; $messageType = 'success'; 
            } else { 
                $message = $t['event_creation_error']; $messageType = 'error'; 
            }
            $stmt->close();
        } else { 
            $message = $t['enter_event_name']; $messageType = 'error'; 
        }
        header('Location: events.php?message=' . urlencode($message) . '&messageType=' . $messageType);
        exit;
    }
}

// --- Get URL parameters ---
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = $_GET['messageType'] ?? 'success';
}

// --- Fetch Events Data with Webhook Status ---
$events = [];
$result = $mysqli->query("
    SELECT e.id, e.event_name, e.event_date_ar, e.created_at, 
           e.n8n_initial_invite_webhook,
           COUNT(g.id) as guest_count,
           SUM(CASE WHEN g.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count
    FROM events e 
    LEFT JOIN guests g ON e.id = g.event_id 
    GROUP BY e.id 
    ORDER BY e.created_at DESC
");
if ($result) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

// إنشاء جدول نتائج الإرسال إذا لم يكن موجوداً
$mysqli->query("
    CREATE TABLE IF NOT EXISTS send_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NULL,
        action_type VARCHAR(50) NOT NULL,
        success_count INT DEFAULT 0,
        failed_count INT DEFAULT 0,
        total_processed INT DEFAULT 0,
        target_count INT NULL,
        response_data TEXT NULL,
        http_code INT DEFAULT 0,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    )
");

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['event_management'] ?> - wosuol.com</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background-color: #f0f2f5; 
        }
        .container {
            max-width: 1400px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        .header-buttons { display: flex; gap: 12px; align-items: center; }
        .create-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
        }
        .bulk-messaging-section {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            color: white;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .event-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            padding: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .event-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .event-date {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .event-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .stat-item {
            text-align: center;
            padding: 8px;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 2px;
        }
        .stat-total { color: #3b82f6; }
        .stat-confirmed { color: #10b981; }
        .stat-pending { color: #f59e0b; }
        .event-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }
        .messaging-actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
        }
        .send-results-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-top: 1px solid #e5e7eb;
        }
        .result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .result-item:last-child {
            border-bottom: none;
        }
        .result-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .result-action {
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .result-time {
            font-size: 0.75rem;
            color: #6b7280;
        }
        .result-numbers {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .result-success {
            color: #10b981;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .result-failed {
            color: #ef4444;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .result-rate {
            font-size: 0.8rem;
            color: #6b7280;
            margin-left: 8px;
        }
        .webhook-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
        }
        .webhook-configured {
            background-color: #dcfce7;
            color: #166534;
        }
        .webhook-missing {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .btn {
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .btn-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }
        .btn-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
        .btn-pink { background: linear-gradient(135deg, #ec4899, #db2777); color: white; }
        .btn-small { padding: 6px 10px; font-size: 0.75rem; }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.active { display: flex; justify-content: center; align-items: center; }
        .modal-content { 
            background-color: white; 
            padding: 30px; 
            border-radius: 15px; 
            width: 90%; 
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .guest-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin: 15px 0;
        }
        .guest-item {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .guest-item:last-child { border-bottom: none; }
        .guest-item:hover { background-color: #f8f9fa; }
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-confirmed { background-color: #dcfce7; color: #166534; }
        .status-canceled { background-color: #fee2e2; color: #991b1b; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        
        .quick-copy {
            position: relative;
            display: inline-block;
        }
        .copy-tooltip {
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        .copy-tooltip.show { opacity: 1; }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
        }
        .loading-overlay.active {
            display: flex;
        }
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .events-grid { grid-template-columns: 1fr; }
            .event-actions { grid-template-columns: 1fr; }
            .messaging-actions { flex-direction: column; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .create-section form { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="text-4xl font-bold text-gray-800"><?= $t['event_management'] ?></h1>
            <div class="header-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg border border-gray-300 transition-colors">
                        <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                    </button>
                </form>
                <a href="logout.php" class="btn btn-danger"><?= $t['logout'] ?></a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-6 text-sm rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Create New Event Section -->
        <div class="create-section">
            <h2 class="text-2xl font-bold mb-4"><?= $t['create_new_event'] ?></h2>
            <form method="POST" action="events.php" class="flex items-end gap-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="flex-grow">
                    <label for="event_name" class="block mb-2 font-medium text-white"><?= $t['event_name'] ?>:</label>
                    <input type="text" id="event_name" name="event_name" required 
                           class="w-full px-4 py-3 border-0 rounded-lg text-gray-800 focus:ring-2 focus:ring-white focus:ring-opacity-50">
                </div>
                <button type="submit" name="create_event" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-bold py-3 px-8 rounded-lg border border-white border-opacity-30 transition-all">
                    <?= $t['create'] ?>
                </button>
            </form>
        </div>

        <!-- Global Messaging Section -->
        <div class="bulk-messaging-section">
            <h2 class="text-xl font-bold mb-3"><?= $t['bulk_messaging'] ?></h2>
            <p class="mb-4 opacity-90"><?= $t['bulk_messaging_description'] ?></p>
            <form method="POST" action="events.php" style="display: inline;" onsubmit="return confirm('<?= $t['confirm_global_send'] ?>');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="messaging_action" value="global_send_all">
                <button type="submit" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white font-bold py-2 px-6 rounded-lg border border-white border-opacity-30 transition-all">
                    🚀 <?= $t['global_send_all'] ?>
                </button>
            </form>
        </div>

        <!-- Current Events -->
        <div>
            <h2 class="text-2xl font-bold mb-4 text-gray-700"><?= $t['current_events'] ?></h2>
            
            <?php if (empty($events)): ?>
                <div class="text-center py-16">
                    <div class="text-6xl mb-4">🎉</div>
                    <p class="text-xl text-gray-500 mb-6"><?= $t['no_events'] ?></p>
                    <p class="text-gray-400"><?= $t['create_first_event'] ?></p>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card" data-event-id="<?= $event['id'] ?>">
                            <!-- Loading Overlay -->
                            <div class="loading-overlay" id="loading-<?= $event['id'] ?>">
                                <div class="text-center">
                                    <div class="spinner"></div>
                                    <p class="mt-2 text-gray-600"><?= $t['sending_messages'] ?></p>
                                </div>
                            </div>
                            
                            <div class="event-header">
                                <div>
                                    <div class="flex items-center">
                                        <h3 class="event-title"><?= htmlspecialchars($event['event_name']) ?></h3>
                                        <!-- Webhook Status -->
                                        <?php if (!empty($event['n8n_initial_invite_webhook'])): ?>
                                            <span class="webhook-status webhook-configured">✓ Webhook</span>
                                        <?php else: ?>
                                            <span class="webhook-status webhook-missing">⚠ No Webhook</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="event-date"><?= htmlspecialchars($event['event_date_ar']) ?></p>
                                    <p class="text-sm text-gray-500">
                                        <?= $event['guest_count'] ?> <?= $lang === 'ar' ? 'ضيف' : 'guests' ?>, <?= $event['confirmed_count'] ?> <?= $lang === 'ar' ? 'مؤكد' : 'confirmed' ?>
                                    </p>
                                </div>
                                <div class="quick-copy">
                                    <button onclick="copyRegistrationLink(<?= $event['id'] ?>)" class="btn btn-secondary btn-small">
                                        📋 <?= $t['copy_link'] ?>
                                    </button>
                                    <div class="copy-tooltip" id="tooltip-<?= $event['id'] ?>"><?= $t['link_copied'] ?></div>
                                </div>
                            </div>

                            <!-- Event Statistics -->
                            <div class="event-stats" id="stats-<?= $event['id'] ?>">
                                <div class="stat-item">
                                    <span class="stat-number stat-total" data-stat="total">0</span>
                                    <div class="stat-label"><?= $t['total_guests'] ?></div>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number stat-confirmed" data-stat="confirmed">0</span>
                                    <div class="stat-label"><?= $t['confirmed_guests'] ?></div>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number stat-pending" data-stat="pending">0</span>
                                    <div class="stat-label"><?= $t['pending_guests'] ?></div>
                                </div>
                            </div>

                            <!-- Main Actions -->
                            <div class="event-actions">
                                <a href="guests.php?event_id=<?= $event['id'] ?>" class="btn btn-primary">
                                    👥 <?= $t['manage_guests'] ?>
                                </a>
                                <a href="admin.php?event_id=<?= $event['id'] ?>" class="btn btn-secondary">
                                    ⚙️ <?= $t['settings'] ?>
                                </a>
                                <a href="dashboard.php?event_id=<?= $event['id'] ?>" class="btn btn-success">
                                    📊 <?= $t['dashboard'] ?>
                                </a>
                                <a href="send_invitations.php?event_id=<?= $event['id'] ?>" class="btn btn-purple">
                                    📤 <?= $t['send_invitations'] ?>
                                </a>
                                <a href="checkin.php?event_id=<?= $event['id'] ?>" class="btn btn-warning">
                                    ✅ <?= $t['checkin'] ?>
                                </a>
                                <a href="register.php?event_id=<?= $event['id'] ?>" target="_blank" class="btn btn-success">
                                    🔗 <?= $t['registration_link'] ?>
                                </a>
                            </div>

                            <!-- Messaging Actions -->
                            <div class="messaging-actions">
                                <?php if (!empty($event['n8n_initial_invite_webhook'])): ?>
                                    <form method="POST" action="events.php" style="flex: 1;" onsubmit="return handleSendSubmit(this, <?= $event['id'] ?>)">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="messaging_action" value="send_to_all">
                                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                        <button type="submit" class="btn btn-pink w-full">
                                            📢 <?= $t['send_to_all'] ?>
                                        </button>
                                    </form>
                                    <button onclick="openGuestSelection(<?= $event['id'] ?>)" class="btn btn-purple" style="flex: 1;">
                                        🎯 <?= $t['send_to_selected'] ?>
                                    </button>
                                <?php else: ?>
                                    <div class="w-full text-center p-3 bg-yellow-100 text-yellow-800 rounded-lg text-sm">
                                        <?= $t['webhook_not_configured'] ?>
                                        <br>
                                        <a href="admin.php?event_id=<?= $event['id'] ?>" class="underline font-semibold">
                                            <?= $t['go_to_settings'] ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Send Results Section - محسّن مع ترتيب أفضل -->
                            <div class="send-results-section" id="send-results-<?= $event['id'] ?>">
                                <div class="flex justify-between items-center mb-3">
                                    <h4 class="font-semibold text-sm text-gray-700"><?= $t['last_send_results'] ?></h4>
                                    <button onclick="refreshSendResults(<?= $event['id'] ?>)" class="text-xs text-blue-600 hover:underline">
                                        🔄 <?= $t['refresh_results'] ?>
                                    </button>
                                </div>
                                <div id="results-content-<?= $event['id'] ?>">
                                    <p class="text-sm text-gray-500"><?= $t['no_send_history'] ?></p>
                                </div>
                            </div>

                            <!-- Delete Action -->
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                                <form method="POST" action="events.php" onsubmit="return confirm('<?= $t['confirm_delete_event'] ?>');" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="delete_id" value="<?= $event['id'] ?>">
                                    <button type="submit" name="delete_event" class="btn btn-danger btn-small">
                                        🗑️ <?= $t['delete'] ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Guest Selection Modal -->
    <div id="guestSelectionModal" class="modal">
        <div class="modal-content">
            <h2 class="text-2xl font-bold mb-6 text-gray-800"><?= $t['select_guests_title'] ?></h2>
            
            <div class="mb-4">
                <input type="text" id="guest-search" placeholder="<?= $t['search_guests'] ?>" 
                       class="w-full p-3 border border-gray-300 rounded-lg">
            </div>
            
            <div class="flex justify-between items-center mb-4">
                <div class="flex gap-2">
                    <button onclick="selectAllGuests()" class="btn btn-primary btn-small"><?= $t['select_all'] ?></button>
                    <button onclick="clearGuestSelection()" class="btn btn-secondary btn-small"><?= $t['clear_selection'] ?></button>
                </div>
                <span id="selection-count" class="text-gray-600">0 <?= $t['guests_selected'] ?></span>
            </div>
            
            <div class="guest-list" id="guest-list">
                <div class="text-center p-8 text-gray-500"><?= $t['processing'] ?></div>
            </div>
            
            <div class="flex justify-end gap-4 mt-6">
                <button onclick="closeGuestSelection()" class="btn btn-secondary"><?= $t['cancel'] ?></button>
                <button onclick="sendToSelectedGuests()" class="btn btn-success" id="send-selected-btn" disabled>
                    <?= $t['send_selected'] ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        const lang = '<?= $lang ?>';
        let currentEventId = null;
        let allGuests = [];
        let selectedGuests = [];

        // Load statistics and send results for all events
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($events as $event): ?>
                loadEventStats(<?= $event['id'] ?>);
                loadSendResults(<?= $event['id'] ?>);
            <?php endforeach; ?>
        });

        // Load event statistics
        async function loadEventStats(eventId) {
            try {
                const response = await fetch(`events.php?api=true&get_stats=true&event_id=${eventId}`);
                const stats = await response.json();
                
                const statsContainer = document.getElementById(`stats-${eventId}`);
                if (statsContainer) {
                    statsContainer.querySelector('[data-stat="total"]').textContent = stats.total || 0;
                    statsContainer.querySelector('[data-stat="confirmed"]').textContent = stats.confirmed || 0;
                    statsContainer.querySelector('[data-stat="pending"]').textContent = stats.pending || 0;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load send results مع تحسين الترتيب والعرض
        async function loadSendResults(eventId) {
            try {
                const response = await fetch(`events.php?api=true&get_send_results=true&event_id=${eventId}`);
                const results = await response.json();
                
                const resultsContainer = document.getElementById(`results-content-${eventId}`);
                if (resultsContainer && results.length > 0) {
                    let html = '';
                    results.slice(0, 3).forEach(result => {
                        const date = new Date(result.created_at);
                        const timeString = date.toLocaleString(lang === 'ar' ? 'ar-EG' : 'en-US', {
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        // تحسين عرض نوع الإجراء
                        let actionText = '';
                        switch(result.action_type) {
                            case 'send_global_all':
                                actionText = texts['send_global_all'];
                                break;
                            case 'send_event_all':
                                actionText = texts['send_event_all'];
                                break;
                            case 'send_selected':
                                actionText = texts['send_selected_guests'];
                                break;
                            default:
                                actionText = result.action_type;
                        }
                        
                        // حساب معدل النجاح
                        const total = result.success_count + result.failed_count;
                        const successRate = total > 0 ? Math.round((result.success_count / total) * 100) : 0;
                        
                        html += `
                            <div class="result-item">
                                <div class="result-meta">
                                    <div class="result-action">${actionText}</div>
                                    <div class="result-time">${timeString}</div>
                                </div>
                                <div class="result-numbers">
                                    <span class="result-success">✓ ${result.success_count}</span>
                                    ${result.failed_count > 0 ? `<span class="result-failed">✗ ${result.failed_count}</span>` : ''}
                                    ${total > 0 ? `<span class="result-rate">(${successRate}%)</span>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    resultsContainer.innerHTML = html;
                } else if (resultsContainer) {
                    resultsContainer.innerHTML = '<p class="text-sm text-gray-500">' + texts.no_send_history + '</p>';
                }
            } catch (error) {
                console.error('Error loading send results:', error);
            }
        }

        // Refresh send results
        function refreshSendResults(eventId) {
            loadSendResults(eventId);
        }

        // Handle form submission with loading overlay
        function handleSendSubmit(form, eventId) {
            const confirmed = confirm(texts['confirm_event_send']);
            if (confirmed) {
                showLoading(eventId);
                // Allow form to submit normally, then refresh after delay
                setTimeout(() => {
                    hideLoading(eventId);
                    loadSendResults(eventId);
                }, 2000);
            }
            return confirmed;
        }

        function showLoading(eventId) {
            const overlay = document.getElementById(`loading-${eventId}`);
            if (overlay) overlay.classList.add('active');
        }

        function hideLoading(eventId) {
            const overlay = document.getElementById(`loading-${eventId}`);
            if (overlay) overlay.classList.remove('active');
        }

        // Copy registration link
        function copyRegistrationLink(eventId) {
            const baseUrl = window.location.origin + window.location.pathname.replace('events.php', '');
            const registrationUrl = `${baseUrl}register.php?event_id=${eventId}`;
            
            navigator.clipboard.writeText(registrationUrl).then(() => {
                const tooltip = document.getElementById(`tooltip-${eventId}`);
                tooltip.classList.add('show');
                setTimeout(() => {
                    tooltip.classList.remove('show');
                }, 2000);
            });
        }

        // Guest selection modal functions
        async function openGuestSelection(eventId) {
            currentEventId = eventId;
            document.getElementById('guestSelectionModal').classList.add('active');
            document.getElementById('guest-list').innerHTML = '<div class="text-center p-8 text-gray-500">' + texts.processing + '</div>';
            
            try {
                const response = await fetch(`events.php?api=true&get_guests=true&event_id=${eventId}`);
                allGuests = await response.json();
                selectedGuests = [];
                renderGuestList();
                updateSelectionCount();
            } catch (error) {
                console.error('Error loading guests:', error);
                document.getElementById('guest-list').innerHTML = '<div class="text-center p-8 text-red-500">' + 
                    (lang === 'ar' ? 'خطأ في تحميل الضيوف' : 'Error loading guests') + '</div>';
            }
        }

        function renderGuestList(searchTerm = '') {
            const filteredGuests = allGuests.filter(guest => 
                guest.name_ar.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (guest.phone_number && guest.phone_number.includes(searchTerm))
            );

            const guestListHTML = filteredGuests.map(guest => {
                const isSelected = selectedGuests.includes(guest.id);
                const statusClass = `status-${guest.status}`;
                const statusText = guest.status === 'confirmed' ? texts.confirmed : 
                                 guest.status === 'canceled' ? texts.canceled : texts.pending;

                return `
                    <div class="guest-item">
                        <input type="checkbox" ${isSelected ? 'checked' : ''} 
                               onchange="toggleGuestSelection(${guest.id})" 
                               class="mr-2">
                        <div class="flex-grow">
                            <div class="font-medium">${guest.name_ar}</div>
                            <div class="text-sm text-gray-500">${guest.phone_number || (lang === 'ar' ? 'لا يوجد هاتف' : 'No phone')}</div>
                        </div>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                `;
            }).join('');

            document.getElementById('guest-list').innerHTML = guestListHTML || 
                '<div class="text-center p-8 text-gray-500">' + (lang === 'ar' ? 'لا يوجد ضيوف' : 'No guests') + '</div>';
        }

        function toggleGuestSelection(guestId) {
            const index = selectedGuests.indexOf(guestId);
            if (index > -1) {
                selectedGuests.splice(index, 1);
            } else {
                selectedGuests.push(guestId);
            }
            updateSelectionCount();
        }

        function selectAllGuests() {
            selectedGuests = allGuests.map(guest => guest.id);
            renderGuestList(document.getElementById('guest-search').value);
            updateSelectionCount();
        }

        function clearGuestSelection() {
            selectedGuests = [];
            renderGuestList(document.getElementById('guest-search').value);
            updateSelectionCount();
        }

        function updateSelectionCount() {
            const count = selectedGuests.length;
            document.getElementById('selection-count').textContent = `${count} ${texts.guests_selected}`;
            document.getElementById('send-selected-btn').disabled = count === 0;
        }

        function closeGuestSelection() {
            document.getElementById('guestSelectionModal').classList.remove('active');
            currentEventId = null;
            allGuests = [];
            selectedGuests = [];
        }

        function sendToSelectedGuests() {
            if (selectedGuests.length === 0) {
                alert(lang === 'ar' ? 'يرجى تحديد الضيوف المراد إرسال الدعوات لهم' : 'Please select guests to send invitations to');
                return;
            }

            const confirmMessage = lang === 'ar' ? 
                `إرسال دعوات لـ ${selectedGuests.length} ضيف؟` : 
                `Send invitations to ${selectedGuests.length} guests?`;

            if (confirm(confirmMessage)) {
                showLoading(currentEventId);
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'events.php';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = 'csrf_token';
                csrfToken.value = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';

                const messagingAction = document.createElement('input');
                messagingAction.type = 'hidden';
                messagingAction.name = 'messaging_action';
                messagingAction.value = 'send_to_selected';

                const eventIdInput = document.createElement('input');
                eventIdInput.type = 'hidden';
                eventIdInput.name = 'event_id';
                eventIdInput.value = currentEventId;

                const selectedGuestsInput = document.createElement('input');
                selectedGuestsInput.type = 'hidden';
                selectedGuestsInput.name = 'selected_guests';
                selectedGuestsInput.value = JSON.stringify(selectedGuests);

                form.appendChild(csrfToken);
                form.appendChild(messagingAction);
                form.appendChild(eventIdInput);
                form.appendChild(selectedGuestsInput);

                document.body.appendChild(form);
                form.submit();
                
                closeGuestSelection();
            }
        }

        // Guest search functionality
        document.getElementById('guest-search').addEventListener('input', function(e) {
            renderGuestList(e.target.value);
        });

        // Close modal when clicking outside
        document.getElementById('guestSelectionModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeGuestSelection();
            }
        });

        // Auto-refresh stats and results every 2 minutes
        setInterval(() => {
            <?php foreach ($events as $event): ?>
                loadEventStats(<?= $event['id'] ?>);
                loadSendResults(<?= $event['id'] ?>);
            <?php endforeach; ?>
        }, 120000);
    </script>
</body>
</html>