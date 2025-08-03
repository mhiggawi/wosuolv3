<?php
// checkin_offline.php - Enhanced system with offline capabilities
session_start();
require_once 'db_config.php';

// Language System (unchanged)
$lang = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'ar';
if (isset($_POST['switch_language'])) {
    $lang = $_POST['switch_language'] === 'en' ? 'en' : 'ar';
    $_SESSION['language'] = $lang;
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
}

$texts = [
    'ar' => [
        'checkin_system' => 'نظام تسجيل دخول الضيوف',
        'event_title' => 'حفل',
        'back_to_events' => 'عودة للحفلات',
        'logout' => 'تسجيل الخروج',
        'offline_mode' => 'وضع عدم الاتصال',
        'online_mode' => 'متصل',
        'sync_pending' => 'في انتظار المزامنة',
        'sync_now' => 'مزامنة الآن',
        'sync_success' => 'تمت المزامنة بنجاح',
        'sync_failed' => 'فشلت المزامنة',
        'cache_guests' => 'تحميل بيانات الضيوف',
        'guests_cached' => 'تم تحميل {count} ضيف للعمل بدون إنترنت',
        'connection_lost' => 'تم فقدان الاتصال - التبديل للوضع المحلي',
        'connection_restored' => 'تم استعادة الاتصال - المزامنة التلقائية',
        'offline_checkins' => 'تسجيلات محلية',
        'pending_sync' => 'في انتظار الإرسال',
        'local_storage_full' => 'مساحة التخزين المحلي ممتلئة',
        'clear_cache' => 'مسح البيانات المحلية',
        // ... rest of texts remain the same
        'scan_qr_or_search' => 'امسح رمز QR أو ابحث بالاسم ثم اضغط تسجيل',
        'start_scanning' => 'بدء المسح',
        'stop_scanning' => 'إيقاف المسح',
        'search_placeholder' => 'ابحث بالاسم أو الهاتف...',
        'checkin_button' => 'تسجيل دخول',
        'confirm_and_checkin' => 'تأكيد الحضور والتسجيل',
        'force_checkin' => 'تسجيل إجباري',
        'results_appear_here' => 'النتائج ستظهر هنا...',
        'checking' => 'جاري التحقق...',
        'updating_status' => 'جاري تحديث الحالة...',
        'camera_error' => 'لا يمكن الوصول للكاميرا.',
        'connection_error' => 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
        'guest_not_found' => 'الضيف غير موجود في قائمة هذا الحفل.',
        'guest_already_checked_in' => 'تم تسجيل دخول {name} مسبقاً.',
        'guest_checked_in_success' => 'تم تسجيل دخول {name} بنجاح.',
        'sound_enabled' => 'الصوت مفعل',
        'sound_disabled' => 'الصوت معطل',
        'manual_entry' => 'إدخال يدوي'
    ],
    'en' => [
        'checkin_system' => 'Guest Check-in System',
        'event_title' => 'Event',
        'back_to_events' => 'Back to Events',
        'logout' => 'Logout',
        'offline_mode' => 'Offline Mode',
        'online_mode' => 'Online',
        'sync_pending' => 'Sync Pending',
        'sync_now' => 'Sync Now',
        'sync_success' => 'Sync Successful',
        'sync_failed' => 'Sync Failed',
        'cache_guests' => 'Cache Guest Data',
        'guests_cached' => '{count} guests cached for offline use',
        'connection_lost' => 'Connection lost - Switching to offline mode',
        'connection_restored' => 'Connection restored - Auto syncing',
        'offline_checkins' => 'Offline Check-ins',
        'pending_sync' => 'Pending Sync',
        'local_storage_full' => 'Local storage is full',
        'clear_cache' => 'Clear Local Cache',
        // ... rest of texts
        'scan_qr_or_search' => 'Scan QR code or search by name then click check-in',
        'start_scanning' => 'Start Scanning',
        'stop_scanning' => 'Stop Scanning',
        'search_placeholder' => 'Search by name or phone...',
        'checkin_button' => 'Check In',
        'confirm_and_checkin' => 'Confirm & Check In',
        'force_checkin' => 'Force Check In',
        'results_appear_here' => 'Results will appear here...',
        'checking' => 'Checking...',
        'updating_status' => 'Updating status...',
        'camera_error' => 'Cannot access camera.',
        'connection_error' => 'Connection error occurred. Please try again.',
        'guest_not_found' => 'Guest not found in this event list.',
        'guest_already_checked_in' => '{name} was already checked in.',
        'guest_checked_in_success' => '{name} checked in successfully.',
        'sound_enabled' => 'Sound Enabled',
        'sound_disabled' => 'Sound Disabled',
        'manual_entry' => 'Manual Entry'
    ]
];

