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
 $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM admindashboard_notification WHERE status = 'unread'");
 $stmt->execute();
 $result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $notificationCount = $row['unread'];
}

// Get actual notifications content
 $notifications = [];
 $stmt = $conn->prepare("SELECT * FROM admindashboard_notification WHERE status = 'unread' ORDER BY created_at DESC LIMIT 5");
 $stmt->execute();
 $notificationResult = $stmt->get_result();
while ($row = $notificationResult->fetch_assoc()) {
    $notifications[] = $row;
}

// Get PCR deadline
 $stmt = $conn->prepare("SELECT setting_value FROM ipcr_settings WHERE setting_name = 'submission_deadline'");
 $stmt->execute();
 $deadlineResult = $stmt->get_result();
 $deadlineRow = $deadlineResult->fetch_assoc();
 $submissionDeadline = $deadlineRow ? $deadlineRow['setting_value'] : date('Y-m-d H:i:s', strtotime('+1 month'));

// Check if deadline has passed
 $deadlinePassed = (strtotime($submissionDeadline) < time());

// Dashboard Stats
 $totalUsers = 0;
 $totalSubmissions = 0;
 $totalPending = 0;
 $totalApproved = 0;

 $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
 $stmt->execute();
 $result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $totalUsers = $row['total'];

 $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ipcr_submissions");
 $stmt->execute();
 $result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $totalSubmissions = $row['total'];

 $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM ipcr_submissions WHERE status = 'Pending'");
 $stmt->execute();
 $result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $totalPending = $row['pending'];

 $stmt = $conn->prepare("SELECT COUNT(*) as approved FROM ipcr_submissions WHERE status = 'Approved'");
 $stmt->execute();
 $result = $stmt->get_result();
if ($row = $result->fetch_assoc()) $totalApproved = $row['approved'];

// Get PCR Analytics Data for different periods
// This Week Data
 $weeklyData = [0, 0, 0, 0, 0, 0, 0]; // Mon, Tue, Wed, Thu, Fri, Sat, Sun
 $weeklyTotal = 0;
 $lastWeekTotal = 0;

// Get current week start and end
 $today = date('Y-m-d');
 $dayOfWeek = date('N', strtotime($today));
 $weekStart = date('Y-m-d', strtotime($today . ' -' . ($dayOfWeek - 1) . ' days'));
 $weekEnd = date('Y-m-d', strtotime($today . ' +' . (7 - $dayOfWeek) . ' days'));
 $lastWeekStart = date('Y-m-d', strtotime($weekStart . ' -7 days'));
 $lastWeekEnd = date('Y-m-d', strtotime($weekEnd . ' -7 days'));

// Get this week's submissions
 $stmt = $conn->prepare("SELECT DAYOFWEEK(created_at) as day_of_week, COUNT(*) as count 
                        FROM ipcr_submissions 
                        WHERE created_at BETWEEN ? AND ?
                        GROUP BY DAYOFWEEK(created_at)");
 $startDateTime = $weekStart . ' 00:00:00';
 $endDateTime = $weekEnd . ' 23:59:59';
 $stmt->bind_param("ss", $startDateTime, $endDateTime);
 $stmt->execute();
 $result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // DAYOFWEEK returns 1 for Sunday, 2 for Monday, etc.
        $dayIndex = ($row['day_of_week'] == 1) ? 6 : $row['day_of_week'] - 2; // Convert to 0=Mon, 6=Sun
        $weeklyData[$dayIndex] = (int)$row['count'];
        $weeklyTotal += (int)$row['count'];
    }
}

// Get last week's submissions for comparison
 $stmt = $conn->prepare("SELECT COUNT(*) as total 
                        FROM ipcr_submissions 
                        WHERE created_at BETWEEN ? AND ?");
 $lastStartDateTime = $lastWeekStart . ' 00:00:00';
 $lastEndDateTime = $lastWeekEnd . ' 23:59:59';
 $stmt->bind_param("ss", $lastStartDateTime, $lastEndDateTime);
 $stmt->execute();
 $result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $lastWeekTotal = (int)$row['total'];
}

// Calculate weekly change percentage
 $weeklyChange = 0;
