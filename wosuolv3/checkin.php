<?php
// checkin.php - Enhanced Check-in System with Advanced Features
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
        'checkin_system' => 'نظام تسجيل دخول الضيوف',
        'event_title' => 'حفل',
        'back_to_events' => 'عودة للحفلات',
        'logout' => 'تسجيل الخروج',
        'scan_qr_or_search' => 'امسح رمز QR أو ابحث بالاسم',
        'start_scanning' => 'بدء المسح',
        'stop_scanning' => 'إيقاف المسح',
        'search_placeholder' => 'ابحث بالاسم أو الهاتف...',
        'checkin_button' => 'تسجيل دخول',
        'confirm_and_checkin' => 'تأكيد وتسجيل دخول',
        'results_appear_here' => 'النتائج ستظهر هنا...',
        'checking' => 'جاري التحقق...',
        'camera_error' => 'لا يمكن الوصول للكاميرا.',
        'connection_error' => 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
        'guest_not_found' => 'الضيف غير موجود في قائمة هذا الحفل.',
        'guest_already_checked_in' => 'تم تسجيل دخول {name} مسبقاً.',
        'guest_checked_in_success' => 'تم تسجيل دخول {name} بنجاح.',
        'guest_confirmed_and_checked_in' => 'تم تأكيد حضور {name} وتسجيل دخوله بنجاح.',
        'guest_declined' => 'الضيف {name} قام بإلغاء الحضور.',
        'guest_not_confirmed' => 'الضيف {name} لم يؤكد حضوره بعد.',
        'guest_pending_options' => 'الضيف {name} لم يؤكد حضوره. يمكنك تأكيد حضوره وتسجيل دخوله.',
        'multiple_guests_found' => 'تم العثور على عدة ضيوف بنفس البيانات. الرجاء استخدام المعرف الفريد (QR) أو رقم الهاتف للتمييز بينهم.',
        'name' => 'الاسم',
        'guests_count' => 'عدد الضيوف',
        'table_number' => 'رقم الطاولة',
        'assigned_location' => 'الموقع المخصص',
        'checkin_status' => 'حالة تسجيل الدخول',
        'guest_status' => 'حالة الدعوة',
        'checked_in' => 'تم تسجيل الدخول',
        'not_checked_in' => 'لم يتم',
        'confirmed' => 'مؤكد',
        'pending' => 'في الانتظار',
        'canceled' => 'معتذر',
        'quick_stats' => 'إحصائيات سريعة',
        'today_checkins' => 'تسجيلات اليوم',
        'total_confirmed' => 'إجمالي المؤكدين',
        'total_pending' => 'بانتظار التأكيد',
        'remaining_guests' => 'الضيوف المتبقين',
        'sound_enabled' => 'الصوت مفعل',
        'sound_disabled' => 'الصوت معطل',
        'recent_checkins' => 'التسجيلات الأخيرة',
        'clear_recent' => 'مسح القائمة',
        'manual_entry' => 'إدخال يدوي',
        'add_note' => 'إضافة ملاحظة',
        'notes' => 'الملاحظات',
        'note_added' => 'تمت إضافة الملاحظة بنجاح',
        'enter_note' => 'أدخل ملاحظة...',
        'export_report' => 'تصدير تقرير',
        'print_list' => 'طباعة القائمة',
        'backup_data' => 'نسخ احتياطي',
        'advanced_search' => 'بحث متقدم',
        'search_by_table' => 'البحث برقم الطاولة',
        'search_by_status' => 'البحث بالحالة',
        'all_statuses' => 'كل الحالات',
        'volume_control' => 'التحكم بالصوت',
        'offline_mode' => 'وضع غير متصل',
        'online_mode' => 'وضع متصل',
        'sync_data' => 'مزامنة البيانات',
        'powered_by' => 'مدعوم من',
        'wosuol_tagline' => 'نظام إدارة الفعاليات والدعوات',
        'viewer_mode' => 'وضع المشاهدة فقط',
        'no_permission_checkin' => 'ليس لديك صلاحية تسجيل الدخول',
        'download_offline' => 'تحميل للاستخدام بدون إنترنت',
        'install_app' => 'تثبيت التطبيق',
        'offline_ready' => 'جاهز للعمل بدون إنترنت'
    ],
    'en' => [
        'checkin_system' => 'Guest Check-in System',
        'event_title' => 'Event',
        'back_to_events' => 'Back to Events',
        'logout' => 'Logout',
        'scan_qr_or_search' => 'Scan QR code or search by name',
        'start_scanning' => 'Start Scanning',
        'stop_scanning' => 'Stop Scanning',
        'search_placeholder' => 'Search by name or phone...',
        'checkin_button' => 'Check In',
        'confirm_and_checkin' => 'Confirm & Check In',
        'results_appear_here' => 'Results will appear here...',
        'checking' => 'Checking...',
        'camera_error' => 'Cannot access camera.',
        'connection_error' => 'Connection error occurred. Please try again.',
        'guest_not_found' => 'Guest not found in this event list.',
        'guest_already_checked_in' => '{name} was already checked in.',
        'guest_checked_in_success' => '{name} checked in successfully.',
        'guest_confirmed_and_checked_in' => '{name} confirmed and checked in successfully.',
        'guest_declined' => 'Guest {name} declined attendance.',
        'guest_not_confirmed' => 'Guest {name} has not confirmed attendance yet.',
        'guest_pending_options' => 'Guest {name} has not confirmed attendance. You can confirm and check them in.',
        'multiple_guests_found' => 'Multiple guests found with same data. Please use unique ID (QR) or phone number to distinguish.',
        'name' => 'Name',
        'guests_count' => 'Guests Count',
        'table_number' => 'Table Number',
        'assigned_location' => 'Assigned Location',
        'checkin_status' => 'Check-in Status',
        'guest_status' => 'Invitation Status',
        'checked_in' => 'Checked In',
        'not_checked_in' => 'Not Checked In',
        'confirmed' => 'Confirmed',
        'pending' => 'Pending',
        'canceled' => 'Canceled',
        'quick_stats' => 'Quick Stats',
        'today_checkins' => 'Today\'s Check-ins',
        'total_confirmed' => 'Total Confirmed',
        'total_pending' => 'Awaiting Confirmation',
        'remaining_guests' => 'Remaining Guests',
        'sound_enabled' => 'Sound Enabled',
        'sound_disabled' => 'Sound Disabled',
        'recent_checkins' => 'Recent Check-ins',
        'clear_recent' => 'Clear List',
        'manual_entry' => 'Manual Entry',
        'add_note' => 'Add Note',
        'notes' => 'Notes',
        'note_added' => 'Note added successfully',
        'enter_note' => 'Enter note...',
        'export_report' => 'Export Report',
        'print_list' => 'Print List',
        'backup_data' => 'Backup Data',
        'advanced_search' => 'Advanced Search',
        'search_by_table' => 'Search by Table',
        'search_by_status' => 'Search by Status',
        'all_statuses' => 'All Statuses',
        'volume_control' => 'Volume Control',
        'offline_mode' => 'Offline Mode',
        'online_mode' => 'Online Mode',
        'sync_data' => 'Sync Data',
        'powered_by' => 'Powered by',
        'wosuol_tagline' => 'Event & Invitation Management System',
        'viewer_mode' => 'View Only Mode',
        'no_permission_checkin' => 'You do not have check-in permissions',
        'download_offline' => 'Download for Offline Use',
        'install_app' => 'Install App',
        'offline_ready' => 'Ready for Offline Use'
    ]
];

$t = $texts[$lang];

// --- Security & Permission Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'checkin_user', 'viewer'])) {
    header('Location: login.php');
    exit;
}

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$user_role = $_SESSION['role'];
$user_event_access = $_SESSION['event_id_access'] ?? null;

if (!$event_id) {
    if ($user_role === 'admin') { header('Location: events.php'); exit; }
    else { die('Access Denied: Event ID is required.'); }
}

if ($user_role !== 'admin' && $event_id != $user_event_access) {
    die('Access Denied: You do not have permission to access this check-in page.');
}

// Check if user is viewer (read-only mode)
$isViewerMode = ($user_role === 'viewer');

