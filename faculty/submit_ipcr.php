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

// Get faculty ID from session - use consistent variable name
 $facultyId = $_SESSION['user_id'] ?? 0;

// Get announcements
 $announcementSql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
 $announcementResult = mysqli_query($conn, $announcementSql);
 $announcements = [];
while ($row = mysqli_fetch_assoc($announcementResult)) {
    $announcements[] = $row;
}

// Get IPCR deadline from database
 $deadlineSql = "SELECT setting_value FROM ipcr_settings WHERE setting_name = 'submission_deadline'";
 $deadlineResult = mysqli_query($conn, $deadlineSql);
 $deadlineRow = mysqli_fetch_assoc($deadlineResult);
 $submissionDeadline = $deadlineRow ? $deadlineRow['setting_value'] : date('Y-m-d H:i:s', strtotime('+1 month'));

// Check if deadline has passed
 $deadlinePassed = (strtotime($submissionDeadline) < time());

// Check if faculty already submitted - but allow resubmission
 $checkSql = "SELECT * FROM ipcr_submissions WHERE faculty_id = ? AND period = ? ORDER BY created_at DESC LIMIT 1";
 $period = date('Y'); // Assuming period is the current year
 $stmt = $conn->prepare($checkSql);
 $stmt->bind_param("is", $facultyId, $period);
 $stmt->execute();
 $checkResult = $stmt->get_result();
 $lastSubmission = null;