$t = $texts[$lang];

// Security check (unchanged)
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

// API for offline functionality
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $api_event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Security check
    if ($_SESSION['role'] !== 'admin' && $api_event_id != ($_SESSION['event_id_access'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'وصول غير مصرح به.']);
        exit;
    }

    // Cache all guests for offline use
    if (isset($_GET['cache_guests'])) {
        $stmt = $mysqli->prepare("SELECT * FROM guests WHERE event_id = ? ORDER BY name_ar");
        $stmt->bind_param("i", $api_event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $guests = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'guests' => $guests,
            'count' => count($guests),
            'cached_at' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // Sync offline check-ins to server
    if (isset($_GET['sync_checkins'])) {
        $checkins = $input['checkins'] ?? [];
        $synced = 0;
        $errors = [];
        
        foreach ($checkins as $checkin) {
            try {
                // Verify guest exists and update
                $stmt = $mysqli->prepare("UPDATE guests SET checkin_status = 'checked_in', checkin_time = ?, notes = CONCAT(COALESCE(notes, ''), '\n[OFFLINE] ', ?) WHERE guest_id = ? AND event_id = ?");
                $stmt->bind_param("sssi", $checkin['checkin_time'], $checkin['note'] ?? '', $checkin['guest_id'], $api_event_id);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $synced++;
                } else {
                    $errors[] = "Failed to sync guest: " . $checkin['guest_id'];
                }
                $stmt->close();
            } catch (Exception $e) {
                $errors[] = "Error syncing guest " . $checkin['guest_id'] . ": " . $e->getMessage();
            }
        }
        
        echo json_encode([
            'success' => $synced > 0,
            'synced' => $synced,
            'total' => count($checkins),
            'errors' => $errors
        ]);
        exit;
    }

    // Regular API endpoints (stats, recent, etc.) - keep existing code
    // ... (previous API code remains the same)
}

