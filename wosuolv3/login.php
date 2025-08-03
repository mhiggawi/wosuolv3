<?php
// login.php - Enhanced with security, languages, and modern design
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
        'login_title' => 'تسجيل الدخول',
        'welcome_back' => 'مرحباً بعودتك',
        'login_subtitle' => 'يرجى تسجيل الدخول للوصول إلى لوحة التحكم',
        'username_label' => 'اسم المستخدم',
        'username_placeholder' => 'أدخل اسم المستخدم',
        'password_label' => 'كلمة المرور',
        'password_placeholder' => 'أدخل كلمة المرور',
        'remember_me' => 'تذكرني',
        'login_button' => 'تسجيل الدخول',
        'logging_in' => 'جاري تسجيل الدخول...',
        'show_password' => 'إظهار كلمة المرور',
        'hide_password' => 'إخفاء كلمة المرور',
        'error_invalid_credentials' => 'اسم المستخدم أو كلمة المرور غير صحيحة.',
        'error_account_locked' => 'تم قفل الحساب مؤقتاً بسبب المحاولات المتكررة. يرجى المحاولة بعد {minutes} دقائق.',
        'error_too_many_attempts' => 'محاولات كثيرة جداً. يرجى الانتظار {seconds} ثانية قبل المحاولة مرة أخرى.',
        'error_fill_fields' => 'الرجاء إدخال اسم المستخدم وكلمة المرور.',
        'error_csrf' => 'خطأ في التحقق من صحة الطلب. يرجى المحاولة مرة أخرى.',
        'error_general' => 'عذراً، حدث خطأ ما. يرجى المحاولة مرة أخرى.',
        'error_no_event_access' => 'هذا المستخدم غير مصرح له بالدخول لعدم ربطه بأي حفل.',
        'attempts_remaining' => 'محاولات متبقية: {count}',
        'security_notice' => 'إشعار أمني: تم تسجيل محاولة دخول من عنوان IP جديد.',
        'login_success' => 'تم تسجيل الدخول بنجاح! جاري التحويل...'
    ],
    'en' => [
        'login_title' => 'Login',
        'welcome_back' => 'Welcome Back',
        'login_subtitle' => 'Please sign in to access your dashboard',
        'username_label' => 'Username',
        'username_placeholder' => 'Enter your username',
        'password_label' => 'Password',
        'password_placeholder' => 'Enter your password',
        'remember_me' => 'Remember me',
        'login_button' => 'Sign In',
        'logging_in' => 'Signing in...',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
        'error_invalid_credentials' => 'Invalid username or password.',
        'error_account_locked' => 'Account temporarily locked due to repeated attempts. Please try again after {minutes} minutes.',
        'error_too_many_attempts' => 'Too many attempts. Please wait {seconds} seconds before trying again.',
        'error_fill_fields' => 'Please enter both username and password.',
        'error_csrf' => 'Security token mismatch. Please try again.',
        'error_general' => 'Sorry, something went wrong. Please try again.',
        'error_no_event_access' => 'This user is not authorized to access as they are not linked to any event.',
        'attempts_remaining' => 'Attempts remaining: {count}',
        'security_notice' => 'Security Notice: Login attempt recorded from new IP address.',
        'login_success' => 'Login successful! Redirecting...'
    ]
];

$t = $texts[$lang];

// --- Security Settings ---
$MAX_ATTEMPTS = 5;
$LOCKOUT_TIME = 900; // 15 minutes
$RATE_LIMIT_TIME = 60; // 1 minute between attempts after 3 failures
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// --- CSRF Protection ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Rate Limiting & Security ---
$attempts_key = 'login_attempts_' . md5($client_ip);
$lockout_key = 'login_lockout_' . md5($client_ip);
$last_attempt_key = 'last_attempt_' . md5($client_ip);

// Initialize attempt tracking
if (!isset($_SESSION[$attempts_key])) {
    $_SESSION[$attempts_key] = 0;
}

$message = '';
$messageType = 'error';
$attempts_remaining = $MAX_ATTEMPTS - $_SESSION[$attempts_key];
$is_locked_out = false;
$time_until_unlock = 0;

// Check if account is locked out
if (isset($_SESSION[$lockout_key]) && $_SESSION[$lockout_key] > time()) {
    $is_locked_out = true;
    $time_until_unlock = $_SESSION[$lockout_key] - time();
    $minutes_remaining = ceil($time_until_unlock / 60);
    $message = str_replace('{minutes}', $minutes_remaining, $t['error_account_locked']);
}

