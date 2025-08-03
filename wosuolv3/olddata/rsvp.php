<?php
// rsvp.php - محسّن مع خلفية بيضاء وتقويم ديناميكي وصلاحية محددة
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

session_start();
require_once 'db_config.php';

// --- Language System ---
$lang = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'ar';
if (isset($_POST['switch_language'])) {
    $lang = $_POST['switch_language'] === 'en' ? 'en' : 'ar';
    $_SESSION['language'] = $lang;
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Language texts
$texts = [
    'ar' => [
        'invalid_link' => 'رابط غير صالح أو منتهي الصلاحية',
        'welcome_guest' => 'أهلاً وسهلاً',
        'dear_guest' => 'ضيفنا الكريم',
        'guest_count' => 'عدد الضيوف',
        'table_number' => 'رقم الطاولة',
        'view_location' => 'عرض الموقع',
        'confirm_attendance' => 'تأكيد الحضور',
        'decline_attendance' => 'الاعتذار عن الحضور',
        'add_to_calendar' => 'إضافة للتقويم',
        'share_invitation' => 'مشاركة الدعوة',
        'get_directions' => 'الحصول على الاتجاهات',
        'entry_card' => 'بطاقة الدخول',
        'qr_code' => 'رمز QR للدخول',
        'show_at_entrance' => 'أظهر هذا الرمز عند المدخل',
        'download_qr' => 'تحميل رمز QR',
        'already_confirmed' => 'تم تأكيد حضورك مسبقاً',
        'already_declined' => 'تم تسجيل اعتذارك مسبقاً',
        'success_confirmed' => 'تم تأكيد حضورك بنجاح!',
        'success_declined' => 'تم تسجيل اعتذارك',
        'error_occurred' => 'حدث خطأ، يرجى المحاولة مرة أخرى',
        'connection_error' => 'خطأ في الاتصال',
        'csrf_error' => 'خطأ أمني، يرجى إعادة تحميل الصفحة',
        'rate_limit_error' => 'تم إرسال طلبات كثيرة، يرجى الانتظار',
        'powered_by' => 'مدعوم من',
        'all_rights_reserved' => 'جميع الحقوق محفوظة',
        'link_expired' => 'انتهت صلاحية الرابط (الصلاحية: 30 يوماً من تاريخ الإنشاء)',
        'assigned_location' => 'الموقع المخصص'
    ],
    'en' => [
        'invalid_link' => 'Invalid or expired link',
        'welcome_guest' => 'Welcome',
        'dear_guest' => 'Dear Guest',
        'guest_count' => 'Guest Count',
        'table_number' => 'Table Number',
        'view_location' => 'View Location',
        'confirm_attendance' => 'Confirm Attendance',
        'decline_attendance' => 'Decline Attendance',
        'add_to_calendar' => 'Add to Calendar',
        'share_invitation' => 'Share Invitation',
        'get_directions' => 'Get Directions',
        'entry_card' => 'Entry Card',
        'qr_code' => 'QR Code for Entry',
        'show_at_entrance' => 'Show this code at entrance',
        'download_qr' => 'Download QR Code',
        'already_confirmed' => 'Your attendance has been confirmed',
        'already_declined' => 'Your decline has been recorded',
        'success_confirmed' => 'Your attendance confirmed successfully!',
        'success_declined' => 'Your decline recorded',
        'error_occurred' => 'An error occurred, please try again',
        'connection_error' => 'Connection error',
        'csrf_error' => 'Security error, please reload the page',
        'rate_limit_error' => 'Too many requests, please wait',
        'powered_by' => 'Powered by',
        'all_rights_reserved' => 'All Rights Reserved',
        'link_expired' => 'Link has expired (Validity: 30 days from creation)',
        'assigned_location' => 'Assigned Location'
    ]
];

$t = $texts[$lang];

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Rate Limiting ---
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'rsvp_rate_limit_' . md5($client_ip);
$current_time = time();

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'first_attempt' => $current_time];
}

if ($current_time - $_SESSION[$rate_limit_key]['first_attempt'] > 300) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'first_attempt' => $current_time];
}

// --- Data Initialization ---
$guest_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$event_data = null;
$guest_data = null;
$error_message = '';
$is_rate_limited = $_SESSION[$rate_limit_key]['count'] >= 10;