if ($checkResult->num_rows > 0) {
    $lastSubmission = $checkResult->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Submit PCR - PCR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../images/pcc1.png">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="../js/bootstrap.bundle.min.js"></script>
  <style>
    /* All your existing CSS styles remain the same */
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
    [data-theme='dark'] .topbar {
      background-color: var(--sidebar-bg-dark);
    }
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 15px;
      flex: 1;
    }
    .topbar-center {
      display: flex;
      justify-content: center;
      flex: 2;
    }
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 10px;
      flex: 1;
      justify-content: flex-end;
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
    
    /* Progress Bar Styles - Aligned with IPCR Form */
    .form-progress-container {
      width: 100%;
      max-width: 100%;
      margin-bottom: 20px;
      position: sticky;
      top: 80px;
      z-index: 100;
      background-color: var(--bg-light);
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }
    [data-theme='dark'] .form-progress-container {
      background-color: var(--bg-dark);
    }
    .form-progress-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .form-progress-title {
      font-size: 1.1rem;
      font-weight: bold;
      color: var(--text-light);
      display: flex;
      align-items: center;
      flex: 1;
      min-width: 0;
    }
    [data-theme='dark'] .form-progress-title {
      color: var(--text-dark);
    }
    .form-progress-stats {
      font-size: 0.9rem;
      color: #3a6ea5;
      white-space: nowrap;
      flex-shrink: 0;
    }
    [data-theme='dark'] .form-progress-stats {
      color: #6ba8ff;
    }
    .form-progress-bar {
      height: 12px;
      border-radius: 6px;
      background-color: #e9ecef;
      overflow: hidden;
      box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
      width: 100%;
    }
    [data-theme='dark'] .form-progress-bar {
      background-color: #2c2c3e;
    }
    .form-progress-fill {
      height: 100%;
      border-radius: 6px;
      transition: width 0.5s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
      font-weight: bold;
      color: white;
      min-width: 30px; /* Ensure percentage text is visible even at low progress */
    }
    .form-progress-low {
      background: linear-gradient(45deg, #dc3545, #c82333);
    }
    .form-progress-medium {
      background: linear-gradient(45deg, #ffc107, #e0a800);
    }
    .form-progress-high {
      background: linear-gradient(45deg, #28a745, #218838);
    }
    .form-progress-complete {
      background: linear-gradient(45deg, #17a2b8, #138496);
    }
    
    /* Page-specific styles */
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      margin-bottom: 20px;
      background-color: var(--card-light);
    }
    [data-theme='dark'] .card {
      background-color: var(--card-dark);
    }
    .card-header {
      border-radius: 16px 16px 0 0 !important;
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
    .form-control {
      background-color: var(--card-light);
      color: var(--text-light);
      border-color: #ced4da;
    }
    [data-theme='dark'] .form-control {
      background-color: var(--card-dark);
      color: var(--text-dark);
      border-color: #444;
    }
    .form-control:focus {
      background-color: var(--card-light);
      color: var(--text-light);
    }
    [data-theme='dark'] .form-control:focus {
      background-color: var(--card-dark);
      color: var(--text-dark);
    }
    
    /* File Upload Styles */
    .file-input-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }
    .file-input-wrapper input[type=file] {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    .file-input-label {
      display: block;
      padding: 5px 10px;
      background-color: #f8f9fa;
      border: 1px solid #ced4da;
      border-radius: 4px;
      cursor: pointer;
      text-align: center;
      font-size: 0.8rem;
      transition: all 0.3s ease;
    }
    [data-theme='dark'] .file-input-label {
      background-color: #2c2c3e;
      border-color: #444;
    }
    .file-input-label:hover {
      background-color: #e9ecef;
    }
    [data-theme='dark'] .file-input-label:hover {
      background-color: #3a3a4e;
    }
    .file-input-label i {
      margin-right: 5px;
    }
    .file-name {
      font-size: 0.75rem;
      color: #6c757d;
      margin-top: 3px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    [data-theme='dark'] .file-name {
      color: #adb5bd;
    }
    .file-error {
      color: #dc3545;
      font-size: 0.75rem;
      margin-top: 3px;
    }
    
    /* Deadline passed and submission info styles */
    .deadline-passed {
      background-color: #f8d7da;
      color: #721c24;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 25px;
      text-align: center;
    }
    [data-theme='dark'] .deadline-passed {
      background-color: #5a1c1f;
      color: #e6b8b8;
    }
    
    /* Topbar Countdown Styles - Centered with Label */
    .deadline-countdown {
      display: flex;
      flex-direction: column;
      align-items: center;
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 8px 15px;
      transition: all 0.3s ease;
      justify-content: center;
    }
    .deadline-countdown:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }
    .deadline-countdown.warning {
      background-color: rgba(255, 193, 7, 0.3);
    }
    .deadline-countdown.danger {
      background-color: rgba(220, 53, 69, 0.3);
    }
    .deadline-countdown-label {
      font-size: 0.75rem;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 5px;
      opacity: 0.9;
    }
    .deadline-countdown-content {
      display: flex;
      align-items: center;
    }
    .deadline-countdown-icon {
      margin-right: 12px;
      font-size: 1.3rem;
    }
    .deadline-countdown-timer {
      display: flex;
      align-items: center;
      font-weight: bold;
    }
    .deadline-countdown-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin: 0 8px;
    }
    .deadline-countdown-value {
      font-size: 1.2rem;
      line-height: 1;
    }
    .deadline-countdown-item-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      opacity: 0.8;
    }
    
    /* Form closing animation */
    .form-closing {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      color: white;
      flex-direction: column;
    }
    .form-closing h2 {
      font-size: 2.5rem;
      margin-bottom: 20px;
    }
    .form-closing p {
      font-size: 1.2rem;
      max-width: 600px;
      text-align: center;
    }
    .form-closing .btn {
      margin-top: 20px;
    }
    
    /* Enhanced Responsive Styles */
    @media (max-width: 1200px) {
      .form-progress-container {
        padding: 12px;
      }
      .form-progress-title {
        font-size: 1rem;
      }
      .form-progress-stats {
        font-size: 0.85rem;
      }
    }

    @media (max-width: 992px) {
      .form-progress-container {
        top: 80px;
        padding: 10px;
        margin-bottom: 15px;
      }
      .form-progress-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
      }
      .form-progress-title {
        font-size: 0.95rem;
      }
      .form-progress-stats {
        font-size: 0.8rem;
        align-self: flex-end;
      }
      .form-progress-bar {
        height: 10px;
      }
      .form-progress-fill {
        font-size: 0.65rem;
      }
      .deadline-countdown {
        padding: 6px 12px;
      }
      .deadline-countdown-value {
        font-size: 1.1rem;
      }
      .deadline-countdown-item-label {
        font-size: 0.65rem;
      }
      .deadline-countdown-item {
        margin: 0 6px;
      }
      .deadline-countdown-label {
        font-size: 0.7rem;
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
      .form-control {
        font-size: 0.85rem;
      }
      .form-progress-container {
        top: 80px;
        padding: 8px;
        margin-bottom: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      }
      .form-progress-title {
        font-size: 0.9rem;
      }
      .form-progress-title i {
        font-size: 0.9rem;
      }
      .form-progress-stats {
        font-size: 0.75rem;
      }
      .form-progress-bar {
        height: 8px;
      }
      .form-progress-fill {
        font-size: 0.6rem;
        min-width: 25px;
      }
      .deadline-countdown {
        padding: 5px 10px;
      }
      .deadline-countdown-item {
        margin: 0 4px;
      }
      .deadline-countdown-value {
        font-size: 1rem;
      }
      .deadline-countdown-item-label {
        font-size: 0.6rem;
      }
      .deadline-countdown-label {
        font-size: 0.65rem;
      }
    }
    

    @media (max-width: 576px) {
      .form-control {
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
      }
      .card-body {
        padding: 1rem;
      }
      .table {
        font-size: 0.8rem;
      }
      .table td, .table th {
        padding: 0.5rem;
      }
      .form-progress-container {
        top: 75px; /* Slightly lower for smaller topbar on mobile */
        padding: 6px;
        margin-bottom: 10px;
        border-radius: 6px;
      }
      .form-progress-header {
        margin-bottom: 6px;
      }
      .form-progress-title {
        font-size: 0.85rem;
      }
      .form-progress-title i {
        margin-right: 6px;
      }
      .form-progress-stats {
        font-size: 0.7rem;
      }
      .form-progress-bar {
        height: 6px;
      }
      .form-progress-fill {
        font-size: 0.55rem;
        min-width: 20px;
        letter-spacing: -0.5px; /* Prevent text overflow */
      }
      .deadline-countdown {
        padding: 6px 8px;
      }
      .deadline-countdown-content {
        flex-direction: column;
      }
      .deadline-countdown-icon {
        margin-right: 0;
        margin-bottom: 5px;
      }
      .deadline-countdown-timer {
        width: 100%;
        justify-content: space-between;
      }
      .form-closing h2 {
        font-size: 1.8rem;
      }
      .form-closing p {
        font-size: 1rem;
        padding: 0 20px;
      }
    }

    /* Extra small devices (phones in portrait mode) */
    @media (max-width: 375px) {
      .form-progress-container {
        padding: 5px;
        margin-bottom: 8px;
      }
      .form-progress-title {
        font-size: 0.8rem;
      }
      .form-progress-stats {
        font-size: 0.65rem;
      }
      .form-progress-bar {
        height: 5px;
      }
      .form-progress-fill {
        font-size: 0.5rem;
      }
    }

    /* Landscape orientation for mobile devices */
    @media (max-height: 500px) and (orientation: landscape) {
      .form-progress-container {
        top: 70px;
        padding: 6px;
        margin-bottom: 8px;
      }
      .form-progress-header {
        flex-direction: row;
        align-items: center;
      }
      .form-progress-bar {
        height: 8px;
      }
    }
    
    /* New styles for improved form submission */
    .submission-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      color: white;
      flex-direction: column;
    }
    .submission-spinner {
      border: 5px solid #f3f3f3;
      border-top: 5px solid #3498db;
      border-radius: 50%;
      width: 50px;
      height: 50px;
      animation: spin 1s linear infinite;
      margin-bottom: 20px;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .field-error {
      border-color: #dc3545 !important;
    }
    .error-message {
      color: #dc3545;
      font-size: 0.8rem;
      margin-top: 0.25rem;
    }
    
    /* Signature section styling */
    .signature-section {
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      background-color: var(--card-light);
    }
    [data-theme='dark'] .signature-section {
      border-color: #444;
      background-color: var(--card-dark);
    }
    .signature-title {
      font-weight: bold;
      margin-bottom: 15px;
      color: var(--text-light);
    }
    [data-theme='dark'] .signature-title {
      color: var(--text-dark);
    }
    .signature-divider {
      border-left: 1px solid #dee2e6;
      height: 100%;
      margin: 0 15px;
    }
    [data-theme='dark'] .signature-divider {
      border-color: #444;
    }
  </style>
</head>
<body>
<div class="topbar">
  <div class="topbar-left">
    <button class="menu-toggle" id="menuToggle">
      <i class="fas fa-bars"></i>
    </button>
    <img src="../images/pcc1.png" alt="PCC Logo" class="pcc-logo">
  </div>
  
  <!-- Center section for the countdown timer -->
  <div class="topbar-center">
    <?php if (!$deadlinePassed): ?>
    <div class="deadline-countdown" id="topbarCountdown">
      <div class="deadline-countdown-label">Submission Deadline</div>
      <div class="deadline-countdown-content">
        <div class="deadline-countdown-icon">
        </div>
        <div class="deadline-countdown-timer">
          <div class="deadline-countdown-item">
            <span id="topbar-days" class="deadline-countdown-value">00</span>
            <span class="deadline-countdown-item-label">Days</span>
          </div>
          <div class="deadline-countdown-item">
            <span id="topbar-hours" class="deadline-countdown-value">00</span>
            <span class="deadline-countdown-item-label">Hours</span>
          </div>
          <div class="deadline-countdown-item">
            <span id="topbar-minutes" class="deadline-countdown-value">00</span>
            <span class="deadline-countdown-item-label">Mins</span>
          </div>
          <div class="deadline-countdown-item">
            <span id="topbar-seconds" class="deadline-countdown-value">00</span>
            <span class="deadline-countdown-item-label">Secs</span>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
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
  <a href="Submit_IPCR.php" class="active"><i class="fas fa-edit me-2"></i> Submit PCR</a>
  <a href="my_submissions.php"><i class="fas fa-folder-open me-2"></i> My Submissions</a>
    <a href="generatePDF.php" id="pdfLink"><i class="fas fa-download me-2"></i> Generate PDF</a>
  <a href="view_announcements.php"><i class="fas fa-bullhorn me-2"></i> View Announcements</a>
  <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
<div class="main-content">
  <?php if ($deadlinePassed): ?>
    <div class="deadline-passed">
      <h5><i class="fas fa-exclamation-triangle me-2"></i> Submission Closed</h5>
      <p>The deadline for PCR submission has passed. Please contact the admin for assistance.</p>
    </div>
  <?php endif; ?>
  
  <?php if (!$deadlinePassed): ?>
  <!-- Progress Bar - Aligned with IPCR Form -->
  <div class="form-progress-container">
    <div class="form-progress-header">
      <div class="form-progress-title">
        <i class="fas fa-tasks me-2"></i>PCR Completion Progress
      </div>
      <div class="form-progress-stats" id="progressStats">0 of 20 row completed (0%)</div>
    </div>
    <div class="form-progress-bar">
      <div class="form-progress-fill form-progress-low" id="progressBar" style="width: 0%">0%</div>
    </div>
  </div>
  
  <!-- Form with improved validation and submission handling -->
  <form id="ipcrForm" enctype="multipart/form-data" action="save_ipcr.php" method="POST" novalidate>
    <!-- Hidden fields for faculty information -->
    <input type="hidden" name="faculty_id" value="<?= $facultyId ?>">
    <input type="hidden" name="user_id" value="<?= $facultyId ?>">
    <input type="hidden" name="period" value="<?= date('Y') ?>">
    
    <div class="card shadow">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Performance Commitment and Review (PCR)</h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i> For each success indicator, you may optionally upload evidence (any file type, maximum 10MB per file). File upload is not required.
        </div>
        
        <div class="mb-3">
          <label class="form-label">I, <input type="text" name="faculty_name" class="form-control d-inline w-auto" value="<?= htmlspecialchars($fullName) ?>" required>,
          of the <select name="department" class="form-select d-inline w-auto" required>
            <option value="">Select Department</option>
            <option value="BSIT">BSIT</option>
            <option value="BSBA">BSBA</option>
            <option value="BSCRIM">BSCRIM</option>
          </select>
          of Philippine Countryville College, Inc., commit to deliver and agree to be rated on the following targets 
          in accordance with the indicated measure for the period of 
          <input type="date" name="period_display" class="form-control d-inline w-auto" value="<?= date('Y') ?>" required>.</label>
        </div>
        <p>Reviewed by: _____________________<br>
        <div class="mb-3">
          <strong>Rating Scale:</strong>
          <p>The following scale is used to rate performance:</p>
          <ul>
            <li><strong>5 – Outstanding</strong></li>
            <li><strong>4 – Very Satisfactory</strong></li>
            <li><strong>3 – Satisfactory</strong></li>
            <li><strong>2 – Unsatisfactory</strong></li>
            <li><strong>1 – Poor</strong></li>
          </ul>
        </div>
        
        <div class="table-responsive">
          <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
              <tr>
                <th>SUCCESS INDICATORS</th>
                <th>Accomplishment</th>
                <th>Quality</th>
                <th>Efficiency</th>
                <th>Time</th>
                <th>Average</th>
                <th>Remarks</th>
                <th>Upload File</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="ipcrBody">
              <tr>
                <td><input type="text" class="form-control indicator-field" name="indicator[]" value="" required></td>
                <td><input type="text" class="form-control accomplishment-field" name="accomplishment[]" value="" required></td>
                <td><input type="number" class="form-control rating" name="q[]" min="1" max="5"></td>
                <td><input type="number" class="form-control rating" name="e[]" min="1" max="5"></td>
                <td><input type="number" class="form-control rating" name="t[]" min="1" max="5"></td>
                <td><input type="text" class="form-control average" name="a[]" readonly></td>
                <td><input type="text" class="form-control" name="remarks[]"></td>
                <td>
                  <div class="file-input-wrapper">
                    <label class="file-input-label">
                      <i class="fas fa-upload"></i> Choose File
                      <input type="file" class="file-input" name="file[]">
                    </label>
                    <div class="file-name"></div>
                    <div class="file-error"></div>
                  </div>
                </td>
                <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
              </tr>
            </tbody>
          </table>
          <button type="button" id="addRow" class="btn btn-primary"><i class="fas fa-plus"></i> Add Row</button>
        </div>
        <div class="row">
          <div class="col-md-6">
            <label><strong>Final Average Rating:</strong></label>
            <input type="text" name="final_avg_rating" id="finalAverage" class="form-control" readonly>
          </div>
          <div class="col-md-6">
            <label><strong>Adjectival Rating:</strong></label>
            <input type="text" name="adjectival_rating" id="adjectivalRating" class="form-control" readonly>
          </div>
        </div>
        
        <!-- Signature Section with Two Columns -->
        <div class="signature-section">
          <div class="row">
            <div class="col-md-6">
              <div class="signature-title">Name and Signature of Ratee:</div>
              <div class="mb-3">
                <input type="text" name="ratee_name" class="form-control" placeholder="Full Name">
              </div>
              <div class="mb-3">
                <input type="text" name="ratee_position" class="form-control" placeholder="Position">
              </div>
              <div class="mb-3">
                <input type="date" name="ratee_date" class="form-control">
              </div>
            </div>
            <div class="col-md-6">
              <div class="signature-title">Final Rating by Program Head:</div>
              <div class="mb-3">
                <input type="text" name="final_rating_program_head" class="form-control" placeholder="Program Head Name">
              </div>
              <div class="mb-3">
                <input type="text" name="ph_position" class="form-control" placeholder="Position">
              </div>
              <div class="mb-3">
                <input type="date" name="ph_date" class="form-control">
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">
        <button type="submit" class="btn btn-success me-2">Submit PCR</button>
        <a href="faculty_dashboard.php" class="btn btn-secondary">Cancel</a>
      </div>
    </div>
  </form>
  <?php endif; ?>
</div>
<!-- Form Closing Overlay (hidden by default) -->
<div id="formClosingOverlay" class="form-closing" style="display: none;">
  <h2><i class="fas fa-hourglass-end me-3"></i>Time's Up!</h2>
  <p>The submission period for IPCR has now ended. Your form will be closed automatically.</p>
  <button class="btn btn-primary" onclick="redirectToDashboard()">Return to Dashboard</button>
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
// Global variables
let rowCount = 1;
const targetRows = 20;
let formSubmitted = false;

// Initialize the form
document.addEventListener('DOMContentLoaded', function() {
  calculateRatings();
  updateProgressBar();
  setupFormValidation();
  setupFileUploads();
});

// Setup file upload handling for all rows
function setupFileUploads() {
  document.querySelectorAll('#ipcrBody tr').forEach(row => {
    setupFileUpload(row);
  });
}

// File upload handling for each row
function setupFileUpload(row) {
  const fileInput = row.querySelector('input[type="file"]');
  const fileName = row.querySelector('.file-name');
  const fileError = row.querySelector('.file-error');
  
  if (!fileInput) return;
  
  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    
    // Reset previous messages
    fileName.textContent = '';
    fileError.textContent = '';
    
    if (file) {
      // Check file size (10MB = 10 * 1024 * 1024 bytes)
      if (file.size > 10 * 1024 * 1024) {
        fileError.textContent = 'Error: File size exceeds 10MB limit.';
        this.value = '';
        return;
      }
      
      // Display file name
      fileName.textContent = file.name;
    }
  });
}

