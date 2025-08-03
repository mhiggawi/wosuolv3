<?php
// v2 rsvp.php - محسّن لحفلات الزفاف مع العداد التنازلي
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
    // Redirect to avoid re-posting
    $redirect_url = $_SERVER['REQUEST_URI'];
    header("Location: $redirect_url");
    exit;
}

// Language texts
$texts = [
    'ar' => [
        'welcome_guest' => 'مرحباً بكم',
        'dear_guest' => 'ضيفنا الكريم',
        'guest_count' => 'عدد الضيوف',
        'table_number' => 'رقم الطاولة',
        'confirm_attendance' => 'تأكيد الحضور',
        'decline_attendance' => 'الاعتذار عن الحضور',
        'add_to_calendar' => 'إضافة للتقويم',
        'share_invitation' => 'مشاركة الدعوة',
        'get_directions' => 'الحصول على الاتجاهات',
        'download_qr' => 'تحميل QR',
        'entry_card' => 'بطاقة الدخول',
        'qr_code' => 'رمز الاستجابة السريعة',
        'show_at_entrance' => 'أظهر هذا الرمز عند الدخول',
        'already_confirmed' => 'تم تأكيد حضورك بنجاح!',
        'already_declined' => 'تم تسجيل اعتذارك',
        'success_confirmed' => 'تم تأكيد حضورك بنجاح!',
        'success_declined' => 'شكراً لك، تم تسجيل اعتذارك عن الحضور.',
        'error_occurred' => 'حدث خطأ، يرجى المحاولة مرة أخرى',
        'invalid_link' => 'رابط الدعوة غير صالح',
        'csrf_error' => 'خطأ في الحماية، يرجى إعادة تحميل الصفحة',
        'rate_limit_error' => 'تم إرسال طلبات كثيرة، يرجى الانتظار',
        'connection_error' => 'خطأ في الاتصال'
    ],
    'en' => [
        'welcome_guest' => 'Welcome',
        'dear_guest' => 'Dear Guest',
        'guest_count' => 'Guest Count',
        'table_number' => 'Table Number',
        'confirm_attendance' => 'Confirm Attendance',
        'decline_attendance' => 'Decline Attendance',
        'add_to_calendar' => 'Add to Calendar',
        'share_invitation' => 'Share Invitation',
        'get_directions' => 'Get Directions',
        'download_qr' => 'Download QR',
        'entry_card' => 'Entry Card',
        'qr_code' => 'QR Code',
        'show_at_entrance' => 'Show this code at entrance',
        'already_confirmed' => 'Your attendance has been confirmed!',
        'already_declined' => 'Your decline has been recorded',
        'success_confirmed' => 'Your attendance has been confirmed successfully!',
        'success_declined' => 'Thank you, your decline has been recorded.',
        'error_occurred' => 'An error occurred, please try again',
        'invalid_link' => 'Invalid invitation link',
        'csrf_error' => 'Security error, please reload the page',
        'rate_limit_error' => 'Too many requests, please wait',
        'connection_error' => 'Connection error'
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
    // Fetch guest data with prepared statements
    $sql_guest = "SELECT g.*, e.* FROM guests g 
                  JOIN events e ON g.event_id = e.id 
                  WHERE g.guest_id = ? LIMIT 1";
    
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
                'status' => $combined_data['status'],
                'checkin_status' => $combined_data['checkin_status']
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
            $error_message = $t['invalid_link'];
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

function parseEventDate($event_date_text) {
    // استخراج التاريخ للعداد التنازلي
    if (preg_match('/(\d{1,2})[\/\-\.\s]+(\d{1,2})[\/\-\.\s]+(\d{4})/', $event_date_text, $matches)) {
        $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year = $matches[3];
        return "$year-$month-$day";
    } elseif (preg_match('/(\d{4})[\/\-\.\s]+(\d{1,2})[\/\-\.\s]+(\d{1,2})/', $event_date_text, $matches)) {
        $year = $matches[1];
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
        return "$year-$month-$day";
    }
    
    // تاريخ افتراضي إذا لم نجد تاريخ محدد
    return date('Y-m-d', strtotime('+1 week'));
}

$calendar_data = generateCalendarData($event_data, $lang);
$event_date_formatted = parseEventDate($event_data['event_date_ar'] ?? '');

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $event_data ? htmlspecialchars($event_data['event_name']) : 'دعوة' ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($event_data['event_paragraph_ar'] ?? 'دعوة خاصة') ?>">
    <meta name="keywords" content="دعوة,حفل,زفاف,invitation,wedding">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($event_data['event_name'] ?? 'دعوة') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($event_data['event_paragraph_ar'] ?? 'دعوة خاصة') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($event_data['background_image_url'] ?? '') ?>">
    
    <!-- Fonts & Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background: white;
            min-height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            padding: 20px;
            color: #000000;
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
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(45, 74, 34, 0.3);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #2d4a22;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(45, 74, 34, 0.1);
        }
        
        .language-toggle button:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-1px);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            box-shadow: 0 4px 12px rgba(45, 74, 34, 0.15);
        }
        
        .description-box {
            padding: 40px 25px;
            background: #f8f9fa;
            text-align: center;
            color: #000000;
            font-size: 1.1rem;
            line-height: 1.8;
        }
        
        .card-content { 
            padding: 30px; 
            background: white;
        }
        
        /* تصميم الصناديق مثل الأزرار تماماً */
        .guest-welcome,
        .countdown-section,
        .location-card,
        .qr-code-section,
        .guest-details {
            padding: 18px 25px;
            border-radius: 50px;
            font-weight: 600;
            color: #2d4a22;
            border: 2px solid rgba(45, 74, 34, 0.3);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
            margin: 20px 0;
        }
        
        .guest-welcome::before,
        .countdown-section::before,
        .location-card::before,
        .qr-code-section::before,
        .guest-details::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .guest-welcome:hover::before,
        .countdown-section:hover::before,
        .location-card:hover::before,
        .qr-code-section:hover::before,
        .guest-details:hover::before {
            left: 100%;
        }
        
        .guest-welcome:hover,
        .countdown-section:hover,
        .location-card:hover,
        .qr-code-section:hover,
        .guest-details:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(45, 74, 34, 0.2);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .guest-welcome {
            text-align: center;
            margin-bottom: 25px;
        }
        
