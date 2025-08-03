<?php
// languages.php - نظام اللغات المركزي
// استخدام: require_once 'languages.php';

/**
 * دالة الحصول على النصوص حسب اللغة
 * @param string $page اسم الصفحة (optional)
 * @return array
 */
function getTexts($page = 'common') {
    $texts = [
        'ar' => [
            // نصوص مشتركة
            'common' => [
                'save' => 'حفظ',
                'cancel' => 'إلغاء',
                'delete' => 'حذف',
                'edit' => 'تعديل',
                'add' => 'إضافة',
                'close' => 'إغلاق',
                'yes' => 'نعم',
                'no' => 'لا',
                'loading' => 'جاري التحميل...',
                'processing' => 'جاري المعالجة...',
                'success' => 'تم بنجاح',
                'error' => 'حدث خطأ',
                'warning' => 'تحذير',
                'info' => 'معلومات',
                'confirm' => 'تأكيد',
                'search' => 'بحث',
                'filter' => 'فلتر',
                'export' => 'تصدير',
                'import' => 'استيراد',
                'print' => 'طباعة',
                'download' => 'تحميل',
                'upload' => 'رفع',
                'send' => 'إرسال',
                'back' => 'رجوع',
                'next' => 'التالي',
                'previous' => 'السابق',
                'continue' => 'متابعة',
                'finish' => 'إنهاء',
                'name' => 'الاسم',
                'email' => 'البريد الإلكتروني',
                'phone' => 'الهاتف',
                'address' => 'العنوان',
                'date' => 'التاريخ',
                'time' => 'الوقت',
                'status' => 'الحالة',
                'active' => 'نشط',
                'inactive' => 'غير نشط',
                'enabled' => 'مفعل',
                'disabled' => 'معطل',
                'login' => 'تسجيل الدخول',
                'logout' => 'تسجيل الخروج',
                'register' => 'تسجيل',
                'username' => 'اسم المستخدم',
                'password' => 'كلمة المرور',
                'dashboard' => 'لوحة التحكم',
                'settings' => 'الإعدادات',
                'profile' => 'الملف الشخصي',
                'help' => 'مساعدة',
                'about' => 'حول',
                'contact' => 'اتصل بنا',
                'home' => 'الرئيسية',
            ],
            
            // نصوص صفحة RSVP
            'rsvp' => [
                'wedding_blessing' => 'بارك الله لهما وبارك عليهما وجمع بينهما بخير',
                'wedding_occasion' => 'وذلك بمناسبة حفل زفافهما المبارك',
                'location_in' => 'في',
                'guest_name_label' => 'السيد/ة',
                'guest_count' => 'عدد الضيوف',
                'table_number' => 'رقم الطاولة',
                'not_specified' => 'غير محدد',
                'confirm_attendance' => 'تأكيد الحضور',
                'decline_attendance' => 'إلغاء الحضور',
                'already_confirmed' => 'تم تأكيد حضورك مسبقاً',
                'already_declined' => 'تم تسجيل اعتذارك مسبقاً',
                'success_confirmed' => 'تم تأكيد حضورك بنجاح!',
                'success_declined' => 'تم تسجيل اعتذارك.',
                'error_occurred' => 'حدث خطأ، يرجى المحاولة مرة أخرى.',
                'connection_error' => 'خطأ في الاتصال، يرجى التحقق من الإنترنت.',
                'invalid_link' => 'رابط الدعوة غير صحيح أو منتهي الصلاحية.',
                'show_qr_instruction' => 'يرجى إظهار هذا الرمز عند الدخول',
                'download_qr' => 'تحميل رمز QR',
                'add_to_calendar' => 'إضافة للتقويم',
                'share_invitation' => 'مشاركة الدعوة',
                'view_location' => 'عرض الموقع',
                'guest_details' => 'تفاصيل الضيف',
                'welcome_guest' => 'أهلاً وسهلاً',
                'dear_guest' => 'ضيف كريم',
                'get_directions' => 'الحصول على الاتجاهات',
                'entry_card' => 'بطاقة الدخول',
                'qr_code' => 'رمز QR',
                'show_at_entrance' => 'يرجى إظهار هذا الرمز عند الدخول',
                'csrf_error' => 'خطأ في التحقق من صحة الطلب.',
                'rate_limit_error' => 'محاولات كثيرة جداً. يرجى الانتظار.',
            ],
            
            // نصوص صفحة التسجيل
            'register' => [
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
            ],
            
            // نصوص صفحة الدخول
            'login' => [
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
            
            // نصوص صفحة إدارة الأحداث
            'events' => [
                'event_management' => 'إدارة الحفلات',
                'create_new_event' => 'إنشاء حفل جديد',
                'event_name' => 'اسم الحفل',
                'create' => 'إنشاء',
                'current_events' => 'الحفلات الحالية',
                'event_date' => 'تاريخ الحفل',
                'actions' => 'إجراءات',
                'manage_guests' => 'إدارة الضيوف',
                'send_invitations' => 'إرسال الدعوات',
                'checkin' => 'تسجيل الدخول',
                'registration_link' => 'رابط التسجيل',
                'send_to_all' => 'إرسال لجميع ضيوف هذا الحفل',
                'send_to_selected' => 'إرسال لمحددين',
                'bulk_messaging' => 'رسائل جماعية',
                'global_send_all' => 'إرسال عام لكل الأحداث',
                'no_events' => 'لا توجد حفلات حالياً.',
                'event_created_success' => 'تم إنشاء الحفل بنجاح!',
                'event_deleted_success' => 'تم حذف الحفل وكل بياناته بنجاح.',
                'event_creation_error' => 'حدث خطأ أثناء إنشاء الحفل.',
                'event_deletion_error' => 'فشل حذف الحفل.',
                'enter_event_name' => 'الرجاء إدخال اسم للحفل.',
                'confirm_delete_event' => 'هل أنت متأكد؟ سيتم حذف الحفل وكل ضيوفه بشكل نهائي.',
                'messages_sent_success' => 'تم إرسال الرسائل بنجاح!',
                'global_messages_sent' => 'تم تشغيل إرسال الرسائل العام لجميع الأحداث.',
                'messaging_error' => 'حدث خطأ في إرسال الرسائل.',
            ],
        ],
        
        'en' => [
            // Common texts
            'common' => [
                'save' => 'Save',
                'cancel' => 'Cancel',
                'delete' => 'Delete',
                'edit' => 'Edit',
                'add' => 'Add',
                'close' => 'Close',
                'yes' => 'Yes',
                'no' => 'No',
                'loading' => 'Loading...',
                'processing' => 'Processing...',
                'success' => 'Success',
                'error' => 'Error',
                'warning' => 'Warning',
                'info' => 'Information',
                'confirm' => 'Confirm',
                'search' => 'Search',
                'filter' => 'Filter',
                'export' => 'Export',
                'import' => 'Import',
                'print' => 'Print',
                'download' => 'Download',
                'upload' => 'Upload',
                'send' => 'Send',
                'back' => 'Back',
                'next' => 'Next',
                'previous' => 'Previous',
                'continue' => 'Continue',
                'finish' => 'Finish',
                'name' => 'Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'address' => 'Address',
                'date' => 'Date',
                'time' => 'Time',
                'status' => 'Status',
                'active' => 'Active',
                'inactive' => 'Inactive',
                'enabled' => 'Enabled',
                'disabled' => 'Disabled',
                'login' => 'Login',
                'logout' => 'Logout',
                'register' => 'Register',
                'username' => 'Username',
                'password' => 'Password',
                'dashboard' => 'Dashboard',
                'settings' => 'Settings',
                'profile' => 'Profile',
                'help' => 'Help',
                'about' => 'About',
                'contact' => 'Contact',
                'home' => 'Home',
            ],
            
            // RSVP page texts
            'rsvp' => [
                'wedding_blessing' => 'May Allah bless them and unite them in goodness',
                'wedding_occasion' => 'On the occasion of their blessed wedding',
                'location_in' => 'at',
                'guest_name_label' => 'Mr./Mrs.',
                'guest_count' => 'Number of Guests',
                'table_number' => 'Table Number',
                'not_specified' => 'Not Specified',
                'confirm_attendance' => 'Confirm Attendance',
                'decline_attendance' => 'Decline Attendance',
                'already_confirmed' => 'Your attendance has already been confirmed',
                'already_declined' => 'Your decline has already been recorded',
                'success_confirmed' => 'Your attendance has been confirmed successfully!',
                'success_declined' => 'Your decline has been recorded.',
                'error_occurred' => 'An error occurred, please try again.',
                'connection_error' => 'Connection error, please check your internet.',
                'invalid_link' => 'Invalid or expired invitation link.',
                'show_qr_instruction' => 'Please show this code at the entrance',
                'download_qr' => 'Download QR Code',
                'add_to_calendar' => 'Add to Calendar',
                'share_invitation' => 'Share Invitation',
                'view_location' => 'View Location',
                'guest_details' => 'Guest Details',
                'welcome_guest' => 'Welcome',
                'dear_guest' => 'Dear Guest',
                'get_directions' => 'Get Directions',
                'entry_card' => 'Entry Card',
                'qr_code' => 'QR Code',
                'show_at_entrance' => 'Please show this code at the entrance',
                'csrf_error' => 'Security token mismatch.',
                'rate_limit_error' => 'Too many attempts. Please wait.',
            ],
            
            // Register page texts
            'register' => [
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
            ],
            
            // Login page texts
            'login' => [
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
            ],
            
            // Events page texts
            'events' => [
                'event_management' => 'Event Management',
                'create_new_event' => 'Create New Event',
                'event_name' => 'Event Name',
                'create' => 'Create',
                'current_events' => 'Current Events',
                'event_date' => 'Event Date',
                'actions' => 'Actions',
                'manage_guests' => 'Manage Guests',
                'send_invitations' => 'Send Invitations',
                'checkin' => 'Check-in',
                'registration_link' => 'Registration Link',
                'send_to_all' => 'Send to All Event Guests',
                'send_to_selected' => 'Send to Selected',
                'bulk_messaging' => 'Bulk Messaging',
                'global_send_all' => 'Global Send to All Events',
                'no_events' => 'No events currently available.',
                'event_created_success' => 'Event created successfully!',
                'event_deleted_success' => 'Event and all its data deleted successfully.',
                'event_creation_error' => 'Error occurred while creating event.',
                'event_deletion_error' => 'Failed to delete event.',
                'enter_event_name' => 'Please enter an event name.',
                'confirm_delete_event' => 'Are you sure? The event and all its guests will be permanently deleted.',
                'messages_sent_success' => 'Messages sent successfully!',
                'global_messages_sent' => 'Global messaging initiated for all events.',
                'messaging_error' => 'Error occurred while sending messages.',
            ],
        ]
    ];

    return $texts;
}

/**
 * دالة الحصول على النصوص لصفحة معينة
 * @param string $page
 * @param string $lang
 * @return array
 */
function getPageTexts($page, $lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $allTexts = getTexts();
    $pageTexts = $allTexts[$lang][$page] ?? [];
    $commonTexts = $allTexts[$lang]['common'] ?? [];
    
    // دمج النصوص المشتركة مع نصوص الصفحة
    return array_merge($commonTexts, $pageTexts);
}

/**
 * دالة الحصول على اللغة الحالية
 * @return string
 */
function getCurrentLanguage() {
    return $_SESSION['language'] ?? $_COOKIE['language'] ?? 'ar';
}

/**
 * دالة تغيير اللغة
 * @param string $lang
 */
function setLanguage($lang) {
    $lang = in_array($lang, ['ar', 'en']) ? $lang : 'ar';
    $_SESSION['language'] = $lang;
    setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/');
}

/**
 * دالة معالجة تغيير اللغة
 */
function handleLanguageSwitch() {
    if (isset($_POST['switch_language'])) {
        $newLang = $_POST['switch_language'] === 'en' ? 'en' : 'ar';
        setLanguage($newLang);
        
        // إعادة توجيه لنفس الصفحة
        $currentUrl = $_SERVER['REQUEST_URI'];
        header("Location: $currentUrl");
        exit;
    }
}

/**
 * دالة إنشاء زر تغيير اللغة
 * @param string $currentLang
 * @param string $csrfToken
 * @return string
 */
function getLanguageToggleButton($currentLang = null, $csrfToken = '') {
    if ($currentLang === null) {
        $currentLang = getCurrentLanguage();
    }
    
    $newLang = $currentLang === 'ar' ? 'en' : 'ar';
    $buttonText = $currentLang === 'ar' ? 'English' : 'العربية';
    
    $csrfInput = '';
    if (!empty($csrfToken)) {
        $csrfInput = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
    }
    
    return '
    <form method="POST" style="display: inline;">
        ' . $csrfInput . '
        <button type="submit" name="switch_language" value="' . $newLang . '" 
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg border border-gray-300 transition-colors">
            ' . $buttonText . '
        </button>
    </form>';
}

/**
 * دالة إنشاء خيارات HTML للدول (للاستخدام في صفحة التسجيل)
 * @param string $lang
 * @return array
 */
function getCountriesList($lang = 'ar') {
    $countries = [
        'ar' => [
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
        ],
        'en' => [
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
    ];
    
    return $countries[$lang] ?? $countries['ar'];
}

/**
 * دالة مساعدة للحصول على نص معين - تحقق من وجود الدالة أولاً
 * @param string $key
 * @param array $texts
 * @param string $default
 * @return string
 */
if (!function_exists('getText')) {
    function getText($key, $texts, $default = '') {
        return $texts[$key] ?? $default;
    }
}

/**
 * دالة آمنة لعرض HTML - تحقق من وجود الدالة أولاً
 * @param string $value
 * @param string $default
 * @return string
 */
if (!function_exists('safeHtml')) {
    function safeHtml($value, $default = '') {
        return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
    }
}
?>