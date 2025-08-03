<?php
// guests.php - Enhanced with search, filters, and improved import for large guest lists
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
        'manage_guests' => 'إدارة ضيوف',
        'back_to_events' => 'العودة للحفلات',
        'logout' => 'تسجيل الخروج',
        'add_guest_manually' => 'إضافة ضيف يدوياً',
        'import_guest_list' => 'استيراد قائمة ضيوف (CSV)',
        'current_guest_list' => 'قائمة الضيوف الحالية',
        'guest_name' => 'اسم الضيف',
        'phone_number' => 'رقم الهاتف',
        'guests_count' => 'عدد الضيوف',
        'table_number' => 'رقم الطاولة',
        'assigned_location' => 'الموقع المخصص',
        'guest_status' => 'حالة الدعوة',
        'checkin_status' => 'حالة الحضور',
        'actions' => 'إجراءات',
        'add' => 'إضافة',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'save_changes' => 'حفظ التعديلات',
        'cancel' => 'إلغاء',
        'search_guests' => 'ابحث في الضيوف...',
        'filter_by_status' => 'فلترة حسب الحالة',
        'all_statuses' => 'كل الحالات',
        'confirmed' => 'مؤكد الحضور',
        'canceled' => 'معتذر',
        'pending' => 'في الانتظار',
        'checked_in' => 'حضر الحفل',
        'not_checked_in' => 'لم يحضر',
        'filter_by_checkin' => 'فلترة حسب الحضور',
        'all_checkin_status' => 'كل حالات الحضور',
        'export_guests' => 'تصدير الضيوف',
        'bulk_actions' => 'إجراءات جماعية',
        'select_all' => 'تحديد الكل',
        'delete_selected' => 'حذف المحدد',
        'send_invitations' => 'إرسال دعوات',
        'edit_guest_data' => 'تعديل بيانات الضيف',
        'guest_added_success' => 'تمت إضافة الضيف بنجاح.',
        'guest_updated_success' => 'تم تحديث بيانات الضيف بنجاح.',
        'guest_deleted_success' => 'تم حذف الضيف بنجاح.',
        'guests_deleted_success' => 'تم حذف الضيوف المحددين بنجاح.',
        'import_completed' => 'اكتمل الاستيراد: تمت إضافة {success} ضيوف بنجاح. فشل {failed}.',
        'error_adding_guest' => 'خطأ في إضافة الضيف.',
        'error_updating_guest' => 'خطأ في تحديث البيانات.',
        'error_deleting_guest' => 'خطأ في حذف الضيف.',
        'error_file_upload' => 'حدث خطأ أثناء رفع الملف.',
        'csv_format_help' => 'ارفع ملف CSV بالأعمدة التالية بالترتيب: `name_ar`, `phone_number`, `guests_count`, `table_number`, `assigned_location`',
        'csv_sample_download' => 'تحميل ملف نموذجي',
        'showing_results' => 'عرض {count} من {total} ضيف',
        'no_guests_found' => 'لا يوجد ضيوف يطابقون البحث.',
        'confirm_delete_guest' => 'هل أنت متأكد من حذف هذا الضيف؟',
        'confirm_delete_selected' => 'هل أنت متأكد من حذف الضيوف المحددين؟',
        'processing' => 'جاري المعالجة...',
        'total_guests' => 'إجمالي الضيوف',
        'confirmed_guests' => 'مؤكدين',
        'pending_guests' => 'في الانتظار',
        'canceled_guests' => 'معتذرين',
        'checked_in_guests' => 'حضروا',
        'operation_successful' => 'تمت العملية بنجاح!',
        'operation_failed' => 'فشلت العملية!'
    ],
    'en' => [
        'manage_guests' => 'Manage Guests',
        'back_to_events' => 'Back to Events',
        'logout' => 'Logout',
        'add_guest_manually' => 'Add Guest Manually',
        'import_guest_list' => 'Import Guest List (CSV)',
        'current_guest_list' => 'Current Guest List',
        'guest_name' => 'Guest Name',
        'phone_number' => 'Phone Number',
        'guests_count' => 'Guests Count',
        'table_number' => 'Table Number',
        'assigned_location' => 'Assigned Location',
        'guest_status' => 'Invitation Status',
        'checkin_status' => 'Check-in Status',
        'actions' => 'Actions',
        'add' => 'Add',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save_changes' => 'Save Changes',
        'cancel' => 'Cancel',
        'search_guests' => 'Search guests...',
        'filter_by_status' => 'Filter by Status',
        'all_statuses' => 'All Statuses',
        'confirmed' => 'Confirmed',
        'canceled' => 'Canceled',
        'pending' => 'Pending',
        'checked_in' => 'Checked In',
        'not_checked_in' => 'Not Checked In',
        'filter_by_checkin' => 'Filter by Check-in',
        'all_checkin_status' => 'All Check-in Status',
        'export_guests' => 'Export Guests',
        'bulk_actions' => 'Bulk Actions',
        'select_all' => 'Select All',
        'delete_selected' => 'Delete Selected',
        'send_invitations' => 'Send Invitations',
        'edit_guest_data' => 'Edit Guest Data',
        'guest_added_success' => 'Guest added successfully.',
        'guest_updated_success' => 'Guest updated successfully.',
        'guest_deleted_success' => 'Guest deleted successfully.',
        'guests_deleted_success' => 'Selected guests deleted successfully.',
        'import_completed' => 'Import completed: {success} guests added successfully. {failed} failed.',
        'error_adding_guest' => 'Error adding guest.',
        'error_updating_guest' => 'Error updating guest data.',
        'error_deleting_guest' => 'Error deleting guest.',
        'error_file_upload' => 'Error occurred while uploading file.',
        'csv_format_help' => 'Upload CSV file with columns in order: `name_ar`, `phone_number`, `guests_count`, `table_number`, `assigned_location`',
        'csv_sample_download' => 'Download Sample File',
        'showing_results' => 'Showing {count} of {total} guests',
        'no_guests_found' => 'No guests match the search criteria.',
        'confirm_delete_guest' => 'Are you sure you want to delete this guest?',
        'confirm_delete_selected' => 'Are you sure you want to delete selected guests?',
        'processing' => 'Processing...',
        'total_guests' => 'Total Guests',
        'confirmed_guests' => 'Confirmed',
        'pending_guests' => 'Pending',
        'canceled_guests' => 'Canceled',
        'checked_in_guests' => 'Checked In',
        'operation_successful' => 'Operation successful!',
        'operation_failed' => 'Operation failed!'
    ]
];

