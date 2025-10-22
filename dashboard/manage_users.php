<?php
session_start();
include('../db/connection.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
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
 $stmt = $conn->prepare("SELECT COUNT(*) as unread FROM admindashboard_notification WHERE status = 'unread'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $notificationCount = $row['unread'];
    }
    $stmt->close();
}

// Handle delete operation
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Set success message
        $_SESSION['message'] = "User deleted successfully.";
        $_SESSION['msg_type'] = "success";
    } else {
        // Set error message
        $_SESSION['message'] = "Error deleting user.";
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: manage_users.php");
    exit();
}

// Handle edit operation - fetch user data for editing
 $editUser = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $editUser = $row;
            
            // If user is faculty, redirect to faculty profile edit page
            if ($editUser['role'] === 'faculty') {
                $stmt->close();
                header("Location: editprofile.php?id=$id");
                exit();
            }
        }
        $stmt->close();
    }
}

// Handle view operation - fetch user data for viewing
 $viewUser = null;
if (isset($_GET['view'])) {
    $id = intval($_GET['view']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $viewUser = $row;
        }
        $stmt->close();
    }
}

// Pagination setup
 $limit = 10; // Number of records per page
 $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
 $offset = ($page - 1) * $limit;

// Get total number of users (excluding admin)
 $totalUsers = 0;
 $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalUsers = $row['total'];
    }
    $stmt->close();
}
 $totalPages = ceil($totalUsers / $limit);

// Fetch users for current page
 $users = [];
 $stmt = $conn->prepare("SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC LIMIT ? OFFSET ?");
