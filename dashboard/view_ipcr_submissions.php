<?php
session_start();
include('../db/connection.php');

// Debug mode - set to false in production
define('DEBUG_MODE', true);

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Admin authentication
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle Profile Data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Admin';
 $email = $_SESSION['email'] ?? '';

// Updated Notification Count
 $notificationCount = 0;
 $sql = "SELECT COUNT(*) as unread FROM admindashboard_notification WHERE status = 'unread'";
 $result = mysqli_query($conn, $sql);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $notificationCount = $row['unread'];
}

// Pagination settings
 $limit = 10;
 $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
 $offset = ($page - 1) * $limit;

// Get current user ID from session
 $currentUserId = null;
if (isset($_SESSION['user_id'])) {
    $currentUserId = $_SESSION['user_id'];
} else {
    // Try to get user ID from email or full_name
    $userIdentifier = $_SESSION['email'] ?? $_SESSION['full_name'] ?? '';
    if (!empty($userIdentifier)) {
        // Try to find user by email first
        $userQuery = "SELECT id FROM users WHERE email = ?";
        $userStmt = mysqli_prepare($conn, $userQuery);
        mysqli_stmt_bind_param($userStmt, "s", $userIdentifier);
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);
        
        if ($userRow = mysqli_fetch_assoc($userResult)) {
            $currentUserId = $userRow['id'];
            // Set it in session for future use
            $_SESSION['user_id'] = $currentUserId;
        } else {
            // If not found by email, try by full_name
            $userQuery = "SELECT id FROM users WHERE full_name = ?";
            $userStmt = mysqli_prepare($conn, $userQuery);
            mysqli_stmt_bind_param($userStmt, "s", $userIdentifier);
            mysqli_stmt_execute($userStmt);
            $userResult = mysqli_stmt_get_result($userStmt);
            
            if ($userRow = mysqli_fetch_assoc($userResult)) {
                $currentUserId = $userRow['id'];
                // Set it in session for future use
                $_SESSION['user_id'] = $currentUserId;
            }
        }
        mysqli_stmt_close($userStmt);
    }
}

// If we still don't have a valid user ID, use a default admin ID
if ($currentUserId === null) {
    // Try to find any admin user
    $adminQuery = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
    $adminResult = mysqli_query($conn, $adminQuery);
    if ($adminRow = mysqli_fetch_assoc($adminResult)) {
        $currentUserId = $adminRow['id'];
    } else {
        // As a last resort, use ID 1 (assuming it exists)
        $currentUserId = 1;
    }
}

// Handle delete with CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid request. Please try again.";
        header("Location: view_ipcr_submissions.php");
        exit();
    }
    
    $deleteId = $_POST['delete'];
    
    // Debug: Log the delete attempt
    if (DEBUG_MODE) {
        error_log("Delete attempt for ID: " . $deleteId);
    }
    
    // Validate that the ID is a numeric value
    if (is_numeric($deleteId)) {
        // Start transaction to ensure atomicity
        mysqli_begin_transaction($conn);
        
        try {
            // Get the submission details
            $selectQuery = "SELECT * FROM ipcr_submissions WHERE id = ?";
            $selectStmt = mysqli_prepare($conn, $selectQuery);
            mysqli_stmt_bind_param($selectStmt, "i", $deleteId);
            mysqli_stmt_execute($selectStmt);
            $result = mysqli_stmt_get_result($selectStmt);
            
            if ($result->num_rows === 0) {
                throw new Exception("Submission not found");
            }
            
            $submission = $result->fetch_assoc();
            
            // Insert into deleted_submissions table (without created_at)
            $insertQuery = "INSERT INTO deleted_submissions (original_id, faculty_name, department, period, final_avg_rating, status, deleted_at, deleted_by) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
            $insertStmt = mysqli_prepare($conn, $insertQuery);
            
            mysqli_stmt_bind_param($insertStmt, "isssdsi", 
                $submission['id'],
                $submission['faculty_name'],
                $submission['department'],
                $submission['period'],
                $submission['final_avg_rating'],
                $submission['status'],
                $currentUserId
            );
            
            if (!mysqli_stmt_execute($insertStmt)) {
                throw new Exception("Failed to insert into deleted_submissions: " . mysqli_error($conn));
            }
            
            // Now delete from original table
            $deleteQuery = "DELETE FROM ipcr_submissions WHERE id = ?";
            $deleteStmt = mysqli_prepare($conn, $deleteQuery);
            mysqli_stmt_bind_param($deleteStmt, "i", $deleteId);
            
            if (!mysqli_stmt_execute($deleteStmt)) {
                throw new Exception("Failed to delete from ipcr_submissions: " . mysqli_error($conn));
            }
            
            // If we got here, both operations succeeded
            mysqli_commit($conn);
            
            if (DEBUG_MODE) {
                error_log("Successfully moved ID: " . $deleteId . " to recycle bin by user ID: " . $currentUserId);
            }
            $_SESSION['success'] = "Submission moved to recycle bin successfully!";
            
            // Close statements
            mysqli_stmt_close($selectStmt);
            mysqli_stmt_close($insertStmt);
            mysqli_stmt_close($deleteStmt);
            
        } catch (Exception $e) {
            // Roll back the transaction in case of any error
            mysqli_rollback($conn);
            
            if (DEBUG_MODE) {
                error_log("Error moving submission to recycle bin: " . $e->getMessage());
            }
            $_SESSION['error'] = "Error moving submission to recycle bin: " . $e->getMessage();
            
            // Close statements if they were opened
            if (isset($selectStmt)) mysqli_stmt_close($selectStmt);
            if (isset($insertStmt)) mysqli_stmt_close($insertStmt);
            if (isset($deleteStmt)) mysqli_stmt_close($deleteStmt);
        }
    } else {
        if (DEBUG_MODE) {
            error_log("Invalid ID: " . $deleteId);
        }
        $_SESSION['error'] = "Invalid submission ID: " . $deleteId;
    }
    
    // Redirect to prevent form resubmission
    header("Location: view_ipcr_submissions.php");
    exit();
}