if ($lastWeekTotal > 0) {
    $weeklyChange = round((($weeklyTotal - $lastWeekTotal) / $lastWeekTotal) * 100);
} else if ($weeklyTotal > 0) {
    $weeklyChange = 100; // If last week was 0 and this week has submissions, it's a 100% increase
}

// This Month Data
 $monthlyData = [0, 0, 0, 0]; // Week 1, 2, 3, 4
 $monthlyTotal = 0;
 $lastMonthTotal = 0;

// Get current month start and end
 $monthStart = date('Y-m-01');
 $monthEnd = date('Y-m-t');
 $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
 $lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));

// Get this month's submissions by week
for ($i = 1; $i <= 4; $i++) {
    $weekStart = date('Y-m-d', strtotime($monthStart . ' +' . (($i-1)*7) . ' days'));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
    
    if ($weekEnd > $monthEnd) {
        $weekEnd = $monthEnd;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count 
                            FROM ipcr_submissions 
                            WHERE created_at BETWEEN ? AND ?");
    $startDateTime = $weekStart . ' 00:00:00';
    $endDateTime = $weekEnd . ' 23:59:59';
    $stmt->bind_param("ss", $startDateTime, $endDateTime);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $monthlyData[$i-1] = (int)$row['count'];
        $monthlyTotal += (int)$row['count'];
    }
}

// Get last month's submissions for comparison
 $stmt = $conn->prepare("SELECT COUNT(*) as total 
                        FROM ipcr_submissions 
                        WHERE created_at BETWEEN ? AND ?");
 $lastStartDateTime = $lastMonthStart . ' 00:00:00';
 $lastEndDateTime = $lastMonthEnd . ' 23:59:59';
 $stmt->bind_param("ss", $lastStartDateTime, $lastEndDateTime);
 $stmt->execute();
 $result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $lastMonthTotal = (int)$row['total'];
}

// Calculate monthly change percentage
 $monthlyChange = 0;
if ($lastMonthTotal > 0) {
    $monthlyChange = round((($monthlyTotal - $lastMonthTotal) / $lastMonthTotal) * 100);
} else if ($monthlyTotal > 0) {
    $monthlyChange = 100; // If last month was 0 and this month has submissions, it's a 100% increase
}

// 1st Semester Data (4 months)
 $semesterData = [0, 0, 0, 0]; // Month 1, 2, 3, 4
 $semesterTotal = 0;
 $lastSemesterTotal = 0;

// Determine current semester dates
 $currentMonth = date('n');
 $currentYear = date('Y');

// Assuming 1st semester is from August to November (adjust as needed)
if ($currentMonth >= 8 && $currentMonth <= 11) {
    // We're in 1st semester
    $semesterStartMonth = 8; // August
    $semesterStart = date('Y-m-d', strtotime("$currentYear-$semesterStartMonth-01"));
    $semesterEnd = date('Y-m-t', strtotime("$currentYear-11-01")); // November
    
    // Last semester would be last year's 1st semester
    $lastSemesterStart = date('Y-m-d', strtotime(($currentYear - 1) . "-08-01"));
    $lastSemesterEnd = date('Y-m-t', strtotime(($currentYear - 1) . "-11-30"));
} else {
    // We're not in 1st semester, show the most recent one
    if ($currentMonth < 8) {
        // We're in the same year but before August, show last year's 1st semester
        $semesterStart = date('Y-m-d', strtotime(($currentYear - 1) . "-08-01"));
        $semesterEnd = date('Y-m-t', strtotime(($currentYear - 1) . "-11-30"));
        $lastSemesterStart = date('Y-m-d', strtotime(($currentYear - 2) . "-08-01"));
        $lastSemesterEnd = date('Y-m-t', strtotime(($currentYear - 2) . "-11-30"));
    } else {
        // We're after November, show this year's 1st semester
        $semesterStart = date('Y-m-d', strtotime("$currentYear-08-01"));
        $semesterEnd = date('Y-m-t', strtotime("$currentYear-11-30"));
        $lastSemesterStart = date('Y-m-d', strtotime(($currentYear - 1) . "-08-01"));
        $lastSemesterEnd = date('Y-m-t', strtotime(($currentYear - 1) . "-11-30"));
    }
}