// Progress Bar Functionality
function updateProgressBar() {
  const progressBar = document.getElementById('progressBar');
  const progressStats = document.getElementById('progressStats');
  
  if (!progressBar || !progressStats) return;
  
  const rows = document.querySelectorAll('#ipcrBody tr');
  let filledRows = 0;
  
  // Count rows that have at least one field filled
  rows.forEach(row => {
    const indicatorInput = row.querySelector('.indicator-field');
    const accomplishmentInput = row.querySelector('.accomplishment-field');
    
    if (indicatorInput && accomplishmentInput) {
      const indicator = indicatorInput.value.trim();
      const accomplishment = accomplishmentInput.value.trim();
      
      // Count row as filled if either field has content
      if (indicator !== '' || accomplishment !== '') {
        filledRows++;
      }
    }
  });
  
  // Calculate progress percentage (capped at 100%)
  let progressPercentage = Math.min(100, Math.round((filledRows / targetRows) * 100));
  
  // Update progress bar
  progressBar.style.width = progressPercentage + '%';
  progressBar.textContent = progressPercentage + '%';
  
  // Update progress stats
  progressStats.textContent = `${filledRows} of ${targetRows} rows completed (${progressPercentage}%)`;
  
  // Update progress bar color based on percentage
  progressBar.classList.remove('form-progress-low', 'form-progress-medium', 'form-progress-high', 'form-progress-complete');
  if (progressPercentage === 100) {
    progressBar.classList.add('form-progress-complete');
  } else if (progressPercentage >= 66) {
    progressBar.classList.add('form-progress-high');
  } else if (progressPercentage >= 33) {
    progressBar.classList.add('form-progress-medium');
  } else {
    progressBar.classList.add('form-progress-low');
  }
  
  console.log(`Progress: ${progressPercentage}% (${filledRows}/${targetRows} rows filled)`);
}

