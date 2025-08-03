<?php
// checkin.php - Enhanced with languages and improved functionality
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
        'scan_qr_or_search' => 'امسح رمز QR أو ابحث بالاسم ثم اضغط تسجيل',
        'start_scanning' => 'بدء المسح',
        'stop_scanning' => 'إيقاف المسح',
        'search_placeholder' => 'ابحث بالاسم أو الهاتف...',
        'checkin_button' => 'تسجيل دخول',
        'results_appear_here' => 'النتائج ستظهر هنا...',
        'checking' => 'جاري التحقق...',
        'camera_error' => 'لا يمكن الوصول للكاميرا.',
        'connection_error' => 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
        'guest_not_found' => 'الضيف غير موجود في قائمة هذا الحفل.',
        'guest_already_checked_in' => 'تم تسجيل دخول {name} مسبقاً.',
        'guest_checked_in_success' => 'تم تسجيل دخول {name} بنجاح.',
        'guest_declined' => 'الضيف {name} قام بإلغاء الحضور.',
        'guest_not_confirmed' => 'الضيف {name} لم يؤكد حضوره بعد.',
        'multiple_guests_found' => 'تم العثور على عدة ضيوف بنفس البيانات. الرجاء استخدام المعرف الفريد (QR) أو رقم الهاتف للتمييز بينهم.',
        'name' => 'الاسم',
        'guests_count' => 'عدد الضيوف',
        'table_number' => 'رقم الطاولة',
        'assigned_location' => 'الموقع المخصص',
        'checkin_status' => 'حالة تسجيل الدخول',
        'checked_in' => 'تم تسجيل الدخول',
        'not_checked_in' => 'لم يتم',
        'quick_stats' => 'إحصائيات سريعة',
        'today_checkins' => 'تسجيلات اليوم',
        'total_confirmed' => 'إجمالي المؤكدين',
        'remaining_guests' => 'الضيوف المتبقين',
        'sound_enabled' => 'الصوت مفعل',
        'sound_disabled' => 'الصوت معطل',
        'recent_checkins' => 'التسجيلات الأخيرة',
        'clear_recent' => 'مسح القائمة',
        'manual_entry' => 'إدخال يدوي'
    ],
    'en' => [
        'checkin_system' => 'Guest Check-in System',
        'event_title' => 'Event',
        'back_to_events' => 'Back to Events',
        'logout' => 'Logout',
        'scan_qr_or_search' => 'Scan QR code or search by name then click check-in',
        'start_scanning' => 'Start Scanning',
        'stop_scanning' => 'Stop Scanning',
        'search_placeholder' => 'Search by name or phone...',
        'checkin_button' => 'Check In',
        'results_appear_here' => 'Results will appear here...',
        'checking' => 'Checking...',
        'camera_error' => 'Cannot access camera.',
        'connection_error' => 'Connection error occurred. Please try again.',
        'guest_not_found' => 'Guest not found in this event list.',
        'guest_already_checked_in' => '{name} was already checked in.',
        'guest_checked_in_success' => '{name} checked in successfully.',
        'guest_declined' => 'Guest {name} declined attendance.',
        'guest_not_confirmed' => 'Guest {name} has not confirmed attendance yet.',
        'multiple_guests_found' => 'Multiple guests found with same data. Please use unique ID (QR) or phone number to distinguish.',
        'name' => 'Name',
        'guests_count' => 'Guests Count',
        'table_number' => 'Table Number',
        'assigned_location' => 'Assigned Location',
        'checkin_status' => 'Check-in Status',
        'checked_in' => 'Checked In',
        'not_checked_in' => 'Not Checked In',
        'quick_stats' => 'Quick Stats',
        'today_checkins' => 'Today\'s Check-ins',
        'total_confirmed' => 'Total Confirmed',
        'remaining_guests' => 'Remaining Guests',
        'sound_enabled' => 'Sound Enabled',
        'sound_disabled' => 'Sound Disabled',
        'recent_checkins' => 'Recent Check-ins',
        'clear_recent' => 'Clear List',
        'manual_entry' => 'Manual Entry'
    ]
];

$t = $texts[$lang];

