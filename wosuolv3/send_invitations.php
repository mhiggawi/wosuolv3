<?php
// reminder.php - صفحة إرسال الرسائل التذكيرية
session_start();
require_once 'db_config.php';

// --- Language System ---
$lang = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'ar';
if (isset($_POST['switch_language'])) {
    $lang = $_POST['switch_language'] === 'en' ? 'en' : 'ar';
    $_SESSION['language'] = $lang;
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
}

// Language texts
$texts = [
    'ar' => [
        'reminder_management' => 'إدارة الرسائل التذكيرية',
        'logout' => 'تسجيل الخروج',
        'back_to_events' => 'العودة للحفلات',
        'event_reminders' => 'تذكيرات الحفل',
        'reminder_settings' => 'إعدادات التذكير',
        'days_before_event' => 'عدد الأيام قبل الحفل',
        'reminder_message' => 'رسالة التذكير',
        'send_reminders' => 'إرسال التذكيرات',
        'auto_reminder_title' => 'تذكير تلقائي',
        'enable_auto_reminder' => 'تفعيل التذكير التلقائي',
        'reminder_schedule' => 'جدولة التذكير',
        'reminder_image' => 'صورة التذكير',
        'use_event_image' => 'استخدام صورة الحفل',
        'upload_reminder_image' => 'رفع صورة تذكير جديدة',
        'reminder_stats' => 'إحصائيات التذكير',
        'last_reminder_sent' => 'آخر تذكير مُرسل',
        'reminder_sent_count' => 'عدد التذكيرات المرسلة',
        'confirmed_after_reminder' => 'تأكيدات بعد التذكير',
        'pending_guests' => 'ضيوف لم يؤكدوا',
        'send_to_pending_only' => 'إرسال للمعلقين فقط',
        'send_to_all_guests' => 'إرسال لجميع الضيوف',
        'reminder_type' => 'نوع التذكير',
        'quick_reminder' => 'تذكير سريع',
        'scheduled_reminder' => 'تذكير مجدول',
        'reminder_success' => 'تم إرسال التذكيرات بنجاح!',
        'reminder_error' => 'حدث خطأ في إرسال التذكيرات',
        'no_webhook_configured' => 'لم يتم تكوين webhook للحفل',
        'reminder_history' => 'تاريخ التذكيرات',
        'view_details' => 'عرض التفاصيل',
        'guest_name' => 'اسم الضيف',
        'phone_number' => 'رقم الهاتف',
        'status' => 'الحالة',
        'reminder_sent_at' => 'وقت الإرسال',
        'processing' => 'جاري المعالجة...',
        'select_reminder_image' => 'اختيار صورة التذكير',
        'current_reminder_image' => 'صورة التذكير الحالية',
        'remove_reminder_image' => 'حذف صورة التذكير',
        'image_preview' => 'معاينة الصورة',
        'save_settings' => 'حفظ الإعدادات',
        'event_date_passed' => 'تاريخ الحفل قد مضى',
        'days_until_event' => 'أيام متبقية للحفل',
        'event_is_today' => 'الحفل اليوم!',
        'custom_message_placeholder' => 'اكتب رسالة التذكير هنا... (اختياري - سيتم استخدام الرسالة الافتراضية إذا تُركت فارغة)'
    ],
    'en' => [
        'reminder_management' => 'Reminder Management',
        'logout' => 'Logout',
        'back_to_events' => 'Back to Events',
        'event_reminders' => 'Event Reminders',
        'reminder_settings' => 'Reminder Settings',
        'days_before_event' => 'Days Before Event',
        'reminder_message' => 'Reminder Message',
        'send_reminders' => 'Send Reminders',
        'auto_reminder_title' => 'Auto Reminder',
        'enable_auto_reminder' => 'Enable Auto Reminder',
        'reminder_schedule' => 'Reminder Schedule',
        'reminder_image' => 'Reminder Image',
        'use_event_image' => 'Use Event Image',
        'upload_reminder_image' => 'Upload New Reminder Image',
        'reminder_stats' => 'Reminder Statistics',
        'last_reminder_sent' => 'Last Reminder Sent',
        'reminder_sent_count' => 'Reminders Sent Count',
        'confirmed_after_reminder' => 'Confirmations After Reminder',
        'pending_guests' => 'Pending Guests',
        'send_to_pending_only' => 'Send to Pending Only',
        'send_to_all_guests' => 'Send to All Guests',
        'reminder_type' => 'Reminder Type',
        'quick_reminder' => 'Quick Reminder',
        'scheduled_reminder' => 'Scheduled Reminder',
        'reminder_success' => 'Reminders sent successfully!',
        'reminder_error' => 'Error sending reminders',
        'no_webhook_configured' => 'No webhook configured for event',
        'reminder_history' => 'Reminder History',
        'view_details' => 'View Details',
        'guest_name' => 'Guest Name',
        'phone_number' => 'Phone Number',
        'status' => 'Status',
        'reminder_sent_at' => 'Sent At',
        'processing' => 'Processing...',
        'select_reminder_image' => 'Select Reminder Image',
        'current_reminder_image' => 'Current Reminder Image',
        'remove_reminder_image' => 'Remove Reminder Image',
        'image_preview' => 'Image Preview',
        'save_settings' => 'Save Settings',
        'event_date_passed' => 'Event date has passed',
        'days_until_event' => 'days until event',
        'event_is_today' => 'Event is today!',
        'custom_message_placeholder' => 'Write reminder message here... (optional - default message will be used if left empty)'
    ]
];

