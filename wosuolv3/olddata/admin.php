<?php
/**
 * admin.php - إدارة الأحداث والإعدادات
 * 
 * @package Wosuol
 * @author Wosuol.com
 * @copyright 2025 Wosuol.com - جميع الحقوق محفوظة
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
        'administration' => 'الإدارة',
        'logout' => 'تسجيل الخروج',
        'event_settings' => 'إعدادات الحفل',
        'user_management' => 'إدارة المستخدمين',
        'event_details' => 'تفاصيل الحفل',
        'event_name' => 'اسم الحفل',
        'google_maps_link' => 'رابط خرائط جوجل',
        'event_date_ar' => 'تاريخ الحفل (عربي)',
        'event_date_en' => 'تاريخ الحفل (إنجليزي)',
        'venue_ar' => 'مكان الحفل (عربي)',
        'venue_en' => 'مكان الحفل (إنجليزي)',
        'event_description_ar' => 'وصف الحفل (عربي)',
        'event_description_en' => 'وصف الحفل (إنجليزي)',
        'image_settings' => 'إعدادات الصور',
        'display_image' => 'صورة العرض',
        'whatsapp_image' => 'صورة الواتساب',
        'current_display_image' => 'الصورة الحالية للعرض',
        'current_whatsapp_image' => 'الصورة الحالية للواتساب',
        'upload_display_image' => 'رفع صورة العرض',
        'upload_whatsapp_image' => 'رفع صورة الواتساب',
        'remove_current_image' => 'حذف الصورة الحالية',
        'image_preview' => 'معاينة الصورة',
        'cancel_selection' => 'إلغاء الاختيار',
        'qr_settings' => 'إعدادات QR',
        'webhook_settings' => 'إعدادات Webhook',
        'save_all_settings' => 'حفظ جميع الإعدادات',
        'image_saved_success' => 'تم حفظ الصورة بنجاح',
        'image_removed_success' => 'تم حذف الصورة بنجاح'
    ],
    'en' => [
        'administration' => 'Administration',
        'logout' => 'Logout',
        'event_settings' => 'Event Settings',
        'user_management' => 'User Management',
        'event_details' => 'Event Details',
        'event_name' => 'Event Name',
        'google_maps_link' => 'Google Maps Link',
        'event_date_ar' => 'Event Date (Arabic)',
        'event_date_en' => 'Event Date (English)',
        'venue_ar' => 'Venue (Arabic)',
        'venue_en' => 'Venue (English)',
        'event_description_ar' => 'Event Description (Arabic)',
        'event_description_en' => 'Event Description (English)',
        'image_settings' => 'Image Settings',
        'display_image' => 'Display Image',
        'whatsapp_image' => 'WhatsApp Image',
        'current_display_image' => 'Current Display Image',
        'current_whatsapp_image' => 'Current WhatsApp Image',
        'upload_display_image' => 'Upload Display Image',
        'upload_whatsapp_image' => 'Upload WhatsApp Image',
        'remove_current_image' => 'Remove Current Image',
        'image_preview' => 'Image Preview',
        'cancel_selection' => 'Cancel Selection',
        'qr_settings' => 'QR Settings',
        'webhook_settings' => 'Webhook Settings',
        'save_all_settings' => 'Save All Settings',
        'image_saved_success' => 'Image saved successfully',
        'image_removed_success' => 'Image removed successfully'
    ]
];

$t = $texts[$lang];

// --- Security Check & Get Event ID ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    header('Location: events.php');
    exit;
}

$message = '';
$messageType = '';

// --- Default QR and Webhook Settings ---
$default_settings = [
    'qr_card_title_ar' => 'دعوة حفل زفاف',
    'qr_card_title_en' => 'Wedding Invitation',
    'qr_show_code_instruction_ar' => 'يرجى إظهار هذا الرمز عند الدخول',
    'qr_show_code_instruction_en' => 'Please show this code at entrance',
    'qr_brand_text_ar' => 'وصول',
    'qr_brand_text_en' => 'Wosuol',
    'qr_website' => 'wosuol.com',
    'n8n_confirm_webhook' => 'https://your-n8n-instance.com/webhook/confirm',
    'n8n_initial_invite_webhook' => 'https://your-n8n-instance.com/webhook/invite'
];

// --- Handle Form Submission ---
// User Management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action']) && !isset($_POST['switch_language'])) {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        $action = $_POST['user_action'];
        $username = trim($_POST['username'] ?? '');
        
        if ($action === 'add' || $action === 'edit') {
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            $user_event_id = !empty($_POST['user_event_id']) ? filter_input(INPUT_POST, 'user_event_id', FILTER_VALIDATE_INT) : NULL;
            if ($role === 'admin') { $user_event_id = NULL; }
        }

        switch ($action) {
            case 'add':
                if (!empty($username) && !empty($password) && !empty($role)) {
                    $stmt_check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt_check->bind_param("s", $username);
                    $stmt_check->execute();
                    $stmt_check->store_result();
                    if ($stmt_check->num_rows > 0) {
                        $message = 'اسم المستخدم موجود بالفعل.'; $messageType = 'error';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt_insert = $mysqli->prepare("INSERT INTO users (username, password_hash, role, event_id) VALUES (?, ?, ?, ?)");
                        $stmt_insert->bind_param("sssi", $username, $hashedPassword, $role, $user_event_id);
                        if ($stmt_insert->execute()) { 
                            $message = 'تم إضافة المستخدم بنجاح.'; $messageType = 'success'; 
                        } else { 
                            $message = 'حدث خطأ في إضافة المستخدم.'; $messageType = 'error'; 
                        }
                        $stmt_insert->close();
                    }
                    $stmt_check->close();
                } else { 
                    $message = 'الرجاء إدخال كل الحقول.'; $messageType = 'error'; 
                }
                break;
                
            case 'edit':
                $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                if (!empty($user_id) && !empty($username) && !empty($role)) {
                    if (!empty($password)) {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $mysqli->prepare("UPDATE users SET username = ?, password_hash = ?, role = ?, event_id = ? WHERE id = ?");
                        $stmt->bind_param("sssii", $username, $hashedPassword, $role, $user_event_id, $user_id);
                    } else {
                        $stmt = $mysqli->prepare("UPDATE users SET username = ?, role = ?, event_id = ? WHERE id = ?");
                        $stmt->bind_param("ssii", $username, $role, $user_event_id, $user_id);
                    }
                    if ($stmt->execute()) { 
                        $message = 'تم تحديث المستخدم بنجاح.'; $messageType = 'success'; 
                    } else { 
                        $message = 'حدث خطأ في التحديث.'; $messageType = 'error'; 
                    }
                    $stmt->close();
                }
                break;
                
            case 'delete':
                if (!empty($username)) {
                    $stmt = $mysqli->prepare("DELETE FROM users WHERE username = ? AND username != ?");
                    $stmt->bind_param("ss", $username, $_SESSION['username']);
                    if ($stmt->execute()) { 
                        $message = 'تم حذف المستخدم بنجاح.'; $messageType = 'success'; 
                    } else { 
                        $message = 'حدث خطأ في الحذف.'; $messageType = 'error'; 
                    }
                    $stmt->close();
                }
                break;
        }
        header('Location: admin.php?event_id=' . $event_id . '&message=' . urlencode($message) . '&messageType=' . $messageType . '&tab=user-management');
        exit;
    }
}

// Event Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event_settings']) && !isset($_POST['switch_language'])) {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        $event_name = trim($_POST['event_name'] ?? '');
        $date_ar = trim($_POST['date_ar'] ?? '');
        $date_en = trim($_POST['date_en'] ?? '');
        $venue_ar = trim($_POST['venue_ar'] ?? '');
        $venue_en = trim($_POST['venue_en'] ?? '');
        $maps_link = trim($_POST['maps_link'] ?? '');
        $event_paragraph_ar = trim($_POST['event_paragraph_ar'] ?? '');
        $event_paragraph_en = trim($_POST['event_paragraph_en'] ?? '');
        
        // QR Settings with defaults
        $qr_card_title_ar = trim($_POST['qr_card_title_ar'] ?? '') ?: $default_settings['qr_card_title_ar'];
        $qr_card_title_en = trim($_POST['qr_card_title_en'] ?? '') ?: $default_settings['qr_card_title_en'];
        $qr_instruction_ar = trim($_POST['qr_show_code_instruction_ar'] ?? '') ?: $default_settings['qr_show_code_instruction_ar'];
        $qr_instruction_en = trim($_POST['qr_show_code_instruction_en'] ?? '') ?: $default_settings['qr_show_code_instruction_en'];
        $qr_brand_ar = trim($_POST['qr_brand_text_ar'] ?? '') ?: $default_settings['qr_brand_text_ar'];
        $qr_brand_en = trim($_POST['qr_brand_text_en'] ?? '') ?: $default_settings['qr_brand_text_en'];
        $qr_website = trim($_POST['qr_website'] ?? '') ?: $default_settings['qr_website'];
        
        // Webhook Settings with defaults
        $n8n_confirm_webhook = trim($_POST['n8n_confirm_webhook'] ?? '') ?: $default_settings['n8n_confirm_webhook'];
        $n8n_initial_invite_webhook = trim($_POST['n8n_initial_invite_webhook'] ?? '') ?: $default_settings['n8n_initial_invite_webhook'];
        
        // Current images
        $current_display_image = $_POST['current_display_image'] ?? '';
       // $current_whatsapp_image = $_POST['current_whatsapp_image'] ?? '';

        // Handle Display Image upload/removal
        if (isset($_FILES['display_image_upload']) && $_FILES['display_image_upload']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['display_image_upload']['type'];
            $file_size = $_FILES['display_image_upload']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= 5000000) { // 5MB limit
                $upload_dir = './uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $fileTmpPath = $_FILES['display_image_upload']['tmp_name'];
                $fileName = $_FILES['display_image_upload']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = 'display_event_' . $event_id . '_' . time() . '.' . $fileExtension;
                $destPath = $upload_dir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $destPath)) {
                    // Remove old display image
                    if (!empty($current_display_image) && file_exists($current_display_image)) { 
                        unlink($current_display_image); 
                    }
                    $current_display_image = $destPath;
                    $message = $t['image_saved_success']; $messageType = 'success';
                }
            }
        } elseif (isset($_POST['remove_display_image']) && $_POST['remove_display_image'] === '1') {
            if (!empty($current_display_image) && file_exists($current_display_image)) { 
                unlink($current_display_image); 
            }
            $current_display_image = '';
            $message = $t['image_removed_success']; $messageType = 'success';
        }

        // Handle WhatsApp Image upload/removal
        if (isset($_FILES['whatsapp_image_upload']) && $_FILES['whatsapp_image_upload']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['whatsapp_image_upload']['type'];
            $file_size = $_FILES['whatsapp_image_upload']['size'];
            
            if (in_array($file_type, $allowed_types) && $file_size <= 5000000) { // 5MB limit
                $upload_dir = './uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $fileTmpPath = $_FILES['whatsapp_image_upload']['tmp_name'];
                $fileName = $_FILES['whatsapp_image_upload']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $newFileName = 'whatsapp_event_' . $event_id . '_' . time() . '.' . $fileExtension;
                $destPath = $upload_dir . $newFileName;
                
                if(move_uploaded_file($fileTmpPath, $destPath)) {
                    // Remove old WhatsApp image
                    if (!empty($current_whatsapp_image) && file_exists($current_whatsapp_image)) { 
                        unlink($current_whatsapp_image); 
                    }
                    $current_whatsapp_image = $destPath;
                    $message = $t['image_saved_success']; $messageType = 'success';
                }
            }
        } elseif (isset($_POST['remove_whatsapp_image']) && $_POST['remove_whatsapp_image'] === '1') {
            if (!empty($current_whatsapp_image) && file_exists($current_whatsapp_image)) { 
                unlink($current_whatsapp_image); 
            }
            $current_whatsapp_image = '';
            $message = $t['image_removed_success']; $messageType = 'success';
        }

        // Update database with separate images
        $stmt = $mysqli->prepare("UPDATE events SET 
            event_name=?, event_date_ar=?, event_date_en=?, venue_ar=?, venue_en=?, Maps_link=?, 
            event_paragraph_ar=?, event_paragraph_en=?, 
            background_image_url=?, whatsapp_image_url=?,
            qr_card_title_ar=?, qr_card_title_en=?, qr_show_code_instruction_ar=?, 
            qr_show_code_instruction_en=?, qr_brand_text_ar=?, qr_brand_text_en=?, qr_website=?, 
            n8n_confirm_webhook=?, n8n_initial_invite_webhook=?
            WHERE id=?");
        
        $stmt->bind_param("sssssssssssssssssssi", 
            $event_name, $date_ar, $date_en, $venue_ar, $venue_en, $maps_link,
            $event_paragraph_ar, $event_paragraph_en, 
            $current_display_image, $current_whatsapp_image,
            $qr_card_title_ar, $qr_card_title_en, $qr_instruction_ar,
            $qr_instruction_en, $qr_brand_ar, $qr_brand_en, $qr_website, 
            $n8n_confirm_webhook, $n8n_initial_invite_webhook, $event_id
        );

        if ($stmt->execute()) { 
            if (empty($message)) { 
                $message = 'تم حفظ الإعدادات بنجاح.'; $messageType = 'success'; 
            } 
        } else { 
            $message = 'حدث خطأ أثناء حفظ الإعدادات.'; $messageType = 'error'; 
        }
        $stmt->close();
        header('Location: admin.php?event_id=' . $event_id . '&message=' . urlencode($message) . '&messageType=' . $messageType . '&tab=general-settings');
        exit;
    }
}

// --- Data Fetching ---
$users = [];
$result_users = $mysqli->query("SELECT id, username, role, event_id FROM users ORDER BY username ASC");
if ($result_users) { $users = $result_users->fetch_all(MYSQLI_ASSOC); }

$all_events = [];
$result_events_list = $mysqli->query("SELECT id, event_name FROM events ORDER BY event_name ASC");
if ($result_events_list) { $all_events = $result_events_list->fetch_all(MYSQLI_ASSOC); }

$event = [];
$stmt_event = $mysqli->prepare("SELECT * FROM events WHERE id = ?");
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$result_event = $stmt_event->get_result();
if ($result_event->num_rows > 0) { 
    $event = $result_event->fetch_assoc(); 
    
    // Apply defaults for QR and Webhook settings if empty
    foreach ($default_settings as $key => $default_value) {
        if (empty($event[$key])) {
            $event[$key] = $default_value;
        }
    }
} else { 
    header('Location: events.php'); exit; 
}
$stmt_event->close();

if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']));
    $messageType = htmlspecialchars($_GET['messageType']);
}

function safe_html($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

function getPageTitle($title, $lang) {
    $site_name = $lang === 'ar' ? 'وصول' : 'Wosuol';
    return "{$title} - {$site_name}";
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getPageTitle($t['administration'] . ': ' . safe_html($event['event_name'] ?? 'حفل'), $lang) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background-color: #f0f2f5; 
        }
        .container { max-width: 1000px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 30px; }
        .main-nav { display: flex; flex-wrap: wrap; gap: 10px; background-color: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .main-nav a { padding: 10px 15px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background-color 0.2s; }
        .main-nav a:hover { background-color: #e9ecef; }
        .tabs { display: flex; border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; }
        .tab-button { padding: 12px 20px; cursor: pointer; border: none; background-color: transparent; font-size: 1.1rem; font-weight: 600; color: #64748b; }
        .tab-button.active { color: #3b82f6; border-bottom: 2px solid #3b82f6; }
        .tab-content { display: none; padding-top: 20px; }
        .tab-content.active { display: block; }
        .accordion-header { cursor: pointer; padding: 15px; background-color: #f3f4f6; border-radius: 8px; font-weight: bold; margin-top: 15px; display: flex; justify-content: space-between; align-items: center; }
        .accordion-content { display: none; padding: 20px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; }
        .user-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .user-table th, .user-table td { border: 1px solid #ddd; padding: 10px; text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>; }
        .user-table .actions-cell { display: flex; gap: 5px; }
        .image-preview { max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ddd; margin-top: 10px; }
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
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.active { display: flex; justify-content: center; align-items: center; }
        .modal-content { background-color: white; margin: 15% auto; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; }
    </style>
</head>
<body class="p-5">
    <div class="container">
        
        <div class="flex justify-between items-center mb-4">
             <h1 class="text-3xl font-bold text-gray-800"><?= $t['administration'] ?>: "<?= safe_html($event['event_name']) ?>"</h1>
             <div class="flex gap-3 items-center">
                 <form method="POST" style="display: inline;">
                     <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                             class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg border border-gray-300 transition-colors">
                         <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                     </button>
                 </form>
                 <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg"><?= $t['logout'] ?></a>
             </div>
        </div>
        
        <nav class="main-nav">
            <a href="events.php" class="text-blue-600">كل الحفلات</a>
            <a href="dashboard.php?event_id=<?= $event_id ?>" class="text-blue-600">متابعة</a>
            <a href="guests.php?event_id=<?= $event_id ?>" class="text-blue-600">إدارة الضيوف</a>
            <a href="send_invitations.php?event_id=<?= $event_id ?>" class="text-blue-600">إرسال الدعوات</a>
            <a href="checkin.php?event_id=<?= $event_id ?>" class="text-blue-600">تسجيل الدخول</a>
            <a href="register.php?event_id=<?= $event_id ?>" target="_blank" class="text-green-600 font-bold">عرض صفحة التسجيل</a>
        </nav>
        
        <?php if ($message): ?>
            <div class="p-4 mb-4 text-sm rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-button active" data-tab="general-settings"><?= $t['event_settings'] ?></button>
            <button class="tab-button" data-tab="user-management"><?= $t['user_management'] ?></button>
        </div>
        
        <div id="general-settings" class="tab-content active">
            <form id="event-settings-form" method="POST" action="admin.php?event_id=<?= $event_id ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="update_event_settings" value="1">
                
                <div class="accordion-header" onclick="toggleAccordion(this)"><?= $t['event_details'] ?> <span class="toggle-icon">▼</span></div>
                <div class="accordion-content" style="display: block;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label><?= $t['event_name'] ?>:</label>
                            <input type="text" name="event_name" value="<?= safe_html($event['event_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label><?= $t['google_maps_link'] ?>:</label>
                            <input type="url" name="maps_link" value="<?= safe_html($event['Maps_link']) ?>">
                        </div>
                        <div class="form-group">
                            <label><?= $t['event_date_ar'] ?>:</label>
                            <input type="text" name="date_ar" value="<?= safe_html($event['event_date_ar']) ?>">
                        </div>
                        <div class="form-group">
                            <label><?= $t['event_date_en'] ?>:</label>
                            <input type="text" name="date_en" value="<?= safe_html($event['event_date_en']) ?>">
                        </div>
                        <div class="form-group">
                            <label><?= $t['venue_ar'] ?>:</label>
                            <input type="text" name="venue_ar" value="<?= safe_html($event['venue_ar']) ?>">
                        </div>
                        <div class="form-group">
                            <label><?= $t['venue_en'] ?>:</label>
                            <input type="text" name="venue_en" value="<?= safe_html($event['venue_en']) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $t['event_description_ar'] ?>:</label>
                        <textarea name="event_paragraph_ar" rows="4"><?= safe_html($event['event_paragraph_ar']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= $t['event_description_en'] ?>:</label>
                        <textarea name="event_paragraph_en" rows="4"><?= safe_html($event['event_paragraph_en']) ?></textarea>
                    </div>
                </div>

                <div class="accordion-header" onclick="toggleAccordion(this)"><?= $t['image_settings'] ?> <span class="toggle-icon">▼</span></div>
                <div class="accordion-content">
                    <!-- صورة العرض (للموقع) -->
                    <div class="image-section <?= !empty($event['background_image_url']) ? 'has-image' : '' ?>">
                        <h4 class="font-bold text-lg mb-4 text-blue-800">
                            <i class="fas fa-desktop"></i>
                            <?= $t['display_image'] ?>
                        </h4>
                        <p class="text-sm text-gray-600 mb-4">هذه الصورة ستظهر في صفحة RSVP وصفحة التسجيل على الموقع</p>
                        
                        <?php if(!empty($event['background_image_url'])): ?>
                            <div class="my-4 p-4 border rounded-lg bg-gray-50">
                                <p class="font-semibold mb-2"><?= $t['current_display_image'] ?>:</p>
                                <img src="<?= safe_html($event['background_image_url']) ?>" alt="<?= $t['current_display_image'] ?>" class="image-preview">
                                <div class="mt-3">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="remove_display_image" value="1" class="mx-2" onchange="toggleImageUpload(this, 'display')">
                                        <?= $t['remove_current_image'] ?>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div id="display-image-upload-section" class="mt-2">
                             <label class="block font-medium"><?= $t['upload_display_image'] ?>:</label>
                             <input type="file" id="display_image_upload" name="display_image_upload" accept="image/*" class="mt-1" onchange="previewNewImage(this, 'display')">
                             <p class="text-sm text-gray-600 mt-1">حد أقصى: 5MB، الأنواع المدعومة: JPG, PNG, GIF, WebP</p>
                        </div>

                        <div id="display-image-preview-container" class="my-2" style="display: none;">
                             <p class="font-semibold"><?= $t['image_preview'] ?>:</p>
                             <img id="display-image-preview" src="#" alt="<?= $t['image_preview'] ?>" class="image-preview">
                             <button type="button" class="mt-2 text-sm text-red-600 hover:underline" onclick="cancelImageSelection('display')"><?= $t['cancel_selection'] ?></button>
                        </div>
                        <input type="hidden" name="current_display_image" value="<?= safe_html($event['background_image_url']) ?>">
                    </div>

                    <!-- صورة الواتساب (للرسائل) -->
                    <div class="image-section <?= !empty($event['whatsapp_image_url']) ? 'has-image' : '' ?>">
                        <h4 class="font-bold text-lg mb-4 text-green-800">
                            <i class="fab fa-whatsapp"></i>
                            <?= $t['whatsapp_image'] ?>
                        </h4>
                        <p class="text-sm text-gray-600 mb-4">هذه الصورة ستُرسل مع رسائل الدعوة عبر الواتساب</p>
                        
                        <?php if(!empty($event['whatsapp_image_url'])): ?>
                            <div class="my-4 p-4 border rounded-lg bg-gray-50">
                                <p class="font-semibold mb-2"><?= $t['current_whatsapp_image'] ?>:</p>
                                <img src="<?= safe_html($event['whatsapp_image_url']) ?>" alt="<?= $t['current_whatsapp_image'] ?>" class="image-preview">
                                <div class="mt-3">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="remove_whatsapp_image" value="1" class="mx-2" onchange="toggleImageUpload(this, 'whatsapp')">
                                        <?= $t['remove_current_image'] ?>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div id="whatsapp-image-upload-section" class="mt-2">
                             <label class="block font-medium"><?= $t['upload_whatsapp_image'] ?>:</label>
                             <input type="file" id="whatsapp_image_upload" name="whatsapp_image_upload" accept="image/*" class="mt-1" onchange="previewNewImage(this, 'whatsapp')">
                             <p class="text-sm text-gray-600 mt-1">حد أقصى: 5MB، الأنواع المدعومة: JPG, PNG, GIF, WebP</p>
                        </div>

                        <div id="whatsapp-image-preview-container" class="my-2" style="display: none;">
                             <p class="font-semibold"><?= $t['image_preview'] ?>:</p>
                             <img id="whatsapp-image-preview" src="#" alt="<?= $t['image_preview'] ?>" class="image-preview">
                             <button type="button" class="mt-2 text-sm text-red-600 hover:underline" onclick="cancelImageSelection('whatsapp')"><?= $t['cancel_selection'] ?></button>
                        </div>
                        <input type="hidden" name="current_whatsapp_image" value="<?= safe_html($event['whatsapp_image_url']) ?>">
                    </div>
                </div>

                <div class="accordion-header" onclick="toggleAccordion(this)"><?= $t['qr_settings'] ?> <span class="toggle-icon">▼</span></div>
                <div class="accordion-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>عنوان بطاقة QR (عربي):</label>
                            <input type="text" name="qr_card_title_ar" value="<?= safe_html($event['qr_card_title_ar']) ?>">
                        </div>
                        <div class="form-group">
                            <label>عنوان بطاقة QR (إنجليزي):</label>
                            <input type="text" name="qr_card_title_en" value="<?= safe_html($event['qr_card_title_en']) ?>">
                        </div>
                        <div class="form-group">
                            <label>تعليمات إظهار الكود (عربي):</label>
                            <input type="text" name="qr_show_code_instruction_ar" value="<?= safe_html($event['qr_show_code_instruction_ar']) ?>">
                        </div>
                        <div class="form-group">
                            <label>تعليمات إظهار الكود (إنجليزي):</label>
                            <input type="text" name="qr_show_code_instruction_en" value="<?= safe_html($event['qr_show_code_instruction_en']) ?>">
                        </div>
                        <div class="form-group">
                            <label>نص العلامة التجارية (عربي):</label>
                            <input type="text" name="qr_brand_text_ar" value="<?= safe_html($event['qr_brand_text_ar']) ?>">
                        </div>
                        <div class="form-group">
                            <label>نص العلامة التجارية (إنجليزي):</label>
                            <input type="text" name="qr_brand_text_en" value="<?= safe_html($event['qr_brand_text_en']) ?>">
                        </div>
                        <div class="form-group">
                            <label>موقع الويب على البطاقة:</label>
                            <input type="text" name="qr_website" value="<?= safe_html($event['qr_website']) ?>">
                        </div>
                    </div>
                </div>

                <div class="accordion-header" onclick="toggleAccordion(this)"><?= $t['webhook_settings'] ?> <span class="toggle-icon">▼</span></div>
                <div class="accordion-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Webhook لتأكيد الحضور:</label>
                            <input type="url" name="n8n_confirm_webhook" value="<?= safe_html($event['n8n_confirm_webhook']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Webhook للدعوات الأولية:</label>
                            <input type="url" name="n8n_initial_invite_webhook" value="<?= safe_html($event['n8n_initial_invite_webhook']) ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="mt-6 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg"><?= $t['save_all_settings'] ?></button>
            </form>
        </div>

        <div id="user-management" class="tab-content">
            <h3 class="text-xl font-semibold mt-4 mb-4">إضافة مستخدم جديد</h3>
            <form method="POST" action="admin.php?event_id=<?= $event_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="user_action" value="add">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="form-group">
                        <label>اسم المستخدم:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>كلمة المرور:</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>الدور:</label>
                        <select name="role" required onchange="toggleEventSelect(this.value, 'add_event_select_container')">
                            <option value="admin">مدير</option>
                            <option value="viewer">مشاهد</option>
                            <option value="checkin_user">مسجل دخول</option>
                        </select>
                    </div>
                    <div class="form-group" id="add_event_select_container" style="display:none;">
                        <label>الحفل المخصص:</label>
                        <select name="user_event_id">
                            <option value="">-- اختر الحفل --</option>
                            <?php foreach($all_events as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['event_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg">إضافة مستخدم</button>
            </form>
            
            <hr class="my-8">
            
            <h3 class="text-xl font-semibold mt-8 mb-4">المستخدمون الحاليون</h3>
            <div class="overflow-x-auto">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>اسم المستخدم</th>
                            <th>الدور</th>
                            <th>الحفل المخصص</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <em>(كل الحفلات)</em>
                                <?php else: ?>
                                    <?php 
                                    $event_name = 'غير محدد';
                                    foreach ($all_events as $e) {
                                        if ($e['id'] == $user['event_id']) {
                                            $event_name = $e['event_name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($event_name);
                                    ?>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <button type="button" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded" onclick='openEditModal(<?= json_encode($user) ?>)'>تعديل</button>
                                <?php if ($_SESSION['username'] !== $user['username']): ?>
                                <form method="POST" action="admin.php?event_id=<?= $event_id ?>" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');" class="inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="user_action" value="delete">
                                    <input type="hidden" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">حذف</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <h2 class="text-2xl font-bold mb-4">تعديل اسم المستخدم</h2>
            <form method="POST" action="admin.php?event_id=<?= $event_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="user_action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label>اسم المستخدم:</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                <div class="form-group">
                    <label>كلمة المرور (اتركه فارغاً لعدم التغيير):</label>
                    <input type="password" name="password" id="edit_password">
                </div>
                <div class="form-group">
                    <label>الدور:</label>
                    <select name="role" id="edit_role" required onchange="toggleEventSelect(this.value, 'edit_event_select_container')">
                        <option value="admin">مدير</option>
                        <option value="viewer">مشاهد</option>
                        <option value="checkin_user">مسجل دخول</option>
                    </select>
                </div>
                <div class="form-group" id="edit_event_select_container" style="display:none;">
                    <label>الحفل المخصص:</label>
                    <select name="user_event_id" id="edit_user_event_id">
                        <option value="">-- اختر الحفل --</option>
                        <?php foreach($all_events as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['event_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex justify-end gap-4 mt-6">
                    <button type="button" onclick="closeEditModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded">إلغاء</button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class='text-center text-gray-600 text-sm mt-8 py-4 border-t'>
        <p><?= $lang === 'ar' ? 'جميع الحقوق محفوظة &copy; 2025 وصول' : 'All Rights Reserved &copy; 2025 Wosuol' ?></p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Tab Logic
            const tabs = document.querySelectorAll('.tab-button');
            const contents = document.querySelectorAll('.tab-content');
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'general-settings';

            function switchTab(tabId) {
                contents.forEach(content => content.classList.remove('active'));
                tabs.forEach(tab => tab.classList.remove('active'));
                const contentToShow = document.getElementById(tabId);
                const tabToActivate = document.querySelector(`[data-tab='${tabId}']`);
                if(contentToShow) contentToShow.classList.add('active');
                if(tabToActivate) tabToActivate.classList.add('active');
            }
            
            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    const tabId = tab.dataset.tab;
                    switchTab(tabId);
                    const url = new URL(window.location);
                    url.searchParams.set('tab', tabId);
                    window.history.pushState({}, '', url);
                });
            });
            switchTab(activeTab);
        });

        // Accordion Logic
        function toggleAccordion(header) {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            const isOpen = content.style.display === 'block';
            
            content.style.display = isOpen ? 'none' : 'block';
            icon.textContent = isOpen ? '▼' : '▲';
        }

        // Image Management Functions
        function previewNewImage(input, type) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById(type + '-image-preview').src = e.target.result;
                    document.getElementById(type + '-image-preview-container').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function cancelImageSelection(type) {
            document.getElementById(type + '_image_upload').value = '';
            document.getElementById(type + '-image-preview-container').style.display = 'none';
        }

        function toggleImageUpload(checkbox, type) {
            const uploadSection = document.getElementById(type + '-image-upload-section');
            if (checkbox.checked) {
                uploadSection.style.display = 'none';
                cancelImageSelection(type);
            } else {
                uploadSection.style.display = 'block';
            }
        }

        // User Management Functions
        function toggleEventSelect(role, containerId) { 
            const container = document.getElementById(containerId);
            if(container) {
                container.style.display = (role === 'viewer' || role === 'checkin_user') ? 'block' : 'none';
            }
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_user_event_id').value = user.event_id || '';
            
            toggleEventSelect(user.role, 'edit_event_select_container');
            document.getElementById('editUserModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editUserModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editUserModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>