// --- Security & Permission Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'checkin_user'])) {
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
        
        // Remaining guests (confirmed but not checked in)
        $stmt_remaining = $mysqli->prepare("SELECT COUNT(*) as remaining_count FROM guests WHERE event_id = ? AND status = 'confirmed' AND checkin_status != 'checked_in'");
        $stmt_remaining->bind_param("i", $api_event_id);
        $stmt_remaining->execute();
        $remaining_guests = $stmt_remaining->get_result()->fetch_assoc()['remaining_count'];
        $stmt_remaining->close();
        
        echo json_encode([
            'today_checkins' => $today_checkins,
            'total_confirmed' => $total_confirmed,
            'remaining_guests' => $remaining_guests
        ]);
        exit;
    }

    // --- Recent Check-ins API ---
    if (isset($_GET['recent'])) {
        $stmt = $mysqli->prepare("SELECT name_ar, checkin_time FROM guests WHERE event_id = ? AND checkin_status = 'checked_in' ORDER BY checkin_time DESC LIMIT 5");
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
        $stmt = $mysqli->prepare("SELECT guest_id, name_ar, phone_number, status, checkin_status FROM guests WHERE (name_ar LIKE ? OR phone_number LIKE ?) AND event_id = ? LIMIT 10");
        $stmt->bind_param("ssi", $searchTermLike, $searchTermLike, $api_event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $guests = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode($guests);
        exit;
    }

    // --- Check-in Logic ---
    $response = ['success' => false, 'message' => 'حدث خطأ غير متوقع.'];
    $searchTerm = trim($input['searchTerm'] ?? '');

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
        $response['message'] = 'الضيف غير موجود في قائمة هذا الحفل.';
    } elseif ($result->num_rows === 1) {
        $guest = $result->fetch_assoc();
        if ($guest['status'] === 'confirmed') {
            if ($guest['checkin_status'] === 'checked_in') {
                $response['success'] = true;
                $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), 'تم تسجيل دخول {name} مسبقاً.');
                $response['type'] = 'warning';
                $response['guestDetails'] = $guest;
            } else {
                $update_stmt = $mysqli->prepare("UPDATE guests SET checkin_status = 'checked_in', checkin_time = NOW() WHERE guest_id = ?");
                $update_stmt->bind_param("s", $guest['guest_id']);
                if ($update_stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), 'تم تسجيل دخول {name} بنجاح.');
                    $response['type'] = 'success';
                    $guest['checkin_status'] = 'checked_in';
                    $response['guestDetails'] = $guest;
                }
                $update_stmt->close();
            }
        } elseif ($guest['status'] === 'canceled') {
            $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), 'الضيف {name} قام بإلغاء الحضور.');
            $response['type'] = 'error';
            $response['guestDetails'] = $guest;
        } else {
            $response['message'] = str_replace('{name}', htmlspecialchars($guest['name_ar']), 'الضيف {name} لم يؤكد حضوره بعد.');
            $response['type'] = 'warning';
            $response['guestDetails'] = $guest;
        }
    } else {
        $response['message'] = 'تم العثور على عدة ضيوف بنفس البيانات. الرجاء استخدام المعرف الفريد (QR) أو رقم الهاتف للتمييز بينهم.';
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background-color: #f0f2f5; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 20px; 
        }
        .header { 
            width: 100%; 
            max-width: 600px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1rem; 
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header-buttons { display: flex; gap: 12px; align-items: center; }
        .container { 
            max-width: 600px; 
            width: 100%; 
            background-color: #ffffff; 
            border-radius: 20px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            padding: 30px; 
            text-align: center; 
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-top: 4px;
        }
        .response-area { 
            margin-top: 20px; 
            padding: 20px; 
            border-radius: 12px; 
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>; 
            border: 2px solid #eee; 
            min-height: 120px; 
            background-color: #f9fafb;
            transition: all 0.3s ease;
        }
        .response-area.success { 
            border-color: #10b981; 
            background: linear-gradient(135deg, #d1fae5, #a7f3d0); 
            color: #065f46; 
        }
        .response-area.error { 
            border-color: #ef4444; 
            background: linear-gradient(135deg, #fee2e2, #fca5a5); 
            color: #991b1b; 
        }
        .response-area.warning { 
            border-color: #f59e0b; 
            background: linear-gradient(135deg, #fef3c7, #fde68a); 
            color: #92400e; 
        }
        .detail-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 8px 0; 
            border-bottom: 1px dashed rgba(0,0,0,0.1); 
        }
        .detail-item:last-child { border-bottom: none; }
        #video { 
            width: 100%; 
            max-width: 400px; 
            height: 300px; 
            border-radius: 10px; 
            margin: 20px auto; 
            display: block; 
            background-color: #000; 
            object-fit: cover;
        }
        .search-container { position: relative; margin: 20px 0; }
        #suggestions-box {
            position: absolute; 
            top: 100%; 
            left: 0; 
            right: 0;
            background-color: white; 
            border: 1px solid #d1d5db;
            border-radius: 0 0 0.5rem 0.5rem; 
            max-height: 200px;
            overflow-y: auto; 
            z-index: 10;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .suggestion-item {
            padding: 12px; 
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>; 
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background-color: #f3f4f6; }
        .suggestion-item.confirmed { border-left: 4px solid #10b981; }
        .suggestion-item.canceled { border-left: 4px solid #ef4444; }
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
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-toggle { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-toggle.active { background: #3b82f6; color: white; }
        
        .recent-checkins {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            max-height: 150px;
            overflow-y: auto;
        }
        .recent-item {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }
        .recent-item:last-child { border-bottom: none; }
        
        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        @media (max-width: 640px) {
            .stats-bar { grid-template-columns: 1fr; gap: 10px; }
            .control-buttons { flex-direction: column; }
            .header { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="text-xl font-bold text-gray-700"><?= $t['event_title'] ?>: <?= htmlspecialchars($event_name) ?></h1>
        <div class="header-buttons">
            <form method="POST" style="display: inline;">
                <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg border border-gray-300 transition-colors">
                    <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                </button>
            </form>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="events.php" class="btn btn-secondary"><?= $t['back_to_events'] ?></a>
            <?php else: ?>
                <a href="logout.php" class="btn btn-secondary"><?= $t['logout'] ?></a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <h2 class="text-2xl font-bold text-gray-800 mb-4"><?= $t['checkin_system'] ?></h2>
        
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
                <span class="stat-number" id="remaining-guests">0</span>
                <div class="stat-label"><?= $t['remaining_guests'] ?></div>
            </div>
        </div>
        
        <p class="text-gray-600 mb-6"><?= $t['scan_qr_or_search'] ?></p>
        
        <video id="video" playsinline></video>
        <canvas id="canvas" class="hidden"></canvas>
        
        <div class="control-buttons">
            <button id="start-scan-button" class="btn btn-primary"><?= $t['start_scanning'] ?></button>
            <button id="stop-scan-button" class="btn btn-secondary"><?= $t['stop_scanning'] ?></button>
            <button id="sound-toggle" class="btn btn-toggle"><?= $t['sound_enabled'] ?></button>
            <button id="manual-toggle" class="btn btn-toggle"><?= $t['manual_entry'] ?></button>
        </div>
        
        <div class="search-container">
            <div class="flex gap-2">
                <input type="text" 
                       id="search-input" 
                       class="search-input flex-grow" 
                       placeholder="<?= $t['search_placeholder'] ?>" 
                       autocomplete="off">
                <button id="check-in-button" class="btn btn-success"><?= $t['checkin_button'] ?></button>
            </div>
            <div id="suggestions-box" class="hidden"></div>
        </div>
        
        <div id="response-area" class="response-area">
            <p class="text-gray-500"><?= $t['results_appear_here'] ?></p>
        </div>
        
        <!-- Recent Check-ins -->
        <div class="recent-checkins">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold text-gray-700"><?= $t['recent_checkins'] ?></h3>
                <button id="clear-recent" class="text-sm text-blue-600 hover:underline"><?= $t['clear_recent'] ?></button>
            </div>
            <div id="recent-list"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const suggestApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true&suggest=true';
        const checkinApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true';
        const statsApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true&stats=true';
        const recentApiUrl = 'checkin.php?event_id=<?= $event_id ?>&api=true&recent=true';
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        
        const searchInput = document.getElementById('search-input');
        const checkinButton = document.getElementById('check-in-button');
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
        
        let videoStream = null;
        let animationFrameId = null;
        let debounceTimer;
        let soundEnabled = localStorage.getItem('checkin_sound') !== 'false';
        let manualMode = false;
        let recentCheckins = JSON.parse(localStorage.getItem('recent_checkins') || '[]');
        
        // Initialize UI
        updateSoundToggle();
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
                document.getElementById('today-checkins').textContent = stats.today_checkins;
                document.getElementById('total-confirmed').textContent = stats.total_confirmed;
                document.getElementById('remaining-guests').textContent = stats.remaining_guests;
            } catch (error) {
                console.error('Error loading stats:', error);
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
                recentCheckins = recentCheckins.slice(0, 5); // Keep only latest 5
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
                    <span class="font-medium">${item.name_ar}</span>
                    <span class="text-gray-500">${time}</span>
                `;
                recentList.appendChild(div);
            });
            localStorage.setItem('recent_checkins', JSON.stringify(recentCheckins));
        }

        // --- Sound Functions ---
        function playSuccessSound() {
            if (!soundEnabled) return;
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmggBgAAWQoCOQAABSKj2eqvYh0GZaL16Dqp2AUhW9n7ZLk7LgQ=');
            audio.play().catch(() => {});
        }

        function playErrorSound() {
            if (!soundEnabled) return;
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBziR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmggBgAAWQoCOQAABSKj2eqvYh0GZaL16Dqp2AUhW9n7ZLk7LgQ=');
            audio.volume = 0.5;
            audio.play().catch(() => {});
        }

        function updateSoundToggle() {
            soundToggle.textContent = soundEnabled ? texts['sound_enabled'] : texts['sound_disabled'];
            soundToggle.classList.toggle('active', soundEnabled);
        }

        // --- Suggestion Functions ---
        async function fetchSuggestions(searchTerm) {
            if (searchTerm.length < 2) {
                suggestionsBox.innerHTML = '';
                suggestionsBox.classList.add('hidden');
                return;
            }
            try {
                const response = await fetch(suggestApiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ searchTerm: searchTerm })
                });
                const suggestions = await response.json();
                suggestionsBox.innerHTML = '';
                if (suggestions.length > 0) {
                    suggestions.forEach(guest => {
                        const item = document.createElement('div');
                        item.className = `suggestion-item ${guest.status}`;
                        
                        let statusIcon = '';
                        if (guest.checkin_status === 'checked_in') statusIcon = '✅';
                        else if (guest.status === 'confirmed') statusIcon = '🟢';
                        else if (guest.status === 'canceled') statusIcon = '🔴';
                        else statusIcon = '🟡';
                        
                        item.innerHTML = `
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="font-medium">${guest.name_ar}</div>
                                    <div class="text-sm text-gray-500">${guest.phone_number || 'لا يوجد هاتف'}</div>
                                </div>
                                <div class="text-lg">${statusIcon}</div>
                            </div>
                        `;
                        item.dataset.guestId = guest.guest_id;
                        
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
            } catch (error) {
                console.error('Suggestion fetch error:', error);
            }
        }

        // --- Check-in Functions ---
        async function performCheckIn(searchTerm) {
            responseArea.innerHTML = `<p class="text-gray-500">${texts['checking']}</p>`;
            responseArea.className = `response-area`;
            
            try {
                const response = await fetch(checkinApiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ searchTerm: searchTerm })
                });
                const data = await response.json();
                
                let displayStatus = data.type || 'error';
                displayResponse(data.message, displayStatus, data.guestDetails);
                
                if (data.success) {
                    if (data.type === 'success') {
                        playSuccessSound();
                        // Add to recent checkins
                        recentCheckins.unshift({
                            name_ar: data.guestDetails.name_ar,
                            checkin_time: new Date().toISOString()
                        });
                        recentCheckins = recentCheckins.slice(0, 5);
                        updateRecentList();
                        loadStats(); // Refresh stats
                    }
                    searchInput.value = ''; // Clear search on success
                } else {
                    playErrorSound();
                }

            } catch (error) {
                console.error('Check-in error:', error);
                displayResponse(texts['connection_error'], 'error');
                playErrorSound();
            }
        }

        function displayResponse(message, status, details = null) {
            responseArea.innerHTML = `<p class="font-semibold text-lg mb-2">${message}</p>`;
            responseArea.className = `response-area ${status}`;
            
            if (details) {
                const detailsHtml = `
                    <div class="detail-item">
                        <span>${texts['name']}:</span>
                        <span>${details.name_ar || ''}</span>
                    </div>
                    <div class="detail-item">
                        <span>${texts['guests_count']}:</span>
                        <span>${details.guests_count || '1'}</span>
                    </div>
                    <div class="detail-item">
                        <span>${texts['table_number']}:</span>
                        <span>${details.table_number || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <span>${texts['assigned_location']}:</span>
                        <span>${details.assigned_location || 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <span>${texts['checkin_status']}:</span>
                        <span>${details.checkin_status === 'checked_in' ? texts['checked_in'] : texts['not_checked_in']}</span>
                    </div>
                `;
                responseArea.innerHTML += `<div class="mt-4 border-t pt-4">${detailsHtml}</div>`;
            }
        }
        
        function clearResponseArea() {
             responseArea.innerHTML = `<p class="text-gray-500">${texts['results_appear_here']}</p>`;
             responseArea.className = `response-area`;
        }

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

        // --- Event Listeners ---
        searchInput.addEventListener('input', () => {
            if (!manualMode) return;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchSuggestions(searchInput.value.trim());
            }, 300);
        });
        
        checkinButton.addEventListener('click', () => {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                performCheckIn(searchTerm);
            }
        });
        
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                checkinButton.click();
            }
        });
        
        // Hide suggestions if user clicks outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                suggestionsBox.classList.add('hidden');
            }
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
            if (manualMode) {
                video.style.display = 'none';
                stopScanner();
            } else {
                video.style.display = 'block';
                suggestionsBox.classList.add('hidden');
            }
        });

        clearRecentBtn.addEventListener('click', () => {
            recentCheckins = [];
            updateRecentList();
        });
        
        startScanButton.addEventListener('click', startScanner);
        stopScanButton.addEventListener('click', stopScanner);
        
        // Auto-start scanner if not in manual mode
        if (!manualMode) {
            setTimeout(startScanner, 1000);
        }
    });
    </script>
</body>
</html>