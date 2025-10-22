<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

// Handle Profile Data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Admin';
 $email = $_SESSION['email'] ?? '';

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'mark_as_read' && isset($_POST['notification_id'])) {
                $notificationId = (int)$_POST['notification_id'];
                $updateSql = "UPDATE admindashboard_notification SET status = 'read' WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $notificationId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $_SESSION['message'] = "Notification marked as read.";
                $_SESSION['message_type'] = "success";
                header("Location: " . basename(__FILE__));
                exit();
            } elseif ($_POST['action'] === 'mark_all_as_read') {
                $updateSql = "UPDATE admindashboard_notification SET status = 'read' WHERE status = 'unread'";
                if (!$conn->query($updateSql)) {
                    throw new Exception("Query failed: " . $conn->error);
                }
                
                $_SESSION['message'] = "All notifications marked as read.";
                $_SESSION['message_type'] = "success";
                header("Location: " . basename(__FILE__));
                exit();
            } elseif ($_POST['action'] === 'delete_notification' && isset($_POST['notification_id'])) {
                $notificationId = (int)$_POST['notification_id'];
                $deleteSql = "DELETE FROM admindashboard_notification WHERE id = ?";
                $stmt = $conn->prepare($deleteSql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmt->bind_param("i", $notificationId);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $_SESSION['message'] = "Notification deleted successfully.";
                $_SESSION['message_type'] = "success";
                header("Location: " . basename(__FILE__));
                exit();
            } elseif ($_POST['action'] === 'clear_all_notifications') {
                $deleteSql = "DELETE FROM admindashboard_notification";
                if (!$conn->query($deleteSql)) {
                    throw new Exception("Query failed: " . $conn->error);
                }
                
                $_SESSION['message'] = "All notifications cleared successfully.";
                $_SESSION['message_type'] = "success";
                header("Location: " . basename(__FILE__));
                exit();
            }
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header("Location: " . basename(__FILE__));
        exit();
    }
}

// Get filter parameters
 $statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
 $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
 $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
 $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings
 $itemsPerPage = 10;
 $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $currentPage = max(1, $currentPage);

// Build the query with filters
 $query = "SELECT * FROM admindashboard_notification WHERE 1=1";
 $params = [];
 $types = '';

if ($statusFilter === 'unread') {
    $query .= " AND status = 'unread'";
} elseif ($statusFilter === 'read') {
    $query .= " AND status = 'read'";
}

if (!empty($startDate)) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $startDate;
    $types .= 's';
}

if (!empty($endDate)) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $endDate;
    $types .= 's';
}

if (!empty($searchTerm)) {
    $query .= " AND (title LIKE ? OR message LIKE ?)";
    $searchPattern = "%$searchTerm%";
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $types .= 'ss';
}

// Get total count for pagination
 $countQuery = "SELECT COUNT(*) as total FROM admindashboard_notification WHERE 1=1";
 $countParams = [];
 $countTypes = '';

if ($statusFilter === 'unread') {
    $countQuery .= " AND status = 'unread'";
} elseif ($statusFilter === 'read') {
    $countQuery .= " AND status = 'read'";
}

if (!empty($startDate)) {
    $countQuery .= " AND DATE(created_at) >= ?";
    $countParams[] = $startDate;
    $countTypes .= 's';
}

if (!empty($endDate)) {
    $countQuery .= " AND DATE(created_at) <= ?";
    $countParams[] = $endDate;
    $countTypes .= 's';
}

if (!empty($searchTerm)) {
    $countQuery .= " AND (title LIKE ? OR message LIKE ?)";
    $searchPattern = "%$searchTerm%";
    $countParams[] = $searchPattern;
    $countParams[] = $searchPattern;
    $countTypes .= 'ss';
}

// Execute count query
 $countStmt = $conn->prepare($countQuery);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
 $countStmt->execute();
 $countResult = $countStmt->get_result();
 $totalCount = $countResult->fetch_assoc()['total'];
 $totalPages = max(1, ceil($totalCount / $itemsPerPage));
 $currentPage = min($currentPage, $totalPages);

// Add pagination to main query
 $offset = ($currentPage - 1) * $itemsPerPage;
 $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
 $params[] = $itemsPerPage;
 $params[] = $offset;
 $types .= 'ii';

// Prepare and execute the main query
 $stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
 $stmt->execute();
 $result = $stmt->get_result();
 $notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

// Get notification counts
 $unreadCount = 0;
 $readCount = 0;

 $countSql = "SELECT 
                SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread_count,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count
            FROM admindashboard_notification";
 $countResult = $conn->query($countSql);
