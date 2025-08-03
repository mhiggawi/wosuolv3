<?php
// db_config.php
// ملف مركزي لتخزين إعدادات الاتصال بقاعدة البيانات

// --- قم بتعديل هذه المتغيرات لتطابق إعدادات قاعدة بياناتك ---

// اسم المضيف (عادة يكون 'localhost' إذا كان السيرفر على نفس الجهاز)
define('DB_SERVER', 'localhost');

// اسم مستخدم قاعدة البيانات
define('DB_USERNAME', 'u747253029_dbhijjawi'); // مثال: 'root' أو اسم المستخدم الخاص بك

// كلمة مرور مستخدم قاعدة البيانات
define('DB_PASSWORD', 'Hijjawi@1300**@'); // مثال: كلمة المرور الخاصة بك

// اسم قاعدة البيانات التي أنشأتها
define('DB_NAME', 'u747253029_wosuol'); // مثال: 'invitation_system'

// --- لا تقم بتعديل ما بعد هذا السطر ---

// إنشاء اتصال بقاعدة البيانات باستخدام MySQLi
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// التحقق من نجاح الاتصال
if ($mysqli->connect_error) {
    // إيقاف تنفيذ الكود وعرض رسالة خطأ واضحة إذا فشل الاتصال
    // هذا مهم جداً لتشخيص المشاكل
    die("ERROR: Could not connect. " . $mysqli->connect_error);
}

// تعيين ترميز الاتصال إلى utf8mb4 لضمان دعم اللغة العربية بشكل كامل
if (!$mysqli->set_charset("utf8mb4")) {
    // عرض خطأ إذا فشل تعيين الترميز
    printf("Error loading character set utf8mb4: %s\n", $mysqli->error);
    exit();
}

?>