$t = $texts[$lang];

// Security & Permission Check
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

// --- Generate CSV Sample ---
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="guest_import_sample.csv"');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    
    // CSV Headers
    fputcsv($output, ['name_ar', 'phone_number', 'guests_count', 'table_number', 'assigned_location'], ',');
    
    // Sample data
    $sample_data = [
        ['أحمد محمد', '+962791234567', '2', '1', 'القاعة الرئيسية'],
        ['فاطمة علي', '+962791234568', '1', '2', 'القاعة الرئيسية'],
        ['محمود خالد', '+962791234569', '3', '3', 'القاعة الجانبية'],
        ['Sara Smith', '+1234567890', '2', '4', 'Main Hall'],
        ['علي حسن', '', '1', '5', 'القاعة الرئيسية']
    ];
    
    foreach ($sample_data as $row) {
        fputcsv($output, $row, ',');
    }
    
    fclose($output);
    exit;
}

// --- POST Request Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['switch_language'])) {
    // CSRF Check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security token mismatch.'; $messageType = 'error';
    } else {
        // Handle guest actions
        if (isset($_POST['guest_action'])) {
            $action = $_POST['guest_action'];
            $name_ar = trim($_POST['name_ar'] ?? '');
            $phone_number = trim($_POST['phone_number'] ?? '');
            $guests_count = filter_input(INPUT_POST, 'guests_count', FILTER_VALIDATE_INT) ?: 1;
            $table_number = trim($_POST['table_number'] ?? '');
            $assigned_location = trim($_POST['assigned_location'] ?? '');
            $guest_db_id = filter_input(INPUT_POST, 'guest_db_id', FILTER_VALIDATE_INT);
            $status = $_POST['status'] ?? 'pending';
            
            switch ($action) {
                case 'add':
                    if (!empty($name_ar)) {
                        $guest_id = substr(md5(uniqid(rand(), true)), 0, 4);
                        $stmt = $mysqli->prepare("INSERT INTO guests (event_id, guest_id, name_ar, phone_number, guests_count, table_number, assigned_location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssises", $event_id, $guest_id, $name_ar, $phone_number, $guests_count, $table_number, $assigned_location, $status);
                        if ($stmt->execute()) { 
                            $message = $t['guest_added_success']; $messageType = 'success'; 
                        } else { 
                            $message = $t['error_adding_guest']; $messageType = 'error'; 
                        }
                        $stmt->close();
                    }
                    break;
                    
                case 'edit':
                    if (!empty($name_ar) && !empty($guest_db_id)) {
                        $stmt = $mysqli->prepare("UPDATE guests SET name_ar = ?, phone_number = ?, guests_count = ?, table_number = ?, assigned_location = ?, status = ? WHERE id = ? AND event_id = ?");
                        $stmt->bind_param("ssisssii", $name_ar, $phone_number, $guests_count, $table_number, $assigned_location, $status, $guest_db_id, $event_id);
                        if ($stmt->execute()) { 
                            $message = $t['guest_updated_success']; $messageType = 'success'; 
                        } else { 
                            $message = $t['error_updating_guest']; $messageType = 'error'; 
                        }
                        $stmt->close();
                    }
                    break;
                    
                case 'bulk_delete':
                    $selected_ids = $_POST['selected_guests'] ?? [];
                    if (!empty($selected_ids) && is_array($selected_ids)) {
                        $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                        $stmt = $mysqli->prepare("DELETE FROM guests WHERE id IN ($placeholders) AND event_id = ?");
                        $params = array_merge($selected_ids, [$event_id]);
                        $types = str_repeat('i', count($selected_ids)) . 'i';
                        $stmt->bind_param($types, ...$params);
                        if ($stmt->execute()) { 
                            $message = $t['guests_deleted_success']; $messageType = 'success'; 
                        } else { 
                            $message = $t['error_deleting_guest']; $messageType = 'error'; 
                        }
                        $stmt->close();
                    }
                    break;
            }
            header("Location: guests.php?event_id=$event_id&message=" . urlencode($message) . "&messageType=$messageType");
            exit;
        }
    }
}

