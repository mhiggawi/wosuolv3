<?php
session_start();
require_once 'db_config.php';

// Security & Permission Check - منح صلاحيات مشاهدة وتعديل للـ viewer
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'checkin_user', 'viewer'])) {
    header("location: login.php");
    exit;
}

$user_role = $_SESSION['role'];
$user_event_access = $_SESSION['event_id_access'] ?? $_SESSION['event_id'] ?? null;

// Get event ID from URL or session
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT) ?: $user_event_access;

if (!$event_id) {
    echo "<script>alert('لم يتم تحديد الحدث.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Check event access permissions
if ($user_role !== 'admin' && $event_id != $user_event_access) {
    echo "<script>alert('ليس لديك صلاحية للوصول لهذا الحدث.'); window.location.href='dashboard.php';</script>";
    exit;
}

// Get event details
$event_query = "SELECT event_name FROM events WHERE id = ?";
$event_stmt = $mysqli->prepare($event_query);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();
$event = $event_result->fetch_assoc();
$event_stmt->close();

if (!$event) {
    echo "<script>alert('الحدث غير موجود.'); window.location.href='dashboard.php';</script>";
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_guest'])) {
            // Add new guest
            $name_ar = trim($_POST['name_ar']);
            $phone_number = trim($_POST['phone_number']);
            $guests_count = intval($_POST['guests_count']);
            $table_number = trim($_POST['table_number']);
            $assigned_location = trim($_POST['assigned_location']);
            $notes = trim($_POST['notes']);
            
            if (empty($name_ar)) {
                throw new Exception('اسم الضيف مطلوب');
            }
            
            // Generate unique guest ID
            do {
                $guest_id = substr(str_shuffle('0123456789abcdef'), 0, 4);
                $check_stmt = $mysqli->prepare("SELECT COUNT(*) FROM guests WHERE guest_id = ?");
                $check_stmt->bind_param("s", $guest_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $exists = $check_result->fetch_row()[0];
                $check_stmt->close();
            } while ($exists > 0);
            
            $insert_query = "INSERT INTO guests (event_id, guest_id, name_ar, phone_number, guests_count, table_number, assigned_location, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $mysqli->prepare($insert_query);
            $insert_stmt->bind_param("isssssss", $event_id, $guest_id, $name_ar, $phone_number, $guests_count, $table_number, $assigned_location, $notes);
            
            if ($insert_stmt->execute()) {
                $success_message = 'تم إضافة الضيف بنجاح';
            } else {
                throw new Exception('فشل في إضافة الضيف');
            }
            $insert_stmt->close();
        }
        
        if (isset($_POST['update_guest'])) {
            // Update guest
            $guest_id = $_POST['guest_id'];
            $name_ar = trim($_POST['name_ar']);
            $phone_number = trim($_POST['phone_number']);
            $guests_count = intval($_POST['guests_count']);
            $table_number = trim($_POST['table_number']);
            $assigned_location = trim($_POST['assigned_location']);
            $notes = trim($_POST['notes']);
            $status = $_POST['status'];
            
            $update_query = "UPDATE guests SET name_ar = ?, phone_number = ?, guests_count = ?, table_number = ?, assigned_location = ?, notes = ?, status = ? WHERE guest_id = ? AND event_id = ?";
            $update_stmt = $mysqli->prepare($update_query);
            $update_stmt->bind_param("ssisssssi", $name_ar, $phone_number, $guests_count, $table_number, $assigned_location, $notes, $status, $guest_id, $event_id);
            
            if ($update_stmt->execute()) {
                $success_message = 'تم تحديث بيانات الضيف بنجاح';
            } else {
                throw new Exception('فشل في تحديث بيانات الضيف');
            }
            $update_stmt->close();
        }
        
        if (isset($_POST['delete_guest'])) {
            // Delete guest
            $guest_id = $_POST['guest_id'];
            $delete_query = "DELETE FROM guests WHERE guest_id = ? AND event_id = ?";
            $delete_stmt = $mysqli->prepare($delete_query);
            $delete_stmt->bind_param("si", $guest_id, $event_id);
            
            if ($delete_stmt->execute()) {
                $success_message = 'تم حذف الضيف بنجاح';
            } else {
                throw new Exception('فشل في حذف الضيف');
            }
            $delete_stmt->close();
        }
        
        if (isset($_POST['bulk_action'])) {
            // Bulk actions
            $action = $_POST['bulk_action'];
            $selected_guests = $_POST['selected_guests'] ?? [];
            
            if (empty($selected_guests)) {
                throw new Exception('يرجى تحديد ضيف واحد على الأقل');
            }
            
            $placeholders = str_repeat('?,', count($selected_guests) - 1) . '?';
            
            switch ($action) {
                case 'confirm':
                    $bulk_query = "UPDATE guests SET status = 'confirmed' WHERE guest_id IN ($placeholders) AND event_id = ?";
                    break;
                case 'cancel':
                    $bulk_query = "UPDATE guests SET status = 'canceled' WHERE guest_id IN ($placeholders) AND event_id = ?";
                    break;
                case 'checkin':
                    $bulk_query = "UPDATE guests SET checkin_status = 'checked_in', checkin_time = NOW() WHERE guest_id IN ($placeholders) AND event_id = ?";
                    break;
                case 'delete':
                    $bulk_query = "DELETE FROM guests WHERE guest_id IN ($placeholders) AND event_id = ?";
                    break;
                default:
                    throw new Exception('إجراء غير صحيح');
            }
            
            $bulk_stmt = $mysqli->prepare($bulk_query);
            $params = array_merge($selected_guests, [$event_id]);
            $types = str_repeat('s', count($selected_guests)) . 'i';
            $bulk_stmt->bind_param($types, ...$params);
            
            if ($bulk_stmt->execute()) {
                $affected_rows = $bulk_stmt->affected_rows;
                $success_message = "تم تنفيذ الإجراء على {$affected_rows} ضيف بنجاح";
            } else {
                throw new Exception('فشل في تنفيذ الإجراء الجماعي');
            }
            $bulk_stmt->close();
        }
        
        // Quick status update via AJAX
        if (isset($_POST['quick_status_update'])) {
            $guest_id = $_POST['guest_id'];
            $new_status = $_POST['new_status'];
            
            $update_query = "UPDATE guests SET status = ? WHERE guest_id = ? AND event_id = ?";
            $update_stmt = $mysqli->prepare($update_query);
            $update_stmt->bind_param("ssi", $new_status, $guest_id, $event_id);
            
            if ($update_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'تم تحديث الحالة بنجاح']);
            } else {
                echo json_encode(['success' => false, 'message' => 'فشل في تحديث الحالة']);
            }
            $update_stmt->close();
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch guests
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$table_filter = $_GET['table'] ?? '';

$where_conditions = ["event_id = ?"];
$params = [$event_id];
$types = "i";

if (!empty($search)) {
    $where_conditions[] = "(name_ar LIKE ? OR phone_number LIKE ? OR guest_id LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= "sss";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($table_filter)) {
    $where_conditions[] = "table_number = ?";
    $params[] = $table_filter;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);
$guests_query = "SELECT * FROM guests WHERE $where_clause ORDER BY name_ar ASC";
$guests_stmt = $mysqli->prepare($guests_query);
$guests_stmt->bind_param($types, ...$params);
$guests_stmt->execute();
$guests_result = $guests_stmt->get_result();
$guests = $guests_result->fetch_all(MYSQLI_ASSOC);
$guests_stmt->close();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
    SUM(CASE WHEN checkin_status = 'checked_in' THEN 1 ELSE 0 END) as checked_in
FROM guests WHERE event_id = ?";
$stats_stmt = $mysqli->prepare($stats_query);
$stats_stmt->bind_param("i", $event_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Get unique tables for filter
$tables_query = "SELECT DISTINCT table_number FROM guests WHERE event_id = ? AND table_number IS NOT NULL AND table_number != '' ORDER BY table_number";
$tables_stmt = $mysqli->prepare($tables_query);
$tables_stmt->bind_param("i", $event_id);
$tables_stmt->execute();
$tables_result = $tables_stmt->get_result();
$tables = $tables_result->fetch_all(MYSQLI_ASSOC);
$tables_stmt->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الضيوف - <?php echo htmlspecialchars($event['event_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container-fluid {
            max-width: 1400px;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.375rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1.25rem;
        }
        
        .stats-row {
            background: #fff;
            border-radius: 0.375rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .table th {
            background-color: #f8f9fa;
            border-top: none;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }
        
        .badge-status {
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            border-radius: 0.375rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-control, .form-select {
            border-radius: 0.375rem;
        }
        
        .modal-header {
            background-color: #f8f9fa;
        }
        
        .filters-section {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .bulk-actions {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="mb-0">
                                <i class="fas fa-users me-2 text-primary"></i>
                                إدارة ضيوف: <?php echo htmlspecialchars($event['event_name']); ?>
                            </h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-right me-1"></i>العودة للوحة التحكم
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="stat-item bg-light">
                    <div class="stat-number text-primary"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">إجمالي الضيوف</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item bg-light">
                    <div class="stat-number text-success"><?php echo $stats['confirmed']; ?></div>
                    <div class="stat-label">مؤكد الحضور</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item bg-light">
                    <div class="stat-number text-warning"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">بانتظار التأكيد</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-item bg-light">
                    <div class="stat-number text-info"><?php echo $stats['checked_in']; ?></div>
                    <div class="stat-label">حضر فعلياً</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="row g-3">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            
            <div class="col-md-4">
                <label class="form-label">البحث</label>
                <input type="text" class="form-control" name="search" placeholder="اسم الضيف، الهاتف، أو رقم الضيف..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="col-md-3">
                <label class="form-label">حالة الحضور</label>
                <select name="status" class="form-select">
                    <option value="">جميع الحالات</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>بانتظار التأكيد</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                    <option value="canceled" <?php echo $status_filter === 'canceled' ? 'selected' : ''; ?>>ملغي</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">رقم الطاولة</label>
                <select name="table" class="form-select">
                    <option value="">جميع الطاولات</option>
                    <?php foreach ($tables as $table): ?>
                        <option value="<?php echo htmlspecialchars($table['table_number']); ?>" 
                                <?php echo $table_filter === $table['table_number'] ? 'selected' : ''; ?>>
                            طاولة <?php echo htmlspecialchars($table['table_number']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>بحث
                    </button>
                </div>
            </div>
        </form>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGuestModal">
                        <i class="fas fa-user-plus me-1"></i>إضافة ضيف
                    </button>
                    <button type="button" class="btn btn-info" onclick="exportGuests()">
                        <i class="fas fa-download me-1"></i>تصدير Excel
                    </button>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-upload me-1"></i>استيراد ضيوف
                    </button>
                    <a href="?event_id=<?php echo $event_id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-refresh me-1"></i>تحديث
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Guests Table -->
    <div class="card">
        <div class="card-header">
            <form id="bulkForm" method="POST">
                <div class="bulk-actions">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label fw-bold" for="selectAll">
                                    تحديد الكل
                                </label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <select name="bulk_action" class="form-select" required>
                                    <option value="">اختر إجراء جماعي</option>
                                    <option value="confirm">تأكيد الحضور</option>
                                    <option value="cancel">إلغاء الحضور</option>
                                    <option value="checkin">تسجيل الدخول</option>
                                    <option value="delete">حذف</option>
                                </select>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-bolt me-1"></i>تنفيذ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50"><input type="checkbox" id="selectAllHeader"></th>
                            <th>رقم الضيف</th>
                            <th>اسم الضيف</th>
                            <th>رقم الهاتف</th>
                            <th>عدد الأشخاص</th>
                            <th>رقم الطاولة</th>
                            <th>الموقع</th>
                            <th>حالة الحضور</th>
                            <th>تسجيل الدخول</th>
                            <th>الملاحظات</th>
                            <th width="150">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($guests)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-users-slash fa-3x mb-3"></i>
                                        <h5>لا توجد ضيوف</h5>
                                        <p>لم يتم العثور على ضيوف مطابقة لمعايير البحث</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($guests as $guest): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_guests[]" value="<?php echo htmlspecialchars($guest['guest_id']); ?>" class="guest-checkbox">
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($guest['guest_id']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($guest['name_ar']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($guest['phone_number'] ?: 'غير محدد'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $guest['guests_count']; ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($guest['table_number'])): ?>
                                            <span class="badge bg-primary">طاولة <?php echo htmlspecialchars($guest['table_number']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($guest['assigned_location'] ?: 'غير محدد'); ?>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm status-select" 
                                                data-guest-id="<?php echo $guest['guest_id']; ?>" 
                                                data-current-status="<?php echo $guest['status']; ?>">
                                            <option value="pending" <?php echo $guest['status'] === 'pending' ? 'selected' : ''; ?>>بانتظار التأكيد</option>
                                            <option value="confirmed" <?php echo $guest['status'] === 'confirmed' ? 'selected' : ''; ?>>مؤكد</option>
                                            <option value="canceled" <?php echo $guest['status'] === 'canceled' ? 'selected' : ''; ?>>ملغي</option>
                                        </select>
                                    </td>
                                    <td>
                                        <?php if ($guest['checkin_status'] === 'checked_in'): ?>
                                            <span class="badge bg-success">حضر</span>
                                            <?php if ($guest['checkin_time']): ?>
                                                <small class="d-block text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($guest['checkin_time'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">لم يحضر</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($guest['notes'])): ?>
                                            <i class="fas fa-comment text-warning" title="<?php echo htmlspecialchars($guest['notes']); ?>" data-bs-toggle="tooltip"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary btn-sm" 
                                                    onclick="editGuest(<?php echo htmlspecialchars(json_encode($guest)); ?>)" 
                                                    title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($guest['checkin_status'] !== 'checked_in'): ?>
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="quickCheckin('<?php echo $guest['guest_id']; ?>')" 
                                                        title="تسجيل دخول">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteGuest('<?php echo $guest['guest_id']; ?>', '<?php echo htmlspecialchars($guest['name_ar']); ?>')" 
                                                    title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </form>
    </div>
</div>

<!-- Add Guest Modal -->
<div class="modal fade" id="addGuestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>إضافة ضيف جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم الضيف <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name_ar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="tel" class="form-control" name="phone_number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">عدد الأشخاص</label>
                            <input type="number" class="form-control" name="guests_count" value="1" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الطاولة</label>
                            <input type="text" class="form-control" name="table_number">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">الموقع المخصص</label>
                            <select class="form-select" name="assigned_location">
                                <option value="">اختر الموقع</option>
                                <option value="أهل العروس">أهل العروس</option>
                                <option value="أهل العريس">أهل العريس</option>
                                <option value="الأصدقاء">الأصدقاء</option>
                                <option value="العمل">العمل</option>
                                <option value="VIP">VIP</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="add_guest" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>إضافة الضيف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Guest Modal -->
<div class="modal fade" id="editGuestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>تعديل بيانات الضيف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="guest_id" id="edit_guest_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">اسم الضيف <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name_ar" id="edit_name_ar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="tel" class="form-control" name="phone_number" id="edit_phone_number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">عدد الأشخاص</label>
                            <input type="number" class="form-control" name="guests_count" id="edit_guests_count" min="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">رقم الطاولة</label>
                            <input type="text" class="form-control" name="table_number" id="edit_table_number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">حالة الحضور</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="pending">بانتظار التأكيد</option>
                                <option value="confirmed">مؤكد</option>
                                <option value="canceled">ملغي</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">الموقع المخصص</label>
                            <select class="form-select" name="assigned_location" id="edit_assigned_location">
                                <option value="">اختر الموقع</option>
                                <option value="أهل العروس">أهل العروس</option>
                                <option value="أهل العريس">أهل العريس</option>
                                <option value="الأصدقاء">الأصدقاء</option>
                                <option value="العمل">العمل</option>
                                <option value="VIP">VIP</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea class="form-control" name="notes" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" name="update_guest" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-upload me-2"></i>استيراد ضيوف من ملف Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>تعليمات الاستيراد:</h6>
                    <ul class="mb-0">
                        <li>يجب أن يكون الملف بصيغة Excel (.xlsx)</li>
                        <li>الصف الأول يجب أن يحتوي على العناوين</li>
                        <li>الأعمدة المطلوبة: اسم الضيف (مطلوب)، رقم الهاتف، عدد الأشخاص، رقم الطاولة، الموقع، ملاحظات</li>
                    </ul>
                </div>
                
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">اختر ملف Excel</label>
                        <input type="file" class="form-control" id="excelFile" accept=".xlsx,.xls" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">معاينة البيانات:</label>
                        <div id="previewContainer" class="border rounded p-3 bg-light" style="min-height: 100px;">
                            <div class="text-muted text-center">
                                <i class="fas fa-file-excel fa-2x mb-2"></i>
                                <p>اختر ملف Excel لمعاينة البيانات</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" id="importBtn" class="btn btn-success" disabled>
                    <i class="fas fa-upload me-1"></i>استيراد الضيوف
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show');
        }, 5000);
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.guest-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

document.getElementById('selectAllHeader').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.guest-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    document.getElementById('selectAll').checked = this.checked;
});

// Update main checkbox when individual checkboxes change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('guest-checkbox')) {
        const allCheckboxes = document.querySelectorAll('.guest-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.guest-checkbox:checked');
        const selectAllMain = document.getElementById('selectAll');
        const selectAllHeader = document.getElementById('selectAllHeader');
        
        selectAllMain.checked = allCheckboxes.length === checkedCheckboxes.length;
        selectAllHeader.checked = allCheckboxes.length === checkedCheckboxes.length;
    }
});

// Status change handler - just refresh page
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('status-select')) {
        const guestId = e.target.dataset.guestId;
        const newStatus = e.target.value;
        const currentStatus = e.target.dataset.currentStatus;
        
        if (newStatus !== currentStatus) {
            // Show loading state
            e.target.disabled = true;
            
            // Create form and submit to update status
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="guest_id" value="${guestId}">
                <input type="hidden" name="bulk_action" value="${newStatus === 'confirmed' ? 'confirm' : newStatus === 'canceled' ? 'cancel' : 'pending'}">
                <input type="hidden" name="selected_guests[]" value="${guestId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
});

// Edit guest function
function editGuest(guest) {
    document.getElementById('edit_guest_id').value = guest.guest_id;
    document.getElementById('edit_name_ar').value = guest.name_ar;
    document.getElementById('edit_phone_number').value = guest.phone_number || '';
    document.getElementById('edit_guests_count').value = guest.guests_count;
    document.getElementById('edit_table_number').value = guest.table_number || '';
    document.getElementById('edit_status').value = guest.status;
    document.getElementById('edit_assigned_location').value = guest.assigned_location || '';
    document.getElementById('edit_notes').value = guest.notes || '';
    
    new bootstrap.Modal(document.getElementById('editGuestModal')).show();
}

// Quick check-in function
function quickCheckin(guestId) {
    Swal.fire({
        title: 'تسجيل الدخول',
        text: 'هل تريد تسجيل دخول هذا الضيف؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، سجل الدخول',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="guest_id" value="${guestId}">
                <input type="hidden" name="bulk_action" value="checkin">
                <input type="hidden" name="selected_guests[]" value="${guestId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Delete guest function
function deleteGuest(guestId, guestName) {
    Swal.fire({
        title: 'حذف الضيف',
        text: `هل تريد حذف الضيف "${guestName}"؟`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="guest_id" value="${guestId}">
                <input type="hidden" name="delete_guest" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Export to Excel function
function exportGuests() {
    Swal.fire({
        title: 'جاري التصدير...',
        text: 'يرجى الانتظار',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch(`guests_api.php?event_id=<?php echo $event_id; ?>&fetch_guests=1`)
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                const exportData = data.map(guest => ({
                    'رقم الضيف': guest.guest_id,
                    'اسم الضيف': guest.name_ar,
                    'رقم الهاتف': guest.phone_number || '',
                    'عدد الأشخاص': guest.guests_count,
                    'رقم الطاولة': guest.table_number || '',
                    'الموقع المخصص': guest.assigned_location || '',
                    'حالة الحضور': guest.status === 'confirmed' ? 'مؤكد' : 
                                   guest.status === 'canceled' ? 'ملغي' : 'بانتظار التأكيد',
                    'تسجيل الدخول': guest.checkin_status === 'checked_in' ? 'حضر' : 'لم يحضر',
                    'الملاحظات': guest.notes || ''
                }));
                
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.json_to_sheet(exportData);
                
                const colWidths = [
                    {wch: 12}, {wch: 25}, {wch: 15}, {wch: 12}, 
                    {wch: 12}, {wch: 15}, {wch: 15}, {wch: 15}, {wch: 30}
                ];
                ws['!cols'] = colWidths;
                
                XLSX.utils.book_append_sheet(wb, ws, 'قائمة الضيوف');
                
                const now = new Date();
                const dateStr = now.toISOString().split('T')[0];
                const filename = `ضيوف_<?php echo str_replace(' ', '_', $event['event_name']); ?>_${dateStr}.xlsx`;
                
                XLSX.writeFile(wb, filename);
                
                Swal.fire({
                    icon: 'success',
                    title: 'تم التصدير بنجاح',
                    text: 'تم تحميل الملف بنجاح'
                });
            } else {
                throw new Error('Invalid data format');
            }
        })
        .catch(error => {
            console.error('Export error:', error);
            Swal.fire({
                icon: 'error',
                title: 'خطأ في التصدير',
                text: 'حدث خطأ أثناء تصدير البيانات'
            });
        });
}

// Bulk form submission with confirmation
document.getElementById('bulkForm').addEventListener('submit', function(e) {
    const selectedGuests = document.querySelectorAll('.guest-checkbox:checked');
    const action = document.querySelector('select[name="bulk_action"]').value;
    
    if (selectedGuests.length === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'تحديد مطلوب',
            text: 'يرجى تحديد ضيف واحد على الأقل'
        });
        return;
    }
    
    if (!action) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'إجراء مطلوب',
            text: 'يرجى اختيار إجراء للتنفيذ'
        });
        return;
    }
    
    e.preventDefault();
    
    const actionNames = {
        'confirm': 'تأكيد الحضور',
        'cancel': 'إلغاء الحضور',
        'checkin': 'تسجيل الدخول',
        'delete': 'حذف'
    };
    
    Swal.fire({
        title: 'تأكيد الإجراء الجماعي',
        text: `سيتم ${actionNames[action]} لـ ${selectedGuests.length} ضيف. هل تريد المتابعة؟`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، تنفيذ',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: action === 'delete' ? '#dc3545' : '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

// Import functionality
let previewData = [];

document.getElementById('excelFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, {type: 'array'});
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet, {header: 1});
            
            if (jsonData.length < 2) {
                throw new Error('الملف يجب أن يحتوي على صف العناوين وصف واحد على الأقل من البيانات');
            }
            
            previewData = jsonData;
            displayPreview(jsonData);
            document.getElementById('importBtn').disabled = false;
            
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في قراءة الملف',
                text: error.message
            });
            document.getElementById('importBtn').disabled = true;
        }
    };
    reader.readAsArrayBuffer(file);
});

