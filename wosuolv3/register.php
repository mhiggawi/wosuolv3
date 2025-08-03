<?php
// register.php - Enhanced with security and languages, keeping original design
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
        'registration_instruction' => 'يرجى تسجيل بياناتك لتأكيد الحضور أو الاعتذار.',
        'name_label' => 'الاسم الكريم:',
        'phone_label' => 'رقم الجوال (مع رمز الدولة):',
        'guests_count_label' => 'عدد الضيوف (شاملاً لك):',
        'confirm_attendance' => 'تأكيد الحضور',
        'decline_attendance' => 'الاعتذار عن الحضور',
        'select_country' => 'اختر الدولة',
        'enter_local_number' => 'أدخل رقم الجوال المحلي فقط (مثال: 791234567)',
        'enter_full_number' => 'أدخل الرقم كاملاً مع رمز الدولة (مثال: +96279123456)',
        'choose_country_first' => 'اختر الدولة أولاً',
        'registration_success_confirm' => 'تم تأكيد حضورك بنجاح! سيتم الآن نقلك لصفحة الدعوة الخاصة بك للحصول على QR Code.',
        'registration_success_cancel' => 'شكراً لك، تم تسجيل اعتذارك عن الحضور.',
        'registration_error' => 'حدث خطأ أثناء التسجيل. قد يكون رقم الهاتف مسجلاً مسبقاً.',
        'fill_all_fields' => 'الرجاء إدخال جميع الحقول المطلوبة.',
        'invalid_phone_format' => 'يرجى إدخال رقم الجوال المحلي فقط (بدون رمز الدولة). مثال: 791234567',
        'invalid_phone_international' => 'يرجى إدخال الرقم مع رمز الدولة الكامل (مثال: 96279...).',
        'invalid_phone_general' => 'صيغة رقم الجوال غير صحيحة. يرجى إدخال الرقم كاملاً مع رمز الدولة.',
        'event_location' => 'مكان الحفل',
        'event_time' => 'موعد الحفل',
        'get_directions' => 'الحصول على الاتجاهات',
        'view_on_map' => 'عرض على الخريطة',
        'countdown_title' => 'العد التنازلي للحفل',
        'days' => 'يوم',
        'hours' => 'ساعة',
        'minutes' => 'دقيقة',
        'seconds' => 'ثانية',
        'event_time_reached' => '🎉 حان وقت الحفل! 🎉',
        'enjoy_time' => 'نتمنى لكم وقتاً ممتعاً',
        'countries' => [
            '+962' => 'الأردن (+962)',
            '+966' => 'السعودية (+966)', 
            '+971' => 'الإمارات (+971)',
            '+965' => 'الكويت (+965)',
            '+974' => 'قطر (+974)',
            '+973' => 'البحرين (+973)',
            '+968' => 'عُمان (+968)',
            '+961' => 'لبنان (+961)',
            '+963' => 'سوريا (+963)',
            '+964' => 'العراق (+964)',
            '+970' => 'فلسطين (+970)',
            '+20' => 'مصر (+20)',
            '+1' => 'أمريكا/كندا (+1)',
            '+44' => 'بريطانيا (+44)',
            '+49' => 'ألمانيا (+49)',
            '+33' => 'فرنسا (+33)',
            '+90' => 'تركيا (+90)',
            'other' => 'دولة أخرى'
        ]
    ],
    'en' => [
        'registration_instruction' => 'Please enter your details to confirm or decline attendance',
        'name_label' => 'Full Name:',
        'phone_label' => 'Mobile Number (with country code):',
        'guests_count_label' => 'Number of Guests (including you):',
        'confirm_attendance' => 'Confirm Attendance',
        'decline_attendance' => 'Decline Attendance',
        'select_country' => 'Select Country',
        'enter_local_number' => 'Enter local mobile number only (example: 791234567)',
        'enter_full_number' => 'Enter full number with country code (example: +96279123456)',
        'choose_country_first' => 'Choose country first',
        'registration_success_confirm' => 'Your attendance has been confirmed successfully! You will now be redirected to your invitation page to get the QR Code.',
        'registration_success_cancel' => 'Thank you, your decline has been recorded.',
        'registration_error' => 'An error occurred during registration. The phone number may already be registered.',
        'fill_all_fields' => 'Please fill in all required fields.',
        'invalid_phone_format' => 'Please enter local mobile number only (without country code). Example: 791234567',
        'invalid_phone_international' => 'Please enter the number with full country code (example: 96279...).',
        'invalid_phone_general' => 'Invalid mobile number format. Please enter the full number with country code.',
        'event_location' => 'Event Location',
        'event_time' => 'Event Time',
        'get_directions' => 'Get Directions',
        'view_on_map' => 'View on Map',
        'countdown_title' => 'Event Countdown',
        'days' => 'Days',
        'hours' => 'Hours',
        'minutes' => 'Minutes',
        'seconds' => 'Seconds',
        'event_time_reached' => '🎉 Event Time! 🎉',
        'enjoy_time' => 'Have a wonderful time!',
        'countries' => [
            '+962' => 'Jordan (+962)',
            '+966' => 'Saudi Arabia (+966)', 
            '+971' => 'UAE (+971)',
            '+965' => 'Kuwait (+965)',
            '+974' => 'Qatar (+974)',
            '+973' => 'Bahrain (+973)',
            '+968' => 'Oman (+968)',
            '+961' => 'Lebanon (+961)',
            '+963' => 'Syria (+963)',
            '+964' => 'Iraq (+964)',
            '+970' => 'Palestine (+970)',
            '+20' => 'Egypt (+20)',
            '+1' => 'USA/Canada (+1)',
            '+44' => 'UK (+44)',
            '+49' => 'Germany (+49)',
            '+33' => 'France (+33)',
            '+90' => 'Turkey (+90)',
            'other' => 'Other Country'
        ]
    ]
];

