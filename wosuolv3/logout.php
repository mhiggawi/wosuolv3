<?php
// logout.php - Clean and working version

// Configure session settings BEFORE starting session
ini_set('session.cookie_httponly', 1);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once 'db_config.php';

// Language System
$lang = $_SESSION['language'] ?? $_COOKIE['language'] ?? 'ar';
if (isset($_POST['switch_language'])) {
    $lang = $_POST['switch_language'] === 'en' ? 'en' : 'ar';
    $_SESSION['language'] = $lang;
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
}

// Language texts
$texts = [
    'ar' => [
        'logout_title' => 'تسجيل الخروج',
        'confirm_logout' => 'تأكيد تسجيل الخروج',
        'logout_message' => 'هل أنت متأكد من رغبتك في تسجيل الخروج؟',
        'logout_warning' => 'سيتم إنهاء جلستك الحالية وستحتاج لتسجيل الدخول مرة أخرى.',
        'session_info' => 'معلومات الجلسة',
        'logged_in_as' => 'مسجل دخول باسم',
        'login_time' => 'وقت تسجيل الدخول',
        'session_duration' => 'مدة الجلسة',
        'ip_address' => 'عنوان IP',
        'user_role' => 'دور المستخدم',
        'confirm_logout_btn' => 'تأكيد تسجيل الخروج',
        'cancel_btn' => 'إلغاء',
        'back_to_dashboard' => 'العودة للوحة التحكم',
        'goodbye_message' => 'شكراً لاستخدامك نظام وصول',
        'logout_success' => 'تم تسجيل خروجك بنجاح',
        'see_you_soon' => 'نراك قريباً!',
        'login_again' => 'تسجيل دخول مرة أخرى',
        'minutes' => 'دقيقة',
        'hours' => 'ساعة',
        'seconds' => 'ثانية',
        'admin' => 'مدير',
        'viewer' => 'مشاهد',
        'checkin_user' => 'مسجل دخول'
    ],
    'en' => [
        'logout_title' => 'Logout',
        'confirm_logout' => 'Confirm Logout',
        'logout_message' => 'Are you sure you want to logout?',
        'logout_warning' => 'Your current session will be terminated and you will need to login again.',
        'session_info' => 'Session Information',
        'logged_in_as' => 'Logged in as',
        'login_time' => 'Login Time',
        'session_duration' => 'Session Duration',
        'ip_address' => 'IP Address',
        'user_role' => 'User Role',
        'confirm_logout_btn' => 'Confirm Logout',
        'cancel_btn' => 'Cancel',
        'back_to_dashboard' => 'Back to Dashboard',
        'goodbye_message' => 'Thank you for using our system',
        'logout_success' => 'You have been logged out successfully',
        'see_you_soon' => 'See you soon!',
        'login_again' => 'Login Again',
        'minutes' => 'minutes',
        'hours' => 'hours',
        'seconds' => 'seconds',
        'admin' => 'Administrator',
        'viewer' => 'Viewer',
        'checkin_user' => 'Check-in User'
    ]
];

$t = $texts[$lang];

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$step = $_GET['step'] ?? 'confirm';

// Collect session information for display
$session_info = [];
if ($is_logged_in) {
    $session_info = [
        'username' => $_SESSION['username'] ?? 'N/A',
        'role' => $_SESSION['role'] ?? 'N/A',
        'login_time' => $_SESSION['login_time'] ?? time(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
}

// Handle logout confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout']) && !isset($_POST['switch_language'])) {
    
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Security token mismatch.';
    } else {
        
        // Store data for goodbye page
        $goodbye_data = [
            'username' => $_SESSION['username'] ?? '',
            'session_duration' => time() - ($_SESSION['login_time'] ?? time())
        ];
        
        // Clear all session data
        $_SESSION = array();
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Start new session for goodbye message
        session_start();
        $_SESSION['language'] = $lang;
        $_SESSION['goodbye_data'] = $goodbye_data;
        $_SESSION['logout_success'] = true;
        
        // Redirect to goodbye page
        header('Location: logout.php?step=goodbye');
        exit;
    }
}

// Helper functions
function formatDuration($seconds, $texts) {
    if ($seconds < 60) {
        return $seconds . ' ' . $texts['seconds'];
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        return $minutes . ' ' . $texts['minutes'];
    } else {
        $hours = floor($seconds / 3600);
        return $hours . ' ' . $texts['hours'];
    }
}

