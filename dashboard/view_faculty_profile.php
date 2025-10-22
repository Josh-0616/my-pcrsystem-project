<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../db/connection.php';

 $facultyId = $_GET['id'] ?? 0;
if (!is_numeric($facultyId) || $facultyId <= 0) {
    $_SESSION['error'] = "Invalid faculty ID";
    header("Location: manage_users.php");
    exit();
}

// First, let's check what columns exist in the users table
 $usersColumns = [];
 $checkColumnsResult = mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($checkColumnsResult) {
    while ($column = mysqli_fetch_assoc($checkColumnsResult)) {
        $usersColumns[] = $column['Field'];
    }
}

// Determine the name column(s) to use
 $nameColumn = null;
if (in_array('full_name', $usersColumns)) {
    $nameColumn = 'full_name';
} elseif (in_array('name', $usersColumns)) {
    $nameColumn = 'name';
} elseif (in_array('username', $usersColumns)) {
    $nameColumn = 'username';
} elseif (in_array('first_name', $usersColumns) && in_array('last_name', $usersColumns)) {
    $nameColumn = 'CONCAT(first_name, " ", last_name) as full_name';
}

// Build the query based on available columns
 $selectSql = "SELECT *";
if ($nameColumn) {
    if (strpos($nameColumn, 'CONCAT') !== false) {
        $selectSql = "SELECT *, $nameColumn";
    } else {
        $selectSql = "SELECT *, $nameColumn as full_name";
    }
}

 $selectSql .= " FROM users WHERE id = ? AND role = 'faculty'";

// Get faculty details
 $stmt = $conn->prepare($selectSql);
 $stmt->bind_param("i", $facultyId);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Faculty not found";
    header("Location: manage_users.php");
    exit();
}

 $faculty = $result->fetch_assoc();
 $stmt->close();

// Get faculty department if department_id column exists
 $departmentName = 'Not assigned';
if (in_array('department_id', $usersColumns) && !empty($faculty['department_id'])) {
    $deptStmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
    $deptStmt->bind_param("i", $faculty['department_id']);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    if ($deptRow = $deptResult->fetch_assoc()) {
        $departmentName = $deptRow['name'];
    }
    $deptStmt->close();
}

// Get faculty IPCR submissions count
 $submissionsCount = 0;
 $submissionsStmt = $conn->prepare("SELECT COUNT(*) as count FROM ipcr_submissions WHERE faculty_id = ?");
 $submissionsStmt->bind_param("i", $facultyId);
 $submissionsStmt->execute();
 $submissionsResult = $submissionsStmt->get_result();
if ($submissionsRow = $submissionsResult->fetch_assoc()) {
    $submissionsCount = $submissionsRow['count'];
}
 $submissionsStmt->close();

// Get profile picture
 $profilePicture = '../uploads/1.png'; // Default
if (!empty($faculty['profile_picture'])) {
    $profilePicture = '../uploads/' . $faculty['profile_picture'];
}