// Add event listeners to update progress when fields change
document.addEventListener('input', function(e) {
  if (e.target.classList.contains('indicator-field') || 
      e.target.classList.contains('accomplishment-field')) {
    updateProgressBar();
  }
});

// Add row functionality
document.getElementById("addRow").addEventListener("click", function() {
  const tbody = document.getElementById("ipcrBody");
  const row = document.createElement("tr");
  row.innerHTML = `
    <td><input type="text" class="form-control indicator-field" name="indicator[]" required></td>
    <td><input type="text" class="form-control accomplishment-field" name="accomplishment[]" required></td>
    <td><input type="number" class="form-control rating" name="q[]" min="1" max="5"></td>
    <td><input type="number" class="form-control rating" name="e[]" min="1" max="5"></td>
    <td><input type="number" class="form-control rating" name="t[]" min="1" max="5"></td>
    <td><input type="text" class="form-control average" name="a[]" readonly></td>
    <td><input type="text" class="form-control" name="remarks[]"></td>
    <td>
      <div class="file-input-wrapper">
        <label class="file-input-label">
          <i class="fas fa-upload"></i> Choose File
          <input type="file" class="file-input" name="file[]">
        </label>
        <div class="file-name"></div>
        <div class="file-error"></div>
      </div>
    </td>
    <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
  `;
  tbody.appendChild(row);
  
  // Setup file upload for the new row
  setupFileUpload(row);
  
  // Add event listeners to the new row inputs
  addRatingEventListeners(row);
  
  // Calculate ratings for the new row
  calculateRatings();
  
  // Update progress bar
  updateProgressBar();
  
  rowCount++;
});