// Get this semester's submissions by month
for ($i = 0; $i < 4; $i++) {
    $month = 8 + $i; // August (8) to November (11)
    $year = $currentYear;
    
    // If we're showing a previous year's semester, adjust the year
    if ($currentMonth < 8 && $semesterStart < date('Y-m-d', strtotime("$currentYear-01-01"))) {
        $year = $currentYear - 1;
    }
    
    $monthStart = date('Y-m-d', strtotime("$year-$month-01"));
    $monthEnd = date('Y-m-t', strtotime("$year-$month-01"));
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count 
                            FROM ipcr_submissions 
                            WHERE created_at BETWEEN ? AND ?");
    $startDateTime = $monthStart . ' 00:00:00';
    $endDateTime = $monthEnd . ' 23:59:59';
    $stmt->bind_param("ss", $startDateTime, $endDateTime);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $semesterData[$i] = (int)$row['count'];
        $semesterTotal += (int)$row['count'];
    }
}

// Get last semester's submissions for comparison
 $stmt = $conn->prepare("SELECT COUNT(*) as total 
                        FROM ipcr_submissions 
                        WHERE created_at BETWEEN ? AND ?");
 $lastStartDateTime = $lastSemesterStart . ' 00:00:00';
 $lastEndDateTime = $lastSemesterEnd . ' 23:59:59';
 $stmt->bind_param("ss", $lastStartDateTime, $lastEndDateTime);
 $stmt->execute();
 $result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $lastSemesterTotal = (int)$row['total'];
}

// Calculate semester change percentage
 $semesterChange = 0;
if ($lastSemesterTotal > 0) {
    $semesterChange = round((($semesterTotal - $lastSemesterTotal) / $lastSemesterTotal) * 100);
} else if ($semesterTotal > 0) {
    $semesterChange = 100; // If last semester was 0 and this semester has submissions, it's a 100% increase
}

// Get recent submissions
 $recentSubmissions = [];
 $stmt = $conn->prepare("SELECT * FROM ipcr_submissions ORDER BY created_at DESC LIMIT 5");
 $stmt->execute();
 $recentResult = $stmt->get_result();
while ($row = $recentResult->fetch_assoc()) {
    $recentSubmissions[] = $row;
}

// Get submission statistics by department
 $deptStats = [];
 $stmt = $conn->prepare("SELECT department, COUNT(*) as count FROM ipcr_submissions GROUP BY department ORDER BY count DESC LIMIT 5");
 $stmt->execute();
 $deptResult = $stmt->get_result();