if ($countRow = $countResult->fetch_assoc()) {
    $unreadCount = (int)$countRow['unread_count'];
    $readCount = (int)$countRow['read_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Notifications - PCR Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --bg-light: #f0f2f5;
            --text-light: #333;
            --card-light: #ffffff;
            --primary-color: #3a6ea5;
            --secondary-color: #6a9df7;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-light);
        }
        
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .main-content {
            margin-top: 70px;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background-color: var(--card-light);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .notification-item {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: var(--card-light);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .notification-item.unread {
            border-left-color: var(--danger-color);
            background-color: rgba(220, 53, 69, 0.1);
            border-left-width: 5px;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .notification-item.unread .notification-title {
            color: var(--danger-color);
            font-weight: 700;
        }
        
        .notification-message {
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .notification-status {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 12px;
        }
        
        .notification-status.unread {
            background-color: var(--danger-color);
            color: white;
            font-weight: bold;
        }
        
        .notification-status.read {
            background-color: var(--success-color);
            color: white;
        }
        
        .notification-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-section {
            background-color: var(--card-light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .stats-card {
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
            background-color: var(--card-light);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card.total {
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card.unread {
            border-left: 4px solid var(--danger-color);
        }
        
        .stats-card.read {
            border-left: 4px solid var(--success-color);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-card.unread .stats-number {
            color: var(--danger-color);
        }
        
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .empty-notifications {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .empty-notifications i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            border-radius: 0.25rem;
        }
        
        .page-link {
            position: relative;
            display: block;
            padding: 0.5rem 0.75rem;
            margin-left: -1px;
            line-height: 1.25;
            color: var(--primary-color);
            background-color: #fff;
            border: 1px solid #dee2e6;
        }
        
        .page-link:hover {
            z-index: 2;
            color: var(--primary-color);
            text-decoration: none;
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        
        .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            cursor: auto;
            background-color: #fff;
            border-color: #dee2e6;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .btn-outline-danger {
            border-color: var(--danger-color);
            color: var(--danger-color);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .back-button {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            margin-right: 15px;
        }
        
        .back-button:hover {
            color: #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .stats-card {
                margin-bottom: 10px;
            }
            
            .notification-actions {
                flex-direction: column;
            }
            
            .filter-section .row > div {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div style="display: flex; align-items: center;">
            <button class="back-button" onclick="history.back()" title="Go Back">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="ms-3">
                <h5 class="mb-0 text-white">All Notifications</h5>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Notification List</h4>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); endif; ?>
            
            <!-- Notification Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card total" onclick="window.location.href='<?= basename(__FILE__) ?>?status=all'">
                        <div class="stats-number"><?= $unreadCount + $readCount ?></div>
                        <div class="stats-label">Total Notifications</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card unread" onclick="window.location.href='<?= basename(__FILE__) ?>?status=unread'">
                        <div class="stats-number"><?= $unreadCount ?></div>
                        <div class="stats-label">Unread</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card read" onclick="window.location.href='<?= basename(__FILE__) ?>?status=read'">
                        <div class="stats-number"><?= $readCount ?></div>
                        <div class="stats-label">Read</div>
                    </div>
                </div>
            </div>
            
            <!-- Notifications List -->
            <?php if (count($notifications) > 0): ?>
            <div class="notifications-list">
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?= $notification['status'] === 'unread' ? 'unread' : '' ?>">
                    <span class="notification-status <?= $notification['status'] === 'unread' ? 'unread' : 'read' ?>">
                        <?= $notification['status'] === 'unread' ? 'New' : 'Read' ?>
                    </span>
                    <div class="notification-title">
                        <?php 
                        $title = !empty($notification['title']) ? htmlspecialchars($notification['title']) : 'Notification';
                        // Replace "IPCR" with "PCR" in the title
                        echo str_replace('IPCR', 'PCR', $title);
                        ?>
                    </div>
                    <div class="notification-message">
                        <?php 
                        $message = !empty($notification['message']) ? htmlspecialchars($notification['message']) : 'No details available';
                        // Replace "IPCR" with "PCR" in the message
                        echo str_replace('IPCR', 'PCR', $message);
                        ?>
                    </div>
                    <div class="notification-time">
                        <i class="far fa-clock me-1"></i> 
                        <?= date('F j, Y, g:i A', strtotime($notification['created_at'])) ?>
                    </div>
                    <div class="notification-actions">
                        <?php if ($notification['status'] === 'unread'): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="action" value="mark_as_read">
                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-check me-1"></i> Mark as Read
                            </button>
                        </form>
                        <?php endif; ?>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                            <input type="hidden" name="action" value="delete_notification">
                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash me-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Notifications pagination">
                    <ul class="pagination">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage - 1 ?>&status=<?= htmlspecialchars($statusFilter) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&search=<?= htmlspecialchars($searchTerm) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&status=' . htmlspecialchars($statusFilter) . '&start_date=' . htmlspecialchars($startDate) . '&end_date=' . htmlspecialchars($endDate) . '&search=' . htmlspecialchars($searchTerm) . '">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= htmlspecialchars($statusFilter) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&search=<?= htmlspecialchars($searchTerm) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; 
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&status=' . htmlspecialchars($statusFilter) . '&start_date=' . htmlspecialchars($startDate) . '&end_date=' . htmlspecialchars($endDate) . '&search=' . htmlspecialchars($searchTerm) . '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage + 1 ?>&status=<?= htmlspecialchars($statusFilter) ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&search=<?= htmlspecialchars($searchTerm) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div class="empty-notifications">
                <i class="fas fa-bell-slash"></i>
                <h5>No Notifications Found</h5>
                <p>No notifications match your current filters.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize date pickers
        flatpickr("#start_date", {
            dateFormat: "m/d/Y",
            allowInput: true
        });
        
        flatpickr("#end_date", {
            dateFormat: "m/d/Y",
            allowInput: true
        });
    </script>
</body>
</html>