// Remove row functionality
document.addEventListener("click", function(e) {
  if (e.target.closest(".remove-row")) {
    const row = e.target.closest("tr");
    row.remove();
    calculateRatings(); // Recalculate after removing a row
    updateProgressBar(); // Update progress bar
  }
});

// Function to add event listeners to rating inputs
function addRatingEventListeners(row) {
  const ratingInputs = row.querySelectorAll('.rating');
  ratingInputs.forEach(input => {
    input.addEventListener('input', calculateRatings);
  });
}

// Initial event listeners for the first row
addRatingEventListeners(document.querySelector('#ipcrBody tr'));

// Calculate ratings
function calculateRatings() {
  const form = document.getElementById("ipcrForm");
  if (!form) return;
  
  let total = 0, count = 0;
  
  // Get all rows
  const rows = document.querySelectorAll('#ipcrBody tr');
  
  rows.forEach((row) => {
    const qInput = row.querySelector('input[name="q[]"]');
    const eInput = row.querySelector('input[name="e[]"]');
    const tInput = row.querySelector('input[name="t[]"]');
    const aInput = row.querySelector('input[name="a[]"]');
    
    const q = parseFloat(qInput.value) || 0;
    const e = parseFloat(eInput.value) || 0;
    const t = parseFloat(tInput.value) || 0;
    let a = "";
    
    if (q || e || t) {
      a = ((q + e + t) / 3).toFixed(2);
      total += parseFloat(a);
      count++;
    }
    
    aInput.value = a;
  });
  
  const finalAvg = count > 0 ? (total / count).toFixed(2) : "";
  document.getElementById("finalAverage").value = finalAvg;
  document.getElementById("adjectivalRating").value = getAdjectivalRating(finalAvg);
}