while ($row = $deptResult->fetch_assoc()) {
    $deptStats[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - PCR</title>
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
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
    .card-gradient {
      background: linear-gradient(135deg, #4a6cf7, #6a9df7);
      border-radius: 16px;
      padding: 15px;
      color: white;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      height: 100%;
      cursor: pointer;
      text-decoration: none;
      display: block;
    }
    .card-gradient:hover {
      transform: translateY(-5px);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
      text-decoration: none;
      color: white;
    }
    .card-gradient i {
      background-color: rgba(255, 255, 255, 0.2);
      padding: 10px;
      border-radius: 10px;
    }
    .card-gradient h3 {
      font-size: 1.5rem;
      font-weight: bold;
      margin: 0;
    }
    .card-gradient small {
      font-size: 0.85rem;
    }
    .chart-card {
      background-color: #ffffff;
      color: #333;
      padding: 1.5rem;
      border-radius: 1rem;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      width: 100%;
      margin-top: 1rem;
      margin-bottom: 2rem;
    }
    [data-theme='dark'] .chart-card {
      background-color: #2c2c3e;
      color: #dcdcdc;
    }
    .chart-card h3 {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 0;
    }
    .chart-card select {
      background-color: #e2e8f0;
      border: none;
      font-size: 0.9rem;
      padding: 5px 10px;
      border-radius: 6px;
      color: #333;
      outline: none;
    }
    [data-theme='dark'] .chart-card select {
      background-color: #3b3b4f;
      color: #fff;
    }
    .chart-card p {
      font-size: 0.9rem;
      margin: 4px 0;
    }
    #ipcrChart {
      margin-top: 1rem;
      width: 100% !important;
      height: 250px !important;
    }
    .deadline-card {
      background-color: #ffffff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .deadline-card {
      background-color: #2c2c3e;
    }
    .deadline-alert {
      background-color: #f8d7da;
      color: #721c24;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
    }
    [data-theme='dark'] .deadline-alert {
      background-color: #5a1c1f;
      color: #e6b8b8;
    }
    .deadline-warning {
      background-color: #fff3cd;
      color: #856404;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
    }
    [data-theme='dark'] .deadline-warning {
      background-color: #5a4a08;
      color: #f0e6b8;
    }
    .deadline-info {
      background-color: #d1ecf1;
      color: #0c5460;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
    }
    [data-theme='dark'] .deadline-info {
      background-color: #0a3d45;
      color: #a3d4dc;
    }
    .recent-submissions {
      background-color: #ffffff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .recent-submissions {
      background-color: #2c2c3e;
    }
    .dept-stats {
      background-color: #ffffff;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .dept-stats {
      background-color: #2c2c3e;
    }
    .menu-toggle {
      display: none;
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
    }
    
    /* Notification Popup Styles */
    .notification-popup {
        background-color: var(--card-light);
        color: var(--text-light);
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        overflow: hidden;
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    [data-theme='dark'] .notification-popup {
        background-color: var(--card-dark);
        color: var(--text-dark);
    }

    .notification-item {
        background-color: var(--card-light);
        transition: background-color 0.2s;
        border-bottom: 1px solid #eee;
    }

    .notification-item:hover {
        background-color: rgba(58, 110, 165, 0.1);
    }

    [data-theme='dark'] .notification-item {
        background-color: var(--card-dark);
        border-bottom-color: #444;
    }

    [data-theme='dark'] .notification-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .notification-item:last-child {
        border-bottom: none;
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .card-gradient h3 {
        font-size: 1.3rem;
      }
      .card-gradient small {
        font-size: 0.8rem;
      }
      .chart-card {
        padding: 1rem;
      }
      #ipcrChart {
        height: 200px !important;
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
      .card-gradient h3 {
        font-size: 1.2rem;
      }
      .card-gradient i {
        padding: 8px;
        font-size: 1.2rem;
      }
      #ipcrChart {
        height: 180px !important;
      }
      
      .notification-popup {
        width: calc(100% - 40px);
        right: 20px;
        left: 20px;
      }
    }
    
    @media (max-width: 576px) {
      .card-gradient {
        padding: 12px;
      }
      .card-gradient h3 {
        font-size: 1.1rem;
      }
      .card-gradient small {
        font-size: 0.75rem;
      }
      .chart-card h3 {
        font-size: 1rem;
      }
      .chart-card select {
        font-size: 0.8rem;
        padding: 3px 8px;
      }
      .chart-card p {
        font-size: 0.8rem;
      }
      #ipcrChart {
        height: 150px !important;
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
    <a href="notifications.php" class="notification-bell" id="notificationBell">
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

<!-- Notification Popup -->
<div id="notificationPopup" class="notification-popup" style="display: none; position: fixed; top: 80px; right: 20px; width: 350px; z-index: 1001;">
    <div class="notification-header" style="background: #3a6ea5; color: white; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center;">
        <h5 style="margin: 0; font-size: 16px;">Notifications</h5>
        <button id="closeNotifications" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">&times;</button>
    </div>
    <div class="notification-body" style="max-height: 400px; overflow-y: auto;">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item" style="padding: 12px 15px;">
                    <div class="notification-title" style="font-weight: 600; margin-bottom: 5px;">
                        <?php 
                        // Check if 'title' field exists, otherwise use a default or extract from message
                        if (isset($notification['title']) && !empty($notification['title'])) {
                            $title = htmlspecialchars($notification['title']);
                            // Replace "IPCR" with "PCR" in the title
                            echo str_replace('IPCR', 'PCR', $title);
                        } else {
                            // Try to extract a title from the message or use a default
                            echo "New Notification";
                        }
                        ?>
                    </div>
                    <div class="notification-message" style="font-size: 14px; color: #555; margin-bottom: 8px;">
                        <?php 
                        // Check if 'message' field exists, otherwise use 'content' or another field
                        $messageText = '';
                        if (isset($notification['message']) && !empty($notification['message'])) {
                            $messageText = $notification['message'];
                        } elseif (isset($notification['content']) && !empty($notification['content'])) {
                            $messageText = $notification['content'];
                        } elseif (isset($notification['description']) && !empty($notification['description'])) {
                            $messageText = $notification['description'];
                        } else {
                            // If no message content is found, show a generic message
                            $messageText = "You have a new notification";
                        }
                        // Replace "IPCR" with "PCR" in the message
                        $messageText = str_replace('IPCR', 'PCR', $messageText);
                        echo htmlspecialchars($messageText);
                        ?>
                    </div>
                    <div class="notification-time" style="font-size: 12px; color: #888;">
                        <?php 
                        // Check if 'created_at' field exists, otherwise use current time
                        if (isset($notification['created_at']) && !empty($notification['created_at'])) {
                            echo date('M j, Y g:i A', strtotime($notification['created_at']));
                        } else {
                            echo date('M j, Y g:i A');
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-notifications" style="padding: 20px; text-align: center; color: #888;">
                No new notifications
            </div>
        <?php endif; ?>
    </div>
    <div class="notification-footer" style="padding: 10px 15px; text-align: center; border-top: 1px solid #eee;">
        <a href="notifications.php" style="color: #3a6ea5; text-decoration: none; font-size: 14px;">View All Notifications</a>
    </div>
</div>

<div class="sidebar" id="sidebar">
  <a href="admin_dashboard.php" class="active"><i class="fas fa-home me-2"></i> Home</a>
  <a href="manage_users.php"><i class="fas fa-users me-2"></i> Manage Users</a>
  <a href="department_reports.php"><i class="fas fa-layer-group me-2"></i> Department Report</a>
  <a href="announcements.php"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
  <a href="view_ipcr_submissions.php"><i class="fas fa-file-alt me-2"></i> View Submissions</a>
  <a href="deadline_settings.php"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
  <a href="status_report.php"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
  <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
<div class="main-content">
  <div class="row g-3">
    <div class="col-6 col-md-3">
      <a href="manage_users.php" class="card-gradient text-white rounded shadow h-100">
        <div class="d-flex align-items-center">
          <i class="fas fa-users fa-2x me-3"></i>
          <div>
            <h3 class="mb-0"><?= $totalUsers ?></h3>
            <small>Total Users</small>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="view_ipcr_submissions.php" class="card-gradient text-white rounded shadow h-100">
        <div class="d-flex align-items-center">
          <i class="fas fa-file-alt fa-2x me-3"></i>
          <div>
            <h3 class="mb-0"><?= $totalSubmissions ?></h3>
            <small>Submissions</small>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="view_ipcr_submissions.php?status=Pending" class="card-gradient text-white rounded shadow h-100">
        <div class="d-flex align-items-center">
          <i class="fas fa-hourglass-half fa-2x me-3"></i>
          <div>
            <h3 class="mb-0"><?= $totalPending ?></h3>
            <small>Pending</small>
          </div>
        </div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="view_ipcr_submissions.php?status=Approved" class="card-gradient text-white rounded shadow h-100">
        <div class="d-flex align-items-center">
          <i class="fas fa-check-circle fa-2x me-3"></i>
          <div>
            <h3 class="mb-0"><?= $totalApproved ?></h3>
            <small>Approved</small>
          </div>
        </div>
      </a>
    </div>
  </div>
  
  <!-- Deadline Status Card -->
  <div class="deadline-card">
    <h3 class="mb-3">
      <img src="../images/14.png" width="50" height="50" class="me-2"> PCR Submission Deadline
    </h3>
    <?php if ($deadlinePassed): ?>
      <div class="deadline-alert">
        <h5><i class="fas fa-exclamation-triangle me-2"></i> Deadline Passed</h5>
        <p>The deadline for PCR submission has passed on <?= date('F j, Y \a\t g:i A', strtotime($submissionDeadline)) ?>.</p>
        <a href="deadline_settings.php" class="btn btn-danger">Update Deadline</a>
      </div>
    <?php else: ?>
      <?php 
        $timeLeft = strtotime($submissionDeadline) - time();
        $daysLeft = floor($timeLeft / (60 * 60 * 24));
        
        if ($daysLeft <= 3): 
      ?>
        <div class="deadline-warning">
          <h5><i class="fas fa-exclamation-triangle me-2"></i> Deadline Approaching</h5>
          <p>The deadline for PCR submission is in <?= $daysLeft ?> day(s) on <?= date('F j, Y \a\t g:i A', strtotime($submissionDeadline)) ?>.</p>
          <a href="deadline_settings.php" class="btn btn-warning">Update Deadline</a>
        </div>
      <?php else: ?>
        <div class="deadline-info">
          <h5><i class="fas fa-info-circle me-2"></i> Deadline Information</h5>
          <p>The deadline for PCR submission is on <?= date('F j, Y \a\t g:i A', strtotime($submissionDeadline)) ?>.</p>
          <a href="deadline_settings.php" class="btn btn-primary">Update Deadline</a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  
  <!-- Enhanced PCR Analytics Section -->
  <div class="row">
    <div class="col-12">
      <div class="chart-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3 class="text-lg fw-semibold">📊 PCR Analytics</h3>
          <select class="bg-gray-200 dark:bg-gray-700 text-sm px-2 py-1 rounded" id="analyticsPeriod">
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="semester">1st Semester</option>
          </select>
        </div>
        <p class="text-sm mb-1">📈 <span id="totalSubmissions"><?= $weeklyTotal ?></span> new submissions this week</p>
        <p class="text-sm">📉 <span id="submissionChange"><?= $weeklyChange >= 0 ? 'Up ' : 'Down ' ?><?= abs($weeklyChange) ?>%</span> from last week</p>
        <canvas id="ipcrChart" class="mt-4"></canvas>
      </div>
    </div>
  </div>
  
  <div class="row">
    <div class="col-md-6">
      <div class="recent-submissions">
        <h3 class="mb-3">
          <img src="../images/12.png" width="70" height="70" class="me-2"> Recent Submissions
        </h3>
        <?php if (count($recentSubmissions) > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Faculty</th>
                  <th>Department</th>
                  <th>Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentSubmissions as $submission): ?>
                  <tr>
                    <td><?= htmlspecialchars($submission['faculty_name']) ?></td>
                    <td><?= htmlspecialchars($submission['department'] ?? 'N/A') ?></td>
                    <td><?= date('M j, Y', strtotime($submission['created_at'])) ?></td>
                    <td>
                      <span class="badge bg-<?= 
                        $submission['status'] === 'Approved' ? 'success' : 
                        ($submission['status'] === 'Rejected' ? 'danger' : 'warning') 
                      ?>">
                        <?= htmlspecialchars($submission['status']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="text-center mt-3">
            <a href="view_ipcr_submissions.php" class="btn btn-sm btn-outline-primary">View All Submissions</a>
          </div>
        <?php else: ?>
          <p class="text-muted">No submissions found.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-6">
      <div class="dept-stats">
        <h3 class="mb-3">
          <img src="../images/2.png" width="80" height="80" class="me-2"> Submissions by Department
        </h3>
        <?php if (count($deptStats) > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Department</th>
                  <th>Submissions</th>
                  <th>Percentage</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $totalDeptSubmissions = array_sum(array_column($deptStats, 'count'));
                foreach ($deptStats as $dept): 
                  $percentage = $totalDeptSubmissions > 0 ? round(($dept['count'] / $totalDeptSubmissions) * 100, 1) : 0;
                ?>
                  <tr>
                    <td><?= htmlspecialchars($dept['department'] ?? 'N/A') ?></td>
                    <td><?= $dept['count'] ?></td>
                    <td><?= $percentage ?>%</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="text-center mt-3">
            <a href="department_reports.php" class="btn btn-sm btn-outline-primary">View Department Reports</a>
          </div>
        <?php else: ?>
          <p class="text-muted">No department statistics available.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Theme detection
  const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
  const lightColors = ['#4a6cf7', '#17a2b8', '#ffc107', '#28a745'];
  const darkColors = ['#9ab5ff', '#5bc0de', '#ffe177', '#8de48a'];
  
  // Chart data for different periods
  const chartData = {
    week: {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
      data: <?= json_encode($weeklyData) ?>,
      total: <?= $weeklyTotal ?>,
      change: '<?= $weeklyChange >= 0 ? 'Up ' : 'Down ' ?><?= abs($weeklyChange) ?>%'
    },
    month: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      data: <?= json_encode($monthlyData) ?>,
      total: <?= $monthlyTotal ?>,
      change: '<?= $monthlyChange >= 0 ? 'Up ' : 'Down ' ?><?= abs($monthlyChange) ?>%'
    },
    semester: {
      labels: ['August', 'September', 'October', 'November'],
      data: <?= json_encode($semesterData) ?>,
      total: <?= $semesterTotal ?>,
      change: '<?= $semesterChange >= 0 ? 'Up ' : 'Down ' ?><?= abs($semesterChange) ?>%'
    }
  };
  
  const ctx = document.getElementById('ipcrChart').getContext('2d');
  let ipcrChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: chartData.week.labels,
      datasets: [{
        label: 'Submissions',
        data: chartData.week.data,
        backgroundColor: currentTheme === 'dark' ? darkColors : lightColors,
        borderRadius: 8
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
          labels: {
            color: currentTheme === 'dark' ? '#dcdcdc' : '#333',
            font: {
              size: 11
            }
          }
        }
      }
    }
  });
  
  // Analytics period selector
  const periodSelector = document.getElementById('analyticsPeriod');
  const totalSubmissionsEl = document.getElementById('totalSubmissions');
  const submissionChangeEl = document.getElementById('submissionChange');
  
  periodSelector.addEventListener('change', function() {
    const period = this.value;
    const data = chartData[period];
    
    // Update chart
    ipcrChart.data.labels = data.labels;
    ipcrChart.data.datasets[0].data = data.data;
    ipcrChart.update();
    
    // Update statistics
    totalSubmissionsEl.textContent = data.total;
    submissionChangeEl.textContent = data.change;
    
    // Update the text to reflect the selected period
    let periodText, lastPeriodText;
    if (period === 'week') {
      periodText = 'this week';
      lastPeriodText = 'last week';
    } else if (period === 'month') {
      periodText = 'this month';
      lastPeriodText = 'last month';
    } else {
      periodText = '1st semester';
      lastPeriodText = 'last semester';
    }
    
    totalSubmissionsEl.parentElement.innerHTML = `📈 <span id="totalSubmissions">${data.total}</span> new submissions ${periodText}`;
    submissionChangeEl.parentElement.innerHTML = `📉 <span id="submissionChange">${data.change}</span> from ${lastPeriodText}`;
  });
</script>
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
      
      // Update chart colors dynamically
      const isDark = next === 'dark';
      const textColor = isDark ? '#dcdcdc' : '#333';
      const gridColor = isDark ? '#444' : '#ccc';
      const chartColors = isDark ? darkColors : lightColors;
      
      ipcrChart.data.datasets[0].backgroundColor = chartColors;
      ipcrChart.options.scales.x.ticks.color = textColor;
      ipcrChart.options.scales.x.grid.color = gridColor;
      ipcrChart.options.scales.y.ticks.color = textColor;
      ipcrChart.options.scales.y.grid.color = gridColor;
      ipcrChart.options.plugins.legend.labels.color = textColor;
      ipcrChart.update();
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
    
    // Notification popup functionality
    document.addEventListener('DOMContentLoaded', function() {
        const notificationCount = <?= $notificationCount ?>;
        const notificationPopup = document.getElementById('notificationPopup');
        const notificationBell = document.getElementById('notificationBell');
        const closeNotifications = document.getElementById('closeNotifications');
        
        // Show notification popup if there are unread notifications
        if (notificationCount > 0) {
            notificationPopup.style.display = 'block';
            
            // Auto-hide after 10 seconds
            setTimeout(function() {
                notificationPopup.style.display = 'none';
            }, 10000);
        }
        
        // Toggle notification popup when clicking the bell
        notificationBell.addEventListener('click', function(e) {
            e.preventDefault();
            if (notificationPopup.style.display === 'block') {
                notificationPopup.style.display = 'none';
            } else {
                notificationPopup.style.display = 'block';
            }
        });
        
        // Close notification popup when clicking the close button
        closeNotifications.addEventListener('click', function() {
            notificationPopup.style.display = 'none';
        });
        
        // Close notification popup when clicking outside
        document.addEventListener('click', function(e) {
            if (!notificationPopup.contains(e.target) && !notificationBell.contains(e.target)) {
                notificationPopup.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>