if ($stmt) {
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Add profile picture URL to each user
        $row['profile_picture_url'] = isset($row['profile_picture']) && $row['profile_picture'] !== '' 
            ? '../uploads/' . $row['profile_picture'] 
            : '../uploads/1.png';
        $users[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - IPCR</title>
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
      --primary-color: #4a6cf7;
      --secondary-color: #3656d3;
      --accent-color: #6c63ff;
      --success-color: #4caf50;
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
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.08);
      width: 100%;
    }
    
    /* Enhanced Squared Generate Code Button Styles */
    .generate-code-btn {
      position: relative;
      padding: 12px 24px;
      border-radius: 4px;
      background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
      color: white;
      border: none;
      font-weight: 600;
      font-size: 15px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      box-shadow: 0 4px 8px rgba(74, 108, 247, 0.3);
      transition: all 0.3s ease;
      overflow: hidden;
      z-index: 1;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      text-decoration: none; /* Remove underline */
    }
    
    .generate-code-btn:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, var(--accent-color), var(--primary-color));
      z-index: -1;
      transition: opacity 0.3s ease;
      opacity: 0;
    }
    
    .generate-code-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 12px rgba(74, 108, 247, 0.4);
      color: white;
      text-decoration: none; /* Ensure no underline on hover */
    }
    
    .generate-code-btn:hover:before {
      opacity: 1;
    }
    
    .generate-code-btn:active {
      transform: translateY(1px);
      box-shadow: 0 2px 4px rgba(74, 108, 247, 0.3);
    }
    
    .generate-code-btn:focus {
      text-decoration: none; /* Ensure no underline on focus */
      outline: none;
    }
    
    .generate-code-btn i {
      font-size: 18px;
    }
    
    /* Add subtle animation for the button */
    @keyframes subtle-glow {
      0% {
        box-shadow: 0 4px 8px rgba(74, 108, 247, 0.3);
      }
      50% {
        box-shadow: 0 4px 12px rgba(74, 108, 247, 0.4);
      }
      100% {
        box-shadow: 0 4px 8px rgba(74, 108, 247, 0.3);
      }
    }
    
    .generate-code-btn.glow {
      animation: subtle-glow 3s infinite;
    }
    
    /* New Search Bar Styles */
    .search-container {
      position: relative;
      width: 100%;
      max-width: 500px;
      margin: 0 auto;
    }
    
    .search-box {
      position: relative;
      width: 100%;
    }
    
    .search-input {
      width: 100%;
      padding: 15px 20px 15px 50px;
      border: none;
      border-radius: 25px;
      background-color: #ffffff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      font-size: 16px;
      outline: none;
      transition: box-shadow 0.3s ease;
    }
    
    .search-input:focus {
      box-shadow: 0 4px 12px rgba(74, 108, 247, 0.4); /* Blue shadow effect */
    }
    
    .search-icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: #888888;
    }
    
    [data-theme='dark'] .search-input {
      background-color: #2c2c3e;
      color: #dcdcdc;
    }
    
    [data-theme='dark'] .search-icon {
      color: #aaa;
    }
    
    .sticky-topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background-color: var(--card-light);
      border-bottom: 1px solid #e0e0e0;
    }
    [data-theme='dark'] .sticky-topbar {
      background-color: var(--card-dark);
      border-bottom: 1px solid #444;
    }
    .table-responsive {
      height: calc(100vh - 200px);
      overflow-y: auto;
    }
    .table thead th {
      position: sticky;
      top: 0;
      background-color: var(--card-light);
      z-index: 10;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    [data-theme='dark'] .table thead th {
      background-color: var(--card-dark);
    }
    
    /* Profile view modal styles */
    .profile-header {
      background-color: #3a6ea5;
      color: white;
      padding: 20px;
      border-radius: 10px 10px 0 0;
      text-align: center;
    }
    .profile-picture {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid white;
      margin-bottom: 15px;
    }
    .profile-info {
      padding: 20px;
    }
    .profile-info .row {
      margin-bottom: 15px;
    }
    .profile-info .label {
      font-weight: bold;
      color: #666;
    }
    [data-theme='dark'] .profile-info .label {
      color: #aaa;
    }
    
    /* Card view for mobile */
    .user-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      margin-bottom: 15px;
      padding: 15px;
      background-color: var(--card-light);
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    [data-theme='dark'] .user-card {
      background-color: var(--card-dark);
      border-color: #444;
    }
    .user-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
    }
    [data-theme='dark'] .user-card-header {
      border-bottom-color: #444;
    }
    .user-card-title {
      font-weight: bold;
      font-size: 1.1rem;
    }
    .user-card-id {
      background-color: #f0f0f0;
      color: #666;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 0.8rem;
    }
    [data-theme='dark'] .user-card-id {
      background-color: #444;
      color: #aaa;
    }
    .user-card-body {
      margin-bottom: 10px;
    }
    .user-card-row {
      display: flex;
      margin-bottom: 5px;
    }
    .user-card-label {
      font-weight: bold;
      width: 80px;
      color: #666;
    }
    [data-theme='dark'] .user-card-label {
      color: #aaa;
    }
    .user-card-actions {
      display: flex;
      gap: 5px;
      justify-content: flex-end;
    }
    
    /* User profile image in table */
    .user-profile-img {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #e0e0e0;
    }
    
    /* Pagination styles */
    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
    
    .pagination {
      display: flex;
      list-style: none;
      padding: 0;
      margin: 0;
    }
    
    .pagination li {
      margin: 0 5px;
    }
    
    .pagination a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #f0f0f0;
      color: #333;
      text-decoration: none;
      transition: all 0.3s ease;
    }
    
    .pagination a:hover {
      background-color: #3a6ea5;
      color: white;
    }
    
    .pagination .active a {
      background-color: #3a6ea5;
      color: white;
    }
    
    .pagination .disabled a {
      opacity: 0.5;
      cursor: not-allowed;
    }
    
    [data-theme='dark'] .pagination a {
      background-color: #2c2c3e;
      color: #dcdcdc;
    }
    
    [data-theme='dark'] .pagination a:hover,
    [data-theme='dark'] .pagination .active a {
      background-color: #4a6cf7;
    }
    
    /* Alert styles */
    .alert-container {
      position: fixed;
      top: 80px;
      right: 20px;
      z-index: 1050;
      max-width: 350px;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 10px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .table-responsive {
        height: calc(100vh - 180px);
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
        height: calc(100vh - 150px);
      }
      .generate-code-btn {
        padding: 10px 20px;
        font-size: 14px;
      }
      .generate-code-btn i {
        font-size: 16px;
      }
    }
    
    @media (max-width: 576px) {
      .table-responsive {
        height: auto;
        overflow-x: auto;
      }
      .table-responsive table {
        display: none;
      }
      .card-view {
        display: block;
      }
      .search-container {
        max-width: 100%;
        margin-top: 10px;
      }
      .d-flex.justify-content-between {
        flex-direction: column;
        align-items: flex-start !important;
      }
      .header-actions {
        width: 100%;
        flex-direction: column;
        gap: 10px;
      }
      .generate-code-btn {
        width: 100%;
        justify-content: center;
      }
      .modal-dialog {
        margin: 10px;
      }
      .pagination-container {
        margin-top: 15px;
      }
      
      .pagination a {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
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
  <a href="manage_users.php" class="active"><i class="fas fa-users me-2"></i> Manage Users</a>
  <a href="department_reports.php"><i class="fas fa-layer-group me-2"></i>Department Reports</a>
  <a href="announcements.php"><i class="fas fa-bullhorn me-2"></i> Announcements</a>
  <a href="view_ipcr_submissions.php"><i class="fas fa-file-alt me-2"></i> View Submissions</a>
  <a href="deadline_settings.php"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
  <a href="status_report.php"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
  <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
  <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>

<div class="main-content">
  <!-- Alert container for messages -->
  <div class="alert-container">
    <?php if (isset($_SESSION['message']) && isset($_SESSION['msg_type'])): ?>
      <div class="alert alert-<?= $_SESSION['msg_type'] ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php 
        unset($_SESSION['message']);
        unset($_SESSION['msg_type']);
      ?>
    <?php endif; ?>
  </div>
  
  <div class="sticky-topbar p-3">
    
    <!-- Header and Search -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0"><i class="fas fa-users me-2 text-primary"></i> Manage Users</h4>
      <div class="header-actions d-flex gap-2 align-items-center">
        <div class="search-container">
          <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Search user...">
          </div>
        </div>
        <a href="generate_code_registration.php" class="generate-code-btn glow">
          <i class="fas fa-key"></i>
          <span>Generate Code</span>
        </a>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Created</th>
          <th class="text-end">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $row): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td class="px-6 py-3 d-flex align-items-center gap-3">
            <img src="<?= $row['profile_picture_url'] ?>" class="user-profile-img" alt="Profile Picture">
            <span><?= htmlspecialchars($row['name']) ?></span>
          </td>
          <td><?= htmlspecialchars($row['email']) ?></td>
          <td>
            <span class="badge bg-<?= $row['role'] === 'faculty' ? 'success' : 'info' ?>">
              <?= ucfirst($row['role']) ?>
            </span>
          </td>
          <td><?= date('Y-m-d', strtotime($row['created_at'] ?? 'now')) ?></td>
          <td class="text-end">
            <?php if ($row['role'] === 'faculty'): ?>
              <a href="viewprofile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="View Faculty Profile">
                <i class="fas fa-eye"></i>
              </a>
              <a href="editprofile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" title="Edit Faculty Profile">
                <i class="fas fa-edit"></i>
              </a>
            <?php else: ?>
              <a href="?view=<?= $row['id'] ?>" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewProfileModal<?= $row['id'] ?>" title="View Profile">
                <i class="fas fa-eye"></i>
              </a>
              <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary" title="Edit User">
                <i class="fas fa-edit"></i>
              </a>
            <?php endif; ?>
            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Delete User" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
              <i class="fas fa-trash-alt"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  
  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination-container">
    <ul class="pagination">
      <li class="<?= $page <= 1 ? 'disabled' : '' ?>">
        <a href="?page=<?= max(1, $page - 1) ?>" aria-label="Previous">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
      
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
          <li class="<?= $i == $page ? 'active' : '' ?>">
            <a href="?page=<?= $i ?>"><?= $i ?></a>
          </li>
        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
          <li class="disabled">
            <a>...</a>
          </li>
        <?php endif; ?>
      <?php endfor; ?>
      
      <li class="<?= $page >= $totalPages ? 'disabled' : '' ?>">
        <a href="?page=<?= min($totalPages, $page + 1) ?>" aria-label="Next">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    </ul>
  </div>
  <?php endif; ?>
  
  <!-- Card View for Mobile -->
  <div class="card-view">
    <?php foreach($users as $row): ?>
    <div class="user-card">
      <div class="user-card-header">
        <div class="d-flex align-items-center">
          <img src="<?= $row['profile_picture_url'] ?>" class="user-profile-img me-2" alt="Profile Picture">
          <div class="user-card-title"><?= htmlspecialchars($row['name']) ?></div>
        </div>
        <div class="user-card-id">ID: <?= $row['id'] ?></div>
      </div>
      <div class="user-card-body">
        <div class="user-card-row">
          <div class="user-card-label">Email:</div>
          <div><?= htmlspecialchars($row['email']) ?></div>
        </div>
        <div class="user-card-row">
          <div class="user-card-label">Role:</div>
          <div>
            <span class="badge bg-<?= $row['role'] === 'faculty' ? 'success' : 'info' ?>">
              <?= ucfirst($row['role']) ?>
            </span>
          </div>
        </div>
        <div class="user-card-row">
          <div class="user-card-label">Created:</div>
          <div><?= date('Y-m-d', strtotime($row['created_at'] ?? 'now')) ?></div>
        </div>
      </div>
      <div class="user-card-actions">
        <?php if ($row['role'] === 'faculty'): ?>
          <a href="viewprofile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info" title="View Faculty Profile">
            <i class="fas fa-eye"></i>
          </a>
          <a href="editprofile.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" title="Edit Faculty Profile">
            <i class="fas fa-edit"></i>
          </a>
        <?php else: ?>
          <a href="?view=<?= $row['id'] ?>" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewProfileModal<?= $row['id'] ?>" title="View Profile">
            <i class="fas fa-eye"></i>
          </a>
          <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm btn-primary" title="Edit User">
            <i class="fas fa-edit"></i>
          </a>
        <?php endif; ?>
        <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" title="Delete User" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
          <i class="fas fa-trash-alt"></i>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php foreach($users as $row): ?>
<?php if ($row['role'] !== 'faculty'): ?>
<div class="modal fade" id="viewProfileModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="viewProfileLabel<?= $row['id'] ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="profile-header">
        <img src="<?= $row['profile_picture_url'] ?>" class="profile-picture" alt="Profile Picture">
        <h4><?= htmlspecialchars($row['name']) ?></h4>
        <p><?= htmlspecialchars($row['email']) ?></p>
      </div>
      <div class="profile-info">
        <div class="row">
          <div class="col-md-4 label">Username:</div>
          <div class="col-md-8"><?= htmlspecialchars($row['username']) ?></div>
        </div>
        <div class="row">
          <div class="col-md-4 label">Role:</div>
          <div class="col-md-8"><?= ucfirst($row['role']) ?></div>
        </div>
        <?php if (isset($row['department'])): ?>
        <div class="row">
          <div class="col-md-4 label">Department:</div>
          <div class="col-md-8"><?= htmlspecialchars($row['department']) ?></div>
        </div>
        <?php endif; ?>
        <div class="row">
          <div class="col-md-4 label">Account Created:</div>
          <div class="col-md-8"><?= date('F j, Y', strtotime($row['created_at'] ?? 'now')) ?></div>
        </div>
        <?php if (isset($row['last_login'])): ?>
        <div class="row">
          <div class="col-md-4 label">Last Login:</div>
          <div class="col-md-8"><?= date('F j, Y, g:i a', strtotime($row['last_login'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="?edit=<?= $row['id'] ?>" class="btn btn-primary"><i class="fas fa-edit me-1"></i> Edit Profile</a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- Edit User Modal (only shown when editing a non-faculty user) -->
<?php if ($editUser && $editUser['role'] !== 'faculty'): ?>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST" action="edit_user_process.php">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserLabel"><i class="fas fa-user-edit me-2"></i> Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
        <div class="col-md-6">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($editUser['name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($editUser['username']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($editUser['email']) ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Role</label>
          <select class="form-select" name="role" required>
            <option value="faculty" <?= $editUser['role'] === 'faculty' ? 'selected' : '' ?>>Faculty</option>
            <option value="staff" <?= $editUser['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Department</label>
          <select class="form-select" name="department">
            <option value="BSIT" <?= (isset($editUser['department']) && $editUser['department'] === 'BSIT') ? 'selected' : '' ?>>BSIT</option>
            <option value="BSBA" <?= (isset($editUser['department']) && $editUser['department'] === 'BSBA') ? 'selected' : '' ?>>BSBA</option>
            <option value="BS-CRIM" <?= (isset($editUser['department']) && $editUser['department'] === 'BS-CRIM') ? 'selected' : '' ?>>BS-CRIM</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" class="form-control" name="password">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
<script>
  // Auto-show edit modal if editing
  document.addEventListener('DOMContentLoaded', function() {
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    editModal.show();
  });
</script>
<?php endif; ?>

<script>
  // Search functionality
  document.getElementById('searchInput').addEventListener('keyup', function () {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('tbody tr');
    let cards = document.querySelectorAll('.user-card');
    
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
  toggleTheme.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    
    // Update icon
    const icon = document.querySelector('#toggleTheme i');
    if (next === 'dark') {
      icon.classList.remove('fa-moon');
      icon.classList.add('fa-sun');
    } else {
      icon.classList.remove('fa-sun');
      icon.classList.add('fa-moon');
    }
  });
  
  // Initialize theme based on saved preference
  document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Update icon based on saved theme
    const icon = document.querySelector('#toggleTheme i');
    if (savedTheme === 'dark') {
      icon.classList.remove('fa-moon');
      icon.classList.add('fa-sun');
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