// --- API Logic ---
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $api_event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Security check inside API
    if ($_SESSION['role'] !== 'admin' && $api_event_id != ($_SESSION['event_id_access'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'وصول غير مصرح به.']);
        exit;
    }

    // --- Stats API ---
    if (isset($_GET['stats'])) {
        $today = date('Y-m-d');
        
        // Today's check-ins
        $stmt_today = $mysqli->prepare("SELECT COUNT(*) as today_count FROM guests WHERE event_id = ? AND checkin_status = 'checked_in' AND DATE(checkin_time) = ?");
        $stmt_today->bind_param("is", $api_event_id, $today);
        $stmt_today->execute();
        $today_checkins = $stmt_today->get_result()->fetch_assoc()['today_count'];
        $stmt_today->close();
        
        // Total confirmed
        $stmt_confirmed = $mysqli->prepare("SELECT COUNT(*) as confirmed_count FROM guests WHERE event_id = ? AND status = 'confirmed'");
        $stmt_confirmed->bind_param("i", $api_event_id);
        $stmt_confirmed->execute();
        $total_confirmed = $stmt_confirmed->get_result()->fetch_assoc()['confirmed_count'];
        $stmt_confirmed->close();
        
        // Total pending
        $stmt_pending = $mysqli->prepare("SELECT COUNT(*) as pending_count FROM guests WHERE event_id = ? AND status = 'pending'");
        $stmt_pending->bind_param("i", $api_event_id);
        $stmt_pending->execute();
        $total_pending = $stmt_pending->get_result()->fetch_assoc()['pending_count'];
        $stmt_pending->close();
        
        // Remaining guests (confirmed but not checked in)
        $stmt_remaining = $mysqli->prepare("SELECT COUNT(*) as remaining_count FROM guests WHERE event_id = ? AND status = 'confirmed' AND checkin_status != 'checked_in'");
        $stmt_remaining->bind_param("i", $api_event_id);
        $stmt_remaining->execute();
        $remaining_guests = $stmt_remaining->get_result()->fetch_assoc()['remaining_count'];
        $stmt_remaining->close();
        
        echo json_encode([
            'today_checkins' => $today_checkins,
            'total_confirmed' => $total_confirmed,
            'total_pending' => $total_pending,
            'remaining_guests' => $remaining_guests
        ]);
        exit;
    }

    // --- Recent Check-ins API ---
    if (isset($_GET['recent'])) {
        $stmt = $mysqli->prepare("SELECT name_ar, checkin_time, notes FROM guests WHERE event_id = ? AND checkin_status = 'checked_in' ORDER BY checkin_time DESC LIMIT 10");
        $stmt->bind_param("i", $api_event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $recent = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode($recent);
        exit;
    }

    // --- Suggestion Mode ---
    if (isset($_GET['suggest'])) {
        $searchTerm = trim($input['searchTerm'] ?? '');
        if (empty($searchTerm) || !$api_event_id) {
            echo json_encode([]);
            exit;
        }
        
        $searchTermLike = "%" . $searchTerm . "%";
        $stmt = $mysqli->prepare("SELECT guest_id, name_ar, phone_number, status, checkin_status, table_number, guests_count, notes FROM guests WHERE (name_ar LIKE ? OR phone_number LIKE ? OR table_number LIKE ?) AND event_id = ? LIMIT 10");
        $stmt->bind_param("sssi", $searchTermLike, $searchTermLike, $searchTermLike, $api_event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $guests = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode($guests);
        exit;
    }

    // --- Add Note API ---
    if (isset($_GET['add_note'])) {
        $guest_id = trim($input['guest_id'] ?? '');
        $note = trim($input['note'] ?? '');
        
        if (empty($guest_id) || empty($note)) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }
        
        // Get current notes
        $stmt = $mysqli->prepare("SELECT notes FROM guests WHERE guest_id = ? AND event_id = ?");
        $stmt->bind_param("si", $guest_id, $api_event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $guest = $result->fetch_assoc();
        $stmt->close();
        
        if (!$guest) {
            echo json_encode(['success' => false, 'message' => 'Guest not found']);
            exit;
        }
        
        // Append new note with timestamp
        $current_notes = $guest['notes'] ?? '';
        $timestamp = date('Y-m-d H:i:s');
        $new_note = "[{$timestamp}] {$note}";
        $updated_notes = empty($current_notes) ? $new_note : $current_notes . "\n" . $new_note;
        
        // Update notes
        $stmt = $mysqli->prepare("UPDATE guests SET notes = ? WHERE guest_id = ? AND event_id = ?");
        $stmt->bind_param("ssi", $updated_notes, $guest_id, $api_event_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $t['note_added']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add note']);
        }
        $stmt->close();
        exit;
    }

    // --- Check-in Logic ---
    $response = ['success' => false, 'message' => 'حدث خطأ غير متوقع.'];
    $searchTerm = trim($input['searchTerm'] ?? '');
    $confirmAndCheckin = $input['confirmAndCheckin'] ?? false;

    if (empty($searchTerm) || !$api_event_id) {
        $response['message'] = 'بيانات ناقصة (مصطلح البحث مطلوب).';
        echo json_encode($response);
        exit;
    }

    $searchTermLike = "%" . $searchTerm . "%";
    $stmt = $mysqli->prepare("SELECT * FROM guests WHERE (guest_id = ? OR name_ar LIKE ? OR phone_number LIKE ?) AND event_id = ?");
    $stmt->bind_param("sssi", $searchTerm, $searchTermLike, $searchTermLike, $api_event_id);
    
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        $response['message'] = $t['guest_not_found'];
    } elseif ($result->num_rows === 1) {
        $guest = $result->fetch_assoc();
        
        if ($guest['status'] === 'confirmed') {
            if ($guest['checkin_status'] === 'checked_in') {
                $response['success'] = true;
                $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), $t['guest_already_checked_in']);
                $response['type'] = 'warning';
                $response['guestDetails'] = $guest;
            } else {
                $update_stmt = $mysqli->prepare("UPDATE guests SET checkin_status = 'checked_in', checkin_time = NOW() WHERE guest_id = ?");
                $update_stmt->bind_param("s", $guest['guest_id']);
                if ($update_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), $t['guest_checked_in_success']);
                    $response['type'] = 'success';
                    $guest['checkin_status'] = 'checked_in';
                    $response['guestDetails'] = $guest;
                }
                $update_stmt->close();
            }
        } elseif ($guest['status'] === 'canceled') {
            $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), $t['guest_declined']);
            $response['type'] = 'error';
            $response['guestDetails'] = $guest;
        } elseif ($guest['status'] === 'pending') {
            if ($confirmAndCheckin) {
                // Confirm and check in the guest
                $update_stmt = $mysqli->prepare("UPDATE guests SET status = 'confirmed', checkin_status = 'checked_in', checkin_time = NOW() WHERE guest_id = ?");
                $update_stmt->bind_param("s", $guest['guest_id']);
                if ($update_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), $t['guest_confirmed_and_checked_in']);
                    $response['type'] = 'success';
                    $guest['status'] = 'confirmed';
                    $guest['checkin_status'] = 'checked_in';
                    $response['guestDetails'] = $guest;
                }
                $update_stmt->close();
            } else {
                $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), $t['guest_pending_options']);
                $response['type'] = 'pending';
                $response['showConfirmOption'] = true;
                $response['guestDetails'] = $guest;
            }
        }
    } else {
        $response['message'] = $t['multiple_guests_found'];
        $response['type'] = 'warning';
        $response['multipleResults'] = true;
    }

    echo json_encode($response);
    $mysqli->close();
    exit;
}