if (empty($guest_id)) {
    $error_message = $t['invalid_link'];
} else {
    // Fetch guest data with prepared statements and check expiry (30 days)
    $sql_guest = "SELECT g.*, e.* FROM guests g 
                  JOIN events e ON g.event_id = e.id 
                  WHERE g.guest_id = ? 
                  AND g.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                  LIMIT 1";
    
    if ($stmt_guest = $mysqli->prepare($sql_guest)) {
        $stmt_guest->bind_param("s", $guest_id);
        $stmt_guest->execute();
        $result_guest = $stmt_guest->get_result();
        
        if ($result_guest->num_rows === 1) {
            $combined_data = $result_guest->fetch_assoc();
            
            // Separate guest and event data
            $guest_data = [
                'id' => $combined_data['id'],
                'guest_id' => $combined_data['guest_id'],
                'name_ar' => $combined_data['name_ar'],
                'phone_number' => $combined_data['phone_number'],
                'guests_count' => $combined_data['guests_count'],
                'table_number' => $combined_data['table_number'],
                'assigned_location' => $combined_data['assigned_location'],
                'status' => $combined_data['status'],
                'checkin_status' => $combined_data['checkin_status'],
                'created_at' => $combined_data['created_at']
            ];
            
            $event_data = [
                'id' => $combined_data['event_id'],
                'event_name' => $combined_data['event_name'],
                'bride_name_ar' => $combined_data['bride_name_ar'],
                'groom_name_ar' => $combined_data['groom_name_ar'],
                'event_date_ar' => $combined_data['event_date_ar'],
                'venue_ar' => $combined_data['venue_ar'],
                'Maps_link' => $combined_data['Maps_link'],
                'event_paragraph_ar' => $combined_data['event_paragraph_ar'],
                'background_image_url' => $combined_data['background_image_url'],
                'qr_card_title_ar' => $combined_data['qr_card_title_ar'],
                'qr_show_code_instruction_ar' => $combined_data['qr_show_code_instruction_ar'],
                'qr_brand_text_ar' => $combined_data['qr_brand_text_ar'],
                'qr_website' => $combined_data['qr_website'],
                'n8n_confirm_webhook' => $combined_data['n8n_confirm_webhook']
            ];
        } else {
            $error_message = $t['link_expired'];
        }
        $stmt_guest->close();
    }
}