// Get faculty name with fallbacks
 $facultyName = $faculty['full_name'] ?? $faculty['name'] ?? $faculty['username'] ?? 'Unknown Faculty';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Faculty Profile - IPCR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
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
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background-color: var(--card-light);
            margin-bottom: 20px;
        }
        [data-theme='dark'] .card {
            background-color: var(--card-dark);
        }
        .card-header {
            background-color: var(--sidebar-bg-light);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        [data-theme='dark'] .card-header {
            background-color: var(--sidebar-bg-dark);
        }
        .profile-header {
            background: linear-gradient(135deg, var(--sidebar-bg-light) 0%, #2c5282 100%);
            color: white;
            padding: 30px 0;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        [data-theme='dark'] .profile-header {
            background: linear-gradient(135deg, var(--sidebar-bg-dark) 0%, #1a365d 100%);
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .pcc-logo {
            width: 40px;
            height: auto;
            border-radius: 10px;
        }
        .profile-img-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
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
        .sidebar-footer {
            margin-top: auto;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
        }
        
        /* Responsive Styles */
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
        }
    </style>
</head>
<body>
<div class="topbar">
  <div style="display: flex; align-items: center;">
    <button class="menu-toggle" id="menuToggle">
      <i class="fas fa-bars"></i>
    </button>
    <img src="../images/pcc1.png" alt="PCC Logo" class="pcc-logo">
  </div>
  <div class="topbar-right">
    <a href="notifications.php" class="notification-bell">
      <i class="fas fa-bell fa-lg"></i>
    </a>
    <span class="dark-toggle" id="toggleTheme" title="Toggle Dark Mode">
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
<div class="sidebar" id="sidebar">
  <a href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
  <a href="manage_users.php"><i class="fas fa-users me-2"></i> Manage Users</a>
  <a href="department_reports.php"><i class="fas fa-layer-group me-2"></i> Department Report</a>
  <a href="announcements.php"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
  <a href="view_ipcr_submissions.php"><i class="fas fa-file-alt me-2"></i> View Submissions</a>
  <a href="deadline_settings.php"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
  <a href="status_report.php"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
  <div class="sidebar-footer mt-4 pt-2 border-top">
    <small>Version 1.0.0</small>
  </div>
</div>
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user me-2"></i>Faculty Profile</h2>
        <a href="notifications.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Notifications
        </a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="card">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($profilePicture) ?>" class="profile-img mb-3" alt="Faculty Profile">
            <h3><?= htmlspecialchars($facultyName) ?></h3>
            <p class="mb-0">@<?= htmlspecialchars($faculty['username']) ?></p>
            <span class="badge bg-<?= $faculty['status'] === 'active' ? 'success' : 'danger' ?> mt-2">
                <?= ucfirst($faculty['status']) ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Personal Information</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Email:</th>
                            <td><?= htmlspecialchars($faculty['email']) ?></td>
                        </tr>
                        <?php if (in_array('employee_id', $usersColumns)): ?>
                        <tr>
                            <th>Employee ID:</th>
                            <td><?= htmlspecialchars($faculty['employee_id'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (in_array('position', $usersColumns)): ?>
                        <tr>
                            <th>Position:</th>
                            <td><?= htmlspecialchars($faculty['position'] ?? 'N/A') ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Department:</th>
                            <td><?= htmlspecialchars($departmentName) ?></td>
                        </tr>
                        <tr>
                            <th>Date Joined:</th>
                            <td><?= !empty($faculty['created_at']) ? date('M d, Y', strtotime($faculty['created_at'])) : 'N/A' ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Account Statistics</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Account Status:</th>
                            <td>
                                <span class="badge bg-<?= $faculty['status'] === 'active' ? 'success' : 'danger' ?>">
                                    <?= ucfirst($faculty['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>IPCR Submissions:</th>
                            <td><?= $submissionsCount ?></td>
                        </tr>
                        <?php if (in_array('last_login', $usersColumns)): ?>
                        <tr>
                            <th>Last Login:</th>
                            <td><?= !empty($faculty['last_login']) ? date('M d, Y h:i A', strtotime($faculty['last_login'])) : 'Never' ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <div class="d-flex gap-2 mt-4">
                <a href="manage_users.php?action=edit&id=<?= htmlspecialchars($facultyId) ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Profile
                </a>
                <a href="view_ipcr_submissions.php?faculty_id=<?= htmlspecialchars($facultyId) ?>" class="btn btn-info">
                    <i class="fas fa-file-alt me-1"></i> View Submissions
                </a>
                <a href="mailto:<?= htmlspecialchars($faculty['email']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-envelope me-1"></i> Send Email
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Theme toggle
    const toggleTheme = document.getElementById('toggleTheme');
    const htmlTag = document.documentElement;
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) htmlTag.setAttribute('data-theme', savedTheme);
    
    // Update theme icon
    function updateThemeIcon() {
        const theme = document.documentElement.getAttribute('data-theme');
        const icon = toggleTheme.querySelector('i');
        if (theme === 'dark') {
            icon.className = 'fas fa-sun';
        } else {
            icon.className = 'fas fa-moon';
        }
    }
    updateThemeIcon();
    
    toggleTheme.addEventListener('click', () => {
        const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcon();
    });
    
    // Profile settings dropdown
    const profileSettings = document.getElementById("profileSettings");
    const settingsMenu = profileSettings.querySelector(".settings-menu");
    
    profileSettings.addEventListener("click", function (e) {
        e.stopPropagation();
        settingsMenu.style.display = settingsMenu.style.display === "block" ? "none" : "block";
    });
    
    document.addEventListener("click", function (e) {
        if (!profileSettings.contains(e.target)) {
            settingsMenu.style.display = "none";
        }
    });
    
    // Sidebar toggle for mobile
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    
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
</script>
</body>
</html>