// Handle Delete Guest (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $guest_db_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $stmt = $mysqli->prepare("DELETE FROM guests WHERE id = ? AND event_id = ?");
    $stmt->bind_param("ii", $guest_db_id, $event_id);
    if ($stmt->execute()) { 
        $message = $t['guest_deleted_success']; $messageType = 'success'; 
    } else { 
        $message = $t['error_deleting_guest']; $messageType = 'error'; 
    }
    $stmt->close();
    header("Location: guests.php?event_id=$event_id&message=" . urlencode($message) . "&messageType=$messageType");
    exit;
}

// Handle CSV Import with improved processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $success_count = 0; 
        $error_count = 0;
        $line_number = 0;
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $stmt = $mysqli->prepare("INSERT INTO guests (event_id, guest_id, name_ar, phone_number, guests_count, table_number, assigned_location, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $line_number++;
            
            // Skip empty rows
            if (empty(array_filter($data))) continue;
            
            $guest_id = substr(md5(uniqid(rand() . $line_number, true)), 0, 4);
            $name_ar = trim($data[0] ?? '');
            $phone_number = trim($data[1] ?? '');
            $guests_count = !empty($data[2]) ? intval($data[2]) : 1;
            $table_number = trim($data[3] ?? '');
            $assigned_location = trim($data[4] ?? '');
            
            // Validate required fields
            if (!empty($name_ar)) {
                $stmt->bind_param("isssises", $event_id, $guest_id, $name_ar, $phone_number, $guests_count, $table_number, $assigned_location);
                if ($stmt->execute()) { 
                    $success_count++; 
                } else { 
                    $error_count++;
                    error_log("CSV Import Error - Line $line_number: " . $mysqli->error);
                }
            } else {
                $error_count++;
            }
        }
        
        $stmt->close(); 
        fclose($handle);
        
        $message = str_replace(['{success}', '{failed}'], [$success_count, $error_count], $t['import_completed']);
        $messageType = $success_count > 0 ? 'success' : 'error';
    } else { 
        $message = $t['error_file_upload']; $messageType = 'error'; 
    }
    header("Location: guests.php?event_id=$event_id&message=" . urlencode($message) . "&messageType=$messageType");
    exit;
}