// --- Handle AJAX RSVP Response ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_rsvp']) && !isset($_POST['switch_language'])) {
    header('Content-Type: application/json');
    
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => $t['csrf_error']]);
        exit;
    }
    
    // Rate Limiting Check
    if ($is_rate_limited) {
        echo json_encode(['success' => false, 'message' => $t['rate_limit_error']]);
        exit;
    }
    
    $_SESSION[$rate_limit_key]['count']++;
    
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
    $guest_id_post = filter_input(INPUT_POST, 'guest_id', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (!in_array($status, ['confirmed', 'canceled']) || empty($guest_id_post)) {
        echo json_encode(['success' => false, 'message' => $t['error_occurred']]);
        exit;
    }
    
    // Update guest status
    $sql_update = "UPDATE guests SET status = ?, checkin_time = CASE WHEN ? = 'confirmed' THEN NOW() ELSE checkin_time END WHERE guest_id = ?";
    
    if ($stmt_update = $mysqli->prepare($sql_update)) {
        $stmt_update->bind_param("sss", $status, $status, $guest_id_post);
        
        if ($stmt_update->execute() && $stmt_update->affected_rows > 0) {
            // Call webhook if confirmed and webhook exists
            if ($status === 'confirmed' && !empty($event_data['n8n_confirm_webhook'])) {
                $webhook_url = filter_var($event_data['n8n_confirm_webhook'], FILTER_VALIDATE_URL);
                if ($webhook_url) {
                    $webhook_payload = json_encode([
                        'guest_id' => $guest_id_post,
                        'phone_number' => $guest_data['phone_number'] ?? '',
                        'timestamp' => time()
                    ]);
                    
                    $ch = curl_init($webhook_url);
                    curl_setopt_array($ch, [
                        CURLOPT_CUSTOMREQUEST => "POST",
                        CURLOPT_POSTFIELDS => $webhook_payload,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json',
                            'Content-Length: ' . strlen($webhook_payload)
                        ]
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                }
            }
            
            $message = $status === 'confirmed' ? $t['success_confirmed'] : $t['success_declined'];
            echo json_encode(['success' => true, 'message' => $message, 'status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => $t['error_occurred']]);
        }
        $stmt_update->close();
    } else {
        echo json_encode(['success' => false, 'message' => $t['error_occurred']]);
    }
    
    $mysqli->close();
    exit;
}

// --- Helper Functions ---
function generateCalendarData($event_data, $lang) {
    // استخراج التاريخ من النص العربي أو الإنجليزي
    $event_date_text = $lang === 'ar' ? ($event_data['event_date_ar'] ?? '') : ($event_data['event_date_en'] ?? '');
    
    // محاولة استخراج التاريخ بطرق مختلفة
    $calendar_date = null;
    $time_string = '';
    
    // البحث عن أنماط التاريخ الشائعة
    if (preg_match('/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/', $event_date_text, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        $calendar_date = "$year$month$day";
    } elseif (preg_match('/(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/', $event_date_text, $matches)) {
        $year = $matches[1];
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
        $calendar_date = "$year$month$day";
    } else {
        // تاريخ افتراضي إذا لم نجد تاريخ محدد
        $calendar_date = date('Ymd', strtotime('+1 week'));
    }
    
    // البحث عن الوقت
    if (preg_match('/(\d{1,2}):(\d{2})\s*(ص|م|صباحا|مساء|AM|PM)/i', $event_date_text, $time_matches)) {
        $hour = intval($time_matches[1]);
        $minute = $time_matches[2];
        $period = $time_matches[3];
        
        // تحويل إلى 24 ساعة
        if (preg_match('/(م|مساء|PM)/i', $period) && $hour < 12) {
            $hour += 12;
        } elseif (preg_match('/(ص|صباحا|AM)/i', $period) && $hour == 12) {
            $hour = 0;
        }
        
        $time_string = str_pad($hour, 2, '0', STR_PAD_LEFT) . str_pad($minute, 2, '0', STR_PAD_LEFT) . '00';
    } else {
        $time_string = '200000'; // 8:00 PM افتراضي
    }
    
    return [
        'date' => $calendar_date,
        'time' => $time_string,
        'datetime' => $calendar_date . 'T' . $time_string,
        'end_datetime' => $calendar_date . 'T' . date('His', strtotime($time_string) + 3600) // ساعة إضافية
    ];
}

function safeHtml($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

$calendar_data = generateCalendarData($event_data, $lang);

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $event_data ? safeHtml($event_data['event_name']) : 'دعوة' ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= safeHtml($event_data['event_paragraph_ar'] ?? 'دعوة خاصة') ?>">
    <meta name="keywords" content="دعوة,حفل,زفاف,invitation,wedding">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= safeHtml($event_data['event_name'] ?? 'دعوة') ?>">
    <meta property="og:description" content="<?= safeHtml($event_data['event_paragraph_ar'] ?? 'دعوة خاصة') ?>">
    <meta property="og:image" content="<?= safeHtml($event_data['background_image_url'] ?? '') ?>">
    
    <!-- Fonts & Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background: white; /* خلفية بيضاء */
            min-height: 100vh;
            display: flex; 
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
            padding: 20px;
        }
        
        .card-container { 
            max-width: 500px; 
            width: 100%; 
            background: white;
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            overflow: hidden;
            border: 1px solid #e5e7eb;
            position: relative;
        }
        
        .language-toggle {
            position: absolute;
            top: 15px;
            <?= $lang === 'ar' ? 'left: 15px' : 'right: 15px' ?>;
            z-index: 10;
        }
        
        .language-toggle button {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #374151;
        }
        
        .language-toggle button:hover {
            background: #f3f4f6;
            transform: translateY(-1px);
        }
        
        .description-box {
            padding: 40px 25px;
            background: #f8f9fa;
            text-align: center;
            color: #374151;
            font-size: 1.1rem;
            line-height: 1.8;
        }
        
        .card-content { 
            padding: 30px; 
            background: white;
        }
        
        .guest-welcome {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-radius: 15px;
            border: 1px solid #60a5fa;
        }
        
        .guest-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .detail-item {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .detail-value {
            font-weight: bold;
            color: #374151;
        }
        
        .location-card {
            padding: 20px;
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-radius: 12px;
            border: 1px solid #22c55e;
            margin: 20px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .action-buttons button {
            flex: 1;
            padding: 15px;
            border-radius: 12px;
            font-weight: bold;
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .btn-decline {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }
        
        .action-buttons button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .action-buttons button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .qr-code-section {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            padding: 30px;
            display: none;
            text-align: center;
            border-top: 1px solid #f59e0b;
        }
        
        .qr-code-section.active {
            display: block;
            animation: slideDown 0.5s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .qr-grid {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            grid-template-rows: auto auto auto;
            gap: 15px;
            align-items: center;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .qr-title-box {
            grid-column: 1 / 4;
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .qr-code-container {
            grid-column: 2 / 3;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .qr-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .share-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .share-button {
            padding: 10px 15px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .share-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-calendar { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-share { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
        .btn-download { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-location { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(16, 185, 129, 0.95);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.error {
            background: rgba(239, 68, 68, 0.95);
        }
        
        .error-container { 
            text-align: center; 
            padding: 60px 40px;
            background: white;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 20px;
        }
        
        .event-image-container {
            position: relative;
            overflow: hidden;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }
        
        .event-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            object-position: center;
            display: block;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .event-image:hover {
            transform: scale(1.02);
        }
        
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            cursor: pointer;
        }
        
        .image-modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .image-modal .close-button {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            z-index: 10000;
        }
        
        .footer-brand {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(229, 231, 235, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .wosuol-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
        }
        
        .wosuol-icon {
            width: 45px;
            height: 45px;
            background: #4f46e5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
        }
        
        .wosuol-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .wosuol-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e40af;
            line-height: 1.2;
        }
        
        .wosuol-subtitle {
            font-size: 0.7rem;
            color: #6b7280;
            font-weight: 400;
            line-height: 1.2;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @media (max-width: 640px) {
            .card-container {
                margin: 10px;
                max-width: calc(100vw - 20px);
            }
            
            .guest-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .share-buttons {
                flex-direction: column;
            }
            
            .qr-grid {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto auto;
            }
            
            .qr-code-container {
                grid-column: 1 / 2;
            }
            
            .footer-brand {
                flex-direction: column;
                gap: 10px;
            }
            
            .wosuol-logo {
                font-size: 1rem;
            }
            
            .wosuol-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- Language Toggle -->
        <div class="language-toggle">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>">
                    <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                </button>
            </form>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-container">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4"><?= $t['invalid_link'] ?></h2>
                <p class="text-lg text-gray-600"><?= htmlspecialchars($error_message) ?></p>
                
                <?php if (strpos($error_message, 'انتهت صلاحية') !== false): ?>
                    <div class="mt-4 p-4 bg-yellow-100 border border-yellow-400 rounded-lg">
                        <p class="text-yellow-800 text-sm">
                            <i class="fas fa-info-circle"></i>
                            صلاحية روابط RSVP: <strong>30 يوماً</strong> من تاريخ الإنشاء
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            
            <!-- Event Image or Description -->
            <?php if (!empty($event_data['background_image_url'])): ?>
                <div class="event-image-container">
                    <img src="<?= safeHtml($event_data['background_image_url']) ?>" 
                         alt="<?= safeHtml($event_data['event_name']) ?>" 
                         class="event-image"
                         loading="lazy"
                         onclick="toggleImageView(this)">
                </div>
            <?php else: ?>
                <div class="description-box">
                    <p><?= nl2br(safeHtml($event_data['event_paragraph_ar'] ?? 'مرحباً بكم في مناسبتنا الخاصة.')) ?></p>
                </div>
            <?php endif; ?>

            <div class="card-content" id="main-content">
                <!-- Guest Welcome Section -->
                <div class="guest-welcome">
                    <h2 class="text-xl font-bold text-blue-800 mb-2">
                        <?= $t['welcome_guest'] ?>
                    </h2>
                    <p class="text-lg font-semibold text-blue-700">
                        <?= safeHtml($guest_data['name_ar'] ?? $t['dear_guest']) ?>
                    </p>
                </div>

                <!-- Guest Details -->
                <div class="guest-details">
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-users"></i>
                            <?= $t['guest_count'] ?>
                        </div>
                        <div class="detail-value"><?= safeHtml($guest_data['guests_count'] ?? '1') ?></div>
                    </div>
                    
                    <?php if (!empty($guest_data['table_number'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-chair"></i>
                            <?= $t['table_number'] ?>
                        </div>
                        <div class="detail-value"><?= safeHtml($guest_data['table_number']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($guest_data['assigned_location'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= $t['assigned_location'] ?>
                        </div>
                        <div class="detail-value"><?= safeHtml($guest_data['assigned_location']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Location Card -->
                <?php if (!empty($event_data['venue_ar']) || !empty($event_data['Maps_link'])): ?>
                <div class="location-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-green-800 mb-1">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= safeHtml($event_data['venue_ar'] ?? $t['view_location']) ?>
                            </h3>
                            <?php if (!empty($event_data['event_date_ar'])): ?>
                            <p class="text-sm text-green-700">
                                <i class="fas fa-calendar"></i>
                                <?= safeHtml($event_data['event_date_ar']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($event_data['Maps_link'])): ?>
                        <a href="<?= safeHtml($event_data['Maps_link']) ?>" 
                           target="_blank" 
                           class="text-green-600 hover:text-green-800 transition-colors">
                            <i class="fas fa-external-link-alt text-xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div id="action-buttons-section" class="action-buttons">
                    <button id="confirm-button" class="btn-confirm" onclick="handleRSVP('confirmed')">
                        <div class="spinner" id="confirm-spinner"></div>
                        <span id="confirm-text">
                            <i class="fas fa-check"></i>
                            <?= $t['confirm_attendance'] ?>
                        </span>
                    </button>
                    <button id="cancel-button" class="btn-decline" onclick="handleRSVP('canceled')">
                        <div class="spinner" id="cancel-spinner"></div>
                        <span id="cancel-text">
                            <i class="fas fa-times"></i>
                            <?= $t['decline_attendance'] ?>
                        </span>
                    </button>
                </div>
                
                <!-- Response Message -->
                <div id="response-message" class="hidden mt-6 p-4 rounded-lg text-center font-semibold"></div>
                
                <!-- Share Buttons -->
                <div class="share-buttons">
                    <button onclick="addToCalendar()" class="share-button btn-calendar">
                        <i class="fas fa-calendar-plus"></i>
                        <?= $t['add_to_calendar'] ?>
                    </button>
                    
                    <button onclick="shareInvitation()" class="share-button btn-share">
                        <i class="fas fa-share-alt"></i>
                        <?= $t['share_invitation'] ?>
                    </button>
                    
                    <?php if (!empty($event_data['Maps_link'])): ?>
                    <button onclick="openLocation()" class="share-button btn-location">
                        <i class="fas fa-map-marked-alt"></i>
                        <?= $t['get_directions'] ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- QR Code Section -->
            <div id="qr-code-section" class="qr-code-section">
                <div class="qr-grid">
                    <div class="qr-title-box">
                        <h3 class="text-xl font-bold text-amber-800 mb-2">
                            <i class="fas fa-qrcode"></i>
                            <?= safeHtml($event_data['qr_card_title_ar'] ?? $t['entry_card']) ?>
                        </h3>
                        <p class="text-sm text-amber-700"><?= $t['qr_code'] ?></p>
                    </div>
                    
                    <div class="qr-info qr-info-left">
                        <div class="text-center">
                            <div class="text-xs text-gray-600 mb-1"><?= $t['guest_count'] ?></div>
                            <div class="text-2xl font-bold text-gray-800"><?= safeHtml($guest_data['guests_count'] ?? '1') ?></div>
                        </div>
                        <div class="text-xs text-gray-600 mt-4">
                            <?= safeHtml($event_data['qr_brand_text_ar'] ?? 'وصول') ?>
                        </div>
                    </div>
                    
                    <div id="qrcode" class="qr-code-container"></div>
                    
                    <div class="qr-info qr-info-right text-center">
                        <p class="text-sm font-semibold text-gray-700 mb-2">
                            <?= safeHtml($event_data['qr_show_code_instruction_ar'] ?? $t['show_at_entrance']) ?>
                        </p>
                        <div class="text-xs text-gray-600">
                            <?= safeHtml($event_data['qr_website'] ?? 'wosuol.com') ?>
                        </div>
                    </div>
                </div>
                
                <!-- QR Action Buttons -->
                <div class="share-buttons mt-6">
                    <button onclick="downloadQR()" class="share-button btn-download">
                        <i class="fas fa-download"></i>
                        <?= $t['download_qr'] ?>
                    </button>
                    
                    <button onclick="shareQR()" class="share-button btn-share">
                        <i class="fas fa-share"></i>
                        <?= $t['share_invitation'] ?>
                    </button>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <!-- Footer with Wosuol Branding -->
    <div class="footer-brand">
        <a href="https://wosuol.com" target="_blank" class="wosuol-logo">
            <div class="wosuol-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="wosuol-text">
                <div class="wosuol-title">وصول</div>
                <div class="wosuol-subtitle">نظام إدارة الفعاليات والدعوات</div>
            </div>
        </a>
        <div style="color: #6b7280; font-size: 0.8rem;">
            © 2025 <?= $t['all_rights_reserved'] ?>
        </div>
    </div>

    <!-- Image Modal for full screen view -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <span class="close-button" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" src="" alt="Full size image">
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div id="toast-message"></div>
    </div>

    <?php if (empty($error_message)): ?>
    <script>
        // Configuration and Data
        const CONFIG = {
            guestData: <?= json_encode($guest_data, JSON_UNESCAPED_UNICODE) ?>,
            eventData: <?= json_encode($event_data, JSON_UNESCAPED_UNICODE) ?>,
            texts: <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>,
            lang: '<?= $lang ?>',
            csrfToken: '<?= htmlspecialchars($_SESSION['csrf_token']) ?>',
            calendarData: <?= json_encode($calendar_data, JSON_UNESCAPED_UNICODE) ?>
        };

        // Global state
        let qrCodeGenerated = false;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkInitialStatus();
            preloadQRLibrary();
        });

        // Check initial guest status
        function checkInitialStatus() {
            const status = CONFIG.guestData.status;
            
            if (status === 'confirmed') {
                showSuccessState('confirmed');
            } else if (status === 'canceled') {
                showSuccessState('canceled');
            }
        }

        // Handle RSVP response
        async function handleRSVP(status) {
            const confirmBtn = document.getElementById('confirm-button');
            const cancelBtn = document.getElementById('cancel-button');
            const spinner = document.getElementById(status === 'confirmed' ? 'confirm-spinner' : 'cancel-spinner');
            const text = document.getElementById(status === 'confirmed' ? 'confirm-text' : 'cancel-text');
            
            // Disable buttons and show loading
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            spinner.style.display = 'inline-block';
            
            try {
                const formData = new FormData();
                formData.append('ajax_rsvp', '1');
                formData.append('status', status);
                formData.append('guest_id', CONFIG.guestData.guest_id);
                formData.append('csrf_token', CONFIG.csrfToken);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showSuccessState(status);
                    showToast(result.message, 'success');
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                console.error('RSVP Error:', error);
                showToast(error.message || CONFIG.texts.connection_error, 'error');
                
                // Re-enable buttons
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
            } finally {
                spinner.style.display = 'none';
            }
        }

        // Show success state
        function showSuccessState(status) {
            const actionButtons = document.getElementById('action-buttons-section');
            const responseMessage = document.getElementById('response-message');
            const qrSection = document.getElementById('qr-code-section');
            
            actionButtons.style.display = 'none';
            
            if (status === 'confirmed') {
                responseMessage.className = 'mt-6 p-4 rounded-lg text-center font-semibold bg-green-100 text-green-800';
                responseMessage.innerHTML = `
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    ${CONFIG.texts.already_confirmed}
                `;
                responseMessage.style.display = 'block';
                
                // Show QR code section
                qrSection.classList.add('active');
                generateQRCode();
            } else {
                responseMessage.className = 'mt-6 p-4 rounded-lg text-center font-semibold bg-red-100 text-red-800';
                responseMessage.innerHTML = `
                    <i class="fas fa-times-circle text-red-600 mr-2"></i>
                    ${CONFIG.texts.already_declined}
                `;
                responseMessage.style.display = 'block';
            }
        }

        // Generate QR Code
        function generateQRCode() {
            if (qrCodeGenerated) return;
            
            const qrcodeContainer = document.getElementById('qrcode');
            qrcodeContainer.innerHTML = '';
            
            try {
                new QRCode(qrcodeContainer, {
                    text: CONFIG.guestData.guest_id,
                    width: 150,
                    height: 150,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.M
                });
                qrCodeGenerated = true;
            } catch (error) {
                console.error('QR Generation Error:', error);
                qrcodeContainer.innerHTML = '<div class="text-red-500">QR Code generation failed</div>';
            }
        }

        // Rest of the JavaScript functions remain the same...
        // [Calendar, sharing, toast functions - keeping them as they were]

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = `toast ${type === 'error' ? 'error' : ''}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Image handling functions
        function toggleImageView(img) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            
            modalImg.src = img.src;
            modal.classList.add('active');
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            
            // Restore body scroll
            document.body.style.overflow = 'auto';
        }

        // Enhanced Dynamic Calendar Function
        function addToCalendar() {
            const calendarData = CONFIG.calendarData;
            const eventData = CONFIG.eventData;
            
            const title = encodeURIComponent(eventData.event_name || 'Event');
            const location = encodeURIComponent(eventData.venue_ar || '');
            const details = encodeURIComponent(eventData.event_paragraph_ar || '');
            
            const startDate = calendarData.datetime;
            const endDate = calendarData.end_datetime;
            
            const userAgent = navigator.userAgent;
            const isIOS = /iPad|iPhone|iPod/.test(userAgent);
            const isAndroid = /Android/.test(userAgent);
            
            const calendarOptions = {
                google: `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${startDate}/${endDate}&details=${details}&location=${location}`,
                outlook: `https://outlook.live.com/calendar/0/deeplink/compose?subject=${title}&startdt=${startDate}&enddt=${endDate}&body=${details}&location=${location}`,
                yahoo: `https://calendar.yahoo.com/?v=60&view=d&type=20&title=${title}&st=${startDate}&et=${endDate}&desc=${details}&in_loc=${location}`,
                ics: generateICSFile(eventData, calendarData)
            };
            
            if (isIOS) {
                const icsUrl = calendarOptions.ics;
                if (icsUrl) {
                    const link = document.createElement('a');
                    link.href = icsUrl;
                    link.download = 'invitation-event.ics';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    showToast('تم إنشاء ملف التقويم!', 'success');
                } else {
                    window.open(calendarOptions.google, '_blank');
                }
            } else if (isAndroid) {
                window.open(calendarOptions.google, '_blank');
            } else {
                showCalendarOptions(calendarOptions);
            }
            
            showToast('Opening calendar...', 'success');
        }

        function generateICSFile(eventData, calendarData) {
            try {
                const now = new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
                const startDateTime = calendarData.datetime.replace(/[-:]/g, '') + 'Z';
                const endDateTime = calendarData.end_datetime.replace(/[-:]/g, '') + 'Z';
                
                const icsContent = [
                    'BEGIN:VCALENDAR',
                    'VERSION:2.0',
                    'PRODID:-//Wosuol//Event Invitation//EN',
                    'BEGIN:VEVENT',
                    `UID:${CONFIG.guestData.guest_id}@wosuol.com`,
                    `DTSTAMP:${now}`,
                    `DTSTART:${startDateTime}`,
                    `DTEND:${endDateTime}`,
                    `SUMMARY:${eventData.event_name || 'Event'}`,
                    `DESCRIPTION:${eventData.event_paragraph_ar || 'دعوة خاصة'}`,
                    `LOCATION:${eventData.venue_ar || ''}`,
                    'STATUS:CONFIRMED',
                    'END:VEVENT',
                    'END:VCALENDAR'
                ].join('\r\n');
                
                const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
                return URL.createObjectURL(blob);
            } catch (error) {
                console.error('ICS Generation Error:', error);
                return null;
            }
        }

        function showCalendarOptions(options) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white p-6 rounded-lg max-w-sm w-full mx-4">
                    <h3 class="text-lg font-bold mb-4 text-center">اختر التقويم</h3>
                    <div class="space-y-2">
                        <button onclick="window.open('${options.google}', '_blank'); closeModal()" 
                                class="w-full p-3 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Google Calendar
                        </button>
                        <button onclick="window.open('${options.outlook}', '_blank'); closeModal()" 
                                class="w-full p-3 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Outlook Calendar
                        </button>
                        <button onclick="window.open('${options.yahoo}', '_blank'); closeModal()" 
                                class="w-full p-3 bg-purple-500 text-white rounded hover:bg-purple-600">
                            Yahoo Calendar
                        </button>
                        <button onclick="downloadICS(); closeModal()" 
                                class="w-full p-3 bg-gray-500 text-white rounded hover:bg-gray-600">
                            تحميل ملف ICS
                        </button>
                    </div>
                    <button onclick="closeModal()" class="w-full mt-4 p-2 border rounded hover:bg-gray-100">
                        إلغاء
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            window.closeModal = function() {
                document.body.removeChild(modal);
                delete window.closeModal;
                delete window.downloadICS;
            };
            
            window.downloadICS = function() {
                const icsUrl = generateICSFile(CONFIG.eventData, CONFIG.calendarData);
                if (icsUrl) {
                    const link = document.createElement('a');
                    link.href = icsUrl;
                    link.download = 'invitation-event.ics';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    showToast('تم تحميل ملف التقويم!', 'success');
                }
            };
        }

        // Share invitation
        async function shareInvitation() {
            const shareData = {
                title: CONFIG.eventData.event_name,
                text: `${CONFIG.texts.share_invitation} - ${CONFIG.eventData.event_name}`,
                url: window.location.href
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                } else {
                    await navigator.clipboard.writeText(window.location.href);
                    showToast('Link copied to clipboard!', 'success');
                }
            } catch (error) {
                console.error('Share Error:', error);
                fallbackShare();
            }
        }

        // Download QR Code
        function downloadQR() {
            try {
                const qrCanvas = document.querySelector('#qrcode canvas');
                if (!qrCanvas) {
                    showToast('QR Code not generated yet', 'error');
                    return;
                }

                const link = document.createElement('a');
                link.download = `invitation-qr-${CONFIG.guestData.guest_id}.png`;
                link.href = qrCanvas.toDataURL('image/png');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                showToast('QR Code downloaded successfully!', 'success');
            } catch (error) {
                console.error('Download Error:', error);
                showToast('Download failed', 'error');
            }
        }

        // Share QR Code
        async function shareQR() {
            try {
                const qrCanvas = document.querySelector('#qrcode canvas');
                if (!qrCanvas) {
                    showToast('QR Code not generated yet', 'error');
                    return;
                }

                if (navigator.share && navigator.canShare) {
                    qrCanvas.toBlob(async (blob) => {
                        const file = new File([blob], 'invitation-qr.png', { type: 'image/png' });
                        
                        if (navigator.canShare({ files: [file] })) {
                            await navigator.share({
                                title: CONFIG.eventData.event_name,
                                text: `${CONFIG.texts.share_invitation} - ${CONFIG.eventData.event_name}`,
                                files: [file]
                            });
                        } else {
                            fallbackShare();
                        }
                    });
                } else {
                    fallbackShare();
                }
            } catch (error) {
                console.error('Share Error:', error);
                fallbackShare();
            }
        }

        // Fallback share method
        function fallbackShare() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                showToast('Link copied to clipboard!', 'success');
            }).catch(() => {
                prompt('Copy this link:', url);
            });
        }

        // Open location
        function openLocation() {
            if (CONFIG.eventData.Maps_link) {
                window.open(CONFIG.eventData.Maps_link, '_blank');
                showToast('Opening maps...', 'success');
            }
        }

        // Preload QR library for better performance
        function preloadQRLibrary() {
            if (CONFIG.guestData.status === 'confirmed') {
                generateQRCode();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });

        // Enhanced error handling
        window.addEventListener('error', function(e) {
            console.error('Global Error:', e.error);
            showToast(CONFIG.texts.error_occurred, 'error');
        });

        // Performance optimization - lazy load non-critical features
        if ('IntersectionObserver' in window) {
            const qrSection = document.getElementById('qr-code-section');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !qrCodeGenerated) {
                        generateQRCode();
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            if (qrSection) {
                observer.observe(qrSection);
            }
        }

        // Accessibility improvements
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'BUTTON') {
                e.target.click();
            }
            
            if (e.key === 'Escape') {
                const toast = document.getElementById('toast');
                if (toast.classList.contains('show')) {
                    toast.classList.remove('show');
                }
            }
        });

        // Enhanced security - disable right-click context menu on sensitive elements
        document.querySelectorAll('#qrcode, .qr-code-container').forEach(element => {
            element.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>