function getAdjectivalRating(avg) {
  avg = parseFloat(avg);
  if (isNaN(avg)) return "";
  if (avg < 1.55) return "Poor";
  if (avg < 2.55) return "Unsatisfactory";
  if (avg < 3.55) return "Satisfactory";
  if (avg < 4.55) return "Very Satisfactory";
  return "Outstanding";
}

// Form validation setup
function setupFormValidation() {
  const form = document.getElementById("ipcrForm");
  if (!form) return;
  
  form.addEventListener("submit", function(e) {
    if (formSubmitted) {
      e.preventDefault();
      return;
    }
    
    // Prevent default submission
    e.preventDefault();
    
    // Validate form
    if (validateForm()) {
      // Show submission overlay
      showSubmissionOverlay();
      
      // Create FormData
      const formData = new FormData(this);
      
      // Create AJAX request
      const xhr = new XMLHttpRequest();
      
      // Load event listener
      xhr.addEventListener('load', function() {
        hideSubmissionOverlay();
        
        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            
            if (response.success) {
              showAlert('success', 'IPCR submitted successfully!');
              formSubmitted = true;
              setTimeout(() => {
                window.location.href = 'my_submissions.php';
              }, 2000);
            } else {
              showAlert('danger', response.message || 'Error submitting IPCR.');
            }
          } catch (e) {
            console.error('Response parsing error:', e);
            showAlert('danger', 'Invalid server response. Please try again.');
          }
        } else {
          showAlert('danger', `Server error (${xhr.status}). Please try again.`);
        }
      });
      
      // Error event listener
      xhr.addEventListener('error', function() {
        hideSubmissionOverlay();
        showAlert('danger', 'Network error. Please check your connection and try again.');
      });
      
      // Timeout event listener
      xhr.addEventListener('timeout', function() {
        hideSubmissionOverlay();
        showAlert('danger', 'Request timed out. Please try again.');
      });
      
      // Set timeout (30 seconds)
      xhr.timeout = 30000;
      
      // Send request
      xhr.open('POST', 'save_ipcr.php', true);
      xhr.send(formData);
    }
  });
}

