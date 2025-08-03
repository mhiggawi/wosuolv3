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
        'assigned_location_label' => 'الموقع المخصص:',
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
        'powered_by' => 'مدعوم من',
        'all_rights_reserved' => 'جميع الحقوق محفوظة',
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
        'assigned_location_label' => 'Assigned Location:',
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
        'powered_by' => 'Powered by',
        'all_rights_reserved' => 'All Rights Reserved',
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
            $assigned_location = trim($_POST['assigned_location'] ?? '');
            $status = in_array($_POST['rsvp_status'] ?? '', ['confirmed', 'canceled']) ? $_POST['rsvp_status'] : 'canceled';

            if (empty($name_ar) || empty($country_code) || empty($phone_number_raw)) {
                $message = $t['fill_all_fields'];
                $messageType = 'error';
            } else {
                // Phone Number Validation Logic
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
                            
                            $stmt = $mysqli->prepare("INSERT INTO guests (event_id, guest_id, name_ar, phone_number, guests_count, assigned_location, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            if ($stmt) {
                                $stmt->bind_param("isssiss", $event_id, $guest_id, $name_ar, $phone_number_normalized, $guests_count, $assigned_location, $status);
                                
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
        
        .event-header {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-radius: 15px;
            border: 1px solid #60a5fa;
        }
        
        .location-card {
            padding: 20px;
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border-radius: 12px;
            border: 1px solid #22c55e;
            margin: 20px 0;
        }
        
        .form-group { 
            margin-bottom: 1.25rem; 
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 600; 
            color: #374151; 
        }
        
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #d1d5db; 
            border-radius: 8px; 
            transition: border-color 0.3s ease;
            background: white;
        }
        
        .form-group input:focus, .form-group select:focus { 
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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
        
        .message { 
            padding: 12px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            text-align: center; 
            font-weight: 600;
        }
        
        .message.success { 
            background-color: #dcfce7; 
            color: #166534; 
            border: 1px solid #22c55e;
        }
        
        .message.error { 
            background-color: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #ef4444;
        }
        
        .help-text { 
            font-size: 0.875rem; 
            color: #6b7280; 
            margin-top: 0.5rem; 
        }
        
        #phone-help-text { 
            min-height: 1.25rem; 
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
                <input type="hidden" name="csrf_token" value="<?= safe_html($_SESSION['csrf_token']) ?>">
                <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>">
                    <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                </button>
            </form>
        </div>

        <!-- Event Image or Description -->
        <?php if (!empty($event['background_image_url'])): ?>
            <div class="event-image-container">
                <img src="<?= safe_html($event['background_image_url']) ?>" 
                     alt="<?= safe_html($event['event_name']) ?>" 
                     class="event-image"
                     loading="lazy"
                     onclick="toggleImageView(this)">
            </div>
        <?php else: ?>
            <div class="description-box">
                <p><?= nl2br(safe_html($event['event_paragraph_ar'] ?? 'مرحباً بكم في مناسبتنا الخاصة.')) ?></p>
            </div>
        <?php endif; ?>

        <div class="card-content">
            <!-- Event Header -->
            <div class="event-header">
                <h1 class="text-2xl font-bold text-blue-800 mb-2"><?= safe_html($event['event_name']) ?></h1>
                <?php if (!empty($event['event_date_ar'])): ?>
                <p class="text-blue-700 font-semibold">
                    <i class="fas fa-calendar"></i>
                    <?= nl2br(safe_html($event['event_date_ar'])) ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Location Card -->
            <?php if (!empty($event['venue_ar']) || !empty($event['Maps_link'])): ?>
            <div class="location-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-green-800 mb-1">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= $t['event_location'] ?>
                        </h3>
                        <?php if (!empty($event['venue_ar'])): ?>
                        <p class="text-green-700">
                            <?= safe_html($event['venue_ar']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($event['Maps_link'])): ?>
                    <a href="<?= safe_html($event['Maps_link']) ?>" 
                       target="_blank" 
                       class="text-green-600 hover:text-green-800 transition-colors"
                       title="<?= $t['get_directions'] ?>">
                        <i class="fas fa-external-link-alt text-xl"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($message && !$redirect_url): ?>
                <div class="message <?= $messageType ?>"><?= safe_html($message) ?></div>
            <?php endif; ?>

            <?php if (!$registration_successful): ?>
                <form id="rsvpForm" method="POST" action="register.php?event_id=<?= $event_id ?>" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= safe_html($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="rsvp_status" id="rsvp_status" value="confirmed">
                    
                    <p class="text-center text-gray-700 mb-6 font-medium"><?= $t['registration_instruction'] ?></p>
                    
                    <div class="form-group">
                        <label for="name_ar">
                            <i class="fas fa-user text-blue-600"></i>
                            <?= $t['name_label'] ?>
                        </label>
                        <input type="text" id="name_ar" name="name_ar" required 
                               value="<?= safe_html($_POST['name_ar'] ?? '') ?>"
                               placeholder="أدخل اسمك الكريم">
                    </div>
                    
                    <div class="form-group">
                        <label for="country_code">
                            <i class="fas fa-phone text-blue-600"></i>
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
                            <i class="fas fa-users text-blue-600"></i>
                            <?= $t['guests_count_label'] ?>
                        </label>
                        <input type="number" id="guests_count" name="guests_count" 
                               value="<?= safe_html($_POST['guests_count'] ?? '1') ?>" min="1" max="20" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_location">
                            <i class="fas fa-map-marker-alt text-blue-600"></i>
                            <?= $t['assigned_location_label'] ?>
                        </label>
                        <input type="text" id="assigned_location" name="assigned_location" 
                               value="<?= safe_html($_POST['assigned_location'] ?? '') ?>"
                               placeholder="مثال: الطابق الأول - الصالة الشرقية">
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
            <?php endif; ?>
        </div>
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

    <?php if ($registration_successful && !empty($redirect_url)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert("<?= addslashes(safe_html($message)) ?>");
            window.location.href = "<?= $redirect_url ?>";
        });
    </script>
    <?php endif; ?>

    <script>
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        const lang = '<?= $lang ?>';
        
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
        
        // Simple form validation
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

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
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
    </script>
</body>
</html>