// --- Fetch Event Name for Display ---
$event_name = 'تسجيل دخول الضيوف';
$stmt_event = $mysqli->prepare("SELECT event_name FROM events WHERE id = ?");
$stmt_event->bind_param("i", $event_id);
if ($stmt_event->execute()) {
    $result = $stmt_event->get_result();
    if ($row = $result->fetch_assoc()) { $event_name = $row['event_name']; }
}
$stmt_event->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['checkin_system'] ?>: <?= htmlspecialchars($event_name) ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3b82f6">
    <meta name="description" content="<?= $t['checkin_system'] ?> - <?= htmlspecialchars($event_name) ?>">
    <link rel="manifest" href="data:application/json;charset=utf-8,<?= urlencode(json_encode([
        'name' => $t['checkin_system'] . ': ' . $event_name,
        'short_name' => 'تسجيل الدخول',
        'description' => $t['checkin_system'],
        'start_url' => './checkin.php?event_id=' . $event_id,
        'display' => 'standalone',
        'background_color' => '#667eea',
        'theme_color' => '#3b82f6',
        'icons' => [
            [
                'src' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="#3b82f6"><path d="M152.1 38.2c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L7 113C-2.3 103.6-2.3 88.4 7 79s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zm0 160c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L7 273c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zM224 96c0-17.7 14.3-32 32-32H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H256c-17.7 0-32-14.3-32-32zm0 160c0-17.7 14.3-32 32-32H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H256c-17.7 0-32-14.3-32-32zM160 416c0-17.7 14.3-32 32-32H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H192c-17.7 0-32-14.3-32-32zM48 368a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>'),
                'sizes' => '512x512',
                'type' => 'image/svg+xml'
            ]
        ]
    ])) ?>">
    
    <!-- iOS PWA Support -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= $t['checkin_system'] ?>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex; 
            flex-direction: column; 
            padding: 20px; 
        }
        
        .header-brand {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .wosuol-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .wosuol-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .container { 
            max-width: 800px; 
            width: 100%; 
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            padding: 30px; 
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header-buttons { display: flex; gap: 12px; align-items: center; }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
            backdrop-filter: blur(20px);
            border-radius: 15px;
            color: white;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            transition: all 0.5s ease;
        }
        
        .stat-number.pulse {
            animation: pulse 0.6s ease-in-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .response-area { 
            margin-top: 20px; 
            padding: 20px; 
            border-radius: 15px; 
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>; 
            border: 2px solid #eee; 
            min-height: 120px; 
            background: rgba(249, 250, 251, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .response-area.success { 
            border-color: #10b981; 
            background: linear-gradient(135deg, rgba(209, 250, 229, 0.9), rgba(167, 243, 208, 0.9)); 
            color: #065f46; 
        }
        .response-area.error { 
            border-color: #ef4444; 
            background: linear-gradient(135deg, rgba(254, 226, 226, 0.9), rgba(252, 165, 165, 0.9)); 
            color: #991b1b; 
        }
        .response-area.warning { 
            border-color: #f59e0b; 
            background: linear-gradient(135deg, rgba(254, 243, 199, 0.9), rgba(253, 230, 138, 0.9)); 
            color: #92400e; 
        }
        .response-area.pending { 
            border-color: #8b5cf6; 
            background: linear-gradient(135deg, rgba(237, 233, 254, 0.9), rgba(221, 214, 254, 0.9)); 
            color: #5b21b6; 
        }
        
        .detail-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 8px 0; 
            border-bottom: 1px dashed rgba(0,0,0,0.1); 
        }
        .detail-item:last-child { border-bottom: none; }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            min-width: 70px;
            display: inline-block;
        }
        .status-confirmed { background-color: #dcfce7; color: #166534; }
        .status-canceled { background-color: #fee2e2; color: #991b1b; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .checkin-checked_in { background-color: #dbeafe; color: #1e40af; }
        .checkin-not { background-color: #f3f4f6; color: #6b7280; }
        
        #video { 
            width: 100%; 
            max-width: 400px; 
            height: 300px; 
            border-radius: 15px; 
            margin: 20px auto; 
            display: block; 
            background-color: #000; 
            object-fit: cover;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }
        
        .search-container { 
            position: relative; 
            margin: 20px 0; 
        }
        
        #suggestions-box {
            position: absolute; 
            top: 100%; 
            left: 0; 
            right: 0;
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(20px);
            border: 1px solid rgba(209, 213, 219, 0.5);
            border-radius: 0 0 15px 15px; 
            max-height: 300px;
            overflow-y: auto; 
            z-index: 10;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .suggestion-item {
            padding: 15px; 
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>; 
            cursor: pointer;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            transition: all 0.2s ease;
        }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { 
            background: rgba(243, 244, 246, 0.8); 
            transform: translateX(5px);
        }
        .suggestion-item.confirmed { border-left: 4px solid #10b981; }
        .suggestion-item.canceled { border-left: 4px solid #ef4444; }
        .suggestion-item.pending { border-left: 4px solid #f59e0b; }
        .suggestion-item.checked-in { border-left: 4px solid #3b82f6; }
        
        .control-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15); 
        }
        .btn-primary { 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            color: white; 
        }
        .btn-secondary { 
            background: linear-gradient(135deg, #6b7280, #4b5563); 
            color: white; 
        }
        .btn-success { 
            background: linear-gradient(135deg, #10b981, #059669); 
            color: white; 
        }
        .btn-warning { 
            background: linear-gradient(135deg, #f59e0b, #d97706); 
            color: white; 
        }
        .btn-danger { 
            background: linear-gradient(135deg, #ef4444, #dc2626); 
            color: white; 
        }
        .btn-toggle { 
            background: rgba(243, 244, 246, 0.9); 
            color: #374151; 
            border: 1px solid rgba(209, 213, 219, 0.5); 
        }
        .btn-toggle.active { 
            background: linear-gradient(135deg, #3b82f6, #2563eb); 
            color: white; 
        }
        
        .recent-checkins {
            margin-top: 20px;
            padding: 20px;
            background: rgba(248, 250, 252, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        .recent-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .recent-item:last-child { border-bottom: none; }
        .recent-item:hover { background: rgba(243, 244, 246, 0.8); border-radius: 8px; }
        
        .search-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(229, 231, 235, 0.5);
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 1);
        }
        
        .notes-section {
            margin-top: 15px;
            padding: 15px;
            background: rgba(248, 250, 252, 0.9);
            border-radius: 10px;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .note-input {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(209, 213, 219, 0.5);
            border-radius: 8px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .notes-list {
            max-height: 100px;
            overflow-y: auto;
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .volume-slider {
            width: 100px;
            margin: 0 10px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .footer-brand {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .footer-brand a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .footer-brand a:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .stats-bar { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 10px; 
            }
            .control-buttons { 
                flex-direction: column; 
            }
            .header-brand { 
                flex-direction: column; 
                gap: 10px; 
                text-align: center; 
            }
            .wosuol-logo {
                font-size: 1.2rem;
            }
            .wosuol-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header with Wosuol Branding -->
    <div class="header-brand">
        <a href="https://wosuol.com" target="_blank" class="wosuol-logo">
            <div class="wosuol-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <div style="font-size: 1.5rem;">وصول</div>
                <div style="font-size: 0.8rem; opacity: 0.7;"><?= $t['wosuol_tagline'] ?></div>
            </div>
        </a>
        <div class="header-buttons">
            <form method="POST" style="display: inline;">
                <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                        class="btn btn-toggle">
                    <i class="fas fa-globe"></i>
                    <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                </button>
            </form>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="events.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <?= $t['back_to_events'] ?>
                </a>
            <?php else: ?>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <?= $t['logout'] ?>
                </a>
            <?php endif; ?>
            
            <!-- PWA Install Button -->
            <button id="install-button" class="btn btn-primary" style="display: none;">
                <i class="fas fa-download"></i>
                <?= $t['install_app'] ?>
            </button>
            
            <!-- Offline Download Button -->
            <button id="download-offline-button" class="btn btn-success">
                <i class="fas fa-cloud-download-alt"></i>
                <?= $t['download_offline'] ?>
            </button>
        </div>
    </div>
    
    <div class="container">
        <?php if ($isViewerMode): ?>
        <div class="bg-blue-100 text-blue-800 p-4 rounded-lg mb-4 text-center">
            <i class="fas fa-eye"></i>
            <strong><?= $t['viewer_mode'] ?></strong> - <?= $t['no_permission_checkin'] ?>
        </div>
        <?php endif; ?>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">
            <i class="fas fa-clipboard-check text-blue-600"></i>
            <?= $t['checkin_system'] ?>
            <?php if ($isViewerMode): ?>
                <span class="text-sm text-blue-600">(<?= $t['viewer_mode'] ?>)</span>
            <?php endif; ?>
        </h2>
        <p class="text-center text-gray-600 mb-4"><?= $t['event_title'] ?>: <?= htmlspecialchars($event_name) ?></p>
        
        <!-- Quick Stats -->
        <div class="stats-bar" id="stats-bar">
            <div class="stat-item">
                <span class="stat-number" id="today-checkins">0</span>
                <div class="stat-label"><?= $t['today_checkins'] ?></div>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="total-confirmed">0</span>
                <div class="stat-label"><?= $t['total_confirmed'] ?></div>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="total-pending">0</span>
                <div class="stat-label"><?= $t['total_pending'] ?></div>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="remaining-guests">0</span>
                <div class="stat-label"><?= $t['remaining_guests'] ?></div>
            </div>
        </div>
        
        <p class="text-gray-600 mb-6 text-center"><?= $t['scan_qr_or_search'] ?></p>
        
        <video id="video" playsinline></video>
        <canvas id="canvas" class="hidden"></canvas>
        
        <div class="control-buttons">
            <button id="start-scan-button" class="btn btn-primary">
                <i class="fas fa-qrcode"></i>
                <?= $t['start_scanning'] ?>
            </button>
            <button id="stop-scan-button" class="btn btn-secondary">
                <i class="fas fa-stop"></i>
                <?= $t['stop_scanning'] ?>
            </button>
            <button id="sound-toggle" class="btn btn-toggle">
                <i class="fas fa-volume-up"></i>
                <?= $t['sound_enabled'] ?>
            </button>
            <button id="manual-toggle" class="btn btn-toggle">
                <i class="fas fa-keyboard"></i>
                <?= $t['manual_entry'] ?>
            </button>
        </div>
        
        <!-- Volume Control -->
        <div class="flex justify-center items-center mb-4">
            <span class="text-sm text-gray-600"><?= $t['volume_control'] ?>:</span>
            <input type="range" id="volume-slider" class="volume-slider" min="0" max="1" step="0.1" value="0.7">
            <span id="volume-display" class="text-sm text-gray-600 ml-2">70%</span>
        </div>
        
        <div class="search-container">
            <div class="flex gap-2">
                <input type="text" 
                       id="search-input" 
                       class="search-input flex-grow" 
                       placeholder="<?= $t['search_placeholder'] ?>" 
                       autocomplete="off">
                <?php if (!$isViewerMode): ?>
                <button id="check-in-button" class="btn btn-success">
                    <i class="fas fa-check"></i>
                    <?= $t['checkin_button'] ?>
                </button>
                <button id="confirm-checkin-button" class="btn btn-warning" style="display: none;">
                    <i class="fas fa-user-check"></i>
                    <?= $t['confirm_and_checkin'] ?>
                </button>
                <?php else: ?>
                <button class="btn btn-secondary" disabled>
                    <i class="fas fa-eye"></i>
                    <?= $t['viewer_mode'] ?>
                </button>
                <?php endif; ?>
            </div>
            <div id="suggestions-box" class="hidden"></div>
        </div>
        
        <div id="response-area" class="response-area">
            <p class="text-gray-500"><?= $t['results_appear_here'] ?></p>
        </div>
        
        <!-- Advanced Search Options -->
        <div class="mt-4">
            <button id="advanced-search-toggle" class="btn btn-toggle">
                <i class="fas fa-search-plus"></i>
                <?= $t['advanced_search'] ?>
            </button>
        </div>
        
        <div id="advanced-search-panel" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?= $t['search_by_table'] ?>:</label>
                    <input type="text" id="table-search" class="search-input" placeholder="رقم الطاولة...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2"><?= $t['search_by_status'] ?>:</label>
                    <select id="status-filter" class="search-input">
                        <option value=""><?= $t['all_statuses'] ?></option>
                        <option value="confirmed"><?= $t['confirmed'] ?></option>
                        <option value="pending"><?= $t['pending'] ?></option>
                        <option value="canceled"><?= $t['canceled'] ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="flex justify-center gap-2 mt-4">
            <button id="export-button" class="btn btn-primary">
                <i class="fas fa-download"></i>
                <?= $t['export_report'] ?>
            </button>
            <button id="print-button" class="btn btn-secondary">
                <i class="fas fa-print"></i>
                <?= $t['print_list'] ?>
            </button>
            <button id="backup-button" class="btn btn-warning">
                <i class="fas fa-save"></i>
                <?= $t['backup_data'] ?>
            </button>
        </div>
        
        <!-- Recent Check-ins -->
        <div class="recent-checkins">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold text-gray-700">
                    <i class="fas fa-history"></i>
                    <?= $t['recent_checkins'] ?>
                </h3>
                <button id="clear-recent" class="text-sm text-blue-600 hover:underline">
                    <i class="fas fa-trash"></i>
                    <?= $t['clear_recent'] ?>
                </button>
            </div>
            <div id="recent-list"></div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Footer with Wosuol Branding -->
    <div class="footer-brand">
        <p class="text-gray-600 mb-2">
            <?= $t['powered_by'] ?> 
            <a href="https://wosuol.com" target="_blank">
                <strong>وصول - Wosuol.com</strong>
            </a>
        </p>
        <p class="text-sm text-gray-500">
            &copy; <?= date('Y') ?> جميع الحقوق محفوظة
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const suggestApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true&suggest=true';
        const checkinApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true';
        const statsApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true&stats=true';
        const recentApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true&recent=true';
        const addNoteApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true&add_note=true';
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        const isViewerMode = <?= $isViewerMode ? 'true' : 'false' ?>;
        
        const searchInput = document.getElementById('search-input');
        const checkinButton = document.getElementById('check-in-button');
        const confirmCheckinButton = document.getElementById('confirm-checkin-button');
        const responseArea = document.getElementById('response-area');
        const suggestionsBox = document.getElementById('suggestions-box');
        const soundToggle = document.getElementById('sound-toggle');
        const manualToggle = document.getElementById('manual-toggle');
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const startScanButton = document.getElementById('start-scan-button');
        const stopScanButton = document.getElementById('stop-scan-button');
        const recentList = document.getElementById('recent-list');
        const clearRecentBtn = document.getElementById('clear-recent');
        const volumeSlider = document.getElementById('volume-slider');
        const volumeDisplay = document.getElementById('volume-display');
        const loadingOverlay = document.getElementById('loading-overlay');
        const advancedSearchToggle = document.getElementById('advanced-search-toggle');
        const advancedSearchPanel = document.getElementById('advanced-search-panel');
        const tableSearch = document.getElementById('table-search');
        const statusFilter = document.getElementById('status-filter');
        const installButton = document.getElementById('install-button');
        const downloadOfflineButton = document.getElementById('download-offline-button');
        
        let videoStream = null;
        let animationFrameId = null;
        let debounceTimer;
        let soundEnabled = localStorage.getItem('checkin_sound') !== 'false';
        let soundVolume = parseFloat(localStorage.getItem('checkin_volume') || '0.7');
        let manualMode = false;
        let recentCheckins = JSON.parse(localStorage.getItem('recent_checkins') || '[]');
        let currentGuestForConfirm = null;
        let offlineMode = false;
        let offlineData = JSON.parse(localStorage.getItem('offline_checkins') || '[]');
        
        // Initialize UI
        updateSoundToggle();
        updateVolumeDisplay();
        updateRecentList();
        loadStats();
        loadRecentFromServer();
        
        // Auto-refresh stats every 30 seconds
        setInterval(loadStats, 30000);
        setInterval(loadRecentFromServer, 60000);

        // --- Stats Functions ---
        async function loadStats() {
            try {
                const response = await fetch(statsApiUrl);
                const stats = await response.json();
                animateNumber('today-checkins', stats.today_checkins);
                animateNumber('total-confirmed', stats.total_confirmed);
                animateNumber('total-pending', stats.total_pending);
                animateNumber('remaining-guests', stats.remaining_guests);
                offlineMode = false;
            } catch (error) {
                console.error('Error loading stats:', error);
                offlineMode = true;
                showOfflineStats();
            }
        }

        function animateNumber(elementId, targetNumber) {
            const element = document.getElementById(elementId);
            const currentNumber = parseInt(element.textContent) || 0;
            
            if (currentNumber !== targetNumber) {
                element.classList.add('pulse');
                setTimeout(() => element.classList.remove('pulse'), 600);
                
                const increment = targetNumber > currentNumber ? 1 : -1;
                const timer = setInterval(() => {
                    const current = parseInt(element.textContent) || 0;
                    if (current === targetNumber) {
                        clearInterval(timer);
                    } else {
                        element.textContent = current + increment;
                    }
                }, 30);
            }
        }

        function showOfflineStats() {
            // Show cached stats or calculate from offline data
            const todayCheckins = offlineData.filter(item => 
                new Date(item.timestamp).toDateString() === new Date().toDateString()
            ).length;
            
            document.getElementById('today-checkins').textContent = todayCheckins;
            // Show offline indicator
            if (!document.querySelector('.offline-indicator')) {
                const indicator = document.createElement('div');
                indicator.className = 'offline-indicator bg-yellow-100 text-yellow-800 p-2 rounded text-center text-sm mb-4';
                indicator.innerHTML = '<i class="fas fa-wifi-slash"></i> ' + texts['offline_mode'];
                document.querySelector('.container').prepend(indicator);
            }
        }

        async function loadRecentFromServer() {
            try {
                const response = await fetch(recentApiUrl);
                const recent = await response.json();
                // Merge with local recent checkins
                recent.forEach(item => {
                    if (!recentCheckins.find(r => r.name_ar === item.name_ar && r.checkin_time === item.checkin_time)) {
                        recentCheckins.unshift(item);
                    }
                });
                recentCheckins = recentCheckins.slice(0, 10);
                updateRecentList();
            } catch (error) {
                console.error('Error loading recent checkins:', error);
            }
        }

        function updateRecentList() {
            recentList.innerHTML = '';
            if (recentCheckins.length === 0) {
                recentList.innerHTML = '<div class="text-gray-500 text-center py-2">لا توجد تسجيلات حديثة</div>';
                return;
            }
            recentCheckins.forEach(item => {
                const div = document.createElement('div');
                div.className = 'recent-item';
                const time = new Date(item.checkin_time).toLocaleTimeString('ar-EG', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                div.innerHTML = `
                    <div>
                        <span class="font-medium">${item.name_ar}</span>
                        ${item.notes ? `<div class="text-xs text-gray-500">${item.notes.split('\n').pop()}</div>` : ''}
                    </div>
                    <span class="text-gray-500">${time}</span>
                `;
                recentList.appendChild(div);
            });
            localStorage.setItem('recent_checkins', JSON.stringify(recentCheckins));
        }

        // --- Sound Functions ---
        function playSound(type) {
            if (!soundEnabled) return;
            
            let frequency, duration;
            switch(type) {
                case 'success':
                    frequency = [523, 659, 784]; // C, E, G
                    duration = 200;
                    break;
                case 'warning':
                    frequency = [440, 554]; // A, C#
                    duration = 300;
                    break;
                case 'error':
                    frequency = [220, 185]; // A, F#
                    duration = 400;
                    break;
                default:
                    return;
            }
            
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            frequency.forEach((freq, index) => {
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.setValueAtTime(freq, audioContext.currentTime + index * 0.1);
                gainNode.gain.setValueAtTime(soundVolume, audioContext.currentTime + index * 0.1);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + index * 0.1 + duration / 1000);
                
                oscillator.start(audioContext.currentTime + index * 0.1);
                oscillator.stop(audioContext.currentTime + index * 0.1 + duration / 1000);
            });
        }

        function updateSoundToggle() {
            const icon = soundToggle.querySelector('i');
            soundToggle.innerHTML = `<i class="fas fa-volume-${soundEnabled ? 'up' : 'mute'}"></i> ${soundEnabled ? texts['sound_enabled'] : texts['sound_disabled']}`;
            soundToggle.classList.toggle('active', soundEnabled);
        }

        function updateVolumeDisplay() {
            volumeSlider.value = soundVolume;
            volumeDisplay.textContent = Math.round(soundVolume * 100) + '%';
        }

        // --- Offline Data Management ---
        let cachedGuestsData = JSON.parse(localStorage.getItem('cached_guests_data') || '[]');
        let pendingSyncData = JSON.parse(localStorage.getItem('pending_sync_data') || '[]');

        // Load and cache all guests data for offline use
        async function loadAndCacheGuestsData() {
            try {
                const response = await fetch(`guests_api.php?event_id=<?= $event_id ?>&fetch_guests=true`);
                const data = await response.json();
                if (Array.isArray(data)) {
                    cachedGuestsData = data;
                    localStorage.setItem('cached_guests_data', JSON.stringify(cachedGuestsData));
                    console.log(`تم تحميل وحفظ بيانات ${cachedGuestsData.length} ضيف للاستخدام غير المتصل`);
                    updateOfflineIndicator(false);
                    
                    // Show success message
                    const indicator = document.createElement('div');
                    indicator.className = 'bg-blue-100 text-blue-800 p-2 rounded text-center text-sm mb-4';
                    indicator.innerHTML = `<i class="fas fa-download"></i> تم تحميل ${cachedGuestsData.length} ضيف للاستخدام غير المتصل`;
                    document.querySelector('.container').prepend(indicator);
                    setTimeout(() => indicator.remove(), 5000);
                }
            } catch (error) {
                console.error('Error loading guests data:', error);
                updateOfflineIndicator(true);
                
                // Show error if no cached data exists
                if (cachedGuestsData.length === 0) {
                    const indicator = document.createElement('div');
                    indicator.className = 'bg-red-100 text-red-800 p-2 rounded text-center text-sm mb-4';
                    indicator.innerHTML = '<i class="fas fa-exclamation-triangle"></i> فشل في تحميل بيانات الضيوف - تأكد من الاتصال بالإنترنت';
                    document.querySelector('.container').prepend(indicator);
                }
            }
        }

        function updateOfflineIndicator(isOffline) {
            const existingIndicator = document.querySelector('.connection-indicator');
            if (existingIndicator) existingIndicator.remove();
            
            const indicator = document.createElement('div');
            indicator.className = 'connection-indicator p-2 rounded text-center text-sm mb-4';
            
            if (isOffline) {
                indicator.className += ' bg-yellow-100 text-yellow-800';
                indicator.innerHTML = '<i class="fas fa-wifi-slash"></i> وضع غير متصل - البيانات محفوظة محلياً';
            } else {
                indicator.className += ' bg-green-100 text-green-800';
                indicator.innerHTML = '<i class="fas fa-wifi"></i> متصل - البيانات محدثة';
                setTimeout(() => indicator.remove(), 3000);
            }
            
            document.querySelector('.container').prepend(indicator);
        }

        // Sync pending data when back online
        async function syncPendingData() {
            if (pendingSyncData.length === 0) return;
            
            console.log('بدء مزامنة البيانات المعلقة...', pendingSyncData);
            
            const successfulSyncs = [];
            const failedSyncs = [];
            
            for (const syncItem of pendingSyncData) {
                try {
                    const response = await fetch(checkinApiUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(syncItem.data)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        successfulSyncs.push(syncItem);
                    } else {
                        failedSyncs.push(syncItem);
                    }
                } catch (error) {
                    failedSyncs.push(syncItem);
                }
            }
            
            // Update pending sync data (keep only failed ones)
            pendingSyncData = failedSyncs;
            localStorage.setItem('pending_sync_data', JSON.stringify(pendingSyncData));
            
            if (successfulSyncs.length > 0) {
                console.log(`تمت مزامنة ${successfulSyncs.length} عنصر بنجاح`);
                loadStats(); // Refresh stats after sync
            }
            
            if (failedSyncs.length > 0) {
                console.log(`فشل في مزامنة ${failedSyncs.length} عنصر`);
            }
        }

        // --- Suggestion Functions ---
        function searchInCachedData(searchTerm) {
            if (!cachedGuestsData.length) return [];
            
            const term = searchTerm.toLowerCase();
            return cachedGuestsData.filter(guest => {
                return (
                    (guest.name_ar && guest.name_ar.toLowerCase().includes(term)) ||
                    (guest.phone_number && guest.phone_number.includes(term)) ||
                    (guest.table_number && guest.table_number.toString().includes(term)) ||
                    (guest.guest_id && guest.guest_id.toLowerCase() === term.toLowerCase())
                );
            }).slice(0, 10);
        }

        async function fetchSuggestions(searchTerm) {
            if (searchTerm.length < 1) {
                suggestionsBox.innerHTML = '';
                suggestionsBox.classList.add('hidden');
                return;
            }

            let suggestions = [];
            
            // Try online search first
            try {
                const response = await fetch(suggestApiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ searchTerm: searchTerm })
                });
                suggestions = await response.json();
                updateOfflineIndicator(false);
            } catch (error) {
                console.log('البحث عبر الإنترنت فشل، استخدام البيانات المحفوظة محلياً');
                suggestions = searchInCachedData(searchTerm);
                updateOfflineIndicator(true);
            }
            
            displaySuggestions(suggestions);
        }

        function displaySuggestions(suggestions) {
            suggestionsBox.innerHTML = '';
            if (suggestions.length > 0) {
                suggestions.forEach(guest => {
                    const item = document.createElement('div');
                    item.className = `suggestion-item ${guest.status}`;
                    
                    let statusIcon = '';
                    let statusClass = '';
                    if (guest.checkin_status === 'checked_in') {
                        statusIcon = '✅';
                        statusClass = 'checked-in';
                    } else if (guest.status === 'confirmed') {
                        statusIcon = '🟢';
                        statusClass = 'confirmed';
                    } else if (guest.status === 'canceled') {
                        statusIcon = '🔴';
                        statusClass = 'canceled';
                    } else {
                        statusIcon = '🟡';
                        statusClass = 'pending';
                    }
                    
                    item.innerHTML = `
                        <div class="flex justify-between items-center">
                            <div class="flex-grow">
                                <div class="font-medium">${guest.name_ar}</div>
                                <div class="text-sm text-gray-500">
                                    ${guest.phone_number || 'لا يوجد هاتف'} | 
                                    الضيوف: ${guest.guests_count || '1'}
                                    ${guest.table_number ? ` | طاولة: ${guest.table_number}` : ''}
                                </div>
                                ${guest.notes ? `<div class="text-xs text-gray-600 mt-1">${guest.notes.split('\n').pop()}</div>` : ''}
                            </div>
                            <div class="text-lg ml-2">${statusIcon}</div>
                        </div>
                    `;
                    item.dataset.guestId = guest.guest_id;
                    item.dataset.status = guest.status;
                    
                    item.addEventListener('click', () => {
                        searchInput.value = item.dataset.guestId;
                        suggestionsBox.innerHTML = '';
                        suggestionsBox.classList.add('hidden');
                        clearResponseArea();
                    });
                    suggestionsBox.appendChild(item);
                });
                suggestionsBox.classList.remove('hidden');
            } else {
                suggestionsBox.classList.add('hidden');
            }
        }

        // --- Check-in Functions ---
        async function performCheckIn(searchTerm, confirmAndCheckin = false) {
            showLoading();
            responseArea.innerHTML = `<p class="text-gray-500">${texts['checking']}</p>`;
            responseArea.className = `response-area`;
            
            let isOnlineMode = true;
            
            try {
                const response = await fetch(checkinApiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        searchTerm: searchTerm,
                        confirmAndCheckin: confirmAndCheckin
                    })
                });
                
                const data = await response.json();
                handleCheckinResponse(data, searchTerm, confirmAndCheckin);
                updateOfflineIndicator(false);
                
            } catch (error) {
                console.log('العمل في وضع غير متصل');
                isOnlineMode = false;
                updateOfflineIndicator(true);
                
                // Handle offline check-in
                const offlineResult = handleOfflineCheckin(searchTerm, confirmAndCheckin);
                handleCheckinResponse(offlineResult, searchTerm, confirmAndCheckin, true);
            } finally {
                hideLoading();
            }
        }

        function handleOfflineCheckin(searchTerm, confirmAndCheckin) {
            // Search in cached data
            const term = searchTerm.toLowerCase();
            const matchedGuests = cachedGuestsData.filter(guest => {
                return (
                    (guest.guest_id && guest.guest_id.toLowerCase() === term) ||
                    (guest.name_ar && guest.name_ar.toLowerCase().includes(term)) ||
                    (guest.phone_number && guest.phone_number.includes(term))
                );
            });

            if (matchedGuests.length === 0) {
                return {
                    success: false,
                    message: texts['guest_not_found'],
                    type: 'error'
                };
            }

            if (matchedGuests.length > 1) {
                return {
                    success: false,
                    message: texts['multiple_guests_found'],
                    type: 'warning',
                    multipleResults: true
                };
            }

            const guest = matchedGuests[0];
            
            // Check if already checked in offline
            const offlineCheckin = pendingSyncData.find(item => 
                item.data.searchTerm === guest.guest_id && 
                (item.data.confirmAndCheckin || guest.status === 'confirmed')
            );
            
            if (offlineCheckin || guest.checkin_status === 'checked_in') {
                return {
                    success: true,
                    message: texts['guest_already_checked_in'].replace('{name}', guest.name_ar),
                    type: 'warning',
                    guestDetails: guest
                };
            }

            if (guest.status === 'canceled') {
                return {
                    success: false,
                    message: texts['guest_declined'].replace('{name}', guest.name_ar),
                    type: 'error',
                    guestDetails: guest
                };
            }

            if (guest.status === 'pending' && !confirmAndCheckin) {
                return {
                    success: false,
                    message: texts['guest_pending_options'].replace('{name}', guest.name_ar),
                    type: 'pending',
                    showConfirmOption: true,
                    guestDetails: guest
                };
            }

            // Perform offline check-in
            const syncData = {
                searchTerm: guest.guest_id,
                confirmAndCheckin: confirmAndCheckin || guest.status === 'confirmed'
            };

            // Add to pending sync
            pendingSyncData.push({
                id: Date.now(),
                timestamp: new Date().toISOString(),
                data: syncData,
                guest: guest
            });
            localStorage.setItem('pending_sync_data', JSON.stringify(pendingSyncData));

            // Update local cache
            const guestIndex = cachedGuestsData.findIndex(g => g.guest_id === guest.guest_id);
            if (guestIndex !== -1) {
                if (confirmAndCheckin && guest.status === 'pending') {
                    cachedGuestsData[guestIndex].status = 'confirmed';
                }
                cachedGuestsData[guestIndex].checkin_status = 'checked_in';
                cachedGuestsData[guestIndex].checkin_time = new Date().toISOString();
                localStorage.setItem('cached_guests_data', JSON.stringify(cachedGuestsData));
            }

            const message = confirmAndCheckin && guest.status === 'pending' 
                ? texts['guest_confirmed_and_checked_in']
                : texts['guest_checked_in_success'];

            return {
                success: true,
                message: message.replace('{name}', guest.name_ar),
                type: 'success',
                guestDetails: cachedGuestsData[guestIndex],
                offline: true
            };
        }

        function handleCheckinResponse(data, searchTerm, confirmAndCheckin, isOffline = false) {
            if (data.success) {
                let soundType = 'success';
                if (data.type === 'warning') soundType = 'warning';
                
                displayResponse(data.message, data.type || 'success', data.guestDetails, isOffline);
                playSound(soundType);
                
                if (data.type === 'success') {
                    // Add to recent checkins
                    recentCheckins.unshift({
                        name_ar: data.guestDetails.name_ar,
                        checkin_time: data.guestDetails.checkin_time || new Date().toISOString(),
                        notes: data.guestDetails.notes,
                        offline: isOffline
                    });
                    recentCheckins = recentCheckins.slice(0, 10);
                    updateRecentList();
                    
                    if (!isOffline) {
                        loadStats(); // Refresh stats only if online
                    } else {
                        updateOfflineStats();
                    }
                }
                
                if (data.type === 'pending' || data.showConfirmOption) {
                    currentGuestForConfirm = data.guestDetails;
                    confirmCheckinButton.style.display = 'inline-flex';
                    confirmCheckinButton.dataset.guestId = data.guestDetails.guest_id;
                } else {
                    confirmCheckinButton.style.display = 'none';
                    currentGuestForConfirm = null;
                }
                
                searchInput.value = ''; // Clear search on success
            } else {
                displayResponse(data.message, data.type || 'error', data.guestDetails, isOffline);
                playSound('error');
                confirmCheckinButton.style.display = 'none';
                
                // Show confirm option for pending guests
                if (data.type === 'pending' || data.showConfirmOption) {
                    currentGuestForConfirm = data.guestDetails;
                    confirmCheckinButton.style.display = 'inline-flex';
                    confirmCheckinButton.dataset.guestId = data.guestDetails.guest_id;
                }
            }
        }

        function displayResponse(message, status, details = null, isOffline = false) {
            responseArea.innerHTML = `<p class="font-semibold text-lg mb-2">
                ${isOffline ? '<i class="fas fa-wifi-slash text-yellow-600"></i> ' : ''}
                ${message}
                ${isOffline ? ' <span class="text-sm text-yellow-600">(محفوظ محلياً)</span>' : ''}
            </p>`;
            responseArea.className = `response-area ${status}`;
            
            if (details) {
                const detailsHtml = `
                    <div class="detail-item">
                        <span><i class="fas fa-user"></i> ${texts['name']}:</span>
                        <span class="font-medium">${details.name_ar || ''}</span>
                    </div>
                    <div class="detail-item">
                        <span><i class="fas fa-users"></i> ${texts['guests_count']}:</span>
                        <span class="font-medium">${details.guests_count || '1'}</span>
                    </div>
                    ${details.table_number ? `
                    <div class="detail-item">
                        <span><i class="fas fa-chair"></i> ${texts['table_number']}:</span>
                        <span class="font-medium">${details.table_number}</span>
                    </div>` : ''}
                    <div class="detail-item">
                        <span><i class="fas fa-clipboard-list"></i> ${texts['guest_status']}:</span>
                        <span class="status-badge status-${details.status}">${getStatusText(details.status)}</span>
                    </div>
                    <div class="detail-item">
                        <span><i class="fas fa-door-open"></i> ${texts['checkin_status']}:</span>
                        <span class="status-badge checkin-${details.checkin_status === 'checked_in' ? 'checked_in' : 'not'}">${details.checkin_status === 'checked_in' ? texts['checked_in'] : texts['not_checked_in']}</span>
                    </div>
                `;
                responseArea.innerHTML += `<div class="mt-4 border-t pt-4">${detailsHtml}</div>`;
                
                // Add notes section
                if (details.notes || status === 'success' || status === 'warning') {
                    const notesHtml = `
                        <div class="notes-section">
                            <h4 class="font-semibold mb-2">
                                <i class="fas fa-sticky-note"></i> ${texts['notes']}
                            </h4>
                            ${details.notes ? `<div class="notes-list mb-2">${details.notes.replace(/\n/g, '<br>')}</div>` : ''}
                            ${!isOffline ? `<div class="flex gap-2">
                                <input type="text" id="note-input" class="note-input flex-grow" placeholder="${texts['enter_note']}">
                                <button onclick="addNote('${details.guest_id}')" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> ${texts['add_note']}
                                </button>
                            </div>` : '<p class="text-sm text-gray-500">الملاحظات غير متاحة في الوضع غير المتصل</p>'}
                        </div>
                    `;
                    responseArea.innerHTML += notesHtml;
                }
            }
        }

        function updateOfflineStats() {
            const todayCheckins = pendingSyncData.filter(item => {
                const itemDate = new Date(item.timestamp);
                const today = new Date();
                return itemDate.toDateString() === today.toDateString();
            }).length;
            
            const currentTodayCheckins = parseInt(document.getElementById('today-checkins').textContent) || 0;
            animateNumber('today-checkins', currentTodayCheckins + todayCheckins);
        }

        function updateRecentList() {
            recentList.innerHTML = '';
            if (recentCheckins.length === 0) {
                recentList.innerHTML = '<div class="text-gray-500 text-center py-2">لا توجد تسجيلات حديثة</div>';
                return;
            }
            recentCheckins.forEach(item => {
                const div = document.createElement('div');
                div.className = 'recent-item';
                const time = new Date(item.checkin_time).toLocaleTimeString('ar-EG', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                div.innerHTML = `
                    <div>
                        <span class="font-medium">${item.name_ar}</span>
                        ${item.offline ? '<span class="text-xs text-yellow-600 ml-2"><i class="fas fa-wifi-slash"></i> غير متزامن</span>' : ''}
                        ${item.notes ? `<div class="text-xs text-gray-500">${item.notes.split('\n').pop()}</div>` : ''}
                    </div>
                    <span class="text-gray-500">${time}</span>
                `;
                recentList.appendChild(div);
            });
            localStorage.setItem('recent_checkins', JSON.stringify(recentCheckins));
        }
        
        function clearResponseArea() {
             responseArea.innerHTML = `<p class="text-gray-500">${texts['results_appear_here']}</p>`;
             responseArea.className = `response-area`;
             confirmCheckinButton.style.display = 'none';
             currentGuestForConfirm = null;
        }

        function getStatusText(status) {
            switch(status) {
                case 'confirmed': return texts['confirmed'];
                case 'canceled': return texts['canceled'];
                case 'pending': return texts['pending'];
                default: return status;
            }
        }

        // --- Notes Functions ---
        async function addNote(guestId) {
            const noteInput = document.getElementById('note-input');
            const note = noteInput.value.trim();
            
            if (!note) return;
            
            try {
                const response = await fetch(addNoteApiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        guest_id: guestId,
                        note: note
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    noteInput.value = '';
                    // Refresh the guest details to show updated notes
                    performCheckIn(guestId);
                }
            } catch (error) {
                console.error('Error adding note:', error);
            }
        }

        // Make addNote available globally
        window.addNote = addNote;

        // --- QR Scanner Functions ---
        function startScanner() {
            stopScanner(); 
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: "environment",
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                } 
            }).then(stream => {
                videoStream = stream;
                video.srcObject = stream;
                video.play();
                animationFrameId = requestAnimationFrame(tick);
                startScanButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المسح...';
            }).catch(err => { 
                console.error("Camera Error:", err); 
                alert(texts['camera_error']); 
            });
        }

        function stopScanner() {
            if (videoStream) { 
                videoStream.getTracks().forEach(track => track.stop()); 
                videoStream = null;
            }
            if (animationFrameId) { 
                cancelAnimationFrame(animationFrameId); 
                animationFrameId = null; 
            }
            startScanButton.innerHTML = `<i class="fas fa-qrcode"></i> ${texts['start_scanning']}`;
        }

        function tick() {
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.height = video.videoHeight;
                canvas.width = video.videoWidth;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height, { 
                    inversionAttempts: "dontInvert" 
                });
                if (code) {
                    stopScanner();
                    searchInput.value = code.data;
                    performCheckIn(code.data);
                }
            }
            if(videoStream) {
                animationFrameId = requestAnimationFrame(tick);
            }
        }

        // --- Utility Functions ---
        function showLoading() {
            loadingOverlay.style.display = 'flex';
        }

        function hideLoading() {
            loadingOverlay.style.display = 'none';
        }

        function exportData() {
            const data = {
                recent_checkins: recentCheckins,
                offline_data: offlineData,
                timestamp: new Date().toISOString(),
                event_id: <?= $event_id ?>
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `checkin_report_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        function printList() {
            const printContent = `
                <html>
                <head>
                    <title>قائمة الحضور - ${new Date().toLocaleDateString('ar-EG')}</title>
                    <style>
                        body { font-family: 'Cairo', Arial, sans-serif; direction: rtl; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .checkin-item { padding: 10px; border-bottom: 1px solid #ddd; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>قائمة الحضور</h1>
                        <p>${new Date().toLocaleDateString('ar-EG')} - <?= htmlspecialchars($event_name) ?></p>
                    </div>
                    ${recentCheckins.map(item => `
                        <div class="checkin-item">
                            <strong>${item.name_ar}</strong> - 
                            ${new Date(item.checkin_time).toLocaleString('ar-EG')}
                            ${item.notes ? `<br><small>${item.notes.split('\n').pop()}</small>` : ''}
                        </div>
                    `).join('')}
                </body>
                </html>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }

        function backupData() {
            const backup = {
                recent_checkins: recentCheckins,
                offline_data: offlineData,
                settings: {
                    sound_enabled: soundEnabled,
                    sound_volume: soundVolume,
                    manual_mode: manualMode
                },
                timestamp: new Date().toISOString()
            };
            
            localStorage.setItem('checkin_backup', JSON.stringify(backup));
            alert('تم حفظ النسخة الاحتياطية بنجاح!');
        }

        // --- Event Listeners ---
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const searchTerm = searchInput.value.trim();
                if (searchTerm.length >= 1) { // البحث من حرف واحد
                    fetchSuggestions(searchTerm);
                } else {
                    suggestionsBox.classList.add('hidden');
                }
            }, 200); // تقليل زمن التأخير
        });

        // Check-in buttons - only work if not in viewer mode
        if (!isViewerMode && checkinButton) {
            checkinButton.addEventListener('click', () => {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    performCheckIn(searchTerm);
                }
            });
        }

        if (!isViewerMode && confirmCheckinButton) {
            confirmCheckinButton.addEventListener('click', () => {
                if (currentGuestForConfirm) {
                    performCheckIn(currentGuestForConfirm.guest_id, true);
                }
            });
        }
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !isViewerMode) {
                checkinButton?.click();
            }
        });

        // Auto check-in on suggestion click - only if not viewer mode
        suggestionsBox.addEventListener('click', (e) => {
            const suggestionItem = e.target.closest('.suggestion-item');
            if (suggestionItem) {
                const guestId = suggestionItem.dataset.guestId;
                const status = suggestionItem.dataset.status;
                
                searchInput.value = guestId;
                suggestionsBox.classList.add('hidden');
                
                if (!isViewerMode) {
                    // Auto perform check-in for confirmed guests, show options for pending
                    if (status === 'confirmed') {
                        performCheckIn(guestId);
                    } else if (status === 'pending') {
                        performCheckIn(guestId); // This will show the confirm option
                    } else {
                        // Just show details for canceled guests
                        performCheckIn(guestId);
                    }
                } else {
                    // For viewer mode, just show details
                    showGuestDetails(guestId);
                }
            }
        });

        // PWA Installation
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            installButton.style.display = 'inline-flex';
        });

        installButton.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('تم تثبيت التطبيق');
                }
                deferredPrompt = null;
                installButton.style.display = 'none';
            }
        });

        // Offline Download
        downloadOfflineButton.addEventListener('click', async () => {
            downloadOfflineButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري التحميل...';
            downloadOfflineButton.disabled = true;
            
            try {
                await loadAndCacheGuestsData();
                // Cache the current page
                const pageContent = document.documentElement.outerHTML;
                localStorage.setItem('cached_checkin_page', pageContent);
                localStorage.setItem('cached_checkin_timestamp', new Date().toISOString());
                
                // Register service worker for offline functionality
                if ('serviceWorker' in navigator) {
                    try {
                        await navigator.serviceWorker.register(createServiceWorkerScript());
                        console.log('Service Worker registered successfully');
                    } catch (error) {
                        console.log('Service Worker registration failed:', error);
                    }
                }
                
                downloadOfflineButton.innerHTML = '<i class="fas fa-check"></i> ' + texts['offline_ready'];
                downloadOfflineButton.className = 'btn btn-success';
                
                setTimeout(() => {
                    downloadOfflineButton.innerHTML = '<i class="fas fa-cloud-download-alt"></i> ' + texts['download_offline'];
                    downloadOfflineButton.className = 'btn btn-success';
                    downloadOfflineButton.disabled = false;
                }, 3000);
                
            } catch (error) {
                downloadOfflineButton.innerHTML = '<i class="fas fa-exclamation-triangle"></i> فشل التحميل';
                downloadOfflineButton.className = 'btn btn-danger';
                downloadOfflineButton.disabled = false;
            }
        });

        // Service Worker creation for offline functionality
        function createServiceWorkerScript() {
            const swScript = `
                const CACHE_NAME = 'checkin-offline-v1';
                const urlsToCache = [
                    './checkin.php?event_id=<?= $event_id ?>',
                    'https://cdn.tailwindcss.com',
                    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                    'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js'
                ];

                self.addEventListener('install', (event) => {
                    event.waitUntil(
                        caches.open(CACHE_NAME)
                            .then((cache) => cache.addAll(urlsToCache))
                    );
                });

                self.addEventListener('fetch', (event) => {
                    event.respondWith(
                        caches.match(event.request)
                            .then((response) => {
                                if (response) {
                                    return response;
                                }
                                return fetch(event.request);
                            })
                    );
                });
            `;
            
            const blob = new Blob([swScript], { type: 'application/javascript' });
            return URL.createObjectURL(blob);
        }

        // Show guest details for viewer mode
        function showGuestDetails(guestId) {
            // Search in cached data
            const guest = cachedGuestsData.find(g => g.guest_id === guestId);
            if (guest) {
                displayResponse(`تفاصيل الضيف: ${guest.name_ar}`, 'success', guest, false);
            } else {
                displayResponse(texts['guest_not_found'], 'error');
            }
        }
        
        // Hide suggestions if user clicks outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                suggestionsBox.classList.add('hidden');
            }
        });

        // Initialize everything
        loadAndCacheGuestsData(); // Load guests data immediately
        updateSoundToggle();
        updateVolumeDisplay();
        updateRecentList();
        loadStats();
        loadRecentFromServer();
        
        // Auto-refresh and sync
        setInterval(loadStats, 30000);
        setInterval(loadRecentFromServer, 60000);
        setInterval(() => {
            if (navigator.onLine && pendingSyncData.length > 0) {
                syncPendingData();
            }
        }, 10000); // Check for sync every 10 seconds
        
        // Network status monitoring
        window.addEventListener('online', async () => {
            console.log('اتصال الإنترنت متاح مرة أخرى');
            updateOfflineIndicator(false);
            await loadAndCacheGuestsData(); // Refresh cached data
            if (pendingSyncData.length > 0) {
                await syncPendingData();
            }
            loadStats();
        });

        window.addEventListener('offline', () => {
            console.log('انقطع الاتصال بالإنترنت - التبديل للوضع غير المتصل');
            updateOfflineIndicator(true);
        });

        // Toggle buttons
        soundToggle.addEventListener('click', () => {
            soundEnabled = !soundEnabled;
            localStorage.setItem('checkin_sound', soundEnabled);
            updateSoundToggle();
        });

        manualToggle.addEventListener('click', () => {
            manualMode = !manualMode;
            manualToggle.classList.toggle('active', manualMode);
            manualToggle.innerHTML = `<i class="fas fa-keyboard"></i> ${manualMode ? 'وضع يدوي' : texts['manual_entry']}`;
            if (manualMode) {
                video.style.display = 'none';
                stopScanner();
            } else {
                video.style.display = 'block';
                suggestionsBox.classList.add('hidden');
            }
        });

        volumeSlider.addEventListener('input', (e) => {
            soundVolume = parseFloat(e.target.value);
            localStorage.setItem('checkin_volume', soundVolume);
            updateVolumeDisplay();
        });

        clearRecentBtn.addEventListener('click', () => {
            if (confirm('هل أنت متأكد من مسح قائمة التسجيلات الأخيرة؟')) {
                recentCheckins = [];
                updateRecentList();
            }
        });

        advancedSearchToggle.addEventListener('click', () => {
            advancedSearchPanel.classList.toggle('hidden');
            advancedSearchToggle.classList.toggle('active');
        });

        // Advanced search filters
        tableSearch.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (tableSearch.value.trim()) {
                    fetchSuggestions(tableSearch.value.trim());
                }
            }, 300);
        });

        statusFilter.addEventListener('change', () => {
            // This would require additional API endpoint to filter by status
            console.log('Status filter:', statusFilter.value);
        });

        // Export buttons
        document.getElementById('export-button').addEventListener('click', exportData);
        document.getElementById('print-button').addEventListener('click', printList);
        document.getElementById('backup-button').addEventListener('click', backupData);
        
        startScanButton.addEventListener('click', startScanner);
        stopScanButton.addEventListener('click', stopScanner);
        
        // Auto-start scanner if not in manual mode
        if (!manualMode) {
            setTimeout(startScanner, 1000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        soundToggle.click();
                        break;
                    case 'm':
                        e.preventDefault();
                        manualToggle.click();
                        break;
                    case 'f':
                        e.preventDefault();
                        searchInput.focus();
                        break;
                    case 'e':
                        e.preventDefault();
                        exportData();
                        break;
                    case 'p':
                        e.preventDefault();
                        printList();
                        break;
                }
            }
        });

        // Network status monitoring
        window.addEventListener('online', () => {
            offlineMode = false;
            document.querySelector('.offline-indicator')?.remove();
            loadStats();
        });

        window.addEventListener('offline', () => {
            offlineMode = true;
            showOfflineStats();
        });

        // Auto-save state
        window.addEventListener('beforeunload', () => {
            const state = {
                recent_checkins: recentCheckins,
                sound_enabled: soundEnabled,
                sound_volume: soundVolume,
                manual_mode: manualMode
            };
            localStorage.setItem('checkin_state', JSON.stringify(state));
        });

        // Restore state on load
        const savedState = JSON.parse(localStorage.getItem('checkin_state') || '{}');
        if (savedState.manual_mode) {
            manualToggle.click();
        }
    });
    </script>
</body>
</html>