// Form validation function
function validateForm() {
  const form = document.getElementById("ipcrForm");
  if (!form) return false;
  
  let isValid = true;
  
  // Clear previous errors
  document.querySelectorAll('.field-error').forEach(el => {
    el.classList.remove('field-error');
  });
  document.querySelectorAll('.error-message').forEach(el => {
    el.remove();
  });
  
  // Validate faculty name
  const facultyName = form.querySelector('input[name="faculty_name"]');
  if (!facultyName.value.trim()) {
    showFieldError(facultyName, 'Faculty name is required');
    isValid = false;
  }
  
  // Validate department
  const department = form.querySelector('select[name="department"]');
  if (!department.value) {
    showFieldError(department, 'Department selection is required');
    isValid = false;
  }
  
  // Validate at least one row is filled
  const rows = document.querySelectorAll('#ipcrBody tr');
  let hasFilledRow = false;
  
  rows.forEach((row, index) => {
    const indicatorInput = row.querySelector('.indicator-field');
    const accomplishmentInput = row.querySelector('.accomplishment-field');
    
    if (indicatorInput && accomplishmentInput) {
      const indicator = indicatorInput.value.trim();
      const accomplishment = accomplishmentInput.value.trim();
      
      if (indicator || accomplishment) {
        hasFilledRow = true;
        
        // Validate that if one is filled, both should be filled
        if (!indicator) {
          showFieldError(indicatorInput, 'Success indicator is required');
          isValid = false;
        }
        
        if (!accomplishment) {
          showFieldError(accomplishmentInput, 'Accomplishment is required');
          isValid = false;
        }
      }
    }
  });
  
  if (!hasFilledRow) {
    showAlert('warning', 'Please fill at least one row with success indicator and accomplishment');
    isValid = false;
  }
  
  // Validate file sizes
  const fileInputs = form.querySelectorAll('input[type="file"]');
  fileInputs.forEach(input => {
    if (input.files[0] && input.files[0].size > 10 * 1024 * 1024) {
      const fileError = input.closest('td').querySelector('.file-error');
      if (fileError) {
        fileError.textContent = 'File size exceeds 10MB limit.';
      }
      isValid = false;
    }
  });
  
  return isValid;
}

