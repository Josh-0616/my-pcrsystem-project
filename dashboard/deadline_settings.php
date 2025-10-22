<?php
session_start();
include('../db/connection.php');
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

// Get notification count
 $notificationCount = 0;
 $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM admindashboard_notification WHERE status = 'unread'");
 $stmt->execute();
 $result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $notificationCount = $row['unread'];
}

// Get current deadline
 $deadlineSql = "SELECT setting_value FROM ipcr_settings WHERE setting_name = 'submission_deadline'";
 $deadlineResult = mysqli_query($conn, $deadlineSql);
 $deadlineRow = mysqli_fetch_assoc($deadlineResult);
 $currentDeadline = $deadlineRow ? $deadlineRow['setting_value'] : date('Y-m-d H:i:s', strtotime('+1 month'));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDeadline = $_POST['deadline'] . ' ' . $_POST['time'];
    
    $updateSql = "UPDATE ipcr_settings SET setting_value = ?, updated_at = NOW(), updated_by = ? WHERE setting_name = 'submission_deadline'";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("si", $newDeadline, $_SESSION['id']);
    
    if ($stmt->execute()) {
        $successMessage = "Deadline updated successfully!";
        $currentDeadline = $newDeadline;
    } else {
        $errorMessage = "Error updating deadline: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Deadline Settings - PCR Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/all.min.css">
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
    
    .deadline-container {
      background-color: var(--card-light);
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 30px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .deadline-container {
      background-color: var(--card-dark);
    }
    .current-deadline {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .current-deadline {
      background-color: #2c2c3e;
    }
    .countdown-box {
      background-color: #e9ecef;
      border-radius: 8px;
      padding: 15px;
      margin-top: 20px;
    }
    [data-theme='dark'] .countdown-box {
      background-color: #3a3a4e;
    }
    .countdown-item {
      display: inline-block;
      margin: 0 10px;
      text-align: center;
    }
    .countdown-value {
      font-size: 2rem;
      font-weight: bold;
      display: block;
    }
    .countdown-label {
      font-size: 0.8rem;
      text-transform: uppercase;
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
  <a href="department_reports.php"><i class="fas fa-layer-group me-2"></i> Department Reports</a>
  <a href="announcements.php"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
  <a href="view_ipcr_submissions.php"><i class="fas fa-file-alt me-2"></i> View Submissions</a>
  <a href="deadline_settings.php" class="active"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
  <a href="status_report.php"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
  <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
<div class="main-content">
  <div class="deadline-container">
    <h3 class="mb-4"><i class="fas fa-clock me-2"></i> PCR Submission Deadline Settings</h3>
    
    <?php if (isset($successMessage)): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i> <?= $successMessage ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($errorMessage)): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i> <?= $errorMessage ?>
      </div>
    <?php endif; ?>
    
    <div class="current-deadline">
      <h5>Current Deadline:</h5>
      <p class="mb-0"><strong><?= date('F j, Y \a\t g:i A', strtotime($currentDeadline)) ?></strong></p>
    </div>
    
    <div class="countdown-box">
      <h5>Time Remaining Until Deadline:</h5>
      <div id="adminCountdown" class="mt-3">
        <div class="countdown-item">
          <span id="adminDays" class="countdown-value">00</span>
          <span class="countdown-label">Days</span>
        </div>
        <div class="countdown-item">
          <span id="adminHours" class="countdown-value">00</span>
          <span class="countdown-label">Hours</span>
        </div>
        <div class="countdown-item">
          <span id="adminMinutes" class="countdown-value">00</span>
          <span class="countdown-label">Minutes</span>
        </div>
        <div class="countdown-item">
          <span id="adminSeconds" class="countdown-value">00</span>
          <span class="countdown-label">Seconds</span>
        </div>
      </div>
    </div>
    
    <form method="post" action="" class="mt-4">
      <h5>Set New Deadline:</h5>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="deadline" class="form-label">Date</label>
          <input type="date" class="form-control" id="deadline" name="deadline" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="time" class="form-label">Time</label>
          <input type="time" class="form-control" id="time" name="time" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i> Update Deadline
      </button>
    </form>
  </div>
</div>

<script>
  // Theme toggle
  const toggleTheme = document.getElementById('toggleTheme');
  const htmlTag = document.documentElement;
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme) htmlTag.setAttribute('data-theme', savedTheme);
  
  toggleTheme.addEventListener('click', () => {
    const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    htmlTag.setAttribute('data-theme', next);
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
  
  // Set default date and time values
  document.addEventListener('DOMContentLoaded', function() {
    const deadlineInput = document.getElementById('deadline');
    const timeInput = document.getElementById('time');
    
    // Set current deadline as default
    const currentDeadline = new Date('<?= $currentDeadline ?>');
    deadlineInput.value = currentDeadline.toISOString().split('T')[0];
    timeInput.value = currentDeadline.toTimeString().substring(0, 5);
  });
  
  // Admin countdown timer
  function updateAdminCountdown() {
    const deadline = new Date('<?= $currentDeadline ?>').getTime();
    const now = new Date().getTime();
    const timeLeft = deadline - now;
    
    if (timeLeft > 0) {
      const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
      const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
      
      document.getElementById('adminDays').textContent = days.toString().padStart(2, '0');
      document.getElementById('adminHours').textContent = hours.toString().padStart(2, '0');
      document.getElementById('adminMinutes').textContent = minutes.toString().padStart(2, '0');
      document.getElementById('adminSeconds').textContent = seconds.toString().padStart(2, '0');
    } else {
      document.getElementById('adminDays').textContent = '00';
      document.getElementById('adminHours').textContent = '00';
      document.getElementById('adminMinutes').textContent = '00';
      document.getElementById('adminSeconds').textContent = '00';
    }
  }
  
  // Update countdown every second
  setInterval(updateAdminCountdown, 1000);
  updateAdminCountdown();
</script>
</body>
</html>