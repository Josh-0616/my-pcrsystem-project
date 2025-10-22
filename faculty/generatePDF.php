<?php
session_start();
include('../db/connection.php');

// Check if user is logged in and has faculty role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

// Get faculty ID
 $faculty_id = $_SESSION['user_id'] ?? 0;
if ($faculty_id === 0) {
    $_SESSION['error'] = "Invalid session. Please login again.";
    header("Location: ../login.php");
    exit();
}

// Get faculty details
 $facultyData = [];
 $stmt = $conn->prepare("SELECT full_name, email, profile_picture FROM users WHERE id = ? AND role = 'faculty'");
 $stmt->bind_param("i", $faculty_id);
 $stmt->execute();
 $result = $stmt->get_result();
if ($result->num_rows > 0) {
    $facultyData = $result->fetch_assoc();
}

// Handle profile data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? $facultyData['full_name'] ?? 'Faculty';
 $email = $_SESSION['email'] ?? $facultyData['email'] ?? '';

// Get all submissions with PDF files for this faculty
 $stmt = $conn->prepare("SELECT id, faculty_name, department, period, status, pdf_file, final_avg_rating, adjectival_rating FROM ipcr_submissions WHERE faculty_id = ? AND pdf_file IS NOT NULL AND pdf_file != '' ORDER BY id DESC");
 $stmt->bind_param("i", $faculty_id);
 $stmt->execute();
 $result = $stmt->get_result();
 $submissions = [];
while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}

// Get announcement count for notification badge
 $announcementCount = 0;