// Show field error
function showFieldError(field, message) {
  field.classList.add('field-error');
  
  const errorDiv = document.createElement('div');
  errorDiv.className = 'error-message';
  errorDiv.textContent = message;
  
  field.parentNode.appendChild(errorDiv);
}

// Show submission overlay
function showSubmissionOverlay() {
  const overlay = document.createElement('div');
  overlay.className = 'submission-overlay';
  overlay.id = 'submissionOverlay';
  overlay.innerHTML = `
    <div class="submission-spinner"></div>
    <h3>Submitting IPCR...</h3>
    <p>Please wait while we process your submission.</p>
  `;
  
  document.body.appendChild(overlay);
}

// Hide submission overlay
function hideSubmissionOverlay() {
  const overlay = document.getElementById('submissionOverlay');
  if (overlay) {
    overlay.remove();
  }
}

// Show alert message
function showAlert(type, message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
  alertDiv.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  // Insert at the top of the card body
  const cardBody = document.querySelector('.card-body');
  if (cardBody) {
    cardBody.prepend(alertDiv);
  } else {
    // Fallback if card body doesn't exist
    document.querySelector('.main-content').prepend(alertDiv);
  }
  
  // Auto dismiss after 5 seconds
  setTimeout(() => {
    alertDiv.classList.remove('show');
    setTimeout(() => {
      alertDiv.remove();
    }, 150);
  }, 5000);
}

// Countdown timer with form closing functionality
let countdownInterval;
let formClosed = false;

function updateCountdown() {
  const deadline = new Date('<?= $submissionDeadline ?>').getTime();
  const now = new Date().getTime();
  const timeLeft = deadline - now;
  
  if (timeLeft > 0) {
    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
    
    // Update topbar countdown
    const topbarDays = document.getElementById('topbar-days');
    const topbarHours = document.getElementById('topbar-hours');
    const topbarMinutes = document.getElementById('topbar-minutes');
    const topbarSeconds = document.getElementById('topbar-seconds');
    
    if (topbarDays) topbarDays.textContent = days.toString().padStart(2, '0');
    if (topbarHours) topbarHours.textContent = hours.toString().padStart(2, '0');
    if (topbarMinutes) topbarMinutes.textContent = minutes.toString().padStart(2, '0');
    if (topbarSeconds) topbarSeconds.textContent = seconds.toString().padStart(2, '0');
    
    // Add warning classes when time is running out
    const countdownElement = document.getElementById('topbarCountdown');
    if (countdownElement) {
      if (timeLeft < 3600000) { // Less than 1 hour
        countdownElement.classList.add('danger');
        countdownElement.classList.remove('warning');
      } else if (timeLeft < 86400000) { // Less than 1 day
        countdownElement.classList.add('warning');
        countdownElement.classList.remove('danger');
      } else {
        countdownElement.classList.remove('warning', 'danger');
      }
    }
  } else if (!formClosed) {
    // Deadline passed, close the form
    formClosed = true;
    clearInterval(countdownInterval);
    closeForm();
  }
}

function closeForm() {
  // Show the overlay
  document.getElementById('formClosingOverlay').style.display = 'flex';
  
  // Disable form inputs if they exist
  const form = document.getElementById('ipcrForm');
  if (form) {
    const inputs = form.querySelectorAll('input, button, select, textarea');
    inputs.forEach(input => {
      input.disabled = true;
    });
    
    // Change submit button to show "Closed"
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.textContent = 'Submission Closed';
      submitBtn.classList.remove('btn-success');
      submitBtn.classList.add('btn-secondary');
    }
  }
  
  // Auto-redirect after 5 seconds
  setTimeout(() => {
    redirectToDashboard();
  }, 5000);
}

function redirectToDashboard() {
  window.location.href = 'faculty_dashboard.php';
}

// Start countdown
countdownInterval = setInterval(updateCountdown, 1000);
updateCountdown();

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