// Get total count for pagination
 $countQuery = "SELECT COUNT(*) as total FROM ipcr_submissions";
 $countResult = mysqli_query($conn, $countQuery);
 $totalRows = mysqli_fetch_assoc($countResult)['total'];
 $totalPages = ceil($totalRows / $limit);

// Get submissions with pagination
 $query = "SELECT * FROM ipcr_submissions ORDER BY created_at DESC LIMIT $offset, $limit";
 $result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View IPCR Submissions - IPCR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --bg-light: #f0f2f5;
            --text-light: #333;
            --card-light: #ffffff;
            --sidebar-bg-light: #3a6ea5;
            --bg-dark: #1e1e2f;
            --text-dark: #dcdcdc;
            --card-dark: #2c2c3e;
            --sidebar-bg-dark: #252536;
        }
        html[data-theme='dark'] body {
            background-color: var(--bg-dark);
            color: var(--text-dark);
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-light);
        }
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background-color: var(--sidebar-bg-light);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            z-index: 1000;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        [data-theme='dark'] .topbar {
            background-color: var(--sidebar-bg-dark);
        }
        .sidebar {
            width: 250px;
            background-color: var(--sidebar-bg-light);
            color: white;
            padding: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            margin-top: 70px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 999;
        }
        [data-theme='dark'] .sidebar {
            background-color: var(--sidebar-bg-dark);
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            margin: 5px 0;
            padding: 12px 10px;
            border-radius: 5px;
        }
        .sidebar a i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background: white;
            color: #3a6ea5;
            font-weight: bold;
        }
        .main-content {
            margin-left: 250px;
            margin-top: 70px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        .sidebar-footer {
            margin-top: auto;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
        }
        .notification-bell {
            position: relative;
            cursor: pointer;
            color: white;
        }
        .notification-bell .badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
        }
        .dark-toggle {
            cursor: pointer;
            font-size: 1.2rem;
            color: white;
        }
        .settings {
            position: relative;
            display: inline-block;
        }
        .settings-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 60px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            min-width: 280px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            z-index: 1050;
        }
        [data-theme='dark'] .settings-menu {
            background-color: #2e2e2e;
            color: white;
        }
        .settings-header {
            background-color: #3a6ea5;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .settings-menu a {
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            border-bottom: 1px solid #f1f1f1;
            transition: background-color 0.2s;
        }
        .settings-menu a:hover {
            background-color: #f7f7f7;
        }
        [data-theme='dark'] .settings-menu a {
            color: white;
            border-bottom: 1px solid #444;
        }
        [data-theme='dark'] .settings-menu a:hover {
            background-color: #3b3b3b;
        }
        .profile-img-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .profile-img-sm:hover {
            transform: scale(1.05);
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.5);
        }
        .profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            margin: 10px auto;
        }
        .pcc-logo {
            width: 40px;
            height: auto;
            border-radius: 10px;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Page-specific styles */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            width: 100%;
            background-color: var(--card-light);
        }
        [data-theme='dark'] .card {
            background-color: var(--card-dark);
        }
        .table thead {
            background-color: var(--card-light);
        }
        [data-theme='dark'] .table thead {
            background-color: var(--card-dark);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        .status-Pending {
            background-color: #ffeeba;
            color: #856404;
        }
        .status-Reviewed {
            background-color: #c3e6cb;
            color: #155724;
        }
        .status-Approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .table-responsive {
            margin-top: 20px;
            border-radius: 10px;
            overflow: hidden;
        }
        .alert {
            margin-bottom: 20px;
        }
        .debug-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 0.875rem;
        }
        
        /* Search input with icon */
        .search-container {
            position: relative;
            max-width: 250px;
        }
        
        .search-input {
            padding: 0.375rem 0.75rem 0.375rem 2.5rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
            width: 100%;
        }

        [data-theme='dark'] .search-input {
            color: #dcdcdc;
            background-color: #2c2c3e;
            border-color: #444;
        }

        .search-input:focus {
            color: #212529;
            background-color: #fff;
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }

        [data-theme='dark'] .search-input:focus {
            color: #dcdcdc;
            background-color: #2c2c3e;
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        
        [data-theme='dark'] .search-icon {
            color: #adb5bd;
        }
        
        /* Status filter */
        .filter-container {
            position: relative;
            max-width: 200px;
        }
        
        .filter-select {
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
            width: 100%;
        }

        [data-theme='dark'] .filter-select {
            color: #dcdcdc;
            background-color: #2c2c3e;
            border-color: #444;
        }

        .filter-select:focus {
            color: #212529;
            background-color: #fff;
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }

        [data-theme='dark'] .filter-select:focus {
            color: #dcdcdc;
            background-color: #2c2c3e;
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }
        
        /* Pagination */
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        .page-link {
            color: var(--sidebar-bg-light);
        }
        [data-theme='dark'] .page-link {
            color: #9ca3af;
        }
        .page-item.active .page-link {
            background-color: var(--sidebar-bg-light);
            border-color: var(--sidebar-bg-light);
        }
        [data-theme='dark'] .page-item.active .page-link {
            background-color: var(--sidebar-bg-dark);
            border-color: var(--sidebar-bg-dark);
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .table-responsive {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .menu-toggle {
                display: block;
            }
            .topbar {
                padding: 0 10px;
            }
            .profile-img-sm {
                width: 30px;
                height: 30px;
            }
            .pcc-logo {
                width: 35px;
            }
            .settings-menu {
                min-width: 250px;
            }
            .table-responsive {
                font-size: 0.8rem;
            }
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.775rem;
            }
            .search-container, .filter-container {
                max-width: 100%;
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 576px) {
            /* Card view for table on small screens */
            .table-responsive table {
                display: none;
            }
            .card-view {
                display: block;
            }
            .submission-card {
                border: 1px solid #ddd;
                border-radius: 8px;
                margin-bottom: 15px;
                padding: 15px;
                background-color: var(--card-light);
            }
            [data-theme='dark'] .submission-card {
                background-color: var(--card-dark);
                border-color: #444;
            }
            .card-row {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            .card-label {
                font-weight: bold;
                color: #666;
            }
            [data-theme='dark'] .card-label {
                color: #aaa;
            }
            .card-actions {
                margin-top: 10px;
                display: flex;
                gap: 10px;
            }
            
            .status-badge {
                font-size: 0.75rem;
                padding: 3px 8px;
            }
            .btn-sm {
                padding: 0.2rem 0.4rem;
                font-size: 0.75rem;
            }
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }
        
        /* Hide card view on larger screens */
        @media (min-width: 577px) {
            .card-view {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="topbar">
    <div style="display: flex; align-items: center;">
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation menu">
            <i class="fas fa-bars"></i>
        </button>
        <img src="../images/pcc1.png" alt="PCC Logo" class="pcc-logo">
    </div>
    <div class="topbar-right">
        <a href="notifications.php" class="notification-bell" aria-label="Notifications">
            <i class="fas fa-bell fa-lg"></i>
            <?php if ($notificationCount > 0): ?>
                <span class="badge"><?= $notificationCount ?></span>
            <?php endif; ?>
        </a>
        <span class="dark-toggle" id="toggleTheme" title="Toggle Dark Mode" aria-label="Toggle dark mode">
            <i class="fas fa-moon"></i>
        </span>
        <div class="settings" id="profileSettings">
            <img src="<?= $profilePicture ?>" class="profile-img-sm" alt="Profile">
            <div class="settings-menu">
                <div class="settings-header">
                    <img src="<?= $profilePicture ?>" class="profile-img" alt="Profile">
                    <h6><?= htmlspecialchars($fullName) ?></h6>
                    <small><?= htmlspecialchars($email) ?></small>
                </div>
                <a href="my_account.php"><i class="fas fa-user-edit me-2"></i> My Account</a>
                <a href="admin_view_profile.php"><i class="fas fa-eye me-2"></i> View Profile</a>
                <a href="Change_Password.php"><i class="fas fa-key me-2"></i> Change Password</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>
    </div>
</div>
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
    <a href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
    <a href="manage_users.php"><i class="fas fa-users me-2"></i> Manage Users</a>
    <a href="department_reports.php"><i class="fas fa-layer-group me-2"></i> Department Reports</a>
    <a href="announcements.php"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
    <a href="view_ipcr_submissions.php" class="active"><i class="fas fa-file-alt me-2"></i> View Submissions</a>
    <a href="deadline_settings.php"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
    <a href="status_report.php"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
    <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
    <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</nav>
<div class="main-content">
    <!-- Session Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">📄 All PCR Submissions</h4>
        <div class="d-flex gap-2 flex-wrap">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search submissions...">
            </div>
        </div>
    </div>
    
    <!-- Table View -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="submissionsTable">
                <thead>
                    <tr class="text-center">
                        <th>ID</th>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Period</th>
                        <th>Final Rating</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $i = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                            <tr class="text-center align-middle">
                                <td><?= $i++; ?></td>
                                <td><?= htmlspecialchars($row['faculty_name']); ?></td>
                                <td><?= htmlspecialchars($row['department']); ?></td>
                                <td><?= htmlspecialchars($row['period']); ?></td>
                                <td><?= htmlspecialchars($row['final_avg_rating']); ?></td>
                                <td>
                                    <span class="status-badge status-<?= $row['status']; ?>">
                                        <?= $row['status']; ?>
                                    </span>
                                </td>
                                <td><?= date('F d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="view_submission.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="view_ipcr_submissions.php" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to move this submission to the recycle bin?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="delete" value="<?= htmlspecialchars($row['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No submissions found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Card View for Mobile -->
        <div class="card-view">
            <?php if ($result->num_rows > 0): ?>
                <?php $result->data_seek(0); // Reset result pointer ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="submission-card">
                        <div class="card-row">
                            <span class="card-label">Faculty:</span>
                            <span><?= htmlspecialchars($row['faculty_name']); ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Department:</span>
                            <span><?= htmlspecialchars($row['department']); ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Period:</span>
                            <span><?= htmlspecialchars($row['period']); ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Rating:</span>
                            <span><?= htmlspecialchars($row['final_avg_rating']); ?></span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Status:</span>
                            <span class="status-badge status-<?= $row['status']; ?>">
                                <?= $row['status']; ?>
                            </span>
                        </div>
                        <div class="card-row">
                            <span class="card-label">Date:</span>
                            <span><?= date('F d, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="card-actions">
                            <a href="view_submission.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <form method="POST" action="view_ipcr_submissions.php" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to move this submission to the recycle bin?');">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="delete" value="<?= htmlspecialchars($row['id']); ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                    No submissions found.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Submissions pagination">
                <ul class="pagination">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#submissionsTable tbody tr');
        let cards = document.querySelectorAll('.submission-card');
        
        // Filter table rows
        rows.forEach(function (row) {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
        
        // Filter cards
        cards.forEach(function (card) {
            let text = card.textContent.toLowerCase();
            card.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    
    // Theme toggle
    document.addEventListener('DOMContentLoaded', function() {
        const toggleTheme = document.getElementById('toggleTheme');
        if (toggleTheme) {
            toggleTheme.addEventListener('click', () => {
                const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
            });
        }
        
        // Profile settings dropdown
        const profileSettings = document.getElementById("profileSettings");
        if (profileSettings) {
            const settingsMenu = profileSettings.querySelector(".settings-menu");
            
            profileSettings.addEventListener("click", function (e) {
                e.stopPropagation();
                settingsMenu.style.display = settingsMenu.style.display === "block" ? "none" : "block";
            });

            // Close dropdown when clicking on links inside it
            const menuLinks = settingsMenu.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    settingsMenu.style.display = 'none';
                });
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener("click", function (e) {
            const profileSettings = document.getElementById("profileSettings");
            if (profileSettings) {
                const settingsMenu = profileSettings.querySelector(".settings-menu");
                if (settingsMenu && !profileSettings.contains(e.target)) {
                    settingsMenu.style.display = "none";
                }
            }
        });
        
        // Sidebar toggle for mobile
        const menuToggle = document.getElementById("menuToggle");
        const sidebar = document.getElementById("sidebar");
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener("click", function (e) {
                e.stopPropagation();
                sidebar.classList.toggle("active");
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener("click", function (e) {
                if (window.innerWidth <= 768 && 
                    !sidebar.contains(e.target) && 
                    !menuToggle.contains(e.target)) {
                    sidebar.classList.remove("active");
                }
            });
            
            // Handle window resize
            window.addEventListener("resize", function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove("active");
                }
            });
        }
        
        // Prevent multiple form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitButton = this.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Moving to recycle bin...';
                }
            });
        });
    });
</script>
</body>
</html>