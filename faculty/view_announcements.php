<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');
// Handle Profile Data
$profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
$fullName = $_SESSION['full_name'] ?? 'Faculty';
$email = $_SESSION['email'] ?? '';
// Get announcements
$announcementSql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
$announcementResult = mysqli_query($conn, $announcementSql);
$announcements = [];
while ($row = mysqli_fetch_assoc($announcementResult)) {
    $announcements[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>View Announcements - IPCR</title>
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
    
    /* Page-specific styles */
    .announcement-card {
      border-left: 5px solid #3a6ea5;
      background: var(--card-light);
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    [data-theme='dark'] .announcement-card {
      background-color: var(--card-dark);
      color: var(--text-dark);
    }
    .announcement-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .announcement-date {
      font-size: 0.85rem;
      color: #888;
      margin-bottom: 10px;
    }
    [data-theme='dark'] .announcement-date {
      color: #aaa;
    }
    .announcement-title {
      font-size: 1.2rem;
      font-weight: bold;
      color: #3a6ea5;
      margin-bottom: 10px;
    }
    [data-theme='dark'] .announcement-title {
      color: #6ba8ff;
    }
    .announcement-deadline {
      display: inline-block;
      background-color: #fff3cd;
      color: #856404;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.8rem;
      margin-left: 10px;
    }
    [data-theme='dark'] .announcement-deadline {
      background-color: #5a4a08;
      color: #f0e6b8;
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
      .announcement-card {
        padding: 15px;
      }
      .announcement-title {
        font-size: 1.1rem;
      }
    }
    
    @media (max-width: 576px) {
      .announcement-card {
        padding: 12px;
      }
      .announcement-title {
        font-size: 1rem;
      }
      .announcement-date {
        font-size: 0.8rem;
      }
      .announcement-deadline {
        display: block;
        margin-left: 0;
        margin-top: 5px;
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
    <a href="#" class="notification-bell" data-bs-toggle="modal" data-bs-target="#announcementModal">
      <i class="fas fa-bell fa-lg"></i>
      <?php if (count($announcements) > 0): ?>
        <span class="badge"><?= count($announcements) ?></span>
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
          <a href="My_Account.php"><i class="fas fa-user-edit me-2"></i> My Account</a>
          <a href="Change_Password.php"><i class="fas fa-key me-2"></i> Change Password</a>
          <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
      </div>
    </div>
  </div>
</div>
<div class="sidebar" id="sidebar">
  <a href="faculty_dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
  <a href="Submit_IPCR.php"><i class="fas fa-edit me-2"></i> Submit IPCR</a>
  <a href="my_submissions.php"><i class="fas fa-folder-open me-2"></i> My Submissions</a>
  <a href="generatePDF.php"><i class="fas fa-download me-2"></i> Generate PDF</a>
  <a href="view_announcements.php" class="active"><i class="fas fa-bullhorn me-2"></i> View Announcements</a>
  <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
  </div>
</div>

<div class="main-content">
    <h3 class="mb-4"><i class="fas fa-bullhorn me-2"></i> Announcements</h3>
  
  <?php if (count($announcements) > 0): ?>
    <?php foreach ($announcements as $announce): ?>
      <div class="announcement-card">
        <div class="announcement-title"><?= htmlspecialchars($announce['title']) ?></div>
        <div class="announcement-date">
          Posted on <?= date("F j, Y", strtotime($announce['created_at'])) ?>
          <span class="announcement-deadline">
            <i class="fas fa-clock me-1"></i> Deadline: <?= date("F j, Y", strtotime($announce['deadline'])) ?>
          </span>
        </div>
        <hr>
        <div><?= nl2br(htmlspecialchars($announce['content'])) ?></div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-info">
      <i class="fas fa-info-circle me-2"></i> No announcements available.
    </div>
  <?php endif; ?>
</div>

<!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="announcementModalLabel">Latest Announcements</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (count($announcements) > 0): ?>
          <?php foreach ($announcements as $announcement): ?>
            <div class="mb-4">
              <h6><?= htmlspecialchars($announcement['title']) ?></h6>
              <small class="text-muted"><?= date("F j, Y", strtotime($announcement['created_at'])) ?> | Deadline: <?= date("F j", strtotime($announcement['deadline'])) ?></small>
              <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
              <hr>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted">No announcements available.</p>
        <?php endif; ?>
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
</script>
</body>
</html>