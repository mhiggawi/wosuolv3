<?php
// dashboard.php - Enhanced with languages and improved functionality
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
        'dashboard' => 'متابعة',
        'logout' => 'تسجيل الخروج',
        'back_to_events' => 'عودة للحفلات',
        'total_invited' => 'إجمالي المدعوين',
        'confirmed_attendance' => 'تأكيد الحضور',
        'checked_in_hall' => 'سجلوا الدخول الى القاعة',
        'declined_attendance' => 'إلغاء الحضور',
        'awaiting_response' => 'في انتظار الرد',
        'guest_list' => 'قائمة الضيوف',
        'export_report_csv' => 'تصدير تقرير (CSV)',
        'export_dashboard_pdf' => 'تصدير الداشبورد (PDF)',
        'refresh_data' => 'تحديث البيانات',
        'refreshing' => 'جاري التحديث...',
        'search_guest' => 'ابحث باسم الضيف...',
        'no_guests' => 'لا يوجد ضيوف',
        'error_fetching_data' => 'حدث خطأ في جلب البيانات',
        'table_number' => 'طاولة',
        'statistics_summary' => 'ملخص الإحصائيات',
        'guest_details' => 'تفاصيل الضيوف',
        'status_confirmed' => 'مؤكد',
        'status_declined' => 'معتذر',
        'status_pending' => 'في الانتظار',
        'status_checked_in' => 'حضر',
        'name' => 'الاسم',
        'phone' => 'الهاتف',
        'guests_count' => 'عدد الضيوف',
        'table' => 'الطاولة',
        'status' => 'الحالة',
        'checkin_status' => 'حالة الحضور'
    ],
    'en' => [
        'dashboard' => 'Dashboard',
        'logout' => 'Logout',
        'back_to_events' => 'Back to Events',
        'total_invited' => 'Total Invited',
        'confirmed_attendance' => 'Confirmed Attendance',
        'checked_in_hall' => 'Checked into Hall',
        'declined_attendance' => 'Declined Attendance',
        'awaiting_response' => 'Awaiting Response',
        'guest_list' => 'Guest List',
        'export_report_csv' => 'Export Report (CSV)',
        'export_dashboard_pdf' => 'Export Dashboard (PDF)',
        'refresh_data' => 'Refresh Data',
        'refreshing' => 'Refreshing...',
        'search_guest' => 'Search by guest name...',
        'no_guests' => 'No guests',
        'error_fetching_data' => 'Error fetching data',
        'table_number' => 'Table',
        'statistics_summary' => 'Statistics Summary',
        'guest_details' => 'Guest Details',
        'status_confirmed' => 'Confirmed',
        'status_declined' => 'Declined',
        'status_pending' => 'Pending',
        'status_checked_in' => 'Checked In',
        'name' => 'Name',
        'phone' => 'Phone',
        'guests_count' => 'Guests Count',
        'table' => 'Table',
        'status' => 'Status',
        'checkin_status' => 'Check-in Status'
    ]
];

$t = $texts[$lang];

// --- Security Check & Permission Logic ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    if ($_SESSION['role'] === 'admin') { header('Location: events.php'); exit; } 
    else { die('Access Denied: Event ID is required.'); }
}
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'viewer') {
    die('Access Denied: You do not have permission to view this event dashboard.');
}
if ($_SESSION['role'] === 'viewer' && $event_id != ($_SESSION['event_id_access'] ?? null)) {
    die('Access Denied: You do not have permission to view this event dashboard.');
}

// --- CSV Export Logic ---
if (isset($_GET['export_csv']) && $_GET['export_csv'] === 'true') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="guest_report_event_'.$event_id.'_'.date('Y-m-d').'.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV Headers based on language
    $csv_headers = [
        $t['name'], $t['phone'], $t['guests_count'], 
        $t['table'], $t['status'], $t['checkin_status'], 
        'وقت الحضور / Check-in Time'
    ];
    fputcsv($output, $csv_headers);

    $stmt = $mysqli->prepare("SELECT name_ar, phone_number, guests_count, table_number, status, checkin_status, checkin_time FROM guests WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Translate status for CSV
            $status_text = '';
            switch($row['status']) {
                case 'confirmed': $status_text = $t['status_confirmed']; break;
                case 'canceled': $status_text = $t['status_declined']; break;
                default: $status_text = $t['status_pending']; break;
            }
            
            $checkin_text = ($row['checkin_status'] === 'checked_in') ? $t['status_checked_in'] : '-';
            
            fputcsv($output, [
                $row['name_ar'],
                $row['phone_number'],
                $row['guests_count'],
                $row['table_number'] ?: '-',
                $status_text,
                $checkin_text,
                $row['checkin_time'] ?: '-'
            ]);
        }
    }
    fclose($output);
    $stmt->close();
    $mysqli->close();
    exit;
}