$t = $texts[$lang];

// Security check
//if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
   // header('Location: login.php');
   // exit;
//}

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get Event ID
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    header('Location: events.php');
    exit;
}

$message = '';
$messageType = '';

// --- Handle Reminder Sending ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminder']) && !isset($_POST['switch_language'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        $reminder_type = $_POST['reminder_type'] ?? 'pending_only';
        $custom_message = trim($_POST['custom_message'] ?? '');
        $reminder_image = $_POST['reminder_image_option'] ?? 'event_image';
        
        // Get event webhook
        $stmt = $mysqli->prepare("SELECT n8n_initial_invite_webhook FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        $stmt->close();
        
        if ($event && !empty($event['n8n_initial_invite_webhook'])) {
            $webhook_url = $event['n8n_initial_invite_webhook'];
            
            // Prepare payload
            $payload = [
                'action' => 'send_reminder',
                'event_id' => (int)$event_id,
                'reminder_type' => $reminder_type,
                'custom_message' => $custom_message,
                'reminder_image' => $reminder_image,
                'timestamp' => time()
            ];
            
            // Send webhook
            $ch = curl_init($webhook_url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen(json_encode($payload))
                ],
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log reminder
            $stmt = $mysqli->prepare("INSERT INTO reminder_logs (event_id, reminder_type, custom_message, response_data, http_code, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssi", $event_id, $reminder_type, $custom_message, $response, $httpCode);
            $stmt->execute();
            $stmt->close();
            
            if ($httpCode >= 200 && $httpCode < 300) {
                $message = $t['reminder_success']; $messageType = 'success';
            } else {
                $message = $t['reminder_error']; $messageType = 'error';
            }
        } else {
            $message = $t['no_webhook_configured']; $messageType = 'error';
        }
        
        header('Location: reminder.php?event_id=' . $event_id . '&message=' . urlencode($message) . '&messageType=' . $messageType);
        exit;
    }
}

// --- Handle Image Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reminder_settings']) && !isset($_POST['switch_language'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        $current_reminder_image = $_POST['current_reminder_image'] ?? '';
        
        // Handle reminder image upload
        if (isset($_FILES['reminder_image_upload']) && $_FILES['reminder_image_upload']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['reminder_image_upload']['type'];
            $file_size = $_FILES['reminder_image_upload']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= 5000000) {
                $upload_dir = './uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $fileTmpPath = $_FILES['reminder_image_upload']['tmp_name'];
                $fileName = $_FILES['reminder_image_upload']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = 'reminder_event_' . $event_id . '_' . time() . '.' . $fileExtension;
                $destPath = $upload_dir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $destPath)) {
                    // Remove old reminder image
                    if (!empty($current_reminder_image) && file_exists($current_reminder_image)) { 
                        unlink($current_reminder_image); 
                    }
                    $current_reminder_image = $destPath;
                }
            }
        } elseif (isset($_POST['remove_reminder_image']) && $_POST['remove_reminder_image'] === '1') {
            if (!empty($current_reminder_image) && file_exists($current_reminder_image)) { 
                unlink($current_reminder_image); 
            }
            $current_reminder_image = '';
        }
        
        // Update event with reminder image
        $stmt = $mysqli->prepare("UPDATE events SET reminder_image_url = ? WHERE id = ?");
        $stmt->bind_param("si", $current_reminder_image, $event_id);
        $stmt->execute();
        $stmt->close();
        
        $message = 'تم حفظ إعدادات التذكير بنجاح'; $messageType = 'success';
        header('Location: reminder.php?event_id=' . $event_id . '&message=' . urlencode($message) . '&messageType=' . $messageType);
        exit;
    }
}

// --- Get URL parameters ---
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = $_GET['messageType'] ?? 'success';
}

