<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    include '../db/connection.php';
    
    // Set the timezone to match your local timezone
    date_default_timezone_set('Asia/Manila'); // Change this to your timezone
    
    // Get filter parameters
    $selectedDepartment = isset($_GET['department']) ? $_GET['department'] : '';
    $selectedPeriod = isset($_GET['period']) ? $_GET['period'] : date('Y');
    $selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Build the base query
    $query = "SELECT * FROM ipcr_submissions WHERE 1=1";
    $params = [];
    $types = "";
    
    // Add filters to query
    if (!empty($selectedDepartment)) {
        $query .= " AND department = ?";
        $params[] = $selectedDepartment;
        $types .= "s";
    }
    if (!empty($selectedPeriod)) {
        $query .= " AND period = ?";
        $params[] = $selectedPeriod;
        $types .= "s";
    }
    if (!empty($selectedStatus)) {
        $query .= " AND status = ?";
        $params[] = $selectedStatus;
        $types .= "s";
    }
    $query .= " ORDER BY department, faculty_name, created_at DESC";
    
    // Prepare and execute the query
    $stmt = mysqli_prepare($conn, $query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Create a filename with the current date and time
    $filename = "PCR_Department_Report_" . date('Y-m-d_H-i-s') . ".xls";
    
    // Set headers for download
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Start output buffering
    ob_start();
    
    // Add Excel header with HTML formatting
    echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">";
    echo "<head>";
    echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
    echo "<!--[if gte mso 9]>";
    echo "<xml>";
    echo "<x:ExcelWorkbook>";
    echo "<x:ExcelWorksheets>";
    echo "<x:ExcelWorksheet>";
    echo "<x:Name>PCR Department Report</x:Name>";
    echo "<x:WorksheetOptions>";
    echo "<x:Panes></x:Panes>";
    echo "</x:WorksheetOptions>";
    echo "</x:ExcelWorksheet>";
    echo "</x:ExcelWorksheets>";
    echo "</x:ExcelWorkbook>";
    echo "</xml>";
    echo "<![endif]-->";
    echo "</head>";
    echo "<body>";
    
    // Create a table for the Excel data
    echo "<table border=\"1\">";
    
    // Add report title row
    echo "<tr>";
    echo "<td colspan=\"7\" align=\"center\" style=\"font-weight:bold; font-size:16px; background-color:#4472C4; color:#FFFFFF;\">PCR Department Report</td>";
    echo "</tr>";
    
    // Add filter information row
    $filterText = 'Filters: ';
    $filterText .= !empty($selectedDepartment) ? "Department: $selectedDepartment, " : 'All Departments, ';
    $filterText .= !empty($selectedPeriod) ? "Period: $selectedPeriod, " : 'All Periods, ';
    $filterText .= !empty($selectedStatus) ? "Status: $selectedStatus" : 'All Statuses';
    echo "<tr>";
    echo "<td colspan=\"7\" align=\"center\">$filterText</td>";
    echo "</tr>";
    
    // Add generation date row with the correct time
    echo "<tr>";
    echo "<td colspan=\"7\" align=\"center\">Generated on: " . date('F d, Y h:i:s A') . "</td>";
    echo "</tr>";
    
    // Add empty row for spacing
    echo "<tr>";
    echo "<td colspan=\"7\">&nbsp;</td>";
    echo "</tr>";
    
    // Add headers with styling
    echo "<tr style=\"background-color:#4472C4; color:#FFFFFF; font-weight:bold; text-align:center;\">";
    echo "<th>ID</th>";
    echo "<th>Faculty Name</th>";
    echo "<th>Department</th>";
    echo "<th>Period</th>";
    echo "<th>Final Rating</th>";
    echo "<th>Status</th>";
    echo "<th>Date & Time Submitted</th>";
    echo "</tr>";
    
    // Add data rows
    $rowCount = 0;
    while ($submission = mysqli_fetch_assoc($result)) {
        // Alternate row colors
        $rowStyle = ($rowCount % 2 == 0) ? "background-color:#FFFFFF;" : "background-color:#F2F2F2;";
        
        // Set status color
        $statusStyle = "";
        if ($submission['status'] === 'Approved') {
            $statusStyle = "background-color:#90EE90;";
        } elseif ($submission['status'] === 'Rejected') {
            $statusStyle = "background-color:#FFB6C1;";
        } else {
            $statusStyle = "background-color:#FFD700;";
        }
        
        echo "<tr style=\"$rowStyle\">";
        echo "<td align=\"center\">" . $submission['id'] . "</td>";
        echo "<td>" . htmlspecialchars($submission['faculty_name']) . "</td>";
        echo "<td>" . htmlspecialchars($submission['department']) . "</td>";
        echo "<td>" . htmlspecialchars($submission['period']) . "</td>";
        echo "<td align=\"center\">" . number_format($submission['final_avg_rating'], 2) . "</td>";
        echo "<td align=\"center\" style=\"$statusStyle\">" . htmlspecialchars($submission['status']) . "</td>";
        // Format date with time using the correct timezone
        $submissionTime = new DateTime($submission['created_at']);
        $submissionTime->setTimezone(new DateTimeZone('Asia/Manila')); // Change to your timezone
        echo "<td align=\"center\">" . $submissionTime->format('Y-m-d h:i:s A') . "</td>";
        echo "</tr>";
        
        $rowCount++;
    }
    
    // Close the table
    echo "</table>";
    echo "</body>";
    echo "</html>";
    
    // Flush the output buffer
    ob_end_flush();
    exit;
}
// Continue with the rest of the page for normal viewing
include '../db/connection.php';
// Set the timezone to match your local timezone
date_default_timezone_set('Asia/Manila'); // Change this to your timezone