function displayPreview(data) {
    const container = document.getElementById('previewContainer');
    const headers = data[0];
    const rows = data.slice(1, 6); // Show first 5 rows
    
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
    `;
    
    headers.forEach(header => {
        html += `<th>${header || 'عمود فارغ'}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    rows.forEach(row => {
        html += '<tr>';
        headers.forEach((header, index) => {
            html += `<td>${row[index] || ''}</td>`;
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    
    if (data.length > 6) {
        html += `<small class="text-muted">عرض 5 صفوف من أصل ${data.length - 1} صف</small>`;
    }
    
    container.innerHTML = html;
}

document.getElementById('importBtn').addEventListener('click', function() {
    if (previewData.length < 2) {
        Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'لا توجد بيانات للاستيراد'
        });
        return;
    }
    
    Swal.fire({
        title: 'تأكيد الاستيراد',
        text: `سيتم استيراد ${previewData.length - 1} ضيف. هل تريد المتابعة؟`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، استيراد',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            importGuests();
        }
    });
});

function importGuests() {
    Swal.fire({
        title: 'جاري الاستيراد...',
        text: 'يرجى الانتظار',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const headers = previewData[0];
    const rows = previewData.slice(1);
    
    // Map headers to expected fields
    const fieldMapping = {
        'اسم الضيف': 'name_ar',
        'الاسم': 'name_ar',
        'Name': 'name_ar',
        'رقم الهاتف': 'phone_number',
        'الهاتف': 'phone_number',
        'Phone': 'phone_number',
        'عدد الأشخاص': 'guests_count',
        'العدد': 'guests_count',
        'Count': 'guests_count',
        'رقم الطاولة': 'table_number',
        'الطاولة': 'table_number',
        'Table': 'table_number',
        'الموقع': 'assigned_location',
        'Location': 'assigned_location',
        'ملاحظات': 'notes',
        'Notes': 'notes'
    };
    
    const guests = rows.map(row => {
        const guest = {};
        headers.forEach((header, index) => {
            const field = fieldMapping[header];
            if (field) {
                guest[field] = row[index] || '';
            }
        });
        
        // Ensure required fields
        if (!guest.name_ar) {
            guest.name_ar = row[0] || 'ضيف بدون اسم';
        }
        guest.guests_count = parseInt(guest.guests_count) || 1;
        
        return guest;
    });
    
    // Send to server
    fetch('import_guests.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            event_id: <?php echo $event_id; ?>,
            guests: guests
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم الاستيراد بنجاح',
                text: `تم استيراد ${data.imported_count} ضيف بنجاح`
            }).then(() => {
                location.reload();
            });
        } else {
            throw new Error(data.message || 'حدث خطأ أثناء الاستيراد');
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'خطأ في الاستيراد',
            text: error.message
        });
    });
}
</script>

</body>
</html>