// --- PDF Export Logic ---
if (isset($_GET['export_pdf']) && $_GET['export_pdf'] === 'true') {
    // Fetch data for PDF
    $stmt = $mysqli->prepare("SELECT name_ar, guests_count, table_number, status, checkin_status FROM guests WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $guests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get event name
    $event_name = '';
    $stmt_event = $mysqli->prepare("SELECT event_name FROM events WHERE id = ?");
    $stmt_event->bind_param("i", $event_id);
    if ($stmt_event->execute()) {
        $result = $stmt_event->get_result();
        if ($row = $result->fetch_assoc()) { $event_name = $row['event_name']; }
    }
    $stmt_event->close();
    
    // Calculate statistics
    $total = count($guests);
    $confirmed = $canceled = $pending = $checkedIn = 0;
    
    foreach ($guests as $guest) {
        if ($guest['checkin_status'] === 'checked_in') $checkedIn++;
        if ($guest['status'] === 'confirmed') $confirmed++;
        elseif ($guest['status'] === 'canceled') $canceled++;
        else $pending++;
    }
    
    // Generate HTML for PDF
    $html = generateDashboardHTML($event_name, $total, $confirmed, $checkedIn, $canceled, $pending, $guests, $t, $lang);
    
    // Output HTML that can be printed as PDF
    echo $html;
    exit;
}

// --- API Endpoint for Dashboard Display ---
if (isset($_GET['fetch_data'])) {
    header('Content-Type: application/json');
    $stmt = $mysqli->prepare("SELECT name_ar, guests_count, table_number, status, checkin_status FROM guests WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $guests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode($guests);
    $mysqli->close();
    exit;
}

// --- Fetch Event Name for Display ---
$event_name = $t['dashboard'];
$stmt_event = $mysqli->prepare("SELECT event_name FROM events WHERE id = ?");
$stmt_event->bind_param("i", $event_id);
if ($stmt_event->execute()) {
    $result = $stmt_event->get_result();
    if ($row = $result->fetch_assoc()) { $event_name = $row['event_name']; }
    $stmt_event->close();
}

// Function to generate PDF-ready HTML
function generateDashboardHTML($event_name, $total, $confirmed, $checkedIn, $canceled, $pending, $guests, $t, $lang) {
    $dir = $lang === 'ar' ? 'rtl' : 'ltr';
    $font = $lang === 'ar' ? 'Cairo' : 'Inter';
    
    $html = "<!DOCTYPE html>
    <html lang='$lang' dir='$dir'>
    <head>
        <meta charset='UTF-8'>
        <title>{$t['dashboard']}: $event_name</title>
        <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap' rel='stylesheet'>
        <style>
            body { font-family: '$font', sans-serif; margin: 20px; direction: $dir; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #3b82f6; padding-bottom: 20px; }
            .logo { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 20px; }
            .logo-icon { width: 40px; height: 40px; background: #4f46e5; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; }
            .logo-text { font-size: 1.5rem; font-weight: bold; color: #1e40af; }
            .stats-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px; }
            .stat-card { text-align: center; padding: 20px; border-radius: 10px; border: 2px solid #e5e7eb; }
            .stat-card.total { border-color: #6b7280; background-color: #f9fafb; }
            .stat-card.confirmed { border-color: #22c55e; background-color: #dcfce7; }
            .stat-card.checked-in { border-color: #3b82f6; background-color: #dbeafe; }
            .stat-card.canceled { border-color: #ef4444; background-color: #fee2e2; }
            .stat-card.pending { border-color: #f59e0b; background-color: #fef3c7; }
            .stat-value { font-size: 2.5rem; font-weight: bold; margin-bottom: 5px; }
            .stat-label { font-size: 1rem; color: #6b7280; }
            .section-title { font-size: 1.5rem; font-weight: bold; margin: 30px 0 15px 0; color: #374151; }
            .guest-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
            .guest-column { border: 1px solid #e5e7eb; border-radius: 8px; }
            .column-header { padding: 15px; font-weight: bold; text-align: center; }
            .column-header.checked-in { background-color: #3b82f6; color: white; }
            .column-header.confirmed { background-color: #22c55e; color: white; }
            .column-header.canceled { background-color: #ef4444; color: white; }
            .column-header.pending { background-color: #f59e0b; color: white; }
            .guest-item { padding: 10px 15px; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem; }
            .guest-item:last-child { border-bottom: none; }
            .guest-name { font-weight: 600; }
            .guest-details { color: #6b7280; font-size: 0.8rem; margin-top: 2px; }
            .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 0.9rem; }
            @media print {
                body { margin: 0; }
                .stats-grid { page-break-after: avoid; }
            }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='logo'>
                <div class='logo-icon'>✓</div>
                <div class='logo-text'>وصول</div>
            </div>
            <h1>{$t['dashboard']}: $event_name</h1>
            <p style='color: #6b7280; margin: 10px 0;'>" . date('Y-m-d H:i') . "</p>
        </div>
        
        <div class='stats-grid'>
            <div class='stat-card total'>
                <div class='stat-value'>$total</div>
                <div class='stat-label'>{$t['total_invited']}</div>
            </div>
            <div class='stat-card confirmed'>
                <div class='stat-value'>$confirmed</div>
                <div class='stat-label'>{$t['confirmed_attendance']}</div>
            </div>
            <div class='stat-card checked-in'>
                <div class='stat-value'>$checkedIn</div>
                <div class='stat-label'>{$t['checked_in_hall']}</div>
            </div>
            <div class='stat-card canceled'>
                <div class='stat-value'>$canceled</div>
                <div class='stat-label'>{$t['declined_attendance']}</div>
            </div>
            <div class='stat-card pending'>
                <div class='stat-value'>$pending</div>
                <div class='stat-label'>{$t['awaiting_response']}</div>
            </div>
        </div>
        
        <h2 class='section-title'>{$t['guest_details']}</h2>
        <div class='guest-grid'>
            <div class='guest-column'>
                <div class='column-header checked-in'>{$t['checked_in_hall']}</div>";
    
    foreach ($guests as $guest) {
        if ($guest['checkin_status'] === 'checked_in') {
            $guestCount = $guest['guests_count'] ? "({$guest['guests_count']})" : '';
            $tableNumber = $guest['table_number'] ? "{$t['table_number']}: {$guest['table_number']}" : '';
            $html .= "<div class='guest-item'>
                        <div class='guest-name'>{$guest['name_ar']}</div>
                        <div class='guest-details'>$guestCount $tableNumber</div>
                      </div>";
        }
    }
    
    $html .= "</div><div class='guest-column'>
                <div class='column-header confirmed'>{$t['confirmed_attendance']}</div>";
    
    foreach ($guests as $guest) {
        if ($guest['status'] === 'confirmed' && $guest['checkin_status'] !== 'checked_in') {
            $guestCount = $guest['guests_count'] ? "({$guest['guests_count']})" : '';
            $tableNumber = $guest['table_number'] ? "{$t['table_number']}: {$guest['table_number']}" : '';
            $html .= "<div class='guest-item'>
                        <div class='guest-name'>{$guest['name_ar']}</div>
                        <div class='guest-details'>$guestCount $tableNumber</div>
                      </div>";
        }
    }
    
    $html .= "</div><div class='guest-column'>
                <div class='column-header canceled'>{$t['declined_attendance']}</div>";
    
    foreach ($guests as $guest) {
        if ($guest['status'] === 'canceled') {
            $guestCount = $guest['guests_count'] ? "({$guest['guests_count']})" : '';
            $tableNumber = $guest['table_number'] ? "{$t['table_number']}: {$guest['table_number']}" : '';
            $html .= "<div class='guest-item'>
                        <div class='guest-name'>{$guest['name_ar']}</div>
                        <div class='guest-details'>$guestCount $tableNumber</div>
                      </div>";
        }
    }
    
    $html .= "</div><div class='guest-column'>
                <div class='column-header pending'>{$t['awaiting_response']}</div>";
    
    foreach ($guests as $guest) {
        if ($guest['status'] !== 'confirmed' && $guest['status'] !== 'canceled') {
            $guestCount = $guest['guests_count'] ? "({$guest['guests_count']})" : '';
            $tableNumber = $guest['table_number'] ? "{$t['table_number']}: {$guest['table_number']}" : '';
            $html .= "<div class='guest-item'>
                        <div class='guest-name'>{$guest['name_ar']}</div>
                        <div class='guest-details'>$guestCount $tableNumber</div>
                      </div>";
        }
    }
    
    $html .= "</div></div>
        <div class='footer'>
            <p>&copy; " . date('Y') . " <strong>وصول - Wosuol.com</strong> - جميع الحقوق محفوظة</p>
        </div>
        </body></html>";
    return $html;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['dashboard'] ?>: <?= htmlspecialchars($event_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background: white; 
            padding: 20px; 
            color: #2d4a22;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 20px auto; 
            background: white;
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            padding: 30px; 
        }
        
        .wosuol-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #2d4a22;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .wosuol-icon {
            width: 35px;
            height: 35px;
            background: rgba(45, 74, 34, 0.9);
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
            color: #2d4a22;
        }
        
        /* الهيدر بالتصميم الموحد */
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2rem; 
            padding: 20px 25px;
            border-radius: 50px;
            font-weight: 600;
            color: #2d4a22;
            border: 2px solid rgba(45, 74, 34, 0.3);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .page-header:hover::before {
            left: 100%;
        }
        
        .page-header:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(45, 74, 34, 0.2);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .header-buttons { display: flex; gap: 12px; align-items: center; }
        
        /* بطاقات الإحصائيات بالتصميم الموحد */
        .stat-card { 
            padding: 20px 25px;
            border-radius: 50px;
            font-weight: 600;
            color: #2d4a22;
            border: 2px solid rgba(45, 74, 34, 0.3);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            text-align: center; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .stat-card:hover::before {
            left: 100%;
        }
        
        .stat-card:hover { 
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(45, 74, 34, 0.2);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .stat-card .value { 
            font-size: 2.5rem; 
            font-weight: bold; 
            margin-bottom: 8px; 
            color: #2d4a22; /* لون موحد لجميع الأرقام */
        }
        
        .stat-card .percentage {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 5px;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .stat-card .percentage.positive {
            background: rgba(34, 197, 94, 0.1);
            color: #059669;
        }
        
        .stat-card .percentage.negative {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }
        
        .stat-card .mini-chart {
            margin-top: 10px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-card .label { 
            font-size: 1rem; 
            opacity: 0.8; 
            margin-top: 5px; 
        }
        
        /* حاويات قوائم الضيوف بالتصميم الموحد */
        .guest-list-container { 
            max-height: 400px; 
            overflow-y: auto; 
            border-radius: 25px;
            padding: 15px; 
            min-height: 100px; 
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(45, 74, 34, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
        }
        
        .guest-list-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.05), transparent);
            transition: left 0.4s ease;
        }
        
        .guest-list-container:hover::before {
            left: 100%;
        }
        
        .guest-list-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(45, 74, 34, 0.15);
            border-color: rgba(45, 74, 34, 0.5);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .guest-item { 
            padding: 12px 15px; 
            border-bottom: 1px solid rgba(45, 74, 34, 0.1); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: rgba(255, 255, 255, 0.8);
            margin-bottom: 4px; 
            border-radius: 15px; 
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .guest-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.03), transparent);
            transition: left 0.3s ease;
        }
        
        .guest-item:hover::before {
            left: 100%;
        }
        
        .guest-item:last-child { border-bottom: none; }
        .guest-item:hover { 
            background: rgba(255, 255, 255, 0.95);
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(45, 74, 34, 0.1);
        }
        .guest-name { font-weight: 600; color: #2d4a22; }
        .guest-details { font-size: 0.875rem; color: rgba(45, 74, 34, 0.7); }
        
        /* عناوين الأعمدة بالألوان التمييزية والأيقونات */
        .column-header { 
            font-size: 1.25rem; 
            font-weight: bold; 
            margin-bottom: 12px; 
            padding: 15px 20px; 
            border-radius: 25px; 
            text-align: center; 
            color: #2d4a22;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(45, 74, 34, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
        }
        
        /* ألوان تمييزية خفيفة للحالات */
        .column-header.checked-in { 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.08));
            border-color: rgba(59, 130, 246, 0.3);
        }
        .column-header.confirmed { 
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(22, 163, 74, 0.08));
            border-color: rgba(34, 197, 94, 0.3);
        }
        .column-header.canceled { 
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.08));
            border-color: rgba(239, 68, 68, 0.3);
        }
        .column-header.pending { 
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.08));
            border-color: rgba(245, 158, 11, 0.3);
        }
        
        .column-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.1), transparent);
            transition: left 0.4s ease;
        }
        
        .column-header:hover::before {
            left: 100%;
        }
        
        .column-header:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(45, 74, 34, 0.2);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .column-header i {
            margin-right: 8px;
            font-size: 1.1em;
        }
        
        .empty-state { 
            text-align: center; 
            color: rgba(45, 74, 34, 0.6); 
            padding: 30px; 
            font-style: italic; 
        }
        
        /* حقل البحث بالتصميم الموحد */
        .search-input { 
            transition: all 0.3s ease; 
            border: 2px solid rgba(45, 74, 34, 0.3);
            border-radius: 25px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            color: #2d4a22;
            font-weight: 600;
        }
        
        .search-input:focus { 
            border-color: rgba(45, 74, 34, 0.6);
            box-shadow: 0 0 0 3px rgba(45, 74, 34, 0.1); 
            background: rgba(255, 255, 255, 0.95);
            outline: none;
        }
        
        /* الأزرار بالتصميم الموحد */
        .btn { 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            font-weight: 600;
            border-radius: 25px;
            padding: 12px 20px;
            border: 2px solid rgba(45, 74, 34, 0.3);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            color: #2d4a22;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(45, 74, 34, 0.1);
            text-decoration: none;
            display: inline-block;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(45, 74, 34, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover { 
            transform: translateY(-2px) scale(1.02); 
            box-shadow: 0 6px 20px rgba(45, 74, 34, 0.2);
            border-color: rgba(45, 74, 34, 0.5);
            color: #1a2f15;
            background: rgba(255, 255, 255, 0.95);
        }
        
        .btn i {
            margin-right: 8px;
            transition: transform 0.3s ease;
        }
        
        .btn:hover i {
            transform: scale(1.1) rotate(5deg);
        }
        
        .footer { 
            margin-top: 3rem; 
            padding-top: 2rem; 
            border-top: 1px solid rgba(45, 74, 34, 0.2); 
            text-align: center; 
        }
        
        /* وضع العرض التقديمي */
        .presentation-mode {
            font-size: 1.2em;
        }
        
        .presentation-mode .stat-card .value {
            font-size: 4rem !important;
        }
        
        .presentation-mode .guest-item {
            font-size: 1.1rem;
            padding: 15px;
        }
        
        .presentation-mode .column-header {
            font-size: 1.5rem;
            padding: 20px;
        }
        
        /* وضع الشاشة الكاملة */
        .fullscreen-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            background: white;
            overflow: auto;
            padding: 20px;
        }
        
        /* تنبيهات فورية */
        .toast-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(34, 197, 94, 0.3);
            color: #059669;
            padding: 15px 20px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.2);
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 1000;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 300px;
        }
        
        .toast-notification.show {
            transform: translateX(0);
        }
        
        .toast-notification.error {
            border-color: rgba(239, 68, 68, 0.3);
            color: #dc2626;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.2);
        }
        
        .toast-notification i {
            font-size: 1.2em;
        }
        
        /* Pull to refresh */
        .pull-to-refresh {
            position: relative;
            overflow: hidden;
        }
        
        .pull-indicator {
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: rgba(45, 74, 34, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .pull-indicator.active {
            top: 10px;
        }
        
        .pull-indicator i {
            transition: transform 0.3s ease;
        }
        
        .pull-indicator.loading i {
            animation: spin 1s linear infinite;
        }
        
        /* أزرار التحكم */
        .control-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .control-buttons .btn {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        /* عداد مباشر */
        .live-counter {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: #059669;
            margin-left: 15px;
        }
        
        .live-counter .pulse-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        /* التجاوب للموبايل - وضع مضغوط محسّن */
        @media (max-width: 768px) {
            .header-buttons { 
                flex-direction: column; 
                gap: 8px; 
            }
            
            .page-header { 
                flex-direction: column; 
                gap: 15px; 
                text-align: center;
                border-radius: 30px;
                padding: 15px 20px;
            }
            
            .stat-card {
                border-radius: 30px;
                padding: 15px 20px;
            }
            
            .guest-list-container {
                border-radius: 20px;
                max-height: 300px;
            }
            
            .column-header {
                border-radius: 20px;
                font-size: 1rem;
                padding: 12px 15px;
            }
            
            .search-input {
                border-radius: 20px;
            }
            
            .btn {
                border-radius: 20px;
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            
            /* وضع مضغوط للموبايل */
            .compact-mode .guest-item {
                padding: 8px 12px;
                font-size: 0.85rem;
                margin-bottom: 2px;
            }
            
            .compact-mode .stat-card {
                padding: 12px 15px;
            }
            
            .compact-mode .stat-card .value {
                font-size: 2rem;
            }
            
            .compact-mode .column-header {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            .control-buttons {
                justify-content: center;
            }
            
            .control-buttons .btn {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .toast-notification {
                right: 10px;
                left: 10px;
                max-width: none;
                transform: translateY(-100px);
            }
            
            .toast-notification.show {
                transform: translateY(0);
            }
        }
        
        /* للشاشات الصغيرة جداً */
        @media (max-width: 480px) {
            .grid.grid-cols-1.md\\:grid-cols-4 {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .grid.grid-cols-1.md\\:grid-cols-5 {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }
            
            .stat-card .value {
                font-size: 1.8rem;
            }
            
            .stat-card .label {
                font-size: 0.8rem;
            }
        }
        
        /* التجاوب للتابلت */
        @media (min-width: 769px) and (max-width: 1024px) {
            .page-header,
            .stat-card {
                border-radius: 40px;
            }
            
            .guest-list-container {
                border-radius: 22px;
            }
        }
        
        /* للكمبيوتر - الحجم الكامل */
        @media (min-width: 1025px) {
            .page-header,
            .stat-card,
            .column-header {
                border-radius: 50px;
            }
            
            .guest-list-container {
                border-radius: 25px;
            }
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

        <div class="page-header">
            <h1 class="text-3xl font-bold"><?= $t['dashboard'] ?>: <?= htmlspecialchars($event_name) ?></h1>
            <div class="header-buttons">
                <div class="live-counter">
                    <div class="pulse-dot"></div>
                    <span><?= $lang === 'ar' ? 'مباشر' : 'Live' ?></span>
                </div>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                            class="btn">
                        <i class="fas fa-language"></i>
                        <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                    </button>
                </form>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="events.php" class="btn">
                        <i class="fas fa-arrow-left"></i>
                        <?= $t['back_to_events'] ?>
                    </a>
                <?php else: ?>
                    <a href="logout.php" class="btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <?= $t['logout'] ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- أزرار التحكم -->
        <div class="control-buttons">
            <button id="presentation-mode-btn" class="btn">
                <i class="fas fa-tv"></i>
                <?= $lang === 'ar' ? 'وضع العرض' : 'Presentation Mode' ?>
            </button>
            <button id="fullscreen-btn" class="btn">
                <i class="fas fa-expand"></i>
                <?= $lang === 'ar' ? 'شاشة كاملة' : 'Fullscreen' ?>
            </button>
            <button id="compact-mode-btn" class="btn">
                <i class="fas fa-compress-alt"></i>
                <?= $lang === 'ar' ? 'وضع مضغوط' : 'Compact Mode' ?>
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="stat-card total">
                <div class="value" id="total-guests">0</div>
                <div class="percentage positive" id="total-percentage">+0%</div>
                <div class="mini-chart">
                    <canvas id="totalChart" width="80" height="30"></canvas>
                </div>
                <div class="label"><?= $t['total_invited'] ?></div>
            </div>
            <div class="stat-card confirmed">
                <div class="value" id="confirmed-guests">0</div>
                <div class="percentage positive" id="confirmed-percentage">+0%</div>
                <div class="mini-chart">
                    <canvas id="confirmedChart" width="80" height="30"></canvas>
                </div>
                <div class="label"><?= $t['confirmed_attendance'] ?></div>
            </div>
            <div class="stat-card checked-in">
                <div class="value" id="checked-in-guests">0</div>
                <div class="percentage positive" id="checkedin-percentage">+0%</div>
                <div class="mini-chart">
                    <canvas id="checkedinChart" width="80" height="30"></canvas>
                </div>
                <div class="label"><?= $t['checked_in_hall'] ?></div>
            </div>
            <div class="stat-card canceled">
                <div class="value" id="canceled-guests">0</div>
                <div class="percentage negative" id="canceled-percentage">+0%</div>
                <div class="mini-chart">
                    <canvas id="canceledChart" width="80" height="30"></canvas>
                </div>
                <div class="label"><?= $t['declined_attendance'] ?></div>
            </div>
            <div class="stat-card pending">
                <div class="value" id="pending-guests">0</div>
                <div class="percentage positive" id="pending-percentage">+0%</div>
                <div class="mini-chart">
                    <canvas id="pendingChart" width="80" height="30"></canvas>
                </div>
                <div class="label"><?= $t['awaiting_response'] ?></div>
            </div>
        </div>
        
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold"><?= $t['guest_list'] ?></h2>
            <div class="flex gap-4">
                <a href="?event_id=<?= $event_id ?>&export_csv=true" 
                   class="btn">
                    <i class="fas fa-file-csv"></i>
                    CSV
                </a>
                <a href="?event_id=<?= $event_id ?>&export_pdf=true" target="_blank"
                   class="btn">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </a>
                <button id="export-excel-btn" class="btn">
                    <i class="fas fa-file-excel"></i>
                    Excel
                </button>
                <button id="print-btn" class="btn">
                    <i class="fas fa-print"></i>
                    <?= $lang === 'ar' ? 'طباعة' : 'Print' ?>
                </button>
                <button id="refresh-button" 
                        class="btn">
                    <i class="fas fa-sync-alt"></i>
                    <?= $t['refresh_data'] ?>
                </button>
            </div>
        </div>
        
        <input type="text" id="guest-search" 
               class="search-input w-full p-3 text-lg mb-6" 
               placeholder="<?= $t['search_guest'] ?>">
        
        <!-- مؤشر Pull to Refresh -->
        <div class="pull-indicator" id="pull-indicator">
            <i class="fas fa-arrow-down"></i>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pull-to-refresh" id="guest-lists">
            <div>
                <div class="column-header checked-in">
                    <i class="fas fa-check-circle"></i>
                    <?= $t['checked_in_hall'] ?>
                </div>
                <div id="checked-in-list" class="guest-list-container"></div>
            </div>
            <div>
                <div class="column-header confirmed">
                    <i class="fas fa-user-check"></i>
                    <?= $t['confirmed_attendance'] ?>
                </div>
                <div id="confirmed-list" class="guest-list-container"></div>
            </div>
            <div>
                <div class="column-header canceled">
                    <i class="fas fa-user-times"></i>
                    <?= $t['declined_attendance'] ?>
                </div>
                <div id="canceled-list" class="guest-list-container"></div>
            </div>
            <div>
                <div class="column-header pending">
                    <i class="fas fa-user-clock"></i>
                    <?= $t['awaiting_response'] ?>
                </div>
                <div id="pending-list" class="guest-list-container"></div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div id="toast-notification" class="toast-notification">
            <i class="fas fa-bell"></i>
            <span id="toast-message"></span>
        </div>></div>
                <div id="canceled-list" class="guest-list-container"></div>
            </div>
            <div>
                <div class="column-header pending"><?= $t['awaiting_response'] ?></div>
                <div id="pending-list" class="guest-list-container"></div>
            </div>
        </div>

        <!-- Footer with Logo and Copyright -->
        <div class="footer">
            <div class="wosuol-logo justify-center mb-4">
                <div class="wosuol-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="wosuol-text">وصول</div>
            </div>
            <p class="text-sm opacity-70">
                &copy; <?= date('Y') ?> <a href="https://wosuol.com" target="_blank" class="hover:opacity-80 font-medium">وصول - Wosuol.com</a> - جميع الحقوق محفوظة
            </p>
        </div>
    </div>
    
    <script>
        const dashboardApiUrl = 'dashboard.php?event_id=<?= $event_id ?>&fetch_data=true';
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        const lang = '<?= $lang ?>';
        
        const totalGuestsEl = document.getElementById('total-guests');
        const confirmedGuestsEl = document.getElementById('confirmed-guests');
        const canceledGuestsEl = document.getElementById('canceled-guests');
        const pendingGuestsEl = document.getElementById('pending-guests');
        const checkedInGuestsEl = document.getElementById('checked-in-guests');
        const confirmedListEl = document.getElementById('confirmed-list');
        const canceledListEl = document.getElementById('canceled-list');
        const pendingListEl = document.getElementById('pending-list');
        const checkedInListEl = document.getElementById('checked-in-list');
        const guestSearchInput = document.getElementById('guest-search');
        const refreshButton = document.getElementById('refresh-button');
        
        let allGuestsData = [];
        let previousStats = {total: 0, confirmed: 0, canceled: 0, pending: 0, checkedIn: 0};
        let miniCharts = {};
        let isPresentationMode = false;
        let isCompactMode = false;
        let isFullscreen = false;

        // Initialize mini charts
        function initMiniCharts() {
            const chartIds = ['totalChart', 'confirmedChart', 'checkedinChart', 'canceledChart', 'pendingChart'];
            chartIds.forEach(id => {
                const ctx = document.getElementById(id);
                if (ctx) {
                    miniCharts[id] = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: Array(10).fill(''),
                            datasets: [{
                                data: Array(10).fill(0),
                                borderColor: '#2d4a22',
                                backgroundColor: 'rgba(45, 74, 34, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { display: false },
                                y: { display: false }
                            },
                            elements: { point: { radius: 0 } }
                        }
                    });
                }
            });
        }

        // Update mini chart
        function updateMiniChart(chartId, newValue) {
            const chart = miniCharts[chartId];
            if (chart) {
                chart.data.datasets[0].data.shift();
                chart.data.datasets[0].data.push(newValue);
                chart.update('none');
            }
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = `toast-notification ${type === 'error' ? 'error' : ''} show`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // Calculate percentage change
        function calculatePercentage(current, previous) {
            if (previous === 0) return current > 0 ? '+100' : '0';
            const change = ((current - previous) / previous) * 100;
            return (change > 0 ? '+' : '') + change.toFixed(0);
        }

        // Update percentage displays
        function updatePercentages(stats) {
            document.getElementById('total-percentage').textContent = calculatePercentage(stats.total, previousStats.total) + '%';
            document.getElementById('confirmed-percentage').textContent = calculatePercentage(stats.confirmed, previousStats.confirmed) + '%';
            document.getElementById('checkedin-percentage').textContent = calculatePercentage(stats.checkedIn, previousStats.checkedIn) + '%';
            document.getElementById('canceled-percentage').textContent = calculatePercentage(stats.canceled, previousStats.canceled) + '%';
            document.getElementById('pending-percentage').textContent = calculatePercentage(stats.pending, previousStats.pending) + '%';
        }

        // Pull to refresh functionality
        let startY = 0;
        let pullDistance = 0;
        let isRefreshing = false;

        function initPullToRefresh() {
            const pullIndicator = document.getElementById('pull-indicator');
            const guestLists = document.getElementById('guest-lists');

            // Touch events for mobile
            guestLists.addEventListener('touchstart', (e) => {
                if (guestLists.scrollTop === 0) {
                    startY = e.touches[0].pageY;
                }
            });

            guestLists.addEventListener('touchmove', (e) => {
                if (guestLists.scrollTop === 0 && !isRefreshing) {
                    pullDistance = e.touches[0].pageY - startY;
                    if (pullDistance > 0) {
                        e.preventDefault();
                        const progress = Math.min(pullDistance / 80, 1);
                        pullIndicator.style.transform = `translateX(-50%) translateY(${Math.min(pullDistance * 0.5, 40)}px)`;
                        pullIndicator.querySelector('i').style.transform = `rotate(${progress * 180}deg)`;
                        
                        if (pullDistance > 80) {
                            pullIndicator.classList.add('active');
                        } else {
                            pullIndicator.classList.remove('active');
                        }
                    }
                }
            });

            guestLists.addEventListener('touchend', () => {
                if (pullDistance > 80 && !isRefreshing) {
                    triggerRefresh();
                }
                resetPullIndicator();
            });
        }

        function resetPullIndicator() {
            const pullIndicator = document.getElementById('pull-indicator');
            pullIndicator.style.transform = 'translateX(-50%) translateY(-60px)';
            pullIndicator.querySelector('i').style.transform = 'rotate(0deg)';
            pullIndicator.classList.remove('active');
            pullDistance = 0;
        }

        function triggerRefresh() {
            isRefreshing = true;
            const pullIndicator = document.getElementById('pull-indicator');
            pullIndicator.classList.add('loading');
            pullIndicator.querySelector('i').className = 'fas fa-spinner';
            
            fetchAndDisplayData().finally(() => {
                setTimeout(() => {
                    isRefreshing = false;
                    pullIndicator.classList.remove('loading');
                    pullIndicator.querySelector('i').className = 'fas fa-arrow-down';
                    resetPullIndicator();
                    showToast(lang === 'ar' ? 'تم التحديث بنجاح' : 'Refreshed successfully');
                }, 1000);
            });
        }

        // Mode toggles
        function togglePresentationMode() {
            isPresentationMode = !isPresentationMode;
            document.body.classList.toggle('presentation-mode', isPresentationMode);
            const btn = document.getElementById('presentation-mode-btn');
            btn.innerHTML = `<i class="fas fa-${isPresentationMode ? 'times' : 'tv'}"></i> ${isPresentationMode ? (lang === 'ar' ? 'إنهاء العرض' : 'Exit Presentation') : (lang === 'ar' ? 'وضع العرض' : 'Presentation Mode')}`;
        }

        function toggleCompactMode() {
            isCompactMode = !isCompactMode;
            document.body.classList.toggle('compact-mode', isCompactMode);
            const btn = document.getElementById('compact-mode-btn');
            btn.innerHTML = `<i class="fas fa-${isCompactMode ? 'expand-alt' : 'compress-alt'}"></i> ${isCompactMode ? (lang === 'ar' ? 'وضع عادي' : 'Normal Mode') : (lang === 'ar' ? 'وضع مضغوط' : 'Compact Mode')}`;
        }

        function toggleFullscreen() {
            if (!isFullscreen) {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen();
                }
                document.querySelector('.container').classList.add('fullscreen-mode');
                isFullscreen = true;
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                }
                document.querySelector('.container').classList.remove('fullscreen-mode');
                isFullscreen = false;
            }
            
            const btn = document.getElementById('fullscreen-btn');
            btn.innerHTML = `<i class="fas fa-${isFullscreen ? 'compress' : 'expand'}"></i> ${isFullscreen ? (lang === 'ar' ? 'خروج من الشاشة الكاملة' : 'Exit Fullscreen') : (lang === 'ar' ? 'شاشة كاملة' : 'Fullscreen')}`;
        }

        // Export functions
        function exportToExcel() {
            const csvContent = generateCSVContent();
            const blob = new Blob([csvContent], { type: 'application/vnd.ms-excel' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `guest_report_${new Date().toISOString().split('T')[0]}.xls`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showToast(lang === 'ar' ? 'تم تصدير Excel بنجاح' : 'Excel exported successfully');
        }

        function printDashboard() {
            window.print();
        }

        function generateCSVContent() {
            const headers = [texts.name, texts.guests_count, texts.table, texts.status].join(',') + '\n';
            const rows = allGuestsData.map(guest => {
                const status = guest.status === 'confirmed' ? texts.status_confirmed : 
                             guest.status === 'canceled' ? texts.status_declined : texts.status_pending;
                return [
                    `"${guest.name_ar || ''}"`,
                    guest.guests_count || '1',
                    guest.table_number || '',
                    status
                ].join(',');
            }).join('\n');
            return headers + rows;
        }

        async function fetchAndDisplayData() {
            try {
                refreshButton.disabled = true;
                const refreshIcon = refreshButton.querySelector('i');
                refreshIcon.style.animation = 'spin 1s linear infinite';

                const response = await fetch(dashboardApiUrl);
                if (!response.ok) { throw new Error(`HTTP error! Status: ${response.status}`); }
                
                const data = await response.json();
                if (data.error) { throw new Error(data.error); }

                allGuestsData = data;
                updateDashboard(allGuestsData);

            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                showToast(texts['error_fetching_data'] + ': ' + error.message, 'error');
            } finally {
                refreshButton.disabled = false;
                const refreshIcon = refreshButton.querySelector('i');
                refreshIcon.style.animation = '';
            }
        }

        function updateDashboard(guests) {
            let total = guests.length, confirmed = 0, canceled = 0, pending = 0, checkedIn = 0;
            
            // Clear all lists
            confirmedListEl.innerHTML = '';
            canceledListEl.innerHTML = '';
            pendingListEl.innerHTML = '';
            checkedInListEl.innerHTML = '';

            guests.forEach(guest => {
                const guestName = guest.name_ar || 'ضيف';
                const guestCount = guest.guests_count ? `(${guest.guests_count})` : '';
                const tableNumber = guest.table_number ? `${texts['table_number']}: ${guest.table_number}` : '';
                
                const guestItem = document.createElement('div');
                guestItem.className = 'guest-item';
                guestItem.innerHTML = `
                    <div>
                        <div class="guest-name">${guestName}</div>
                        <div class="guest-details">${guestCount} ${tableNumber}</div>
                    </div>
                `;

                if (guest.checkin_status === 'checked_in') {
                    checkedIn++;
                    checkedInListEl.appendChild(guestItem.cloneNode(true));
                }
                
                if (guest.status === 'confirmed') { 
                    confirmed++;
                    if (guest.checkin_status !== 'checked_in') {
                       confirmedListEl.appendChild(guestItem.cloneNode(true));
                    }
                } 
                else if (guest.status === 'canceled') { 
                    canceled++; 
                    canceledListEl.appendChild(guestItem.cloneNode(true)); 
                } 
                else { 
                    pending++; 
                    pendingListEl.appendChild(guestItem.cloneNode(true)); 
                }
            });

            // Add empty states
            if (checkedInListEl.children.length === 0) {
                checkedInListEl.innerHTML = `<div class="empty-state">${texts['no_guests']}</div>`;
            }
            if (confirmedListEl.children.length === 0) {
                confirmedListEl.innerHTML = `<div class="empty-state">${texts['no_guests']}</div>`;
            }
            if (canceledListEl.children.length === 0) {
                canceledListEl.innerHTML = `<div class="empty-state">${texts['no_guests']}</div>`;
            }
            if (pendingListEl.children.length === 0) {
                pendingListEl.innerHTML = `<div class="empty-state">${texts['no_guests']}</div>`;
            }

            // Check for changes and show notifications
            const currentStats = {total, confirmed, canceled, pending, checkedIn};
            if (previousStats.total > 0) {
                if (currentStats.checkedIn > previousStats.checkedIn) {
                    showToast(lang === 'ar' ? 'ضيف جديد سجل الحضور!' : 'New guest checked in!');
                }
                if (currentStats.confirmed > previousStats.confirmed) {
                    showToast(lang === 'ar' ? 'ضيف جديد أكد الحضور!' : 'New guest confirmed attendance!');
                }
            }

            // Update percentages and charts
            updatePercentages(currentStats);
            updateMiniChart('totalChart', total);
            updateMiniChart('confirmedChart', confirmed);
            updateMiniChart('checkedinChart', checkedIn);
            updateMiniChart('canceledChart', canceled);
            updateMiniChart('pendingChart', pending);

            // Update statistics with animation
            animateNumber(totalGuestsEl, total);
            animateNumber(confirmedGuestsEl, confirmed);
            animateNumber(canceledGuestsEl, canceled);
            animateNumber(pendingGuestsEl, pending);
            animateNumber(checkedInGuestsEl, checkedIn);

            previousStats = currentStats;
        }

        function animateNumber(element, targetNumber) {
            const currentNumber = parseInt(element.textContent) || 0;
            const increment = targetNumber > currentNumber ? 1 : -1;
            const timer = setInterval(() => {
                const current = parseInt(element.textContent) || 0;
                if (current === targetNumber) {
                    clearInterval(timer);
                } else {
                    element.textContent = current + increment;
                }
            }, 50);
        }

        // Search functionality
        guestSearchInput.addEventListener('input', () => {
            const searchTerm = guestSearchInput.value.toLowerCase().trim();
            const filteredGuests = allGuestsData.filter(guest => {
                const name = (guest.name_ar || '').toLowerCase();
                const table = (guest.table_number || '').toString().toLowerCase();
                return name.includes(searchTerm) || table.includes(searchTerm);
            });
            updateDashboard(filteredGuests);
        });

        // Event listeners
        refreshButton.addEventListener('click', fetchAndDisplayData);
        document.getElementById('presentation-mode-btn').addEventListener('click', togglePresentationMode);
        document.getElementById('fullscreen-btn').addEventListener('click', toggleFullscreen);
        document.getElementById('compact-mode-btn').addEventListener('click', toggleCompactMode);
        document.getElementById('export-excel-btn').addEventListener('click', exportToExcel);
        document.getElementById('print-btn').addEventListener('click', printDashboard);
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'f':
                        e.preventDefault();
                        toggleFullscreen();
                        break;
                    case 'p':
                        e.preventDefault();
                        togglePresentationMode();
                        break;
                    case 'r':
                        e.preventDefault();
                        fetchAndDisplayData();
                        break;
                }
            }
            if (e.key === 'Escape') {
                if (isPresentationMode) togglePresentationMode();
                if (isFullscreen) toggleFullscreen();
            }
        });
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', () => {
            initMiniCharts();
            initPullToRefresh();
            fetchAndDisplayData();
        });
        
        // Auto-refresh every 30 seconds
        setInterval(fetchAndDisplayData, 30000);

        // Add CSS for refresh animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            @media print {
                .control-buttons, .search-input, .btn {
                    display: none !important;
                }
                .container {
                    box-shadow: none !important;
                    border-radius: 0 !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>