// --- Data Fetching ---
$event_name = '';
$stmt_event = $mysqli->prepare("SELECT event_name FROM events WHERE id = ?");
$stmt_event->bind_param("i", $event_id);
if ($stmt_event->execute()) {
    $result = $stmt_event->get_result();
    if ($row = $result->fetch_assoc()) { $event_name = $row['event_name']; }
}
$stmt_event->close();

// Get filters from URL
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$checkin_filter = $_GET['checkin'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50; // Show 50 guests per page
$offset = ($page - 1) * $per_page;

// Build query with filters
$where_conditions = ["event_id = ?"];
$params = [$event_id];
$types = "i";

if (!empty($search)) {
    $where_conditions[] = "(name_ar LIKE ? OR phone_number LIKE ? OR table_number LIKE ? OR assigned_location LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($checkin_filter)) {
    if ($checkin_filter === 'checked_in') {
        $where_conditions[] = "checkin_status = 'checked_in'";
    } else {
        $where_conditions[] = "(checkin_status IS NULL OR checkin_status != 'checked_in')";
    }
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM guests WHERE $where_clause";
$stmt_count = $mysqli->prepare($count_sql);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_guests = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

// Get guests with pagination
$sql = "SELECT id, guest_id, name_ar, phone_number, guests_count, table_number, assigned_location, status, checkin_status 
        FROM guests WHERE $where_clause 
        ORDER BY name_ar ASC 
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$stmt_guests = $mysqli->prepare($sql);
$stmt_guests->bind_param($types, ...$params);
$stmt_guests->execute();
$result = $stmt_guests->get_result();
$guests = $result->fetch_all(MYSQLI_ASSOC);
$stmt_guests->close();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
    SUM(CASE WHEN status NOT IN ('confirmed', 'canceled') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN checkin_status = 'checked_in' THEN 1 ELSE 0 END) as checked_in
    FROM guests WHERE event_id = ?";
$stmt_stats = $mysqli->prepare($stats_sql);
$stmt_stats->bind_param("i", $event_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();

if (isset($_GET['message'])) {
    $message = htmlspecialchars(urldecode($_GET['message']));
    $messageType = htmlspecialchars($_GET['messageType']);
}

$total_pages = ceil($total_guests / $per_page);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['manage_guests'] ?>: <?= htmlspecialchars($event_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background-color: #f0f2f5; 
        }
        .container { 
            max-width: 1400px; 
            margin: 20px auto; 
            background-color: #ffffff; 
            border-radius: 12px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
            padding: 30px; 
        }
        .wosuol-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .wosuol-icon {
            width: 35px;
            height: 35px;
            background: #4f46e5;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }
        .wosuol-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e40af;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            padding-bottom: 20px; 
            border-bottom: 1px solid #eee; 
        }
        .header-buttons { display: flex; gap: 12px; align-items: center; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }
        .stat-card {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .filters-bar {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal.active { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }
        .modal-content { 
            background-color: white; 
            padding: 30px; 
            border-radius: 15px; 
            width: 90%; 
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .guest-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .guest-table th, .guest-table td { 
            border: 1px solid #ddd; 
            padding: 12px 8px; 
            text-align: <?= $lang === 'ar' ? 'right' : 'left' ?>; 
            font-size: 0.9rem;
        }
        .guest-table th { 
            background-color: #f8f9fa; 
            font-weight: 600; 
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .guest-table tbody tr:hover { background-color: #f8f9fa; }
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
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 10px; 
            border: 2px solid #e5e7eb; 
            border-radius: 8px; 
            font-size: 16px;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
        .btn-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }
        .btn-small { padding: 6px 10px; font-size: 12px; }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            text-decoration: none;
            color: #374151;
            transition: all 0.2s;
        }
        .pagination a:hover { background-color: #3b82f6; color: white; border-color: #3b82f6; }
        .pagination .current { background-color: #3b82f6; color: white; border-color: #3b82f6; }
        .bulk-actions {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background-color: #fef3c7;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .bulk-actions.active { display: flex; }
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .footer { 
            margin-top: 3rem; 
            padding-top: 2rem; 
            border-top: 1px solid #e5e7eb; 
            text-align: center; 
        }
        .success-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        .success-toast.show {
            transform: translateX(0);
        }
        .error-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        .error-toast.show {
            transform: translateX(0);
        }
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filters-bar { grid-template-columns: 1fr; }
            .header { flex-direction: column; gap: 15px; text-align: center; }
            .guest-table { font-size: 0.8rem; }
            .guest-table th, .guest-table td { padding: 8px 4px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo Header -->
        <div class="wosuol-logo">
            <div class="wosuol-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="wosuol-text">وصول</div>
        </div>

        <div class="header">
            <h1 class="text-3xl font-bold text-gray-800"><?= $t['manage_guests'] ?>: <?= htmlspecialchars($event_name) ?></h1>
            <div class="header-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg border border-gray-300 transition-colors">
                        <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                    </button>
                </form>
                <a href="events.php" class="btn btn-secondary"><?= $t['back_to_events'] ?></a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?= $stats['total'] ?></span>
                <div class="stat-label"><?= $t['total_guests'] ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $stats['confirmed'] ?></span>
                <div class="stat-label"><?= $t['confirmed_guests'] ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $stats['pending'] ?></span>
                <div class="stat-label"><?= $t['pending_guests'] ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $stats['canceled'] ?></span>
                <div class="stat-label"><?= $t['canceled_guests'] ?></div>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?= $stats['checked_in'] ?></span>
                <div class="stat-label"><?= $t['checked_in_guests'] ?></div>
            </div>
        </div>

        <!-- Toast Messages -->
        <?php if ($message): ?>
            <div id="toast" class="<?= $messageType === 'success' ? 'success-toast' : 'error-toast' ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Add Guest & Import Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Add Guest Manually -->
            <div class="p-6 bg-blue-50 border border-blue-100 rounded-lg">
                <h3 class="text-lg font-semibold mb-4 text-blue-800"><?= $t['add_guest_manually'] ?></h3>
                <form method="POST" action="guests.php?event_id=<?= $event_id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="guest_action" value="add">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label><?= $t['guest_name'] ?>:</label>
                            <input type="text" name="name_ar" required>
                        </div>
                        <div class="form-group">
                            <label><?= $t['phone_number'] ?>:</label>
                            <input type="text" name="phone_number">
                        </div>
                        <div class="form-group">
                            <label><?= $t['guests_count'] ?>:</label>
                            <input type="number" name="guests_count" value="1" min="1">
                        </div>
                        <div class="form-group">
                            <label><?= $t['table_number'] ?>:</label>
                            <input type="text" name="table_number">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $t['assigned_location'] ?>:</label>
                        <input type="text" name="assigned_location" placeholder="القاعة الرئيسية / Main Hall">
                    </div>
                    <div class="form-group">
                        <label><?= $t['guest_status'] ?>:</label>
                        <select name="status">
                            <option value="pending"><?= $t['pending'] ?></option>
                            <option value="confirmed"><?= $t['confirmed'] ?></option>
                            <option value="canceled"><?= $t['canceled'] ?></option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-full"><?= $t['add'] ?></button>
                </form>
            </div>

            <!-- Import CSV -->
            <div class="p-6 bg-green-50 border border-green-100 rounded-lg">
                <h3 class="text-lg font-semibold mb-4 text-green-800"><?= $t['import_guest_list'] ?></h3>
                <p class="text-sm text-gray-600 mb-4"><?= $t['csv_format_help'] ?></p>
                <div class="mb-4">
                    <a href="?event_id=<?= $event_id ?>&download_sample=1" 
                       class="btn btn-secondary btn-small"><?= $t['csv_sample_download'] ?></a>
                </div>
                <form method="POST" action="guests.php?event_id=<?= $event_id ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="form-group">
                        <input type="file" name="csv_file" accept=".csv" required 
                               class="w-full p-2 border-2 border-dashed border-green-300 rounded-lg">
                    </div>
                    <button type="submit" class="btn btn-success w-full" 
                            onclick="this.innerHTML='<?= $t['processing'] ?>';"><?= $t['import_guest_list'] ?></button>
                </form>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="filters-bar">
            <div class="form-group mb-0">
                <input type="text" id="search-input" placeholder="<?= $t['search_guests'] ?>" 
                       value="<?= htmlspecialchars($search) ?>"
                       class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500">
            </div>
            <div class="form-group mb-0">
                <select id="status-filter" class="w-full p-3 border-2 border-gray-300 rounded-lg">
                    <option value=""><?= $t['all_statuses'] ?></option>
                    <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>><?= $t['confirmed'] ?></option>
                    <option value="canceled" <?= $status_filter === 'canceled' ? 'selected' : '' ?>><?= $t['canceled'] ?></option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>><?= $t['pending'] ?></option>
                </select>
            </div>
            <div class="form-group mb-0">
                <select id="checkin-filter" class="w-full p-3 border-2 border-gray-300 rounded-lg">
                    <option value=""><?= $t['all_checkin_status'] ?></option>
                    <option value="checked_in" <?= $checkin_filter === 'checked_in' ? 'selected' : '' ?>><?= $t['checked_in'] ?></option>
                    <option value="not_checked_in" <?= $checkin_filter === 'not_checked_in' ? 'selected' : '' ?>><?= $t['not_checked_in'] ?></option>
                </select>
            </div>
            <div class="form-group mb-0">
                <button onclick="clearFilters()" class="btn btn-secondary w-full">مسح الفلاتر</button>
            </div>
        </div>

        <!-- Results Info -->
        <div class="flex justify-between items-center mb-4">
            <p class="text-gray-600">
                <?= str_replace(['{count}', '{total}'], [$total_guests, $stats['total']], $t['showing_results']) ?>
            </p>
            <div class="flex gap-2">
                <button onclick="toggleBulkActions()" class="btn btn-warning btn-small"><?= $t['bulk_actions'] ?></button>
                <a href="guests.php?event_id=<?= $event_id ?>&export=csv" class="btn btn-success btn-small"><?= $t['export_guests'] ?></a>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulk-actions-bar" class="bulk-actions">
            <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes()">
            <label for="select-all" class="font-medium"><?= $t['select_all'] ?></label>
            <span id="selected-count" class="text-gray-600">(0 محدد)</span>
            <form method="POST" style="display: inline;" onsubmit="return confirmBulkDelete();">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="guest_action" value="bulk_delete">
                <input type="hidden" name="selected_guests" id="selected-guests-input">
                <button type="submit" class="btn btn-danger btn-small"><?= $t['delete_selected'] ?></button>
            </form>
        </div>

        <!-- Guests Table -->
        <div class="table-container">
            <table class="guest-table">
                <thead>
                    <tr>
                        <th width="40px"><input type="checkbox" id="header-checkbox" style="display: none;"></th>
                        <th><?= $t['guest_name'] ?></th>
                        <th><?= $t['phone_number'] ?></th>
                        <th width="80px"><?= $t['guests_count'] ?></th>
                        <th width="100px"><?= $t['table_number'] ?></th>
                        <th width="120px"><?= $t['assigned_location'] ?></th>
                        <th width="120px"><?= $t['guest_status'] ?></th>
                        <th width="120px"><?= $t['checkin_status'] ?></th>
                        <th width="140px"><?= $t['actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($guests)): ?>
                        <tr>
                            <td colspan="9" class="text-center p-8 text-gray-500">
                                <?= $t['no_guests_found'] ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($guests as $guest): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="guest-checkbox" value="<?= $guest['id'] ?>" style="display: none;">
                            </td>
                            <td class="font-medium"><?= htmlspecialchars($guest['name_ar']) ?></td>
                            <td><?= htmlspecialchars($guest['phone_number']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($guest['guests_count']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($guest['table_number']) ?></td>
                            <td class="text-center"><?= htmlspecialchars($guest['assigned_location']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $guest['status'] ?>">
                                    <?php
                                    switch($guest['status']) {
                                        case 'confirmed': echo $t['confirmed']; break;
                                        case 'canceled': echo $t['canceled']; break;
                                        default: echo $t['pending']; break;
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge checkin-<?= $guest['checkin_status'] === 'checked_in' ? 'checked_in' : 'not' ?>">
                                    <?= $guest['checkin_status'] === 'checked_in' ? $t['checked_in'] : $t['not_checked_in'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-1">
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($guest), ENT_QUOTES) ?>)" 
                                            class="btn btn-warning btn-small"><?= $t['edit'] ?></button>
                                    <a href="guests.php?event_id=<?= $event_id ?>&action=delete&id=<?= $guest['id'] ?>" 
                                       onclick="return confirm('<?= $t['confirm_delete_guest'] ?>')" 
                                       class="btn btn-danger btn-small"><?= $t['delete'] ?></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?event_id=<?= $event_id ?>&page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&checkin=<?= urlencode($checkin_filter) ?>">‹ السابق</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?event_id=<?= $event_id ?>&page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&checkin=<?= urlencode($checkin_filter) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?event_id=<?= $event_id ?>&page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&checkin=<?= urlencode($checkin_filter) ?>">التالي ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Footer with Logo and Copyright -->
        <div class="footer">
            <div class="wosuol-logo justify-center mb-4">
                <div class="wosuol-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="wosuol-text">وصول</div>
            </div>
            <p class="text-gray-500 text-sm">
                &copy; <?= date('Y') ?> <a href="https://wosuol.com" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium">وصول - Wosuol.com</a> - جميع الحقوق محفوظة
            </p>
        </div>
    </div>

    <!-- Edit Guest Modal -->
    <div id="editGuestModal" class="modal">
        <div class="modal-content">
            <h2 class="text-2xl font-bold mb-6 text-gray-800"><?= $t['edit_guest_data'] ?></h2>
            <form method="POST" action="guests.php?event_id=<?= $event_id ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="guest_action" value="edit">
                <input type="hidden" name="guest_db_id" id="edit_guest_db_id">
                
                <div class="form-group">
                    <label><?= $t['guest_name'] ?>:</label>
                    <input type="text" name="name_ar" id="edit_name_ar" required>
                </div>
                <div class="form-group">
                    <label><?= $t['phone_number'] ?>:</label>
                    <input type="text" name="phone_number" id="edit_phone_number">
                </div>
                <div class="form-group">
                    <label><?= $t['guests_count'] ?>:</label>
                    <input type="number" name="guests_count" id="edit_guests_count" min="1">
                </div>
                <div class="form-group">
                    <label><?= $t['table_number'] ?>:</label>
                    <input type="text" name="table_number" id="edit_table_number">
                </div>
                <div class="form-group">
                    <label><?= $t['assigned_location'] ?>:</label>
                    <input type="text" name="assigned_location" id="edit_assigned_location">
                </div>
                <div class="form-group">
                    <label><?= $t['guest_status'] ?>:</label>
                    <select name="status" id="edit_status">
                        <option value="pending"><?= $t['pending'] ?></option>
                        <option value="confirmed"><?= $t['confirmed'] ?></option>
                        <option value="canceled"><?= $t['canceled'] ?></option>
                    </select>
                </div>
                
                <div class="flex justify-end gap-4 mt-6">
                    <button type="button" onclick="closeEditModal()" 
                            class="btn btn-secondary"><?= $t['cancel'] ?></button>
                    <button type="submit" class="btn btn-primary"><?= $t['save_changes'] ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        let bulkActionsVisible = false;
        let selectedGuests = [];

        // Show toast messages
        <?php if ($message): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('toast');
            if (toast) {
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            }
        });
        <?php endif; ?>

        // Search and Filter functionality
        let searchTimeout;
        document.getElementById('search-input').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFilters, 800); // Increased delay for better performance
        });
        document.getElementById('status-filter').addEventListener('change', applyFilters);
        document.getElementById('checkin-filter').addEventListener('change', applyFilters);

        function applyFilters() {
            const search = document.getElementById('search-input').value.trim();
            const status = document.getElementById('status-filter').value;
            const checkin = document.getElementById('checkin-filter').value;
            
            const url = new URL(window.location);
            if (search) url.searchParams.set('search', search);
            else url.searchParams.delete('search');
            
            if (status) url.searchParams.set('status', status);
            else url.searchParams.delete('status');
            
            if (checkin) url.searchParams.set('checkin', checkin);
            else url.searchParams.delete('checkin');
            
            url.searchParams.set('page', '1'); // Reset to first page
            
            window.location.href = url.toString();
        }

        function clearFilters() {
            const url = new URL(window.location);
            url.searchParams.delete('search');
            url.searchParams.delete('status');
            url.searchParams.delete('checkin');
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        // Bulk Actions
        function toggleBulkActions() {
            bulkActionsVisible = !bulkActionsVisible;
            const bulkBar = document.getElementById('bulk-actions-bar');
            const checkboxes = document.querySelectorAll('.guest-checkbox');
            const headerCheckbox = document.getElementById('header-checkbox');
            
            if (bulkActionsVisible) {
                bulkBar.classList.add('active');
                checkboxes.forEach(cb => cb.style.display = 'block');
                headerCheckbox.style.display = 'block';
            } else {
                bulkBar.classList.remove('active');
                checkboxes.forEach(cb => {
                    cb.style.display = 'none';
                    cb.checked = false;
                });
                headerCheckbox.style.display = 'none';
                headerCheckbox.checked = false;
                selectedGuests = [];
                updateSelectedCount();
            }
        }

        function toggleAllCheckboxes() {
            const headerCheckbox = document.getElementById('header-checkbox');
            const checkboxes = document.querySelectorAll('.guest-checkbox');
            
            checkboxes.forEach(cb => {
                cb.checked = headerCheckbox.checked;
            });
            updateSelectedGuestsList();
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('guest-checkbox')) {
                updateSelectedGuestsList();
            }
        });

        function updateSelectedGuestsList() {
            const checkboxes = document.querySelectorAll('.guest-checkbox:checked');
            selectedGuests = Array.from(checkboxes).map(cb => parseInt(cb.value));
            updateSelectedCount();
            
            // Create array for form submission
            const hiddenInput = document.getElementById('selected-guests-input');
            if (hiddenInput) {
                hiddenInput.value = JSON.stringify(selectedGuests);
            }
        }

        function updateSelectedCount() {
            const countEl = document.getElementById('selected-count');
            if (countEl) {
                countEl.textContent = `(${selectedGuests.length} محدد)`;
            }
        }

        function confirmBulkDelete() {
            if (selectedGuests.length === 0) {
                alert('يرجى تحديد الضيوف المراد حذفهم');
                return false;
            }
            return confirm(`${texts['confirm_delete_selected']}\n\nسيتم حذف ${selectedGuests.length} ضيف.`);
        }

        // Edit Modal
        function openEditModal(guest) {
            document.getElementById('edit_guest_db_id').value = guest.id;
            document.getElementById('edit_name_ar').value = guest.name_ar || '';
            document.getElementById('edit_phone_number').value = guest.phone_number || '';
            document.getElementById('edit_guests_count').value = guest.guests_count || 1;
            document.getElementById('edit_table_number').value = guest.table_number || '';
            document.getElementById('edit_assigned_location').value = guest.assigned_location || '';
            document.getElementById('edit_status').value = guest.status || 'pending';
            
            document.getElementById('editGuestModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editGuestModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('editGuestModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        // Handle bulk selection properly
        document.addEventListener('DOMContentLoaded', function() {
            // Fix bulk actions form submission
            const bulkForm = document.querySelector('form[onsubmit*="confirmBulkDelete"]');
            if (bulkForm) {
                bulkForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (selectedGuests.length === 0) {
                        alert('يرجى تحديد الضيوف المراد حذفهم');
                        return false;
                    }
                    
                    if (confirm(`${texts['confirm_delete_selected']}\n\nسيتم حذف ${selectedGuests.length} ضيف.`)) {
                        // Create form data properly
                        const formData = new FormData();
                        formData.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token']) ?>');
                        formData.append('guest_action', 'bulk_delete');
                        
                        // Add each selected guest ID individually
                        selectedGuests.forEach(id => {
                            formData.append('selected_guests[]', id);
                        });
                        
                        // Submit with fetch
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        }).then(response => {
                            if (response.ok) {
                                window.location.reload();
                            } else {
                                alert('حدث خطأ أثناء الحذف');
                            }
                        }).catch(error => {
                            console.error('Error:', error);
                            alert('حدث خطأ أثناء الحذف');
                        });
                    }
                });
            }
        });

        // Auto-refresh page every 5 minutes to show latest changes
        setTimeout(() => {
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 300000);

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>