// Handle Profile Data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Admin';
 $email = $_SESSION['email'] ?? '';

// Get notification count
 $notificationCount = 0;
 $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM admindashboard_notification WHERE status = 'unread'");
 $stmt->execute();
 $result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $notificationCount = $row['unread'];
}

// Get filter parameters
 $selectedDepartment = isset($_GET['department']) ? $_GET['department'] : '';
 $selectedPeriod = isset($_GET['period']) ? $_GET['period'] : date('Y');
 $selectedStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Get unique departments for filter dropdown
 $departments = [];
 $deptSql = "SELECT DISTINCT department FROM ipcr_submissions WHERE department IS NOT NULL AND department != '' ORDER BY department";
 $deptResult = mysqli_query($conn, $deptSql);
while ($row = mysqli_fetch_assoc($deptResult)) {
    $departments[] = $row['department'];
}

// Get unique periods for filter dropdown
 $periods = [];
 $periodSql = "SELECT DISTINCT period FROM ipcr_submissions ORDER BY period DESC";
 $periodResult = mysqli_query($conn, $periodSql);
while ($row = mysqli_fetch_assoc($periodResult)) {
    $periods[] = $row['period'];
}

// Build the base query
 $query = "SELECT * FROM ipcr_submissions WHERE 1=1";
 $params = [];
 $types = "";

// Add filters to query
if (!empty($selectedDepartment)) {
    $query .= " AND department = ?";
    $params[] = $selectedDepartment;
    $types .= "s";
}
if (!empty($selectedPeriod)) {
    $query .= " AND period = ?";
    $params[] = $selectedPeriod;
    $types .= "s";
}
if (!empty($selectedStatus)) {
    $query .= " AND status = ?";
    $params[] = $selectedStatus;
    $types .= "s";
}
 $query .= " ORDER BY created_at DESC";

// Prepare and execute the query
 $stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);
 $submissions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $submissions[] = $row;
}

// Get department statistics
 $deptStats = [];
 $statsQuery = "SELECT department, COUNT(*) as total, 
               SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
               AVG(final_avg_rating) as avg_rating
               FROM ipcr_submissions";
               
// Add filters to stats query
if (!empty($selectedDepartment)) {
    $statsQuery .= " WHERE department = '" . mysqli_real_escape_string($conn, $selectedDepartment) . "'";
}
if (!empty($selectedPeriod)) {
    $statsQuery .= (empty($selectedDepartment) ? " WHERE" : " AND") . " period = '" . mysqli_real_escape_string($conn, $selectedPeriod) . "'";
}
if (!empty($selectedStatus)) {
    $statsQuery .= (empty($selectedDepartment) && empty($selectedPeriod) ? " WHERE" : " AND") . " status = '" . mysqli_real_escape_string($conn, $selectedStatus) . "'";
}
 $statsQuery .= " GROUP BY department ORDER BY total DESC";
 $statsResult = mysqli_query($conn, $statsQuery);
while ($row = mysqli_fetch_assoc($statsResult)) {
    $deptStats[] = $row;
}