try {
    // First check if announcements table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'announcements'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Check if is_active column exists
        $columnCheck = $conn->query("SHOW COLUMNS FROM announcements LIKE 'is_active'");
        if ($columnCheck && $columnCheck->num_rows > 0) {
            // Use is_active column if it exists
            $announcementQuery = $conn->prepare("SELECT COUNT(*) as count FROM announcements WHERE is_active = 1");
        } else {
            // Simple count without is_active condition
            $announcementQuery = $conn->prepare("SELECT COUNT(*) as count FROM announcements");
        }
        
        if ($announcementQuery) {
            $announcementQuery->execute();
            $announcementResult = $announcementQuery->get_result();
            if ($announcementResult->num_rows > 0) {
                $announcementData = $announcementResult->fetch_assoc();
                $announcementCount = $announcementData['count'];
            }
            $announcementQuery->close();
        }
    }
} catch (Exception $e) {
    // If there's any error with announcements, default to 0
    $announcementCount = 0;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $submission_id = $_GET['id'];
    
    // Verify this submission belongs to the current faculty
    $verifySql = "SELECT pdf_file FROM ipcr_submissions WHERE id = ? AND faculty_id = ?";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->bind_param("ii", $submission_id, $faculty_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    $verifyData = $verifyResult->fetch_assoc();
    
    if ($verifyData) {
        // Delete the PDF file
        $pdf_path = '../pdfs/' . $verifyData['pdf_file'];
        if (file_exists($pdf_path)) {
            unlink($pdf_path);
        }
        
        // Update the database to remove the PDF file reference
        $updateSql = "UPDATE ipcr_submissions SET pdf_file = NULL WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $submission_id);
        $updateStmt->execute();
        
        $_SESSION['success'] = "PDF deleted successfully!";
        header("Location: generatePDF.php");
        exit();
    } else {
        $_SESSION['error'] = "You don't have permission to delete this PDF.";
        header("Location: generatePDF.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Saved PDFs - IPCR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="/asset/css/bootstrap.min.css">
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
    .pdf-container {
      background-color: var(--card-light);
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 30px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .pdf-container {
      background-color: var(--card-dark);
    }
    .pdf-card {
      background-color: var(--card-light);
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    [data-theme='dark'] .pdf-card {
      background-color: var(--card-dark);
    }
    .pdf-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    }
    .pdf-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 10px;
      color: #3a6ea5;
    }
    [data-theme='dark'] .pdf-title {
      color: #6a9ed5;
    }
    .pdf-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 15px;
      font-size: 0.9rem;
      color: #666;
    }
    [data-theme='dark'] .pdf-meta {
      color: #aaa;
    }
    .pdf-meta-item {
      display: flex;
      align-items: center;
    }
    .pdf-meta-item i {
      margin-right: 5px;
    }
    .pdf-actions {
      display: flex;
      gap: 10px;
    }
    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    .status-Pending {
      background-color: #fff3cd;
      color: #856404;
    }
    .status-Reviewed {
      background-color: #cfe2ff;
      color: #084298;
    }
    .status-Approved {
      background-color: #d1e7dd;
      color: #0f5132;
    }
    .status-Declined {
      background-color: #f8d7da;
      color: #842029;
    }
    .empty-state {
      text-align: center;
      padding: 50px 20px;
      color: #666;
    }
    [data-theme='dark'] .empty-state {
      color: #aaa;
    }
    .empty-state i {
      font-size: 4rem;
      margin-bottom: 20px;
      color: #ccc;
    }
    [data-theme='dark'] .empty-state i {
      color: #555;
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
      .pdf-container {
        padding: 20px;
      }
    }
    
    @media (max-width: 576px) {
      .pdf-container {
        padding: 15px;
      }
      .pdf-actions {
        flex-direction: column;
      }
      .pdf-actions .btn {
        width: 100%;
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
    <!-- Notification bell - only show badge if there are announcements -->
    <a href="view_announcements.php" class="notification-bell">
      <i class="fas fa-bell fa-lg"></i>
      <?php if ($announcementCount > 0): ?>
        <span class="badge"><?= $announcementCount ?></span>
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
    <a href="Submit_IPCR.php"><i class="fas fa-edit me-2"></i> Submit PCR</a>
    <a href="my_submissions.php"><i class="fas fa-folder-open me-2"></i> My Submissions</a>
    <a href="generatePDF.php" class="active"><i class="fas fa-download me-2"></i> Generate PDF</a>
    <a href="view_announcements.php"><i class="fas fa-bullhorn me-2"></i> View Announcements</a>
    <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
    <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
<div class="main-content">
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
  
  <div class="pdf-container">
    <h2 class="mb-4">Saved PDFs</h2>
    
    <?php if (empty($submissions)): ?>
      <div class="empty-state">
        <i class="fas fa-file-pdf"></i>
        <h3>No PDFs Found</h3>
        <p>You haven't saved any PDFs yet. Go to your submissions and save a PDF to see it here.</p>
        <a href="my_submissions.php" class="btn btn-primary mt-3">
          <i class="fas fa-folder-open me-2"></i> View My Submissions
        </a>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($submissions as $submission): ?>
          <div class="col-md-6 col-lg-4 mb-4">
            <div class="pdf-card">
              <div class="pdf-title">
                <?= htmlspecialchars($submission['faculty_name']) ?> - <?= htmlspecialchars($submission['period']) ?>
              </div>
              <div class="pdf-meta">
                <div class="pdf-meta-item">
                  <i class="fas fa-building"></i>
                  <span><?= htmlspecialchars($submission['department']) ?></span>
                </div>
                <div class="pdf-meta-item">
                  <i class="fas fa-star"></i>
                  <span><?= htmlspecialchars($submission['final_avg_rating']) ?></span>
                </div>
                <div class="pdf-meta-item">
                  <i class="fas fa-tag"></i>
                  <span><?= htmlspecialchars($submission['adjectival_rating']) ?></span>
                </div>
              </div>
              <div class="mb-3">
                <span class="status-badge status-<?= htmlspecialchars($submission['status']) ?>">
                  <?= htmlspecialchars($submission['status']) ?>
                </span>
              </div>
              <div class="pdf-actions">
                <a href="../pdfs/<?= htmlspecialchars($submission['pdf_file']) ?>" class="btn btn-primary" target="_blank">
                  <i class="fas fa-eye me-1"></i> View
                </a>
                <a href="../pdfs/<?= htmlspecialchars($submission['pdf_file']) ?>" class="btn btn-success" download>
                  <i class="fas fa-download me-1"></i> Download
                </a>
                <a href="generatePDF.php?action=delete&id=<?= $submission['id'] ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this PDF?')">
                  <i class="fas fa-trash me-1"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
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
    
    // Update icon
    const icon = toggleTheme.querySelector('i');
    if (next === 'dark') {
      icon.classList.remove('fa-moon');
      icon.classList.add('fa-sun');
    } else {
      icon.classList.remove('fa-sun');
      icon.classList.add('fa-moon');
    }
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