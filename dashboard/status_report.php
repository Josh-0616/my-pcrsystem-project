<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../db/connection.php';
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

// Get the status filter from URL parameter
$status_filter = isset($_GET['status']) ? strtolower($_GET['status']) : 'all';

// Validate status filter
$allowed_statuses = ['all', 'approved', 'pending', 'declined'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'all';
}

// Prepare the SQL query based on the status filter
$sql = "SELECT id, faculty_name, department, period, final_avg_rating, adjectival_rating, status, submitted_at 
        FROM ipcr_submissions";

if ($status_filter !== 'all') {
    $sql .= " WHERE status = '$status_filter'";
}

$sql .= " ORDER BY submitted_at DESC";

// Execute the query
$result = mysqli_query($conn, $sql);

// Get counts for each status
$approved_count = 0;
$pending_count = 0;
$declined_count = 0;
$total_count = 0;

$count_sql = "SELECT 
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined,
                COUNT(*) as total
              FROM ipcr_submissions";

if ($status_filter !== 'all') {
    $count_sql .= " WHERE status = '$status_filter'";
}

$count_result = mysqli_query($conn, $count_sql);
if ($count_result && $row = mysqli_fetch_assoc($count_result)) {
    $approved_count = $row['approved'];
    $pending_count = $row['pending'];
    $declined_count = $row['declined'];
    $total_count = $row['total'];
}

// Add report title based on filter
$status_title = ucfirst($status_filter) . ' Submissions';
if ($status_filter === 'all') {
    $status_title = 'All Submissions';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Status Report - IPCR</title>
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
    
    /* Status Report Specific Styles */
    .status-approved {
      background-color: #d4edda;
    }
    .status-pending {
      background-color: #fff3cd;
    }
    .status-declined {
      background-color: #f8d7da;
    }
    .report-header {
      border-bottom: 2px solid #dee2e6;
      padding-bottom: 15px;
      margin-bottom: 20px;
    }
    .filter-section {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .filter-section {
      background-color: #2c2c3e;
    }
    @media print {
      .no-print {
        display: none !important;
      }
      body {
        font-size: 12px;
      }
      .table {
        font-size: 10px;
      }
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
      <?php if ($notificationCount > 0): ?>
        <span class="badge"><?= $notificationCount ?></span>
      <?php endif; ?>
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
  <a href="admin_dashboard.php" class=""><i class="fas fa-home me-2"></i> Home</a>
  <a href="manage_users.php"><i class="fas fa-users me-2"></i> Manage Users</a>
  <a href="department_reports.php"><i class="fas fa-layer-group me-2"></i> Department Report</a>
  <a href="announcements.php"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
  <a href="view_ipcr_submissions.php"><i class="fas fa-file-alt me-2"></i> View Submissions</a>
  <a href="deadline_settings.php"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
  <a href="status_report.php" class="active"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
  <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
<div class="main-content">
  <!-- Header with print button -->
  <div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2>PCR Status Report</h2>
    <div>

    </div>
  </div>

  <!-- Filter section -->
  <div class="filter-section no-print">
    <h5>Filter Options</h5>
    <div class="row">
      <div class="col-md-6">
        <div class="btn-group" role="group">
          <a href="?status=all" class="btn btn-outline-primary <?= $status_filter === 'all' ? 'active' : '' ?>">All Statuses</a>
          <a href="?status=approved" class="btn btn-outline-success <?= $status_filter === 'approved' ? 'active' : '' ?>">Approved</a>
          <a href="?status=pending" class="btn btn-outline-warning <?= $status_filter === 'pending' ? 'active' : '' ?>">Pending</a>
        </div>
      </div>

    </div>
  </div>

  <!-- Report header -->
  <div class="report-header">
    <h3 class="text-center"><?= $status_title ?></h3>
  </div>

  <!-- Summary statistics -->
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card text-white bg-primary">
        <div class="card-body">
          <h5 class="card-title">Total Submissions</h5>
          <h2 class="card-text"><?= $total_count ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-success">
        <div class="card-body">
          <h5 class="card-title">Approved</h5>
          <h2 class="card-text"><?= $approved_count ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-warning">
        <div class="card-body">
          <h5 class="card-title">Pending</h5>
          <h2 class="card-text"><?= $pending_count ?></h2>
        </div>
      </div>
    </div>
    

  <!-- Submissions table -->
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0">Submissions List</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Faculty Name</th>
              <th>Department</th>
              <th>Period</th>
              <th>Final Rating</th>
              <th>Adjectival Rating</th>
              <th>Status</th>
              <th>Submitted At</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
              <?php $count = 0; ?>
              <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <?php $count++; ?>
                <tr class="status-<?= strtolower($row['status']) ?>">
                  <td><?= $count ?></td>
                  <td><?= htmlspecialchars($row['faculty_name']) ?></td>
                  <td><?= htmlspecialchars($row['department']) ?></td>
                  <td><?= htmlspecialchars($row['period']) ?></td>
                  <td><?= number_format($row['final_avg_rating'], 2) ?></td>
                  <td><?= htmlspecialchars($row['adjectival_rating']) ?></td>
                  <td>
                    <span class="badge bg-<?= 
                      $row['status'] === 'Approved' ? 'success' : 
                      ($row['status'] === 'Pending' ? 'warning' : 'danger') 
                    ?>">
                      <?= htmlspecialchars($row['status']) ?>
                    </span>
                  </td>
                  <td><?= date('M j, Y g:i A', strtotime($row['submitted_at'])) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center">No records found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>


<script>
    // Theme toggle
    const toggleTheme = document.getElementById('toggleTheme');
    const htmlTag = document.documentElement;
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) htmlTag.setAttribute('data-theme', savedTheme);
    
    toggleTheme.addEventListener('click', () => {
      const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
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
<?php
// Close database connection
mysqli_close($conn);
?>