$t = $texts[$lang];

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Rate Limiting ---
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_limit_key = 'rate_limit_' . md5($client_ip);
$current_time = time();

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'first_attempt' => $current_time];
}

$message = '';
$messageType = '';
$registration_successful = false;
$redirect_url = '';

// --- Get Event ID from URL ---
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    die('رابط التسجيل غير صالح: معرف الحفل مفقود.');
}

// --- Fetch Event Details ---
$event = null;
$stmt_event = $mysqli->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
if ($stmt_event) {
    $stmt_event->bind_param("i", $event_id);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();
    if ($result_event && $result_event->num_rows > 0) {
        $event = $result_event->fetch_assoc();
    } else {
        die('الحفل المطلوب غير موجود.');
    }
    $stmt_event->close();
}

// --- Parse Event Date for Countdown ---
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

$event_date_formatted = parseEventDate($event['event_date_ar'] ?? '');

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['switch_language'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch. Please try again.';
        $messageType = 'error';
    } else {
        // Rate Limiting Check
        if ($current_time - $_SESSION[$rate_limit_key]['first_attempt'] > 300) {
            $_SESSION[$rate_limit_key] = ['count' => 0, 'first_attempt' => $current_time];
        }
        
        if ($_SESSION[$rate_limit_key]['count'] >= 5) {
            $message = 'تم إرسال طلبات كثيرة. يرجى الانتظار قبل المحاولة مرة أخرى.';
            $messageType = 'error';
        } else {
            $_SESSION[$rate_limit_key]['count']++;
            
            $name_ar = trim($_POST['name_ar'] ?? '');
            $country_code = trim($_POST['country_code'] ?? '');
            $phone_number_raw = trim($_POST['phone_number'] ?? '');
            $guests_count = filter_input(INPUT_POST, 'guests_count', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 20]
            ]) ?: 1;
            $status = in_array($_POST['rsvp_status'] ?? '', ['confirmed', 'canceled']) ? $_POST['rsvp_status'] : 'canceled';

            if (empty($name_ar) || empty($country_code) || empty($phone_number_raw)) {
                $message = $t['fill_all_fields'];
                $messageType = 'error';
            } else {
                // Phone Number Validation Logic (same as before)
                $is_valid = false;
                $error_message = '';

                if ($country_code === 'other') {
                    $phone_to_validate = $phone_number_raw;
                    
                    if (substr($phone_to_validate, 0, 2) === '00') {
                        $phone_to_validate = substr($phone_to_validate, 2);
                    } elseif (substr($phone_to_validate, 0, 1) === '+') {
                        $phone_to_validate = substr($phone_to_validate, 1);
                    }
                    
                    if (substr($phone_number_raw, 0, 1) === '0' && substr($phone_number_raw, 0, 2) !== '00') {
                        $error_message = $t['invalid_phone_international'];
                    } elseif (!ctype_digit($phone_to_validate) || strlen($phone_to_validate) < 10 || strlen($phone_to_validate) > 15) {
                        $error_message = $t['invalid_phone_general'];
                    } else {
                        $is_valid = true;
                        $phone_number_normalized = '+' . $phone_to_validate;
                    }
                } else {
                    $local_number = $phone_number_raw;
                    
                    if (substr($local_number, 0, 1) === '0') {
                        $local_number = substr($local_number, 1);
                    }
                    
                    if (!ctype_digit($local_number) || strlen($local_number) < 7 || strlen($local_number) > 10) {
                        $error_message = $t['invalid_phone_format'];
                    } else {
                        $is_valid = true;
                        $phone_number_normalized = $country_code . $local_number;
                    }
                }
                
                if (!$is_valid) {
                    $message = $error_message;
                    $messageType = 'error';
                } else {
                    // Check for duplicate
                    $stmt_check = $mysqli->prepare("SELECT id FROM guests WHERE phone_number = ? AND event_id = ? LIMIT 1");
                    if ($stmt_check) {
                        $stmt_check->bind_param("si", $phone_number_normalized, $event_id);
                        $stmt_check->execute();
                        $result_check = $stmt_check->get_result();
                        
                        if ($result_check && $result_check->num_rows > 0) {
                            $message = $t['registration_error'];
                            $messageType = 'error';
                        } else {
                            // Insert new guest
                            $guest_id = substr(md5(uniqid($phone_number_normalized . microtime(), true)), 0, 4);
                            
                            $stmt = $mysqli->prepare("INSERT INTO guests (event_id, guest_id, name_ar, phone_number, guests_count, status) VALUES (?, ?, ?, ?, ?, ?)");
                            if ($stmt) {
                                $stmt->bind_param("isssis", $event_id, $guest_id, $name_ar, $phone_number_normalized, $guests_count, $status);
                                
                                if ($stmt->execute()) {
                                    $registration_successful = true;
                                    if ($status === 'confirmed') {
                                        $message = $t['registration_success_confirm'];
                                        $messageType = 'success';
                                        $redirect_url = "rsvp.php?id=" . $guest_id;
                                        
                                        // Webhook call
                                        $webhook_url = $event['n8n_confirm_webhook'] ?? null;
                                        if ($webhook_url && filter_var($webhook_url, FILTER_VALIDATE_URL)) {
                                            $n8n_payload = json_encode(['guest_id' => $guest_id, 'phone_number' => $phone_number_normalized]);
                                            $ch = curl_init($webhook_url);
                                            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                                            curl_setopt($ch, CURLOPT_POSTFIELDS, $n8n_payload);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                                            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($n8n_payload)]);
                                            curl_exec($ch);
                                            curl_close($ch);
                                        }
                                    } else {
                                        $message = $t['registration_success_cancel'];
                                        $messageType = 'success';
                                    }
                                    
                                    // Reset rate limit on success
                                    unset($_SESSION[$rate_limit_key]);
                                } else {
                                    $message = $t['registration_error'];
                                    $messageType = 'error';
                                }
                                $stmt->close();
                            }
                        }
                        $stmt_check->close();
                    }
                }
            }
        }
    }
}