// --- Fetch Event Data ---
$event = null;
$stmt = $mysqli->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $event = $result->fetch_assoc();
} else {
    header('Location: events.php');
    exit;
}
$stmt->close();

// --- Calculate days until event ---
$days_until_event = null;
$event_date_parsed = null;
if (!empty($event['event_date_ar'])) {
    // Try to parse date from Arabic text
    if (preg_match('/(\d{1,2})\s*\/\s*(\d{1,2})\s*\/\s*(\d{4})/', $event['event_date_ar'], $matches)) {
        $day = $matches[1];
        $month = $matches[2];
        $year = $matches[3];
        $event_date_parsed = "$year-$month-$day";
        $event_timestamp = strtotime($event_date_parsed);
        $today_timestamp = strtotime(date('Y-m-d'));
        $days_until_event = ceil(($event_timestamp - $today_timestamp) / 86400);
    }
}

// --- Fetch Event Statistics ---
$stmt = $mysqli->prepare("SELECT 
    COUNT(*) as total_guests,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_guests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_guests,
    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_guests
    FROM guests WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

// --- Create reminder_logs table if not exists ---
$mysqli->query("
    CREATE TABLE IF NOT EXISTS reminder_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        reminder_type VARCHAR(50) NOT NULL,
        custom_message TEXT,
        response_data TEXT,
        http_code INT DEFAULT 0,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    )
");

// --- Add reminder_image_url column to events table if not exists ---
$mysqli->query("ALTER TABLE events ADD COLUMN IF NOT EXISTS reminder_image_url VARCHAR(1024) DEFAULT NULL COMMENT 'رابط صورة التذكير'");

// --- Fetch recent reminder logs ---
$stmt = $mysqli->prepare("SELECT * FROM reminder_logs WHERE event_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$reminder_logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <title><?= $t['reminder_management'] ?> - <?= safe_html($event['event_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background-color: #f0f2f5; 
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .reminder-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .image-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 15px 0;
            border: 2px dashed #e9ecef;
        }
        .image-section.has-image {
            border-style: solid;
            border-color: #28a745;
            background: #f8fff9;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-top: 10px;
        }
        .days-counter {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
        }
        .days-number {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin: 5px;
        }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .btn-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th, .table td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>;
        }
        .table th {
            background: #f3f4f6;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><?= $t['reminder_management'] ?></h1>
            <div class="flex gap-3 items-center">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg border border-gray-300 transition-colors">
                        <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                    </button>
                </form>
                <a href="events.php" class="btn btn-secondary"><?= $t['back_to_events'] ?></a>
                <a href="logout.php" class="btn btn-danger"><?= $t['logout'] ?></a>
            </div>
        </div>

        <!-- Event Header -->
        <div class="reminder-card">
            <h2 class="text-2xl font-bold mb-4"><?= safe_html($event['event_name']) ?></h2>
            <p class="text-lg opacity-90"><?= $t['event_reminders'] ?></p>
            
            <!-- Days Counter -->
            <?php if ($days_until_event !== null): ?>
            <div class="days-counter mt-4">
                <?php if ($days_until_event > 0): ?>
                    <div class="days-number"><?= $days_until_event ?></div>
                    <div class="text-lg"><?= $t['days_until_event'] ?></div>
                <?php elseif ($days_until_event === 0): ?>
                    <div class="days-number">🎉</div>
                    <div class="text-lg"><?= $t['event_is_today'] ?></div>
                <?php else: ?>
                    <div class="text-lg text-red-200"><?= $t['event_date_passed'] ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-6 text-sm rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number text-blue-600"><?= $stats['total_guests'] ?></div>
                <div class="stat-label">إجمالي الضيوف</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-green-600"><?= $stats['confirmed_guests'] ?></div>
                <div class="stat-label">مؤكدين</div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-yellow-600"><?= $stats['pending_guests'] ?></div>
                <div class="stat-label"><?= $t['pending_guests'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number text-red-600"><?= $stats['canceled_guests'] ?></div>
                <div class="stat-label">معتذرين</div>
            </div>
        </div>

        <!-- Reminder Image Settings -->
        <div class="form-section">
            <h3 class="text-xl font-bold mb-4"><?= $t['reminder_settings'] ?></h3>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="save_reminder_settings" value="1">
                
                <div class="image-section <?= !empty($event['reminder_image_url']) ? 'has-image' : '' ?>">
                    <h4 class="font-bold text-lg mb-4"><?= $t['reminder_image'] ?></h4>
                    
                    <?php if(!empty($event['reminder_image_url'])): ?>
                        <div class="my-4 p-4 border rounded-lg bg-gray-50">
                            <p class="font-semibold mb-2"><?= $t['current_reminder_image'] ?>:</p>
                            <img src="<?= safe_html($event['reminder_image_url']) ?>" alt="<?= $t['current_reminder_image'] ?>" class="image-preview">
                            <div class="mt-3">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="remove_reminder_image" value="1" class="mx-2">
                                    <?= $t['remove_reminder_image'] ?>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-2">
                         <label class="block font-medium"><?= $t['upload_reminder_image'] ?>:</label>
                         <input type="file" name="reminder_image_upload" accept="image/*" class="mt-1">
                         <p class="text-sm text-gray-600 mt-1">حد أقصى: 5MB، الأنواع المدعومة: JPG, PNG, GIF, WebP</p>
                    </div>
                    <input type="hidden" name="current_reminder_image" value="<?= safe_html($event['reminder_image_url']) ?>">
                </div>
                
                <button type="submit" class="btn btn-success"><?= $t['save_settings'] ?></button>
            </form>
        </div>

        <!-- Quick Reminder Form -->
        <div class="form-section">
            <h3 class="text-xl font-bold mb-4"><?= $t['quick_reminder'] ?></h3>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="send_reminder" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-medium mb-2"><?= $t['reminder_type'] ?>:</label>
                        <select name="reminder_type" class="w-full p-3 border border-gray-300 rounded-lg">
                            <option value="pending_only"><?= $t['send_to_pending_only'] ?></option>
                            <option value="all_guests"><?= $t['send_to_all_guests'] ?></option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block font-medium mb-2"><?= $t['reminder_image'] ?>:</label>
                        <select name="reminder_image_option" class="w-full p-3 border border-gray-300 rounded-lg">
                            <option value="event_image"><?= $t['use_event_image'] ?></option>
                            <?php if (!empty($event['reminder_image_url'])): ?>
                            <option value="reminder_image">استخدام صورة التذكير</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block font-medium mb-2"><?= $t['reminder_message'] ?>:</label>
                    <textarea name="custom_message" rows="4" class="w-full p-3 border border-gray-300 rounded-lg" 
                              placeholder="<?= $t['custom_message_placeholder'] ?>"></textarea>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <?= $t['send_reminders'] ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Reminder History -->
        <?php if (!empty($reminder_logs)): ?>
        <div class="form-section">
            <h3 class="text-xl font-bold mb-4"><?= $t['reminder_history'] ?></h3>
            
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= $t['reminder_type'] ?></th>
                            <th><?= $t['reminder_sent_at'] ?></th>
                            <th><?= $t['status'] ?></th>
                            <th><?= $t['view_details'] ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reminder_logs as $log): ?>
                        <tr>
                            <td>
                                <?php 
                                echo $log['reminder_type'] === 'pending_only' ? $t['send_to_pending_only'] : $t['send_to_all_guests'];
                                ?>
                            </td>
                            <td><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                            <td>
                                <?php if ($log['http_code'] >= 200 && $log['http_code'] < 300): ?>
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">نجح</span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">فشل</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="showReminderDetails(<?= htmlspecialchars(json_encode($log), ENT_QUOTES) ?>)" 
                                        class="text-blue-600 hover:underline">
                                    <?= $t['view_details'] ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <h3 class="text-lg font-bold mb-4">تفاصيل التذكير</h3>
            <div id="modalContent"></div>
            <button onclick="closeModal()" class="mt-4 btn btn-secondary">إغلاق</button>
        </div>
    </div>

    <script>
        function showReminderDetails(log) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('modalContent');
            
            let responseData = '';
            try {
                if (log.response_data) {
                    const parsed = JSON.parse(log.response_data);
                    responseData = JSON.stringify(parsed, null, 2);
                }
            } catch (e) {
                responseData = log.response_data || 'لا توجد بيانات';
            }
            
            content.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <strong>نوع التذكير:</strong> ${log.reminder_type}
                    </div>
                    <div>
                        <strong>وقت الإرسال:</strong> ${log.created_at}
                    </div>
                    <div>
                        <strong>كود HTTP:</strong> ${log.http_code}
                    </div>
                    ${log.custom_message ? `
                    <div>
                        <strong>رسالة مخصصة:</strong>
                        <div class="bg-gray-100 p-3 rounded mt-2">${log.custom_message}</div>
                    </div>
                    ` : ''}
                    <div>
                        <strong>استجابة الخادم:</strong>
                        <pre class="bg-gray-100 p-3 rounded mt-2 text-xs overflow-x-auto">${responseData}</pre>
                    </div>
                </div>
            `;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>