// Get overall statistics
 $overallStats = [
    'total' => 0,
    'approved' => 0,
    'pending' => 0,
    'rejected' => 0,
    'avg_rating' => 0
];
 $overallQuery = "SELECT COUNT(*) as total, 
               SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
               AVG(final_avg_rating) as avg_rating
               FROM ipcr_submissions";

// Add filters to overall query
if (!empty($selectedDepartment) || !empty($selectedPeriod) || !empty($selectedStatus)) {
    $overallQuery .= " WHERE 1=1";
    if (!empty($selectedDepartment)) {
        $overallQuery .= " AND department = '" . mysqli_real_escape_string($conn, $selectedDepartment) . "'";
    }
    if (!empty($selectedPeriod)) {
        $overallQuery .= " AND period = '" . mysqli_real_escape_string($conn, $selectedPeriod) . "'";
    }
    if (!empty($selectedStatus)) {
        $overallQuery .= " AND status = '" . mysqli_real_escape_string($conn, $selectedStatus) . "'";
    }
}
 $overallResult = mysqli_query($conn, $overallQuery);
if ($row = mysqli_fetch_assoc($overallResult)) {
    $overallStats = $row;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Department Reports - PCR Admin</title>
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
    
    /* Page-specific styles */
    .stats-card {
      background-color: var(--card-light);
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .stats-card {
      background-color: var(--card-dark);
    }
    .stats-card h3 {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--sidebar-bg-light);
    }
    [data-theme='dark'] .stats-card h3 {
      color: #6ba8ff;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }
    .stat-item {
      background-color: #f8f9fa;
      border-radius: 10px;
      padding: 15px;
      text-align: center;
    }
    [data-theme='dark'] .stat-item {
      background-color: #3a3a4e;
    }
    .stat-value {
      font-size: 1.8rem;
      font-weight: bold;
      color: var(--sidebar-bg-light);
    }
    [data-theme='dark'] .stat-value {
      color: #6ba8ff;
    }
    .stat-label {
      font-size: 0.9rem;
      color: #6c757d;
    }
    [data-theme='dark'] .stat-label {
      color: #adb5bd;
    }
    .filter-card {
      background-color: var(--card-light);
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .filter-card {
      background-color: var(--card-dark);
    }
    .filter-card h3 {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--sidebar-bg-light);
    }
    [data-theme='dark'] .filter-card h3 {
      color: #6ba8ff;
    }
    .table-card {
      background-color: var(--card-light);
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .table-card {
      background-color: var(--card-dark);
    }
    .table-card h3 {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--sidebar-bg-light);
    }
    [data-theme='dark'] .table-card h3 {
      color: #6ba8ff;
    }
    .table {
      background-color: var(--card-light);
    }
    [data-theme='dark'] .table {
      background-color: var(--card-dark);
      color: var(--text-dark);
    }
    .table thead th {
      background-color: var(--card-light);
    }
    [data-theme='dark'] .table thead th {
      background-color: var(--card-dark);
      color: var(--text-dark);
    }
    .dept-chart-container {
      height: 300px;
      margin-top: 20px;
    }
    .rating-distribution {
      height: 250px;
      margin-top: 20px;
    }
    .export-btn {
      margin-left: 10px;
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
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      }
      .stat-value {
        font-size: 1.5rem;
      }
    }
    
    @media (max-width: 576px) {
      .filter-card .row > div {
        margin-bottom: 10px;
      }
      .export-btn {
        margin-left: 0;
        margin-top: 10px;
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
  <a href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
  <a href="manage_users.php"><i class="fas fa-users me-2"></i> Manage Users</a>
  <a href="department_reports.php" class="active"><i class="fas fa-layer-group me-2"></i> Department Reports</a>
  <a href="announcements.php"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
  <a href="view_ipcr_submissions.php"><i class="fas fa-file-alt me-2"></i> View Submissions</a>
  <a href="deadline_settings.php"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
  <a href="status_report.php"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
  <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
<div class="main-content">
  <h3 class="mb-3">
  <img src="/images/10.png"width="50" height="50" class="me-2"></i> Department Reports</h2>
  
  <!-- Overall Statistics Card -->
  <div class="stats-card">
    <h3 class="mb-3">
  <img src="/images/5.png"width="50" height="50" class="me-2"></i> Overall Statistics</h3>
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-value"><?= $overallStats['total'] ?></div>
        <div class="stat-label">Total Submissions</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= $overallStats['approved'] ?></div>
        <div class="stat-label">Approved</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= $overallStats['pending'] ?></div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-item">
        <div class="stat-value"><?= number_format($overallStats['avg_rating'], 2) ?></div>
        <div class="stat-label">Average Rating</div>
      </div>
    </div>
  </div>
  
  <!-- Filter Card -->
  <div class="filter-card">
    <h3 class="mb-3">
  <img src="/images/15.png"width="45" height="45" class="me-2"></i> Filters</h3>
    <form method="get" action="">
      <div class="row">
        <div class="col-md-4">
          <label for="department" class="form-label">Department</label>
          <select class="form-select" id="department" name="department">
            <option value="">All Departments</option>
            <?php foreach ($departments as $dept): ?>
              <option value="<?= htmlspecialchars($dept) ?>" <?= $selectedDepartment === $dept ? 'selected' : '' ?>>
                <?= htmlspecialchars($dept) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="period" class="form-label">Period</label>
          <select class="form-select" id="period" name="period">
            <option value="">All Periods</option>
            <?php foreach ($periods as $period): ?>
              <option value="<?= htmlspecialchars($period) ?>" <?= $selectedPeriod === $period ? 'selected' : '' ?>>
                <?= htmlspecialchars($period) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="status" class="form-label">Status</label>
          <select class="form-select" id="status" name="status">
            <option value="">All Statuses</option>
            <option value="Pending" <?= $selectedStatus === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Approved" <?= $selectedStatus === 'Approved' ? 'selected' : '' ?>>Approved</option>
          </select>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col-12">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-search me-1"></i> Apply Filters
          </button>
          <a href="department_reports.php" class="btn btn-outline-secondary">
            <i class="fas fa-redo me-1"></i> Reset
          </a>
          <a href="?export=excel&department=<?= urlencode($selectedDepartment) ?>&period=<?= urlencode($selectedPeriod) ?>&status=<?= urlencode($selectedStatus) ?>" class="btn btn-success export-btn">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
          </a>
        </div>
      </div>
    </form>
  </div>
  
  <!-- Department Statistics Chart -->
  <div class="stats-card">
    <h3 class="mb-3">
  <img src="/images/2.png"width="80" height="90" class="me-2">Submissions by Department</h3>
    <div class="row">
      <div class="col-md-8">
        <div class="dept-chart-container">
          <canvas id="deptChart"></canvas>
        </div>
      </div>
      <div class="col-md-4">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Department</th>
                <th>Count</th>
                <th>%</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $totalDeptSubmissions = array_sum(array_column($deptStats, 'total'));
              foreach ($deptStats as $dept): 
                $percentage = $totalDeptSubmissions > 0 ? round(($dept['total'] / $totalDeptSubmissions) * 100, 1) : 0;
              ?>
                <tr>
                  <td><?= htmlspecialchars($dept['department'] ?? 'N/A') ?></td>
                  <td><?= $dept['total'] ?></td>
                  <td><?= $percentage ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Rating Distribution Chart -->
  <div class="stats-card">
    <h3 class="mb-3">
  <img src="/images/4.png"width="50" height="50" class="me-2"></i> Rating Distribution</h3>
    <div class="rating-distribution">
      <canvas id="ratingChart"></canvas>
    </div>
  </div>
  
  <!-- Submissions Table -->
  <div class="table-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-3">
  <img src="/images/9.png"width="50" height="50" class="me-2"></i> Submission Details</h3>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-hover" id="submissionsTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Faculty Name</th>
            <th>Department</th>
            <th>Period</th>
            <th>Rating</th>
            <th>Status</th>
            <th>Date & Time</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($submissions) > 0): ?>
            <?php foreach ($submissions as $submission): ?>
              <tr>
                <td><?= $submission['id'] ?></td>
                <td><?= htmlspecialchars($submission['faculty_name']) ?></td>
                <td><?= htmlspecialchars($submission['department'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($submission['period']) ?></td>
                <td><?= number_format($submission['final_avg_rating'], 2) ?></td>
                <td>
                  <span class="badge bg-<?= 
                    $submission['status'] === 'Approved' ? 'success' : 
                    ($submission['status'] === 'Rejected' ? 'danger' : 'warning') 
                  ?>">
                    <?= htmlspecialchars($submission['status']) ?>
                  </span>
                </td>
                <td><?= date('M j, Y h:i A', strtotime($submission['created_at'])) ?></td>
                <td>
                  <a href="view_submission.php?id=<?= $submission['id'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-eye"></i> View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="text-center">No submissions found with the selected filters.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Theme detection
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
  
  // Department specific colors - these colors work well in both light and dark themes
  const departmentColors = {
    'BSIT': '#800000', // Maroon
    'BSBA': '#FFD700', // Yellow/Gold
    'BSCRIM': '#1E90FF' // Blue
  };
  
  // Default colors for other departments
  const defaultColors = ['#4a6cf7', '#17a2b8', '#28a745', '#dc3545', '#6f42c1', '#fd7e14'];
  
  // Function to get colors for departments
  function getDepartmentColors(departments) {
    return departments.map(dept => {
      // Return the specific color if it exists, otherwise use a default color
      return departmentColors[dept] || defaultColors[Math.floor(Math.random() * defaultColors.length)];
    });
  }
  
  // Department Chart
  const deptCtx = document.getElementById('deptChart').getContext('2d');
  const deptLabels = <?= json_encode(array_column($deptStats, 'department')) ?>;
  const deptData = <?= json_encode(array_column($deptStats, 'total')) ?>;
  
  const deptChart = new Chart(deptCtx, {
    type: 'pie',
    data: {
      labels: deptLabels,
      datasets: [{
        data: deptData,
        backgroundColor: getDepartmentColors(deptLabels),
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: currentTheme === 'dark' ? '#dcdcdc' : '#333',
            font: {
              size: 12
            }
          }
        }
      }
    }
  });
  
  // Rating Distribution Chart
  const ratingCtx = document.getElementById('ratingChart').getContext('2d');
  
  // Prepare rating distribution data
  const ratingData = {
    'Outstanding (4.5-5.0)': 0,
    'Very Satisfactory (3.5-4.4)': 0,
    'Satisfactory (2.5-3.4)': 0,
    'Unsatisfactory (1.5-2.4)': 0,
    'Poor (1.0-1.4)': 0
  };
  
  <?php
  foreach ($submissions as $submission) {
    $rating = floatval($submission['final_avg_rating']);
    if ($rating >= 4.5) {
      echo "ratingData['Outstanding (4.5-5.0)']++;";
    } elseif ($rating >= 3.5) {
      echo "ratingData['Very Satisfactory (3.5-4.4)']++;";
    } elseif ($rating >= 2.5) {
      echo "ratingData['Satisfactory (2.5-3.4)']++;";
    } elseif ($rating >= 1.5) {
      echo "ratingData['Unsatisfactory (1.5-2.4)']++;";
    } else {
      echo "ratingData['Poor (1.0-1.4)']++;";
    }
  }
  ?>
  
  const ratingChart = new Chart(ratingCtx, {
    type: 'bar',
    data: {
      labels: Object.keys(ratingData),
      datasets: [{
        label: 'Number of Submissions',
        data: Object.values(ratingData),
        backgroundColor: ['#4a6cf7', '#17a2b8', '#ffc107', '#28a745', '#dc3545'],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { 
            color: currentTheme === 'dark' ? '#dcdcdc' : '#333',
            font: {
              size: 10
            }
          },
          grid: { 
            color: currentTheme === 'dark' ? '#444' : '#ccc',
            drawBorder: false
          }
        },
        x: {
          ticks: { 
            color: currentTheme === 'dark' ? '#dcdcdc' : '#333',
            font: {
              size: 10
            }
          },
          grid: { 
            color: currentTheme === 'dark' ? '#444' : '#ccc',
            drawBorder: false
          }
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });
  
  // Theme toggle
  const toggleTheme = document.getElementById('toggleTheme');
  const htmlTag = document.documentElement;
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) htmlTag.setAttribute('data-theme', savedTheme);
  
  toggleTheme.addEventListener('click', () => {
    const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    htmlTag.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    
    // Update chart colors dynamically
    const isDark = next === 'dark';
    const textColor = isDark ? '#dcdcdc' : '#333';
    const gridColor = isDark ? '#444' : '#ccc';
    
    // Update department chart
    deptChart.options.plugins.legend.labels.color = textColor;
    deptChart.update();
    
    // Update rating chart
    ratingChart.options.scales.x.ticks.color = textColor;
    ratingChart.options.scales.x.grid.color = gridColor;
    ratingChart.options.scales.y.ticks.color = textColor;
    ratingChart.options.scales.y.grid.color = gridColor;
    ratingChart.update();
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