function getRoleText($role, $texts) {
    switch ($role) {
        case 'admin': return $texts['admin'];
        case 'viewer': return $texts['viewer'];
        case 'checkin_user': return $texts['checkin_user'];
        default: return $role;
    }
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['logout_title'] ?> - wosuol.com</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 600px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        
        .logout-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
        }
        
        .success-icon {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }
        
        .session-info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-value {
            font-weight: 500;
            color: #374151;
        }
        
        .logout-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .language-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }
        
        .language-toggle button {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .language-toggle button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .goodbye-animation {
            animation: fadeInUp 1s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        @media (max-width: 640px) {
            .logout-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .logout-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .language-toggle {
                position: fixed;
                top: 10px;
                right: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Language Toggle -->
    <div class="language-toggle">
        <form method="POST" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>">
                <i class="fas fa-globe"></i>
                <?= $lang === 'ar' ? 'English' : 'العربية' ?>
            </button>
        </form>
    </div>

    <div class="logout-container">
        
        <?php if ($step === 'goodbye' || isset($_SESSION['logout_success'])): ?>
            <!-- Goodbye Page -->
            <div class="goodbye-animation">
                <div class="logout-icon success-icon">
                    <i class="fas fa-check text-white text-3xl"></i>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-800 mb-4"><?= $t['goodbye_message'] ?></h1>
                <p class="text-lg text-gray-600 mb-6"><?= $t['logout_success'] ?></p>
                
                <?php if (isset($_SESSION['goodbye_data'])): ?>
                    <?php $goodbye_data = $_SESSION['goodbye_data']; ?>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number"><?= formatDuration($goodbye_data['session_duration'], $t) ?></span>
                            <div class="stat-label"><?= $t['session_duration'] ?></div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?= htmlspecialchars($goodbye_data['username']) ?></span>
                            <div class="stat-label"><?= $t['logged_in_as'] ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <p class="text-gray-600 mb-6"><?= $t['see_you_soon'] ?></p>
                    <a href="login.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i>
                        <?= $t['login_again'] ?>
                    </a>
                </div>
            </div>
            
            <?php 
            // Clear goodbye data
            unset($_SESSION['goodbye_data']); 
            unset($_SESSION['logout_success']);
            ?>
            
        <?php elseif ($is_logged_in): ?>
            <!-- Normal Logout Confirmation Page -->
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt text-white text-3xl"></i>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800 mb-4"><?= $t['confirm_logout'] ?></h1>
            <p class="text-gray-600 mb-6"><?= $t['logout_message'] ?></p>
            <p class="text-sm text-gray-500 mb-6"><?= $t['logout_warning'] ?></p>
            
            <!-- Session Information -->
            <div class="session-info-card">
                <h3 class="text-lg font-semibold mb-4 text-center"><?= $t['session_info'] ?></h3>
                
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-user"></i>
                        <?= $t['logged_in_as'] ?>
                    </div>
                    <div class="info-value"><?= htmlspecialchars($session_info['username']) ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-user-tag"></i>
                        <?= $t['user_role'] ?>
                    </div>
                    <div class="info-value"><?= getRoleText($session_info['role'], $t) ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-clock"></i>
                        <?= $t['login_time'] ?>
                    </div>
                    <div class="info-value"><?= date('Y-m-d H:i:s', $session_info['login_time']) ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-history"></i>
                        <?= $t['session_duration'] ?>
                    </div>
                    <div class="info-value"><?= formatDuration(time() - $session_info['login_time'], $t) ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= $t['ip_address'] ?>
                    </div>
                    <div class="info-value"><?= htmlspecialchars($session_info['ip_address']) ?></div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="logout-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" name="confirm_logout" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <?= $t['confirm_logout_btn'] ?>
                    </button>
                </form>
                
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <?= $t['cancel_btn'] ?>
                </a>
                
                <?php
                // Determine dashboard URL based on role
                $dashboard_url = 'events.php'; // Default for admin
                if (isset($_SESSION['role'])) {
                    switch ($_SESSION['role']) {
                        case 'viewer':
                            $dashboard_url = isset($_SESSION['event_id_access']) ? 'dashboard.php?event_id=' . $_SESSION['event_id_access'] : 'dashboard.php';
                            break;
                        case 'checkin_user':
                            $dashboard_url = isset($_SESSION['event_id_access']) ? 'checkin.php?event_id=' . $_SESSION['event_id_access'] : 'checkin.php';
                            break;
                    }
                }
                ?>
                
                <a href="<?= $dashboard_url ?>" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i>
                    <?= $t['back_to_dashboard'] ?>
                </a>
            </div>
            
        <?php else: ?>
            <!-- Already Logged Out -->
            <div class="logout-icon success-icon">
                <i class="fas fa-info-circle text-white text-3xl"></i>
            </div>
            
            <h1 class="text-2xl font-bold text-gray-800 mb-4"><?= $t['logout_success'] ?></h1>
            <p class="text-gray-600 mb-6"><?= $lang === 'ar' ? 'أنت غير مسجل دخول حالياً' : 'You are not currently logged in' ?></p>
            
            <div class="logout-buttons">
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    <?= $t['login_again'] ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-redirect after successful logout (goodbye page)
        <?php if ($step === 'goodbye'): ?>
        setTimeout(function() {
            window.location.href = 'login.php';
        }, 5000); // Redirect after 5 seconds
        <?php endif; ?>
    </script>
</body>
</html>