function safe_html($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حضور: <?= safe_html($event['event_name']) ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= safe_html($event['event_paragraph_ar'] ?? 'دعوة خاصة') ?>">
    <meta name="keywords" content="دعوة,حفل,زفاف,invitation,wedding">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= safe_html($event['event_name'] ?? 'دعوة') ?>">
    <meta property="og:description" content="<?= safe_html($event['event_paragraph_ar'] ?? 'دعوة خاصة') ?>">
    <meta property="og:image" content="<?= safe_html($event['background_image_url'] ?? '') ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
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
        .event-header,
        .countdown-section,
        .location-card,
        .form-section {
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
        
        .event-header::before,
        .countdown-section::before,
        .location-card::before,
        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .event-header:hover::before,
        .countdown-section:hover::before,
        .location-card:hover::before,
        .form-section:hover::before {
            left: 100%;
        }
        
        .event-header:hover,
        .countdown-section:hover,
        .location-card:hover,
        .form-section:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(45, 74, 34, 0.2);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .event-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .countdown-section {
            text-align: center;
        }
        
        .countdown-timer {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 12px;
            margin-top: 20px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .countdown-item {
            background: rgba(255, 255, 255, 0.8);
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
        
        .form-section {
            cursor: default;
        }
        
        .form-group { 
            margin-bottom: 1.25rem; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 600; 
            color: #2d4a22; 
        }
        
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid rgba(45, 74, 34, 0.3); 
            border-radius: 25px; 
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: #2d4a22;
            backdrop-filter: blur(10px);
        }
        
        .form-group input:focus, .form-group select:focus { 
            outline: none;
            border-color: rgba(45, 74, 34, 0.6);
            box-shadow: 0 0 0 3px rgba(45, 74, 34, 0.1);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .phone-input-container { 
            display: flex; 
            gap: 10px; 
        }
        
        .phone-input-container select { 
            flex: 0 0 40%; 
        }
        
        .phone-input-container input { 
            flex: 1; 
        }
        
        .help-text { 
            font-size: 0.875rem; 
            color: rgba(45, 74, 34, 0.7); 
            margin-top: 0.5rem; 
        }
        
        #phone-help-text { 
            min-height: 1.25rem; 
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
        
        /* Success Modal Styles */
        .success-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }
        
        .success-modal.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        
        .success-modal-content {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            border: 2px solid rgba(45, 74, 34, 0.3);
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 50px rgba(45, 74, 34, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .success-modal-content::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(45, 74, 34, 0.05), transparent);
            animation: shimmer 3s infinite;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #22c55e;
            margin-bottom: 20px;
            animation: bounce 0.6s ease;
        }
        
        .success-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2d4a22;
            margin-bottom: 15px;
        }
        
        .success-message {
            color: #2d4a22;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .success-button {
            padding: 15px 30px;
            border-radius: 25px;
            background: rgba(45, 74, 34, 0.9);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .success-button:hover {
            background: rgba(45, 74, 34, 1);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 74, 34, 0.3);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% { transform: translate3d(0, 0, 0); }
            40%, 43% { transform: translate3d(0, -20px, 0); }
            70% { transform: translate3d(0, -10px, 0); }
            90% { transform: translate3d(0, -4px, 0); }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        @media (max-width: 640px) {
            .card-container {
                margin: 10px;
                max-width: calc(100vw - 20px);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .phone-input-container {
                flex-direction: column;
            }
            
            .phone-input-container select,
            .phone-input-container input {
                flex: none;
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
            
            .event-header,
            .countdown-section,
            .location-card,
            .form-section {
                margin: 15px 0;
                padding: 15px 20px;
                border-radius: 40px;
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
            
            .event-header,
            .countdown-section,
            .location-card,
            .form-section {
                border-radius: 45px;
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
            
            .event-header,
            .countdown-section,
            .location-card,
            .form-section {
                border-radius: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="card-container">
        <!-- Language Toggle -->
        <div class="language-toggle">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?= safe_html($_SESSION['csrf_token']) ?>">
                <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>">
                    <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                </button>
            </form>
        </div>

        <!-- Event Image -->
        <?php if (!empty($event['background_image_url'])): ?>
            <div class="event-image-container">
                <img src="<?= safe_html($event['background_image_url']) ?>" 
                     alt="<?= safe_html($event['event_name']) ?>" 
                     class="event-image"
                     loading="lazy">
            </div>
        <?php else: ?>
            <div class="description-box">
                <p><?= nl2br(safe_html($event['event_paragraph_ar'] ?? 'مرحباً بكم في مناسبتنا الخاصة.')) ?></p>
            </div>
        <?php endif; ?>

        <div class="card-content">
            <!-- Event Header -->
            <div class="event-header">
                <h1 class="text-2xl font-bold mb-2"><?= safe_html($event['event_name']) ?></h1>
                <?php if (!empty($event['event_date_ar'])): ?>
                <p class="font-semibold">
                    <i class="fas fa-calendar"></i>
                    <?= nl2br(safe_html($event['event_date_ar'])) ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Countdown Section -->
            <div class="countdown-section">
                <h3 class="text-lg font-bold mb-2">
                    <i class="fas fa-calendar-alt"></i>
                    <?= $t['countdown_title'] ?>
                </h3>
                <div class="countdown-timer" id="countdown-timer">
                    <div class="countdown-item">
                        <span class="countdown-number" id="days">--</span>
                        <div class="countdown-label"><?= $t['days'] ?></div>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="hours">--</span>
                        <div class="countdown-label"><?= $t['hours'] ?></div>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="minutes">--</span>
                        <div class="countdown-label"><?= $t['minutes'] ?></div>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="seconds">--</span>
                        <div class="countdown-label"><?= $t['seconds'] ?></div>
                    </div>
                </div>
            </div>

            <!-- Location Card -->
            <?php if (!empty($event['venue_ar']) || !empty($event['Maps_link'])): ?>
            <div class="location-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold mb-1">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= $t['event_location'] ?>
                        </h3>
                        <?php if (!empty($event['venue_ar'])): ?>
                        <p>
                            <?= safe_html($event['venue_ar']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($event['Maps_link'])): ?>
                    <a href="<?= safe_html($event['Maps_link']) ?>" 
                       target="_blank" 
                       class="hover:opacity-80 transition-colors"
                       title="<?= $t['get_directions'] ?>">
                        <i class="fas fa-external-link-alt text-xl"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$registration_successful): ?>
                <!-- Registration Form -->
                <div class="form-section">
                    <p class="text-center mb-6 font-medium"><?= $t['registration_instruction'] ?></p>
                    
                    <form id="rsvpForm" method="POST" action="register.php?event_id=<?= $event_id ?>" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= safe_html($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="rsvp_status" id="rsvp_status" value="confirmed">
                        
                        <div class="form-group">
                            <label for="name_ar">
                                <i class="fas fa-user"></i>
                                <?= $t['name_label'] ?>
                            </label>
                            <input type="text" id="name_ar" name="name_ar" required 
                                   value="<?= safe_html($_POST['name_ar'] ?? '') ?>"
                                   placeholder="أدخل اسمك الكريم">
                        </div>
                        
                        <div class="form-group">
                            <label for="country_code">
                                <i class="fas fa-phone"></i>
                                <?= $t['phone_label'] ?>
                            </label>
                            <div class="phone-input-container">
                                <select id="country_code" name="country_code" required onchange="updatePhonePlaceholder()">
                                    <option value=""><?= $t['select_country'] ?></option>
                                    <?php foreach ($t['countries'] as $code => $name): ?>
                                        <option value="<?= safe_html($code) ?>" 
                                                <?= (($_POST['country_code'] ?? '+962') === $code) ? 'selected' : '' ?>>
                                            <?= safe_html($name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="tel" id="phone_number" name="phone_number" required placeholder="791234567"
                                       value="<?= safe_html($_POST['phone_number'] ?? '') ?>">
                            </div>
                            <div class="help-text" id="phone-help-text"><?= $t['enter_local_number'] ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="guests_count">
                                <i class="fas fa-users"></i>
                                <?= $t['guests_count_label'] ?>
                            </label>
                            <input type="number" id="guests_count" name="guests_count" 
                                   value="<?= safe_html($_POST['guests_count'] ?? '1') ?>" min="1" max="20" required>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" onclick="document.getElementById('rsvp_status').value='confirmed';" 
                                    class="btn-confirm">
                                <i class="fas fa-check"></i>
                                <?= $t['confirm_attendance'] ?>
                            </button>
                            <button type="submit" onclick="document.getElementById('rsvp_status').value='canceled';" 
                                    class="btn-decline">
                                <i class="fas fa-times"></i>
                                <?= $t['decline_attendance'] ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="success-modal">
        <div class="success-modal-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="success-title" id="successTitle">تم تأكيد حضورك بنجاح!</div>
            <div class="success-message" id="successMessage">سيتم الآن نقلك لصفحة الدعوة الخاصة بك للحصول على QR Code.</div>
            <button class="success-button" onclick="proceedToInvitation()">
                <i class="fas fa-arrow-right"></i>
                المتابعة للدعوة
            </button>
        </div>
    </div>

    <?php if ($message && $messageType === 'error'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert("<?= addslashes(safe_html($message)) ?>");
        });
    </script>
    <?php endif; ?>

    <script>
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        const lang = '<?= $lang ?>';
        const eventDate = '<?= $event_date_formatted ?>';
        let redirectUrl = '<?= $redirect_url ?>';
        let countdownInterval;

        // Start countdown timer
        function startCountdown() {
            const eventDateTime = new Date(eventDate + 'T20:00:00'); // افتراض الساعة 8 مساءً
            
            function updateCountdown() {
                const now = new Date().getTime();
                const timeLeft = eventDateTime.getTime() - now;
                
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
                                ${texts.event_time_reached}
                            </h3>
                            <p>${texts.enjoy_time}</p>
                        `;
                    }
                }
            }
            
            // تحديث العداد كل ثانية
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }

        // Initialize countdown on page load
        document.addEventListener('DOMContentLoaded', function() {
            startCountdown();
            
            // Check if we need to show success modal
            <?php if ($registration_successful && $status === 'confirmed'): ?>
                showSuccessModal();
            <?php elseif ($registration_successful && $status === 'canceled'): ?>
                alert("<?= addslashes(safe_html($message)) ?>");
            <?php endif; ?>
        });

        // Show success modal
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            const title = document.getElementById('successTitle');
            const message = document.getElementById('successMessage');
            
            title.textContent = lang === 'ar' ? 'تم تأكيد حضورك بنجاح!' : 'Your attendance confirmed successfully!';
            message.textContent = texts.registration_success_confirm;
            
            modal.classList.add('active');
            
            // Auto-redirect after 5 seconds
            setTimeout(() => {
                proceedToInvitation();
            }, 5000);
        }

        // Proceed to invitation page
        function proceedToInvitation() {
            if (redirectUrl) {
                window.location.href = redirectUrl;
            } else {
                document.getElementById('successModal').classList.remove('active');
            }
        }

        // Phone input management
        function updatePhonePlaceholder() {
            const countrySelect = document.getElementById('country_code');
            const phoneInput = document.getElementById('phone_number');
            const helpText = document.getElementById('phone-help-text');
            
            const selectedValue = countrySelect.value;
            
            if (selectedValue === 'other') {
                phoneInput.placeholder = '+96279123456';
                helpText.textContent = texts['enter_full_number'];
            } else if (selectedValue === '+962') {
                phoneInput.placeholder = '791234567';
                helpText.textContent = lang === 'ar' ? 'أدخل رقم الجوال الأردني (مثال: 791234567)' : 'Enter Jordanian mobile number (example: 791234567)';
            } else if (selectedValue === '+966') {
                phoneInput.placeholder = '501234567';
                helpText.textContent = lang === 'ar' ? 'أدخل رقم الجوال السعودي (مثال: 501234567)' : 'Enter Saudi mobile number (example: 501234567)';
            } else if (selectedValue === '+971') {
                phoneInput.placeholder = '501234567';
                helpText.textContent = lang === 'ar' ? 'أدخل رقم الجوال الإماراتي (مثال: 501234567)' : 'Enter UAE mobile number (example: 501234567)';
            } else if (selectedValue) {
                phoneInput.placeholder = '12345678';
                helpText.textContent = texts['enter_local_number'];
            } else {
                phoneInput.placeholder = '';
                helpText.textContent = texts['choose_country_first'];
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updatePhonePlaceholder();
        });
        
        // Form validation
        document.getElementById('rsvpForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name_ar').value.trim();
            const country = document.getElementById('country_code').value;
            const phone = document.getElementById('phone_number').value.trim();
            
            if (name.length < 2) {
                e.preventDefault();
                alert(lang === 'ar' ? 'يرجى إدخال اسم صحيح' : 'Please enter a valid name');
                return;
            }
            
            if (!country) {
                e.preventDefault();
                alert(lang === 'ar' ? 'يرجى اختيار الدولة' : 'Please select country');
                return;
            }
            
            if (phone.length < 7) {
                e.preventDefault();
                alert(lang === 'ar' ? 'يرجى إدخال رقم هاتف صحيح' : 'Please enter a valid phone number');
                return;
            }
        });

        // Enhanced form interactions
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.2s ease';
            });
            
            element.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Button click effects
        document.querySelectorAll('.action-buttons button').forEach(button => {
            button.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });

        // Cleanup countdown on page unload
        window.addEventListener('beforeunload', function() {
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
        });

        // Accessibility improvements
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'BUTTON') {
                e.target.click();
            }
            
            if (e.key === 'Escape') {
                const modal = document.getElementById('successModal');
                if (modal.classList.contains('active')) {
                    modal.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>