// Fetch event name
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .connection-status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .connection-status.online {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .connection-status.offline {
            background: linear-gradient(135deg, #fee2e2, #fca5a5);
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        
        .connection-status.syncing {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border: 1px solid #f59e0b;
        }
        
        .connection-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .connection-indicator.online { background-color: #10b981; }
        .connection-indicator.offline { background-color: #ef4444; }
        .connection-indicator.syncing { background-color: #f59e0b; }
        
        .container { 
            max-width: 600px; 
            width: 100%; 
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            padding: 30px; 
            text-align: center; 
        }
        
        .offline-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .sync-status {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .offline-badge {
            display: inline-block;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 8px;
        }
        
        /* Rest of styles remain the same as previous version */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }
        .stat-item { text-align: center; }
        .stat-number { font-size: 1.5rem; font-weight: bold; display: block; }
        .stat-label { font-size: 0.8rem; opacity: 0.9; margin-top: 4px; }
        
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
        .response-area.success { border-color: #10b981; background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; }
        .response-area.error { border-color: #ef4444; background: linear-gradient(135deg, #fee2e2, #fca5a5); color: #991b1b; }
        .response-area.warning { border-color: #f59e0b; background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
        
        #video { 
            width: 100%; 
            max-width: 400px; 
            height: 300px; 
            border-radius: 10px; 
            margin: 20px auto; 
            display: block; 
            background-color: #000; 
            object-fit: cover;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .search-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255,255,255,0.9);
        }
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
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
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-toggle { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .btn-toggle.active { background: #3b82f6; color: white; }
        
        .control-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        @media (max-width: 640px) {
            .stats-bar { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .control-buttons, .offline-controls { flex-direction: column; }
            .header { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="text-xl font-bold text-gray-700"><?= $t['event_title'] ?>: <?= htmlspecialchars($event_name) ?></h1>
            <div class="connection-status online" id="connection-status">
                <div class="connection-indicator online" id="connection-indicator"></div>
                <span id="connection-text"><?= $t['online_mode'] ?></span>
            </div>
        </div>
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
        
        <!-- Offline Controls -->
        <div class="offline-controls">
            <button id="cache-guests-btn" class="btn btn-primary"><?= $t['cache_guests'] ?></button>
            <button id="sync-now-btn" class="btn btn-warning" style="display: none;"><?= $t['sync_now'] ?></button>
            <button id="clear-cache-btn" class="btn btn-secondary"><?= $t['clear_cache'] ?></button>
        </div>
        
        <!-- Sync Status -->
        <div id="sync-status" class="sync-status" style="display: none;">
            <div id="sync-message"></div>
            <div id="pending-count" class="text-sm text-gray-600 mt-2"></div>
        </div>
        
        <!-- Stats Bar -->
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
            <div class="stat-item">
                <span class="stat-number" id="offline-checkins">0</span>
                <div class="stat-label"><?= $t['offline_checkins'] ?></div>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const eventId = <?= $event_id ?>;
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        
        // API URLs
        const cacheGuestsUrl = `checkin_offline.php?event_id=${eventId}&api=true&cache_guests=true`;
        const syncCheckinsUrl = `checkin_offline.php?event_id=${eventId}&api=true&sync_checkins=true`;
        
        // DOM elements
        const connectionStatus = document.getElementById('connection-status');
        const connectionIndicator = document.getElementById('connection-indicator');
        const connectionText = document.getElementById('connection-text');
        const cacheGuestsBtn = document.getElementById('cache-guests-btn');
        const syncNowBtn = document.getElementById('sync-now-btn');
        const clearCacheBtn = document.getElementById('clear-cache-btn');
        const syncStatus = document.getElementById('sync-status');
        const syncMessage = document.getElementById('sync-message');
        const pendingCount = document.getElementById('pending-count');
        const searchInput = document.getElementById('search-input');
        const checkinButton = document.getElementById('check-in-button');
        const responseArea = document.getElementById('response-area');
        const offlineCheckinsCount = document.getElementById('offline-checkins');
        
        // Offline data management
        let isOnline = navigator.onLine;
        let cachedGuests = JSON.parse(localStorage.getItem(`guests_${eventId}`) || '[]');
        let pendingCheckins = JSON.parse(localStorage.getItem(`pending_checkins_${eventId}`) || '[]');
        let offlineMode = false;
        
        // Initialize
        updateConnectionStatus();
        updateOfflineStats();
        updateSyncStatus();
        
        // Network status monitoring
        window.addEventListener('online', () => {
            isOnline = true;
            updateConnectionStatus();
            showNotification(texts['connection_restored'], 'success');
            autoSync();
        });
        
        window.addEventListener('offline', () => {
            isOnline = false;
            offlineMode = true;
            updateConnectionStatus();
            showNotification(texts['connection_lost'], 'warning');
        });
        
        // Test connection periodically
        setInterval(testConnection, 30000);
        
        async function testConnection() {
            try {
                const response = await fetch(`checkin_offline.php?event_id=${eventId}&api=true&ping=true`, {
                    method: 'GET',
                    timeout: 5000
                });
                
                if (response.ok && !isOnline) {
                    isOnline = true;
                    updateConnectionStatus();
                    showNotification(texts['connection_restored'], 'success');
                    autoSync();
                }
            } catch (error) {
                if (isOnline) {
                    isOnline = false;
                    offlineMode = true;
                    updateConnectionStatus();
                    showNotification(texts['connection_lost'], 'warning');
                }
            }
        }
        
        function updateConnectionStatus() {
            const statusClasses = ['online', 'offline', 'syncing'];
            statusClasses.forEach(cls => {
                connectionStatus.classList.remove(cls);
                connectionIndicator.classList.remove(cls);
            });
            
            if (isOnline && pendingCheckins.length === 0) {
                connectionStatus.classList.add('online');
                connectionIndicator.classList.add('online');
                connectionText.textContent = texts['online_mode'];
                syncNowBtn.style.display = 'none';
            } else if (isOnline && pendingCheckins.length > 0) {
                connectionStatus.classList.add('syncing');
                connectionIndicator.classList.add('syncing');
                connectionText.textContent = texts['sync_pending'];
                syncNowBtn.style.display = 'inline-block';
            } else {
                connectionStatus.classList.add('offline');
                connectionIndicator.classList.add('offline');
                connectionText.textContent = texts['offline_mode'];
                syncNowBtn.style.display = pendingCheckins.length > 0 ? 'inline-block' : 'none';
            }
        }
        
        function updateOfflineStats() {
            offlineCheckinsCount.textContent = pendingCheckins.length;
        }
        
        function updateSyncStatus() {
            if (pendingCheckins.length > 0) {
                syncStatus.style.display = 'block';
                syncMessage.textContent = `${pendingCheckins.length} ${texts['pending_sync']}`;
                pendingCount.textContent = `آخر مزامنة: ${localStorage.getItem(`last_sync_${eventId}`) || 'لم تتم بعد'}`;
            } else {
                syncStatus.style.display = 'none';
            }
        }
        
        // Cache guests data
        async function cacheGuests() {
            if (!isOnline) {
                showNotification('يتطلب الاتصال بالإنترنت لتحميل البيانات', 'error');
                return;
            }
            
            try {
                cacheGuestsBtn.innerHTML = '<span class="loading-spinner"></span> جاري التحميل...';
                cacheGuestsBtn.disabled = true;
                
                const response = await fetch(cacheGuestsUrl);
                const data = await response.json();
                
                if (data.success) {
                    cachedGuests = data.guests;
                    localStorage.setItem(`guests_${eventId}`, JSON.stringify(cachedGuests));
                    localStorage.setItem(`cache_timestamp_${eventId}`, data.cached_at);
                    
                    showNotification(texts['guests_cached'].replace('{count}', data.count), 'success');
                } else {
                    throw new Error('Failed to cache guests');
                }
            } catch (error) {
                console.error('Cache error:', error);
                showNotification('فشل في تحميل البيانات', 'error');
            } finally {
                cacheGuestsBtn.innerHTML = texts['cache_guests'];
                cacheGuestsBtn.disabled = false;
            }
        }
        
        // Sync pending check-ins
        async function syncPendingCheckins() {
            if (!isOnline || pendingCheckins.length === 0) return;
            
            try {
                syncNowBtn.innerHTML = '<span class="loading-spinner"></span> جاري المزامنة...';
                syncNowBtn.disabled = true;
                
                const response = await fetch(syncCheckinsUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ checkins: pendingCheckins })
                });
                
                const data = await response.json();
                
                if (data.success && data.synced > 0) {
                    // Remove synced check-ins from pending
                    pendingCheckins = pendingCheckins.slice(data.synced);
                    localStorage.setItem(`pending_checkins_${eventId}`, JSON.stringify(pendingCheckins));
                    localStorage.setItem(`last_sync_${eventId}`, new Date().toLocaleString('ar-EG'));
                    
                    showNotification(`${texts['sync_success']} (${data.synced}/${data.total})`, 'success');
                    updateConnectionStatus();
                    updateOfflineStats();
                    updateSyncStatus();
                } else {
                    throw new Error(data.errors?.join(', ') || 'Sync failed');
                }
            } catch (error) {
                console.error('Sync error:', error);
                showNotification(texts['sync_failed'], 'error');
            } finally {
                syncNowBtn.innerHTML = texts['sync_now'];
                syncNowBtn.disabled = false;
            }
        }
        
        // Auto sync when connection is restored
        async function autoSync() {
            if (pendingCheckins.length > 0) {
                setTimeout(syncPendingCheckins, 2000); // Wait 2 seconds then sync
            }
        }
        
        // Clear local cache
        function clearCache() {
            if (confirm('هل تريد مسح جميع البيانات المحلية؟ سيتم فقدان التسجيلات غير المزامنة.')) {
                localStorage.removeItem(`guests_${eventId}`);
                localStorage.removeItem(`pending_checkins_${eventId}`);
                localStorage.removeItem(`cache_timestamp_${eventId}`);
                localStorage.removeItem(`last_sync_${eventId}`);
                
                cachedGuests = [];
                pendingCheckins = [];
                
                updateOfflineStats();
                updateSyncStatus();
                updateConnectionStatus();
                
                showNotification('تم مسح البيانات المحلية', 'success');
            }
        }
        
        // Search in cached guests (offline functionality)
        function searchCachedGuests(searchTerm) {
            if (!searchTerm || searchTerm.length < 2) return [];
            
            return cachedGuests.filter(guest => {
                return guest.guest_id === searchTerm ||
                       guest.name_ar.includes(searchTerm) ||
                       (guest.phone_number && guest.phone_number.includes(searchTerm));
            }).slice(0, 10);
        }
        
        // Enhanced check-in function with offline support
        async function performCheckIn(searchTerm, forceConfirm = false) {
            responseArea.innerHTML = `<p class="text-gray-500"><span class="loading-spinner"></span> ${texts['checking']}</p>`;
            responseArea.className = 'response-area';
            
            try {
                let guest = null;
                let isOfflineCheckin = false;
                
                if (isOnline) {
                    // Try online check-in first
                    const response = await fetch(`checkin_offline.php?event_id=${eventId}&api=true`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ 
                            searchTerm: searchTerm,
                            forceConfirm: forceConfirm 
                        }),
                        timeout: 10000
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        displayResponse(data.message, data.type || 'error', data.guestDetails, data.showConfirmOption);
                        
                        if (data.success && data.type === 'success') {
                            playSuccessSound();
                            searchInput.value = '';
                        } else if (data.type === 'warning') {
                            playWarningSound();
                        } else {
                            playErrorSound();
                        }
                        return;
                    } else {
                        throw new Error('Server request failed');
                    }
                } else {
                    throw new Error('Offline mode');
                }
                
            } catch (error) {
                // Fallback to offline mode
                console.log('Falling back to offline mode:', error.message);
                isOfflineCheckin = true;
                
                // Search in cached guests
                const results = searchCachedGuests(searchTerm);
                
                if (results.length === 0) {
                    displayResponse(texts['guest_not_found'], 'error');
                    playErrorSound();
                    return;
                } else if (results.length > 1) {
                    displayResponse(texts['multiple_guests_found'], 'warning');
                    playWarningSound();
                    return;
                }
                
                guest = results[0];
                
                // Check if already checked in (offline)
                const alreadyCheckedIn = pendingCheckins.some(checkin => checkin.guest_id === guest.guest_id) ||
                                        guest.checkin_status === 'checked_in';
                
                if (alreadyCheckedIn) {
                    displayResponse(texts['guest_already_checked_in'].replace('{name}', guest.name_ar), 'warning', guest);
                    playWarningSound();
                    return;
                }
                
                // Handle different guest statuses
                if (guest.status === 'canceled') {
                    displayResponse(texts['guest_declined'].replace('{name}', guest.name_ar), 'error', guest);
                    playErrorSound();
                    return;
                } else if (guest.status === 'pending' && !forceConfirm) {
                    displayResponse(texts['guest_not_confirmed'].replace('{name}', guest.name_ar), 'warning', guest, true);
                    playWarningSound();
                    return;
                }
                
                // Perform offline check-in
                const checkinData = {
                    guest_id: guest.guest_id,
                    name_ar: guest.name_ar,
                    checkin_time: new Date().toISOString(),
                    note: 'Offline check-in',
                    event_id: eventId,
                    offline: true
                };
                
                // Update guest status if needed
                if (guest.status === 'pending' && forceConfirm) {
                    checkinData.status_updated = true;
                    guest.status = 'confirmed';
                }
                
                // Add to pending checkins
                pendingCheckins.push(checkinData);
                localStorage.setItem(`pending_checkins_${eventId}`, JSON.stringify(pendingCheckins));
                
                // Update cached guest data
                guest.checkin_status = 'checked_in';
                guest.checkin_time = checkinData.checkin_time;
                const guestIndex = cachedGuests.findIndex(g => g.guest_id === guest.guest_id);
                if (guestIndex !== -1) {
                    cachedGuests[guestIndex] = guest;
                    localStorage.setItem(`guests_${eventId}`, JSON.stringify(cachedGuests));
                }
                
                // Show success message with offline indicator
                const successMessage = texts['guest_checked_in_success'].replace('{name}', guest.name_ar);
                displayResponse(successMessage, 'success', guest);
                
                playSuccessSound();
                searchInput.value = '';
                
                updateOfflineStats();
                updateSyncStatus();
                updateConnectionStatus();
            }
        }
        
        // Enhanced display response with offline indicator
        function displayResponse(message, status, details = null, showConfirmOption = false) {
            let offlineIndicator = '';
            if (details && pendingCheckins.some(checkin => checkin.guest_id === details.guest_id)) {
                offlineIndicator = '<span class="offline-badge">محلي</span>';
            }
            
            responseArea.innerHTML = `<p class="font-semibold text-lg mb-2">${message} ${offlineIndicator}</p>`;
            responseArea.className = `response-area ${status}`;
            
            if (details) {
                let statusBadge = '';
                switch(details.status) {
                    case 'confirmed': statusBadge = '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">مؤكد</span>'; break;
                    case 'pending': statusBadge = '<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">في الانتظار</span>'; break;
                    case 'canceled': statusBadge = '<span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">ملغي</span>'; break;
                }
                
                let checkinBadge = details.checkin_status === 'checked_in' ? 
                    '<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">تم التسجيل</span>' : 
                    '<span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm">لم يتم</span>';
                
                const detailsHtml = `
                    <div class="detail-item">
                        <span>الاسم:</span>
                        <span class="font-medium">${details.name_ar || ''}</span>
                    </div>
                    <div class="detail-item">
                        <span>حالة الحضور:</span>
                        <span>${statusBadge}</span>
                    </div>
                    <div class="detail-item">
                        <span>حالة التسجيل:</span>
                        <span>${checkinBadge}</span>
                    </div>
                    <div class="detail-item">
                        <span>عدد الضيوف:</span>
                        <span>${details.guests_count || '1'}</span>
                    </div>
                    <div class="detail-item">
                        <span>رقم الطاولة:</span>
                        <span>${details.table_number || 'N/A'}</span>
                    </div>
                `;
                
                let actionButtons = '';
                if (showConfirmOption) {
                    actionButtons = `
                        <div class="action-buttons" style="display: flex; gap: 8px; margin-top: 15px; justify-content: center;">
                            <button onclick="performCheckIn('${details.guest_id}', true)" class="btn btn-warning">
                                ${texts['confirm_and_checkin']}
                            </button>
                        </div>
                    `;
                }
                
                responseArea.innerHTML += `<div class="mt-4 border-t pt-4">${detailsHtml}${actionButtons}</div>`;
            }
        }
        
        // Sound functions
        function playSuccessSound() {
            if (localStorage.getItem('checkin_sound') === 'false') return;
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBziR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmggBgAAWQoCOQAABSKj2eqvYh0GZaL16Dqp2AUhW9n7ZLk7LgQ=');
            audio.volume = 0.7;
            audio.play().catch(() => {});
        }
        
        function playWarningSound() {
            if (localStorage.getItem('checkin_sound') === 'false') return;
            const audio = new Audio('data:audio/wav;base64,UklGRl9bAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQtbAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmggBgAAWQoCOQAABSKj2eqvYh0GZaL16Dqp2AUhW9n7ZLk7LgQ=');
            audio.volume = 0.5;
            audio.play().catch(() => {});
        }
        
        function playErrorSound() {
            if (localStorage.getItem('checkin_sound') === 'false') return;
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBziR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmggBgAAWQoCOQAABSKj2eqvYh0GZaL16Dqp2AUhW9n7ZLk7LgQ=');
            audio.volume = 0.3;
            audio.play().catch(() => {});
            setTimeout(() => audio.play().catch(() => {}), 200);
        }
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full`;
            
            const bgColors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                warning: 'bg-yellow-500 text-black',
                info: 'bg-blue-500 text-white'
            };
            
            notification.className += ` ${bgColors[type] || bgColors.info}`;
            notification.innerHTML = `
                <div class="flex items-center gap-3">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-xl">&times;</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
        
        // QR Scanner (same as before)
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const ctx = canvas.getContext('2d');
        const startScanButton = document.getElementById('start-scan-button');
        const stopScanButton = document.getElementById('stop-scan-button');
        const soundToggle = document.getElementById('sound-toggle');
        const manualToggle = document.getElementById('manual-toggle');
        
        let videoStream = null;
        let animationFrameId = null;
        let soundEnabled = localStorage.getItem('checkin_sound') !== 'false';
        let manualMode = false;
        
        function updateSoundToggle() {
            soundToggle.textContent = soundEnabled ? texts['sound_enabled'] : texts['sound_disabled'];
            soundToggle.classList.toggle('active', soundEnabled);
        }
        
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
                showNotification(texts['camera_error'], 'error');
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
        
        // Event Listeners
        cacheGuestsBtn.addEventListener('click', cacheGuests);
        syncNowBtn.addEventListener('click', syncPendingCheckins);
        clearCacheBtn.addEventListener('click', clearCache);
        
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
            }
        });
        
        startScanButton.addEventListener('click', startScanner);
        stopScanButton.addEventListener('click', stopScanner);
        
        // Global functions
        window.performCheckIn = performCheckIn;
        
        // Initialize UI
        updateSoundToggle();
        
        // Auto-start scanner if not in manual mode
        if (!manualMode) {
            setTimeout(startScanner, 1000);
        }
        
        // Check if we have cached data on load
        if (cachedGuests.length > 0) {
            const cacheTime = localStorage.getItem(`cache_timestamp_${eventId}`);
            showNotification(`تم تحميل ${cachedGuests.length} ضيف من البيانات المحفوظة (${new Date(cacheTime).toLocaleString('ar-EG')})`, 'info');
        }
    });
    </script>
</body>
</html>