/* تصميم العداد المحسن - استبدل الكود الحالي */
.countdown-section {
    padding: 20px;
    border-radius: 25px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.95));
    border: 2px solid rgba(45, 74, 34, 0.15);
    margin: 20px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
}

.countdown-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.05), transparent);
    transition: left 0.6s ease;
}

.countdown-section:hover::before {
    left: 100%;
}

.countdown-section:hover {
    transform: translateY(-3px) scale(1.01);
    box-shadow: 0 8px 25px rgba(45, 74, 34, 0.2);
    border-color: rgba(45, 74, 34, 0.3);
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
}

.countdown-timer {
    display: grid;
    gap: clamp(8px, 2vw, 15px);
    margin-top: 20px;
    justify-content: center;
    /* تخطيط متجاوب محسن */
    grid-template-columns: repeat(auto-fit, minmax(70px, 1fr));
    max-width: min(100%, 400px);
    margin-left: auto;
    margin-right: auto;
}

.countdown-item {
    background: white;
    border-radius: 15px;
    border: 2px solid rgba(45, 74, 34, 0.2);
    box-shadow: 0 4px 12px rgba(45, 74, 34, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    color: #2d4a22;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    /* ارتفاع متجاوب */
    min-height: clamp(65px, 15vw, 85px);
    padding: clamp(8px, 2vw, 12px) clamp(4px, 1vw, 8px);
    will-change: transform;
    transform: translateZ(0);
}

.countdown-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.03), transparent);
    transition: left 0.4s ease;
}

.countdown-item:hover::before {
    left: 100%;
}

.countdown-item:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(45, 74, 34, 0.15);
    border-color: rgba(45, 74, 34, 0.4);
    background: rgba(255, 255, 255, 0.98);
}