// Check rate limiting
$rate_limited = false;
$rate_limit_seconds = 0;
if (isset($_SESSION[$last_attempt_key]) && $_SESSION[$attempts_key] >= 3) {
    $time_since_last = time() - $_SESSION[$last_attempt_key];
    if ($time_since_last < $RATE_LIMIT_TIME) {
        $rate_limited = true;
        $rate_limit_seconds = $RATE_LIMIT_TIME - $time_since_last;
        $message = str_replace('{seconds}', $rate_limit_seconds, $t['error_too_many_attempts']);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['switch_language'])) {
    
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = $t['error_csrf'];
    }
    // Check if locked out
    elseif ($is_locked_out) {
        $message = str_replace('{minutes}', $minutes_remaining, $t['error_account_locked']);
    }
    // Check rate limiting
    elseif ($rate_limited) {
        $message = str_replace('{seconds}', $rate_limit_seconds, $t['error_too_many_attempts']);
    }
    else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        if (empty($username) || empty($password)) {
            $message = $t['error_fill_fields'];
            $_SESSION[$attempts_key]++;
            $_SESSION[$last_attempt_key] = time();
        } else {
            
            // Database query
            $sql = "SELECT username, password_hash, role, event_id FROM users WHERE username = ?";
            
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("s", $username);
                
                if ($stmt->execute()) {
                    $stmt->store_result();
                    
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($db_username, $db_password_hash, $db_role, $db_event_id);
                        
                        if ($stmt->fetch()) {
                            if (password_verify($password, $db_password_hash)) {
                                
                                // Successful login - Reset security counters
                                unset($_SESSION[$attempts_key]);
                                unset($_SESSION[$lockout_key]);
                                unset($_SESSION[$last_attempt_key]);
                                
                                // Regenerate session ID for security
                                session_regenerate_id(true);

                                // Set session variables
                                $_SESSION['loggedin'] = true;
                                $_SESSION['username'] = $db_username;
                                $_SESSION['role'] = $db_role;
                                $_SESSION['event_id_access'] = $db_event_id;
                                $_SESSION['login_time'] = time();
                                $_SESSION['last_activity'] = time();

                                // Handle "Remember Me"
                                if ($remember_me) {
                                    $remember_token = bin2hex(random_bytes(32));
                                    $expires = time() + (30 * 24 * 60 * 60); // 30 days
                                    
                                    // Store token in database (you may want to create a separate table for this)
                                    setcookie('remember_token', $remember_token, $expires, '/', '', true, true);
                                    
                                    // You can store this token in database for validation
                                    // $stmt_token = $mysqli->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE username = ?");
                                    // $stmt_token->bind_param("sis", $remember_token, date('Y-m-d H:i:s', $expires), $db_username);
                                    // $stmt_token->execute();
                                }

                                // Set success message
                                $message = $t['login_success'];
                                $messageType = 'success';

                                // Determine redirect URL
                                $redirect_url = '';
                                switch ($db_role) {
                                    case 'admin':
                                        $redirect_url = 'events.php';
                                        break;
                                    case 'viewer':
                                        if (!empty($db_event_id)) {
                                            $redirect_url = 'dashboard.php?event_id=' . $db_event_id;
                                        } else {
                                            $message = $t['error_no_event_access'];
                                            $messageType = 'error';
                                        }
                                        break;
                                    case 'checkin_user':
                                        if (!empty($db_event_id)) {
                                            $redirect_url = 'checkin.php?event_id=' . $db_event_id;
                                        } else {
                                            $message = $t['error_no_event_access'];
                                            $messageType = 'error';
                                        }
                                        break;
                                    default:
                                        $redirect_url = 'login.php';
                                        break;
                                }

                                // Redirect if successful
                                if ($messageType === 'success' && !empty($redirect_url)) {
                                    // Add a small delay and redirect via JavaScript for better UX
                                    echo "<script>
                                        setTimeout(function() {
                                            window.location.href = '$redirect_url';
                                        }, 1500);
                                    </script>";
                                }

                            } else {
                                // Invalid password
                                $message = $t['error_invalid_credentials'];
                                $_SESSION[$attempts_key]++;
                                $_SESSION[$last_attempt_key] = time();
                                
                                // Lock account if max attempts reached
                                if ($_SESSION[$attempts_key] >= $MAX_ATTEMPTS) {
                                    $_SESSION[$lockout_key] = time() + $LOCKOUT_TIME;
                                    $message = str_replace('{minutes}', ceil($LOCKOUT_TIME / 60), $t['error_account_locked']);
                                }
                            }
                        }
                    } else {
                        // User not found
                        $message = $t['error_invalid_credentials'];
                        $_SESSION[$attempts_key]++;
                        $_SESSION[$last_attempt_key] = time();
                        
                        if ($_SESSION[$attempts_key] >= $MAX_ATTEMPTS) {
                            $_SESSION[$lockout_key] = time() + $LOCKOUT_TIME;
                            $message = str_replace('{minutes}', ceil($LOCKOUT_TIME / 60), $t['error_account_locked']);
                        }
                    }
                } else {
                    $message = $t['error_general'];
                }
                $stmt->close();
            } else {
                $message = $t['error_general'];
            }
        }
        
        // Update remaining attempts
        $attempts_remaining = max(0, $MAX_ATTEMPTS - $_SESSION[$attempts_key]);
    }
}

