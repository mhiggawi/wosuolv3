<?php
session_start();
require_once 'db_config.php';

// --- Security & Permission Check ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'viewer'])) {
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
    die('Access Denied: You do not have permission to access this page.');
}

// --- API Logic: Fetch guests data for the specific event ---
if (isset($_GET['fetch_guests'])) {
    header('Content-Type: application/json');
    $api_event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

    if(!$api_event_id) { echo json_encode([]); exit; }

    // Security check inside API
    if ($_SESSION['role'] !== 'admin' && $api_event_id != ($_SESSION['event_id_access'] ?? null)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access Denied']);
        exit;
    }

    $guests = [];
    $stmt = $mysqli->prepare("SELECT guest_id, name_ar, phone_number, status, guests_count, checkin_status FROM guests WHERE event_id = ? ORDER BY name_ar ASC");
    $stmt->bind_param("i", $api_event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $guests = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();

    $formatted_guests = [];
    foreach ($guests as $guest) {
        $formatted_guests[] = [
            'GuestID' => $guest['guest_id'],
            'Name' => $guest['name_ar'],
            'PhoneNumber' => $guest['phone_number'],
            'Status' => $guest['status'],
            'GuestsCount' => $guest['guests_count'],
            'CheckedIn' => ($guest['checkin_status'] === 'checked_in' ? 'TRUE' : 'FALSE')
        ];
    }

    echo json_encode($formatted_guests);
    $mysqli->close();
    exit;
}

// --- Page Setup: Fetch full event configuration for the specific event ---
$event_config = [];
$event_stmt = $mysqli->prepare("SELECT * FROM events WHERE id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$result = $event_stmt->get_result();
if($result) {
    $event_config = $result->fetch_assoc();
}
$event_stmt->close();

// Construct the base URL for RSVP links
$rsvpPageUrlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/rsvp.php";
$json_event_config = json_encode($event_config, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسال دعوات: <?= htmlspecialchars($event_config['event_name'] ?? '') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f0f2f5; }
        .container { max-width: 1200px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .nav-buttons a { color: white; padding: 10px 18px; border-radius: 8px; font-weight: 600; text-decoration: none; }
        .guest-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 0.9em; }
        .guest-table th, .guest-table td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        .guest-table th { background-color: #f3f4f6; }
        .guest-table .actions button, .guest-table .actions a { padding: 6px 10px; border-radius: 6px; font-weight: 500; cursor: pointer; margin: 2px; display: block; width: 100%; text-align: center; text-decoration: none; border: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="text-3xl font-bold">إرسال دعوات: <?= htmlspecialchars($event_config['event_name'] ?? '') ?></h1>
            <div class="nav-buttons">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="events.php" class="bg-gray-500 hover:bg-gray-600">عودة للحفلات</a>
                <?php endif; ?>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600">تسجيل الخروج</a>
            </div>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8">
            <h3 class="text-xl font-bold mb-3">قالب رسالة واتساب</h3>
            <p class="text-sm text-gray-600 mb-2">يمكنك استخدام المتغيرات التالية: [اسم_الضيف], [اسم_العروس], [اسم_العريس], [تاريخ_الزفاف], [مكان_الحفل], [رابط_الدعوة]</p>
            <textarea id="whatsapp-custom-message" class="w-full p-2 border rounded min-h-[150px]"></textarea>
        </div>

        <div class="flex gap-4 mb-4">
            <input type="text" id="guest-search" placeholder="ابحث باسم الضيف..." class="p-2 border rounded-md flex-grow">
            <select id="guest-status-filter" class="p-2 border rounded-md">
                <option value="all">كل الحالات</option>
                <option value="pending">في انتظار الرد</option>
                <option value="confirmed">أكدوا الحضور</option>
                <option value="canceled">اعتذروا عن الحضور</option>
            </select>
        </div>

        <div id="loading-message" class="text-center text-gray-600 text-lg my-10">جاري تحميل بيانات الضيوف...</div>
        
        <table class="guest-table hidden" id="guest-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>رقم الهاتف</th>
                    <th>الحالة</th>
                    <th>عدد الضيوف</th>
                    <th>رابط الدعوة</th>
                    <th>إجراءات الإرسال</th>
                </tr>
            </thead>
            <tbody id="guest-table-body"></tbody>
        </table>
    </div>

    <script>
        const rsvpPageUrlBase = `<?= $rsvpPageUrlBase ?>`;
        const eventConfig = <?= $json_event_config ?>;
        const guestsApiUrl = 'send_invitations.php?event_id=<?= $event_id ?>&fetch_guests=true';
        let allGuestsData = [];

        document.addEventListener('DOMContentLoaded', async () => {
            const loadingMessage = document.getElementById('loading-message');
            const guestTable = document.getElementById('guest-table');
            const guestTableBody = document.getElementById('guest-table-body');
            const whatsappCustomMessageTextarea = document.getElementById('whatsapp-custom-message');
            const guestSearchInput = document.getElementById('guest-search');
            const guestStatusFilter = document.getElementById('guest-status-filter');

            const defaultCustomMessage = `مرحباً [اسم_الضيف]،\n\nنتشرف بدعوتكم لحضور حفل زفاف [اسم_العروس] و [اسم_العريس] في [تاريخ_الزفاف] بـ [مكان_الحفل].\n\nللتأكيد أو الاعتذار، يرجى استخدام رابط دعوتك الشخصية:\n[رابط_الدعوة]`;
            whatsappCustomMessageTextarea.value = localStorage.getItem(`whatsappCustomMessage_${eventConfig.id}`) || defaultCustomMessage;

            function getWhatsAppMessage(guest) {
                const rsvpLink = `${rsvpPageUrlBase}?id=${encodeURIComponent(guest.GuestID)}`;
                let customMessage = whatsappCustomMessageTextarea.value;
                return customMessage
                    .replace(/\[اسم_الضيف\]/g, guest.Name || '')
                    .replace(/\[اسم_العروس\]/g, eventConfig.bride_name_ar || '')
                    .replace(/\[اسم_العريس\]/g, eventConfig.groom_name_ar || '')
                    .replace(/\[تاريخ_الزفاف\]/g, eventConfig.event_date_ar || '')
                    .replace(/\[مكان_الحفل\]/g, eventConfig.venue_ar || '')
                    .replace(/\[رابط_الدعوة\]/g, rsvpLink);
            }

            function renderGuests() {
                const searchTerm = guestSearchInput.value.toLowerCase().trim();
                const filterStatus = guestStatusFilter.value;

                const filteredGuests = allGuestsData.filter(guest => {
                    const matchesSearch = searchTerm === '' || (guest.Name && guest.Name.toLowerCase().includes(searchTerm));
                    const matchesStatus = filterStatus === 'all' || guest.Status === filterStatus;
                    return matchesSearch && matchesStatus;
                });

                guestTableBody.innerHTML = '';
                if (filteredGuests.length === 0) {
                    guestTableBody.innerHTML = '<tr><td colspan="6" class="text-center p-4">لا يوجد ضيوف يطابقون البحث.</td></tr>';
                    return;
                }

                filteredGuests.forEach(guest => {
                    const rsvpLink = `${rsvpPageUrlBase}?id=${encodeURIComponent(guest.GuestID)}`;
                    const whatsappMessage = getWhatsAppMessage(guest);
                    const whatsappLink = `https://wa.me/${guest.PhoneNumber}?text=${encodeURIComponent(whatsappMessage)}`;

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${guest.Name || 'N/A'}</td>
                        <td>${guest.PhoneNumber || 'N/A'}</td>
                        <td>${guest.Status || 'N/A'}</td>
                        <td>${guest.GuestsCount || '1'}</td>
                        <td><a href="${rsvpLink}" target="_blank" class="text-blue-500 hover:underline">عرض الدعوة</a></td>
                        <td class="actions">
                            <button class="copy-button bg-blue-500 text-white" data-message="${escape(whatsappMessage)}">نسخ الرسالة</button>
                            <a href="${whatsappLink}" target="_blank" class="whatsapp-button bg-green-500 text-white">إرسال واتساب</a>
                        </td>
                    `;
                    guestTableBody.appendChild(row);
                });
            }

            try {
                const response = await fetch(guestsApiUrl);
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                allGuestsData = data;
                loadingMessage.style.display = 'none';
                guestTable.classList.remove('hidden');
                renderGuests();
            } catch (error) {
                loadingMessage.textContent = `فشل تحميل بيانات الضيوف: ${error.message}`;
            }

            guestSearchInput.addEventListener('input', renderGuests);
            guestStatusFilter.addEventListener('change', renderGuests);
            whatsappCustomMessageTextarea.addEventListener('input', () => {
                localStorage.setItem(`whatsappCustomMessage_${eventConfig.id}`, whatsappCustomMessageTextarea.value);
                renderGuests();
            });
            
            guestTableBody.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('copy-button')) {
                    const button = e.target;
                    const messageToCopy = unescape(button.dataset.message);
                    navigator.clipboard.writeText(messageToCopy).then(() => {
                        button.textContent = 'تم النسخ!';
                        setTimeout(() => { button.textContent = 'نسخ الرسالة'; }, 2000);
                    });
                }
            });
        });
    </script>
</body>
</html>