.countdown-number {
    font-weight: 800;
    display: block;
    line-height: 1;
    margin-bottom: clamp(2px, 1vw, 6px);
    color: #2d4a22;
    /* حجم خط متجاوب محسن */
    font-size: clamp(1.2rem, 5vw, 2rem);
}

.countdown-label {
    opacity: 0.85;
    font-weight: 600;
    text-align: center;
    color: #2d4a22;
    /* حجم خط متجاوب للتسميات */
    font-size: clamp(0.6rem, 2.5vw, 0.8rem);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

/* Animation للعناصر */
.countdown-item {
    animation: fadeInUp 0.6s ease-out both;
}

.countdown-item:nth-child(1) { animation-delay: 0.1s; }
.countdown-item:nth-child(2) { animation-delay: 0.2s; }
.countdown-item:nth-child(3) { animation-delay: 0.3s; }
.countdown-item:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* استعلامات الوسائط المحسنة */

/* شاشات صغيرة جداً (أقل من 320px) */
@media (max-width: 319px) {
    .countdown-timer {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        max-width: 250px;
    }
    
    .countdown-item {
        min-height: 55px;
        padding: 6px 4px;
        border-radius: 12px;
    }
    
    .countdown-number {
        font-size: 1rem;
        margin-bottom: 2px;
    }
    
    .countdown-label {
        font-size: 0.55rem;
    }
    
    .countdown-section {
        padding: 12px;
        margin: 12px 0;
        border-radius: 18px;
    }
}

/* شاشات صغيرة (320px - 479px) */
@media (min-width: 320px) and (max-width: 479px) {
    .countdown-timer {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        max-width: 280px;
    }
    
    .countdown-item {
        min-height: 65px;
        padding: 8px 6px;
        border-radius: 12px;
    }
    
    .countdown-number {
        font-size: clamp(1.1rem, 6vw, 1.4rem);
        margin-bottom: 3px;
    }
    
    .countdown-label {
        font-size: clamp(0.6rem, 3vw, 0.7rem);
    }
    
    .countdown-section {
        padding: 15px;
        margin: 15px 0;
        border-radius: 20px;
    }
}

/* شاشات متوسطة صغيرة (480px - 639px) */
@media (min-width: 480px) and (max-width: 639px) {
    .countdown-timer {
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        max-width: 400px;
    }
    
    .countdown-item {
        min-height: 70px;
        padding: 10px 8px;
        border-radius: 14px;
    }
    
    .countdown-number {
        font-size: clamp(1.3rem, 4vw, 1.6rem);
        margin-bottom: 4px;
    }
    
    .countdown-label {
        font-size: clamp(0.65rem, 2.5vw, 0.75rem);
    }
}

/* شاشات متوسطة (640px - 1023px) */
@media (min-width: 640px) and (max-width: 1023px) {
    .countdown-timer {
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        max-width: 400px;
    }
    
    .countdown-item {
        min-height: 75px;
        padding: 12px 10px;
        border-radius: 16px;
    }
    
    .countdown-number {
        font-size: clamp(1.4rem, 3vw, 1.8rem);
        margin-bottom: 5px;
    }
    
    .countdown-label {
        font-size: clamp(0.7rem, 2vw, 0.8rem);
    }
}

/* شاشات كبيرة (1024px وأكثر) */
@media (min-width: 1024px) {
    .countdown-timer {
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        max-width: 420px;
    }
    
    .countdown-item {
        min-height: 85px;
        padding: 15px 12px;
        border-radius: 18px;
    }
    
    .countdown-number {
        font-size: 2rem;
        margin-bottom: 6px;
    }
    
    .countdown-label {
        font-size: 0.85rem;
    }
}

/* وضع الشاشة الأفقية للهواتف */
@media (max-height: 480px) and (orientation: landscape) {
    .countdown-timer {
        grid-template-columns: repeat(4, 1fr);
        gap: 8px;
        max-width: 350px;
    }
    
    .countdown-item {
        min-height: 50px;
        padding: 6px 8px;
    }
    
    .countdown-number {
        font-size: 1.2rem;
        margin-bottom: 2px;
    }
    
    .countdown-label {
        font-size: 0.6rem;
    }
    
    .countdown-section {
        padding: 12px;
        margin: 10px 0;
    }
}

/* تحسين للشاشات عالية الدقة */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .countdown-item {
        border-width: 1.5px;
    }
}
            padding: 15px 8px;
            border-radius: 25px;
            backdrop-filter: blur(5px);
            border: 2px solid rgba(45, 74, 34, 0.3);
            transition: all 0.3s ease;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            color: #2d4a22;
        }
        
        .countdown-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.05), transparent);
            transition: left 0.4s ease;
        }
        
        .countdown-item:hover::before {
            left: 100%;
        }
        
        .countdown-item:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 4px 12px rgba(45, 74, 34, 0.15);
            border-color: rgba(45, 74, 34, 0.5);
            background: rgba(255, 255, 255, 0.95);
            color: #1a2f15;
        }
        
        .countdown-number {
            font-size: clamp(1.5rem, 4vw, 2.2rem);
            font-weight: bold;
            display: block;
            line-height: 1;
            margin-bottom: 5px;
            color: inherit;
        }
        
        .countdown-label {
            font-size: clamp(0.7rem, 2.5vw, 0.85rem);
            opacity: 0.8;
            font-weight: 600;
            text-align: center;
            color: inherit;
        }
        
        .guest-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }
        
        .detail-item {
            text-align: center;
            padding: 15px 10px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            border: 2px solid rgba(45, 74, 34, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            color: #2d4a22;
        }
        
        .detail-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.03), transparent);
            transition: left 0.3s ease;
        }
        
        .detail-item:hover::before {
            left: 100%;
        }
        
        .detail-item:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 4px 12px rgba(45, 74, 34, 0.1);
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(45, 74, 34, 0.4);
            color: #1a2f15;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: inherit;
            margin-bottom: 8px;
            font-weight: 600;
            opacity: 0.8;
        }
        
        .detail-value {
            font-weight: bold;
            color: inherit;
            font-size: 1.1rem;
        }
        
        .qr-code-section {
            text-align: center;
            display: none;
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
            background: rgba(255, 255, 255, 0.8);
            padding: 15px;
            border-radius: 25px;
            text-align: center;
            backdrop-filter: blur(10px);
            color: #2d4a22;
            border: 2px solid rgba(45, 74, 34, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .qr-title-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.05), transparent);
            transition: left 0.4s ease;
        }
        
        .qr-title-box:hover::before {
            left: 100%;
        }
        
        .qr-title-box:hover {
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
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
            color: #2d4a22;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-top: 30px;
        }
        
        .action-buttons button {
            flex: 1;
            padding: 18px 25px;
            border-radius: 50px;
            font-weight: 600;
            color: #2d4a22;
            border: 2px solid rgba(45, 74, 34, 0.3);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 16px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
        }
        
        .action-buttons button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .action-buttons button:hover::before {
            left: 100%;
        }
        
        .action-buttons button:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(45, 74, 34, 0.2);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .action-buttons button:active {
            transform: translateY(-1px) scale(0.98);
            transition: all 0.1s ease;
        }
        
        .action-buttons button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            background: rgba(200, 200, 200, 0.5);
            color: #888;
            border-color: rgba(200, 200, 200, 0.3);
        }
        
        .action-buttons button i {
            margin-right: 8px;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        
        .action-buttons button:hover i {
            transform: scale(1.1);
        }
        
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(45, 74, 34, 0.3);
            border-radius: 50%;
            border-top-color: #2d4a22;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .share-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .share-button {
            padding: 12px 20px;
            border-radius: 30px;
            border: 2px solid rgba(45, 74, 34, 0.3);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            color: #2d4a22;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(45, 74, 34, 0.1);
        }
        
        .share-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.05), transparent);
            transition: left 0.5s ease;
        }
        
        .share-button:hover::before {
            left: 100%;
        }
        
        .share-button:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(45, 74, 34, 0.15);
            border-color: rgba(45, 74, 34, 0.5);
            background: rgba(255, 255, 255, 0.95);
            color: #1a2f15;
        }
        
        .share-button:active {
            transform: translateY(0) scale(0.98);
            transition: all 0.1s ease;
        }
        
        .share-button i {
            font-size: 16px;
            transition: transform 0.3s ease;
        }
        
        .share-button:hover i {
            transform: scale(1.1) rotate(5deg);
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(45, 74, 34, 0.3);
            color: #2d4a22;
            padding: 15px 20px;
            border-radius: 30px;
            box-shadow: 0 10px 25px rgba(45, 74, 34, 0.2);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
            font-weight: 600;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.error {
            background: rgba(255, 240, 240, 0.95);
            border-color: rgba(239, 68, 68, 0.3);
            color: #dc2626;
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
            height: 350px;
            object-fit: cover;
            object-position: center;
            display: block;
        }
        
        .qr-code-section.active {
            display: block;
            animation: slideDown 0.5s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
            
            .countdown-timer {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                max-width: 280px;
            }
            
            .countdown-item {
                min-height: 70px;
                padding: 12px 6px;
                border-radius: 20px;
            }
            
            .countdown-number {
                font-size: clamp(1.2rem, 5vw, 1.8rem);
            }
            
            .countdown-label {
                font-size: clamp(0.6rem, 3vw, 0.75rem);
            }
            
            .guest-welcome,
            .countdown-section,
            .location-card,
            .qr-code-section,
            .guest-details {
                margin: 15px 0;
                padding: 15px 20px;
                border-radius: 40px;
            }
            
            .detail-item {
                padding: 12px 8px;
                border-radius: 15px;
            }
            
            .detail-value {
                font-size: 1rem;
            }
            
            .qr-title-box {
                border-radius: 20px;
            }
        }
        
        @media (min-width: 641px) and (max-width: 1024px) {
            .countdown-timer {
                grid-template-columns: repeat(4, 1fr);
                gap: 12px;
                max-width: 350px;
            }
            
            .countdown-item {
                min-height: 75px;
                padding: 12px 8px;
                border-radius: 22px;
            }
            
            .countdown-number {
                font-size: clamp(1.4rem, 3vw, 2rem);
            }
            
            .countdown-label {
                font-size: clamp(0.65rem, 2vw, 0.8rem);
            }
            
            .guest-welcome,
            .countdown-section,
            .location-card,
            .qr-code-section,
            .guest-details {
                border-radius: 45px;
            }
            
            .qr-title-box {
                border-radius: 22px;
            }
        }
        
        @media (min-width: 1025px) {
            .countdown-timer {
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                max-width: 400px;
            }
            
            .countdown-item {
                min-height: 85px;
                padding: 15px 10px;
                border-radius: 25px;
            }
            
            .countdown-number {
                font-size: 2.2rem;
            }
            
            .countdown-label {
                font-size: 0.85rem;
            }
            
            .guest-welcome,
            .countdown-section,
            .location-card,
            .qr-code-section,
            .guest-details {
                border-radius: 50px;
            }
            
            .qr-title-box {
                border-radius: 25px;
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
            </div>
        <?php else: ?>
            
            <!-- Event Image -->
            <?php if (!empty($event_data['background_image_url'])): ?>
                <div class="event-image-container">
                    <img src="<?= htmlspecialchars($event_data['background_image_url']) ?>" 
                         alt="<?= htmlspecialchars($event_data['event_name']) ?>" 
                         class="event-image"
                         loading="lazy">
                </div>
            <?php else: ?>
                <div class="description-box">
                    <p><?= nl2br(htmlspecialchars($event_data['event_paragraph_ar'] ?? 'مرحباً بكم في مناسبتنا الخاصة.')) ?></p>
                </div>
            <?php endif; ?>

            <div class="card-content" id="main-content">
                <!-- Guest Welcome Section -->
                <div class="guest-welcome">
                    <h2 class="text-xl font-bold mb-2">
                        <?= $t['welcome_guest'] ?>
                    </h2>
                    <p class="text-lg font-semibold">
                        <?= htmlspecialchars($guest_data['name_ar'] ?? $t['dear_guest']) ?>
                    </p>
                </div>

                <!-- Countdown Section -->
                <div class="countdown-section">
                    <h3 class="text-lg font-bold mb-2">
                        <i class="fas fa-calendar-alt"></i>
                        <?= $lang === 'ar' ? 'العد التنازلي للحفل' : 'Event Countdown' ?>
                    </h3>
                    <div class="countdown-timer" id="countdown-timer">
                        <div class="countdown-item">
                            <span class="countdown-number" id="days">--</span>
                            <div class="countdown-label"><?= $lang === 'ar' ? 'يوم' : 'Days' ?></div>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-number" id="hours">--</span>
                            <div class="countdown-label"><?= $lang === 'ar' ? 'ساعة' : 'Hours' ?></div>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-number" id="minutes">--</span>
                            <div class="countdown-label"><?= $lang === 'ar' ? 'دقيقة' : 'Minutes' ?></div>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-number" id="seconds">--</span>
                            <div class="countdown-label"><?= $lang === 'ar' ? 'ثانية' : 'Seconds' ?></div>
                        </div>
                    </div>
                </div>

                <!-- Guest Details -->
                <div class="guest-details">
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-users"></i>
                            <?= $t['guest_count'] ?>
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($guest_data['guests_count'] ?? '1') ?></div>
                    </div>
                    
                    <?php if (!empty($guest_data['table_number'])): ?>
                    <div class="detail-item">
                        <div class="detail-label">
                            <i class="fas fa-chair"></i>
                            <?= $t['table_number'] ?>
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($guest_data['table_number']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Location Card -->
                <?php if (!empty($event_data['venue_ar']) || !empty($event_data['Maps_link'])): ?>
                <div class="location-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold mb-1">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($event_data['venue_ar'] ?? 'مكان الحفل') ?>
                            </h3>
                            <?php if (!empty($event_data['event_date_ar'])): ?>
                            <p class="text-sm">
                                <i class="fas fa-calendar"></i>
                                <?= htmlspecialchars($event_data['event_date_ar']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($event_data['Maps_link'])): ?>
                        <a href="<?= htmlspecialchars($event_data['Maps_link']) ?>" 
                           target="_blank" 
                           class="hover:opacity-80 transition-colors">
                            <i class="fas fa-external-link-alt text-xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div id="action-buttons-section" class="action-buttons">
                    <button id="confirm-button" onclick="handleRSVP('confirmed')">
                        <div class="spinner" id="confirm-spinner"></div>
                        <span id="confirm-text">
                            <i class="fas fa-check"></i>
                            <?= $t['confirm_attendance'] ?>
                        </span>
                    </button>
                    <button id="cancel-button" onclick="handleRSVP('canceled')">
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
                    <button onclick="addToCalendar()" class="share-button">
                        <i class="fas fa-calendar-plus"></i>
                        <?= $t['add_to_calendar'] ?>
                    </button>
                    
                    <button onclick="shareInvitation()" class="share-button">
                        <i class="fas fa-share-alt"></i>
                        <?= $t['share_invitation'] ?>
                    </button>
                    
                    <?php if (!empty($event_data['Maps_link'])): ?>
                    <button onclick="openLocation()" class="share-button">
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
                        <h3 class="text-xl font-bold mb-2">
                            <i class="fas fa-qrcode"></i>
                            <?= htmlspecialchars($event_data['qr_card_title_ar'] ?? $t['entry_card']) ?>
                        </h3>
                        <p class="text-sm"><?= $t['qr_code'] ?></p>
                    </div>
                    
                    <div class="qr-info qr-info-left">
                        <div class="text-center">
                            <div class="text-xs mb-1"><?= $t['guest_count'] ?></div>
                            <div class="text-2xl font-bold"><?= htmlspecialchars($guest_data['guests_count'] ?? '1') ?></div>
                        </div>
                        <div class="text-xs mt-4">
                            <?= htmlspecialchars($event_data['qr_brand_text_ar'] ?? 'وصول') ?>
                        </div>
                    </div>
                    
                    <div id="qrcode" class="qr-code-container"></div>
                    
                    <div class="qr-info qr-info-right text-center">
                        <p class="text-sm font-semibold mb-2">
                            <?= htmlspecialchars($event_data['qr_show_code_instruction_ar'] ?? $t['show_at_entrance']) ?>
                        </p>
                        <div class="text-xs">
                            <?= htmlspecialchars($event_data['qr_website'] ?? 'wosuol.com') ?>
                        </div>
                    </div>
                </div>
                
                <!-- QR Action Buttons -->
                <div class="share-buttons mt-6">
                    <button onclick="downloadQR()" class="share-button">
                        <i class="fas fa-download"></i>
                        <?= $t['download_qr'] ?>
                    </button>
                    
                    <button onclick="shareQR()" class="share-button">
                        <i class="fas fa-share"></i>
                        <?= $t['share_invitation'] ?>
                    </button>
                </div>
            </div>

        <?php endif; ?>
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
            calendarData: <?= json_encode($calendar_data, JSON_UNESCAPED_UNICODE) ?>,
            eventDate: '<?= $event_date_formatted ?>'
        };

        // Global state
        let qrCodeGenerated = false;
        let countdownInterval;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkInitialStatus();
            preloadQRLibrary();
            startCountdown();
        });

        // Countdown Timer Function
        function startCountdown() {
            const eventDate = new Date(CONFIG.eventDate + 'T20:00:00'); // افتراض الساعة 8 مساءً
            
            function updateCountdown() {
                const now = new Date().getTime();
                const timeLeft = eventDate.getTime() - now;
                
                if (timeLeft > 0) {
                    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                    
                    document.getElementById('days').textContent = days.toString().padStart(2, '0');
                    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
                } else {
                    // انتهى الوقت
                    document.getElementById('days').textContent = '00';
                    document.getElementById('hours').textContent = '00';
                    document.getElementById('minutes').textContent = '00';
                    document.getElementById('seconds').textContent = '00';
                    
                    clearInterval(countdownInterval);
                    
                    // إظهار رسالة انتهاء العد التنازلي
                    const countdownSection = document.querySelector('.countdown-section');
                    if (countdownSection) {
                        countdownSection.innerHTML = `
                            <h3 class="text-lg font-bold mb-2">
                                <i class="fas fa-heart"></i>
                                ${CONFIG.lang === 'ar' ? '🎉 حان وقت الحفل! 🎉' : '🎉 Event Time! 🎉'}
                            </h3>
                            <p>${CONFIG.lang === 'ar' ? 'نتمنى لكم وقتاً ممتعاً' : 'Have a wonderful time!'}</p>
                        `;
                    }
                }
            }
            
            // تحديث العداد كل ثانية
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }

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
                responseMessage.className = 'mt-6 p-4 rounded-lg text-center font-semibold';
                responseMessage.style.background = 'rgba(255, 255, 255, 0.9)';
                responseMessage.style.backdropFilter = 'blur(10px)';
                responseMessage.style.border = '2px solid rgba(45, 74, 34, 0.3)';
                responseMessage.style.borderRadius = '30px';
                responseMessage.style.color = '#2d4a22';
                responseMessage.style.boxShadow = '0 4px 15px rgba(45, 74, 34, 0.1)';
                responseMessage.innerHTML = `
                    <i class="fas fa-check-circle mr-2"></i>
                    ${CONFIG.texts.already_confirmed}
                `;
                responseMessage.style.display = 'block';
                
                // Show QR code section
                qrSection.classList.add('active');
                generateQRCode();
            } else {
                responseMessage.className = 'mt-6 p-4 rounded-lg text-center font-semibold';
                responseMessage.style.background = 'rgba(255, 240, 240, 0.9)';
                responseMessage.style.backdropFilter = 'blur(10px)';
                responseMessage.style.border = '2px solid rgba(239, 68, 68, 0.3)';
                responseMessage.style.borderRadius = '30px';
                responseMessage.style.color = '#dc2626';
                responseMessage.style.boxShadow = '0 4px 15px rgba(239, 68, 68, 0.1)';
                responseMessage.innerHTML = `
                    <i class="fas fa-times-circle mr-2"></i>
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

        // Preload QR library for better performance
        function preloadQRLibrary() {
            if (CONFIG.guestData.status === 'confirmed') {
                generateQRCode();
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

        // Fallback share method
        function fallbackShare() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                showToast('Link copied to clipboard!', 'success');
            }).catch(() => {
                // Final fallback - show URL in prompt
                prompt('Copy this link:', url);
            });
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

        // Generate ICS File
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

        // Show calendar options for desktop
        function showCalendarOptions(options) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white p-6 rounded-2xl max-w-sm w-full mx-4 backdrop-blur-lg" style="background: rgba(255, 255, 255, 0.95);">
                    <h3 class="text-lg font-bold mb-6 text-center" style="color: #2d4a22;">اختر التقويم</h3>
                    <div class="space-y-3">
                        <button onclick="window.open('${options.google}', '_blank'); closeModal()" 
                                class="calendar-option-btn w-full p-4 rounded-full transition-all duration-300"
                                style="background: rgba(255, 255, 255, 0.9); border: 2px solid rgba(45, 74, 34, 0.3); color: #2d4a22; font-weight: 600; backdrop-filter: blur(10px);">
                            <i class="fab fa-google mr-2"></i>
                            Google Calendar
                        </button>
                        <button onclick="window.open('${options.outlook}', '_blank'); closeModal()" 
                                class="calendar-option-btn w-full p-4 rounded-full transition-all duration-300"
                                style="background: rgba(255, 255, 255, 0.9); border: 2px solid rgba(45, 74, 34, 0.3); color: #2d4a22; font-weight: 600; backdrop-filter: blur(10px);">
                            <i class="fab fa-microsoft mr-2"></i>
                            Outlook Calendar
                        </button>
                        <button onclick="window.open('${options.yahoo}', '_blank'); closeModal()" 
                                class="calendar-option-btn w-full p-4 rounded-full transition-all duration-300"
                                style="background: rgba(255, 255, 255, 0.9); border: 2px solid rgba(45, 74, 34, 0.3); color: #2d4a22; font-weight: 600; backdrop-filter: blur(10px);">
                            <i class="fab fa-yahoo mr-2"></i>
                            Yahoo Calendar
                        </button>
                        <button onclick="downloadICS(); closeModal()" 
                                class="calendar-option-btn w-full p-4 rounded-full transition-all duration-300"
                                style="background: rgba(255, 255, 255, 0.9); border: 2px solid rgba(45, 74, 34, 0.3); color: #2d4a22; font-weight: 600; backdrop-filter: blur(10px);">
                            <i class="fas fa-download mr-2"></i>
                            تحميل ملف ICS
                        </button>
                    </div>
                    <button onclick="closeModal()" 
                            class="w-full mt-6 p-3 rounded-full transition-all duration-300"
                            style="background: rgba(240, 240, 240, 0.9); border: 2px solid rgba(150, 150, 150, 0.5); color: #666; font-weight: 600; backdrop-filter: blur(10px);">
                        إلغاء
                    </button>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add hover effects to calendar options
            const calendarBtns = modal.querySelectorAll('.calendar-option-btn');
            calendarBtns.forEach(btn => {
                btn.addEventListener('mouseenter', () => {
                    btn.style.transform = 'translateY(-2px) scale(1.02)';
                    btn.style.boxShadow = '0 6px 20px rgba(45, 74, 34, 0.15)';
                    btn.style.borderColor = 'rgba(45, 74, 34, 0.5)';
                });
                btn.addEventListener('mouseleave', () => {
                    btn.style.transform = 'translateY(0) scale(1)';
                    btn.style.boxShadow = 'none';
                    btn.style.borderColor = 'rgba(45, 74, 34, 0.3)';
                });
            });
            
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

        // Open location
        function openLocation() {
            if (CONFIG.eventData.Maps_link) {
                window.open(CONFIG.eventData.Maps_link, '_blank');
                showToast('Opening maps...', 'success');
            }
        }

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

        // Cleanup countdown on page unload
        window.addEventListener('beforeunload', function() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
        });

        // Enhanced security - disable right-click on QR
        document.addEventListener('contextmenu', function(e) {
            if (e.target.closest('#qrcode, .qr-code-container')) {
                e.preventDefault();
            }
        });

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
    </script>
    <?php endif; ?>
</body>
</html>