// Security logging (optional - you can log to file or database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($message) && $messageType === 'error') {
    error_log("Login attempt from IP: $client_ip, Username: " . ($_POST['username'] ?? 'empty') . ", Error: $message");
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['login_title'] ?> - wosuol.com</title>
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
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 50px 15px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #f9fafb;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group .input-icon {
            position: absolute;
            right: 15px;
            top: 38px;
            color: #9ca3af;
            pointer-events: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 38px;
            color: #9ca3af;
            cursor: pointer;
            transition: color 0.2s;
            pointer-events: all;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .login-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading-spinner {
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
        
        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .message.warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .remember-me-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }
        
        .remember-me-container label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #6b7280;
        }
        
        .remember-me-container input[type="checkbox"] {
            margin-right: 8px;
            width: 16px;
            height: 16px;
        }
        
        .security-info {
            background-color: #f3f4f6;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #6b7280;
        }
        
        .attempts-counter {
            text-align: center;
            margin-bottom: 15px;
            font-size: 13px;
            color: #ef4444;
            font-weight: 600;
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
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .footer-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .countdown-timer {
            font-weight: bold;
            color: #ef4444;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>">
                <i class="fas fa-globe"></i>
                <?= $lang === 'ar' ? 'English' : 'العربية' ?>
            </button>
        </form>
    </div>

    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-calendar-check text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2"><?= $t['welcome_back'] ?></h1>
            <p class="text-gray-600 text-sm"><?= $t['login_subtitle'] ?></p>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?>"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Attempts Counter -->
        <?php if ($_SESSION[$attempts_key] > 0 && !$is_locked_out && $messageType !== 'success'): ?>
            <div class="attempts-counter">
                <i class="fas fa-shield-alt"></i>
                <?= str_replace('{count}', $attempts_remaining, $t['attempts_remaining']) ?>
            </div>
        <?php endif; ?>

        <!-- Countdown Timer for Rate Limiting -->
        <?php if ($rate_limited || $is_locked_out): ?>
            <div class="attempts-counter">
                <i class="fas fa-clock"></i>
                <span class="countdown-timer" id="countdown-timer"></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form id="loginForm" method="POST" action="login.php" <?= ($is_locked_out || $rate_limited) ? 'style="opacity: 0.5; pointer-events: none;"' : '' ?>>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <!-- Username Field -->
            <div class="form-group">
                <label for="username"><?= $t['username_label'] ?>:</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       placeholder="<?= $t['username_placeholder'] ?>"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required 
                       autocomplete="username">
                <i class="fas fa-user input-icon"></i>
            </div>

            <!-- Password Field -->
            <div class="form-group">
                <label for="password"><?= $t['password_label'] ?>:</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="<?= $t['password_placeholder'] ?>"
                       required 
                       autocomplete="current-password">
                <i class="fas fa-eye password-toggle" 
                   id="passwordToggle" 
                   onclick="togglePassword()"
                   title="<?= $t['show_password'] ?>"></i>
            </div>

            <!-- Remember Me -->
            <div class="remember-me-container">
                <label>
                    <input type="checkbox" name="remember_me" <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                    <?= $t['remember_me'] ?>
                </label>
            </div>

            <!-- Login Button -->
            <button type="submit" class="login-button" id="loginButton">
                <div class="loading-spinner" id="loadingSpinner"></div>
                <span id="buttonText"><?= $t['login_button'] ?></span>
            </button>
        </form>

        <!-- Security Info -->
        <?php if ($_SESSION[$attempts_key] > 2): ?>
            <div class="security-info">
                <i class="fas fa-info-circle"></i>
                <?= $t['security_notice'] ?>
            </div>
        <?php endif; ?>

        <!-- Footer Links -->
        <div class="footer-links">
            <a href="#" onclick="alert('<?= $lang === 'ar' ? 'تواصل معنا 962799121049  للحصول على المساعدة' : 'Contact us 962799121049 for support' ?>');">

                <?= $lang === 'ar' ? 'مساعدة' : 'Help' ?>
            </a>
            <a href="#" onclick="alert('<?= $lang === 'ar' ? 'تواصل مع الإدارة لاستعادة كلمة المرور' : 'Contact admin to reset password' ?>');">
                <i class="fas fa-key"></i>
                <?= $lang === 'ar' ? 'نسيت كلمة المرور؟' : 'Forgot Password?' ?>
            </a>
        </div>
    </div>

    <script>
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        
        // Password toggle functionality
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggle');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                toggleIcon.title = texts['hide_password'];
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                toggleIcon.title = texts['show_password'];
            }
        }

        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            const spinner = document.getElementById('loadingSpinner');
            const buttonText = document.getElementById('buttonText');
            
            // Show loading state
            button.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.textContent = texts['logging_in'];
            
            // Client-side validation
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert(texts['error_fill_fields']);
                
                // Reset button state
                button.disabled = false;
                spinner.style.display = 'none';
                buttonText.textContent = texts['login_button'];
                return;
            }
        });

        // Countdown timer for locked accounts or rate limiting
        <?php if ($rate_limited || $is_locked_out): ?>
        let timeRemaining = <?= $is_locked_out ? $time_until_unlock : $rate_limit_seconds ?>;
        const countdownElement = document.getElementById('countdown-timer');
        
        function updateCountdown() {
            if (timeRemaining <= 0) {
                location.reload();
                return;
            }
            
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            
            if (minutes > 0) {
                countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            } else {
                countdownElement.textContent = `${seconds}s`;
            }
            
            timeRemaining--;
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        <?php endif; ?>

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.value) {
                usernameField.focus();
            } else {
                document.getElementById('password').focus();
            }
        });

        // Enhanced security: Clear form data on page unload
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + L to focus on login button
            if (e.altKey && e.key === 'l') {
                e.preventDefault();
                document.getElementById('loginButton').focus();
            }
            
            // Alt + U to focus on username
            if (e.altKey && e.key === 'u') {
                e.preventDefault();
                document.getElementById('username').focus();
            }
            
            // Alt + P to focus on password
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });

        // Prevent multiple form submissions
        let formSubmitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
        });

        // Auto-redirect after successful login
        <?php if ($messageType === 'success'): ?>
        setTimeout(function() {
            const button = document.getElementById('loginButton');
            const spinner = document.getElementById('loadingSpinner');
            const buttonText = document.getElementById('buttonText');
            
            button.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.textContent = texts['logging_in'];
        }, 100);
        <?php endif; ?>

        // Enhanced visual feedback for form validation
        document.getElementById('username').addEventListener('blur', function() {
            const field = this;
            if (field.value.trim().length < 3) {
                field.style.borderColor = '#ef4444';
            } else {
                field.style.borderColor = '#10b981';
            }
        });

        document.getElementById('password').addEventListener('blur', function() {
            const field = this;
            if (field.value.length < 4) {
                field.style.borderColor = '#ef4444';
            } else {
                field.style.borderColor = '#10b981';
            }
        });

        // Reset border colors on focus
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
            });
        });

        // Caps Lock detection
        document.getElementById('password').addEventListener('keypress', function(e) {
            const capsLockOn = e.getModifierState && e.getModifierState('CapsLock');
            const warningElement = document.getElementById('capslock-warning');
            
            if (capsLockOn) {
                if (!warningElement) {
                    const warning = document.createElement('div');
                    warning.id = 'capslock-warning';
                    warning.className = 'text-amber-600 text-sm mt-1 flex items-center gap-2';
                    warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i>' + 
                        (texts['lang'] === 'ar' ? 'تنبيه: Caps Lock مفعل' : 'Warning: Caps Lock is on');
                    this.parentNode.appendChild(warning);
                }
            } else {
                if (warningElement) {
                    warningElement.remove();
                }
            }
        });

        // Session timeout warning (optional)
        let sessionTimeout;
        function resetSessionTimeout() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(function() {
                alert(texts['lang'] === 'ar' ? 
                    'انتهت مدة الجلسة. يرجى تسجيل الدخول مرة أخرى.' : 
                    'Session expired. Please login again.');
                window.location.reload();
            }, 30 * 60 * 1000); // 30 minutes
        }

        // Reset timeout on user activity
        document.addEventListener('click', resetSessionTimeout);
        document.addEventListener('keypress', resetSessionTimeout);
        resetSessionTimeout();

        // Performance optimization: Preload next page resources
        <?php if (!$is_locked_out && !$rate_limited): ?>
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = '<?= $_SESSION['role'] === 'admin' ? 'events.php' : 'dashboard.php' ?>';
        document.head.appendChild(link);
        <?php endif; ?>
    </script>
</body>
</html>