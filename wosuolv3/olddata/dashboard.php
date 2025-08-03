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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: <?= $lang === 'ar' ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; 
            background-color: #f0f2f5; 
            padding: 20px; 
        }
        .container { 
            max-width: 1200px; 
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
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 2rem; 
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 20px;
        }
        .header-buttons { display: flex; gap: 12px; align-items: center; }
        .stat-card { 
            background-color: #f9fafb; 
            border-radius: 10px; 
            padding: 20px; 
            text-align: center; 
            transition: transform 0.2s, box-shadow 0.2s; 
        }
        .stat-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.1); 
        }
        .stat-card .value { 
            font-size: 2.5rem; 
            font-weight: bold; 
            margin-bottom: 8px; 
        }
        .stat-card .label { 
            font-size: 1rem; 
            color: #6b7280; 
            margin-top: 5px; 
        }
        .stat-card.confirmed .value { color: #22c55e; }
        .stat-card.canceled .value { color: #ef4444; }
        .stat-card.pending .value { color: #f59e0b; }
        .stat-card.checked-in .value { color: #3b82f6; }
        .stat-card.total .value { color: #6b7280; }
        .guest-list-container { 
            max-height: 400px; 
            overflow-y: auto; 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            padding: 10px; 
            min-height: 100px; 
            background-color: #fafafa; 
        }
        .guest-item { 
            padding: 12px 15px; 
            border-bottom: 1px solid #e5e7eb; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background-color: white; 
            margin-bottom: 4px; 
            border-radius: 6px; 
        }
        .guest-item:last-child { border-bottom: none; }
        .guest-item:hover { background-color: #f8f9fa; }
        .guest-name { font-weight: 600; color: #374151; }
        .guest-details { font-size: 0.875rem; color: #6b7280; }
        .column-header { 
            font-size: 1.25rem; 
            font-weight: bold; 
            margin-bottom: 12px; 
            padding: 12px; 
            border-radius: 8px; 
            text-align: center; 
            color: white; 
        }
        .column-header.confirmed { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .column-header.canceled { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .column-header.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .column-header.checked-in { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .empty-state { 
            text-align: center; 
            color: #9ca3af; 
            padding: 30px; 
            font-style: italic; 
        }
        .search-input { 
            transition: all 0.3s ease; 
            border: 2px solid #e5e7eb; 
        }
        .search-input:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); 
        }
        .btn { 
            transition: all 0.2s ease; 
            font-weight: 600; 
        }
        .btn:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        }
        .footer { 
            margin-top: 3rem; 
            padding-top: 2rem; 
            border-top: 1px solid #e5e7eb; 
            text-align: center; 
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo Header -->
       

       <div class="page-header">
    <div class="flex items-center gap-4">
        <a href="https://wosuol.com" target="_blank" class="wosuol-logo">
            <div class="wosuol-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <div style="font-size: 1.5rem;">وصول</div>
                <div style="font-size: 0.8rem; opacity: 0.7;"><?= $t['wosuol_tagline'] ?? 'نظام إدارة الفعاليات والدعوات' ?></div>
            </div>
        </a>
        <h1 class="text-3xl font-bold text-gray-800"><?= $t['dashboard'] ?>: <?= htmlspecialchars($event_name) ?></h1>
    </div>
    <div class="header-buttons">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="switch_language" value="<?= $lang === 'ar' ? 'en' : 'ar' ?>" 
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg border border-gray-300 transition-colors">
                        <?= $lang === 'ar' ? 'English' : 'العربية' ?>
                    </button>
                </form>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="events.php" class="btn bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg"><?= $t['back_to_events'] ?></a>
                <?php else: ?>
                    <a href="logout.php" class="btn bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg"><?= $t['logout'] ?></a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="stat-card total">
                <div class="value" id="total-guests">0</div>
                <div class="label"><?= $t['total_invited'] ?></div>
            </div>
            <div class="stat-card confirmed">
                <div class="value" id="confirmed-guests">0</div>
                <div class="label"><?= $t['confirmed_attendance'] ?></div>
            </div>
            <div class="stat-card checked-in">
                <div class="value" id="checked-in-guests">0</div>
                <div class="label"><?= $t['checked_in_hall'] ?></div>
            </div>
            <div class="stat-card canceled">
                <div class="value" id="canceled-guests">0</div>
                <div class="label"><?= $t['declined_attendance'] ?></div>
            </div>
            <div class="stat-card pending">
                <div class="value" id="pending-guests">0</div>
                <div class="label"><?= $t['awaiting_response'] ?></div>
            </div>
        </div>
        
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-700"><?= $t['guest_list'] ?></h2>
            <div class="flex gap-4">
                <a href="?event_id=<?= $event_id ?>&export_csv=true" 
                   class="btn bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg">
                    <?= $t['export_report_csv'] ?>
                </a>
                <a href="?event_id=<?= $event_id ?>&export_pdf=true" target="_blank"
                   class="btn bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg">
                    <?= $t['export_dashboard_pdf'] ?>
                </a>
                <button id="refresh-button" 
                        class="btn bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg">
                    <?= $t['refresh_data'] ?>
                </button>
            </div>
        </div>
        
        <input type="text" id="guest-search" 
               class="search-input w-full p-3 border rounded-lg mb-6 text-lg" 
               placeholder="<?= $t['search_guest'] ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <div class="column-header checked-in"><?= $t['checked_in_hall'] ?></div>
                <div id="checked-in-list" class="guest-list-container"></div>
            </div>
            <div>
                <div class="column-header confirmed"><?= $t['confirmed_attendance'] ?></div>
                <div id="confirmed-list" class="guest-list-container"></div>
            </div>
            <div>
                <div class="column-header canceled"><?= $t['declined_attendance'] ?></div>
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
            <p class="text-gray-500 text-sm">
                &copy; <?= date('Y') ?> <a href="https://wosuol.com" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium">وصول - Wosuol.com</a> - جميع الحقوق محفوظة
            </p>
        </div>
    </div>
    
    <script>
        const dashboardApiUrl = 'dashboard.php?event_id=<?= $event_id ?>&fetch_data=true';
        const texts = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
        
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

        async function fetchAndDisplayData() {
            try {
                refreshButton.disabled = true;
                refreshButton.textContent = texts['refreshing'];

                const response = await fetch(dashboardApiUrl);
                if (!response.ok) { throw new Error(`HTTP error! Status: ${response.status}`); }
                
                const data = await response.json();
                if (data.error) { throw new Error(data.error); }

                allGuestsData = data;
                updateDashboard(allGuestsData);

            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                document.querySelector('.container').innerHTML = `<div class="text-center text-red-500 p-10">${texts['error_fetching_data']}: ${error.message}</div>`;
            } finally {
                refreshButton.disabled = false;
                refreshButton.textContent = texts['refresh_data'];
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

            // Update statistics with animation
            animateNumber(totalGuestsEl, total);
            animateNumber(confirmedGuestsEl, confirmed);
            animateNumber(canceledGuestsEl, canceled);
            animateNumber(pendingGuestsEl, pending);
            animateNumber(checkedInGuestsEl, checkedIn);
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
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', fetchAndDisplayData);
        
        // Auto-refresh every 30 seconds
        setInterval(fetchAndDisplayData, 30000);
    </script>
</body>
</html>