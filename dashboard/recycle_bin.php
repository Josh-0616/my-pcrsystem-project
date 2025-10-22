<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

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

// Get filter parameters
 $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
 $search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the base query
 $query = "SELECT ds.*, u.full_name as deleted_by_username 
         FROM deleted_submissions ds
         LEFT JOIN users u ON ds.deleted_by = u.id";

 $where_conditions = [];
 $params = [];
 $types = '';

if ($filter !== 'all') {
    $where_conditions[] = "ds.status = ?";
    $params[] = $filter;
    $types .= 's';
}

if (!empty($search)) {
    $searchTerm = "%$search%";
    $where_conditions[] = "(ds.faculty_name LIKE ? OR ds.department LIKE ? OR ds.period LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss';
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

 $query .= " ORDER BY ds.deleted_at DESC";

// Get deleted submissions
 $deletedSubmissions = [];
 $stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $deletedSubmissions[] = $row;
}

// Get counts for each status
 $statusCounts = [
    'all' => 0,
    'Approved' => 0,
    'Pending' => 0,
    'Declined' => 0
];

 $countQuery = "SELECT 
               COUNT(*) as total,
               SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
               SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined
              FROM deleted_submissions";

 $countResult = mysqli_query($conn, $countQuery);
if ($countResult && $row = mysqli_fetch_assoc($countResult)) {
    $statusCounts['all'] = $row['total'];
    $statusCounts['Approved'] = $row['approved'];
    $statusCounts['Pending'] = $row['pending'];
    $statusCounts['Declined'] = $row['declined'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Recycle Bin - IPCR</title>
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
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
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
      transition: all 0.3s ease;
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
      transform: translateX(5px);
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
      padding: 8px;
      border-radius: 4px;
      transition: background-color 0.3s;
    }
    .menu-toggle:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    /* Page header */
    .page-header {
      margin-bottom: 25px;
    }
    
    .page-title {
      font-size: 1.8rem;
      font-weight: 600;
      margin: 0;
      display: flex;
      align-items: center;
    }
    
    .page-title i {
      margin-right: 10px;
      color: #3a6ea5;
    }
    
    [data-theme='dark'] .page-title i {
      color: #6fa3dc;
    }
    
    /* Recycle bin specific styles */
    .empty-bin {
      text-align: center;
      padding: 50px 20px;
    }
    
    .empty-bin i {
      font-size: 5rem;
      color: #6c757d;
      margin-bottom: 20px;
    }
    
    .empty-bin h4 {
      color: #495057;
      margin-bottom: 15px;
    }
    
    .empty-bin p {
      color: #6c757d;
      margin-bottom: 25px;
    }
    
    /* Action buttons styling */
    .action-buttons {
      display: flex;
      gap: 5px;
    }
    
    .action-buttons .btn {
      margin: 0;
      white-space: nowrap;
    }
    
    .filter-section {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    [data-theme='dark'] .filter-section {
      background-color: #2c2c3e;
    }
    
    .filter-section h5 {
      font-weight: 600;
      margin-bottom: 15px;
      color: #495057;
    }
    
    [data-theme='dark'] .filter-section h5 {
      color: #dcdcdc;
    }
    
    .status-badge {
      font-size: 0.75rem;
      padding: 5px 10px;
      border-radius: 20px;
      font-weight: 600;
    }
    
    .card {
      border-radius: 8px;
      border: none;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 25px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .card:hover {
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    [data-theme='dark'] .card {
      background-color: var(--card-dark);
    }
    
    .card-header {
      background-color: transparent;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      padding: 15px 20px;
      font-weight: 600;
    }
    
    [data-theme='dark'] .card-header {
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .table {
      margin-bottom: 0;
    }
    
    .table thead th {
      border-bottom: 2px solid #dee2e6;
      font-weight: 600;
      color: #495057;
    }
    
    [data-theme='dark'] .table thead th {
      border-bottom: 2px solid #495057;
      color: #dcdcdc;
    }
    
    .table td {
      vertical-align: middle;
    }
    
    [data-theme='dark'] .table {
      color: var(--text-dark);
    }
    
    [data-theme='dark'] .table-striped > tbody > tr:nth-of-type(odd) > td,
    [data-theme='dark'] .table-striped > tbody > tr:nth-of-type(odd) > th {
      background-color: rgba(255, 255, 255, 0.02);
    }
    
    .btn {
      border-radius: 6px;
      padding: 8px 16px;
      font-weight: 500;
      transition: all 0.3s;
      border: none;
    }
    
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-primary {
      background-color: #3a6ea5;
    }
    
    .btn-primary:hover {
      background-color: #2d5680;
    }
    
    .btn-success {
      background-color: #28a745;
    }
    
    .btn-success:hover {
      background-color: #218838;
    }
    
    .btn-danger {
      background-color: #dc3545;
    }
    
    .btn-danger:hover {
      background-color: #c82333;
    }
    
    .btn-secondary {
      background-color: #6c757d;
    }
    
    .btn-secondary:hover {
      background-color: #5a6268;
    }
    
    .form-control {
      border-radius: 6px;
      border: 1px solid #ced4da;
      padding: 10px 15px;
    }
    
    .form-control:focus {
      border-color: #3a6ea5;
      box-shadow: 0 0 0 0.25rem rgba(58, 110, 165, 0.25);
    }
    
    [data-theme='dark'] .form-control {
      background-color: #3a3a4e;
      border-color: #495057;
      color: var(--text-dark);
    }
    
    /* Search input styles */
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
      box-shadow: 0 4px 12px rgba(74, 108, 247, 0.4);
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
    
    /* Toast notification */
    .toast-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 9999;
    }
    
    .toast {
      background-color: white;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      min-width: 300px;
    }
    
    [data-theme='dark'] .toast {
      background-color: #3a3a4e;
      color: var(--text-dark);
    }
    
    /* Responsive Styles */
    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
        padding: 20px;
      }
      
      .sidebar {
        transform: translateX(-100%);
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .menu-toggle {
        display: block;
      }
    }
    
    @media (max-width: 768px) {
      .filter-section .row > div {
        margin-bottom: 15px;
      }
      
      .page-title {
        font-size: 1.5rem;
      }
      
      .card {
        margin-bottom: 20px;
      }
      
      .table-responsive {
        border-radius: 8px;
      }
      
      /* Stack action buttons vertically on small screens */
      .action-buttons {
        flex-direction: column;
      }
      
      .action-buttons .btn {
        margin-bottom: 5px;
      }
      
      .btn-group {
        flex-wrap: wrap;
      }
      
      .btn-group .btn {
        margin-bottom: 5px;
      }
    }
    
    @media (max-width: 576px) {
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
      
      .main-content {
        padding: 15px;
      }
      
      .search-container {
        max-width: 100%;
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
        <a href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="fas fa-user-edit me-2"></i> My Account</a>
        <a href="../change_password.php"><i class="fas fa-key me-2"></i> Change Password</a>
        <a href="../support.php"><i class="fas fa-question-circle me-2"></i> Help & Support</a>
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
    <a href="deadline_settings.php"><i class="fas fa-clock me-2"></i> Deadline Settings</a>
    <a href="status_report.php"><i class="fas fa-clipboard-list me-2"></i> Status Report</a>
    <a href="recycle_bin.php" class="active"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
    <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
<div class="main-content">
  <div class="page-header">
    <h1 class="page-title"><i class="fas fa-trash-alt"></i> Recycle Bin</h1>
  </div>
  
  <!-- Filter and Search Section -->
  <div class="filter-section">
    <div class="row">
      <div class="col-md-6">
        <h5>Filter by Status:</h5>
        <div class="btn-group flex-wrap" role="group">
          <a href="?filter=all" class="btn btn-outline-primary <?= $filter == 'all' ? 'active' : '' ?>">
            All (<?= $statusCounts['all'] ?>)
          </a>
          <a href="?filter=Approved" class="btn btn-outline-success <?= $filter == 'Approved' ? 'active' : '' ?>">
            Approved (<?= $statusCounts['Approved'] ?>)
          </a>
          <a href="?filter=Pending" class="btn btn-outline-warning <?= $filter == 'Pending' ? 'active' : '' ?>">
            Pending (<?= $statusCounts['Pending'] ?>)
          </a>
        </div>
      </div>
      <div class="col-md-6">
        <h5>Search:</h5>
        <div class="search-container">
          <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Search submissions..." value="<?= htmlspecialchars($search) ?>">
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <?php if (count($deletedSubmissions) > 0): ?>
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>ID</th>
                <th>Faculty Name</th>
                <th>Department</th>
                <th>Period</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Deleted At</th>
                <th>Deleted By</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($deletedSubmissions as $submission): ?>
                <tr>
                  <td><?= $submission['original_id'] ?></td>
                  <td><?= htmlspecialchars($submission['faculty_name']) ?></td>
                  <td><?= htmlspecialchars($submission['department']) ?></td>
                  <td><?= htmlspecialchars($submission['period']) ?></td>
                  <td><?= $submission['final_avg_rating'] ? number_format($submission['final_avg_rating'], 2) : 'N/A' ?></td>
                  <td>
                    <span class="badge status-badge bg-<?= 
                      $submission['status'] === 'Approved' ? 'success' : 
                      ($submission['status'] === 'Pending' ? 'warning' : 'danger') 
                    ?>">
                      <?= htmlspecialchars($submission['status']) ?>
                    </span>
                  </td>
                  <td><?= date('M j, Y g:i A', strtotime($submission['deleted_at'])) ?></td>
                  <td><?= htmlspecialchars($submission['deleted_by_username']) ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn btn-sm btn-success restore-btn" data-id="<?= $submission['id'] ?>">
                        <i class="fas fa-undo"></i> Restore
                      </button>
                      <button class="btn btn-sm btn-danger permanent-delete-btn" data-id="<?= $submission['id'] ?>">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body empty-bin">
        <i class="fas fa-trash-alt"></i>
        <h4>Recycle Bin is Empty</h4>
        <p class="text-muted">No deleted submissions found in the recycle bin.</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Toast Container -->
<div class="toast-container"></div>

<!-- Confirmation Modal for Permanent Delete -->
<div class="modal fade" id="permanentDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Permanent Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to permanently delete this submission? This action cannot be undone.</p>
        <input type="hidden" id="deleteSubmissionId">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmPermanentDelete">Delete Permanently</button>
      </div>
    </div>
  </div>
</div>

<!-- Confirmation Modal for Restore -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Restore Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to restore this submission? It will be moved back to the active submissions list.</p>
        <input type="hidden" id="restoreSubmissionId">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="confirmRestore">Restore</button>
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
    const updateThemeIcon = () => {
    const icon = toggleTheme.querySelector('i');
    if (htmlTag.getAttribute('data-theme') === 'dark') {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
    } else {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }};
    
    updateThemeIcon();
    
    toggleTheme.addEventListener('click', () => {
        const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        htmlTag.setAttribute('data-theme', next);
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
        if (window.innerWidth <= 992 && 
            !sidebar.contains(e.target) && 
            !menuToggle.contains(e.target)) {
            sidebar.classList.remove("active");
        }
    });
    
    // Handle window resize
    window.addEventListener("resize", function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove("active");
        }
    });
    
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(function (row) {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
    
    // Toast notification function
    function showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' : 
                       type === 'danger' ? 'bg-danger' : 
                       type === 'warning' ? 'bg-warning' : 'bg-primary';
        
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }
    
    // Permanent Delete functionality
    const permanentDeleteBtns = document.querySelectorAll('.permanent-delete-btn');
    const permanentDeleteModal = new bootstrap.Modal(document.getElementById('permanentDeleteModal'));
    const confirmPermanentDelete = document.getElementById('confirmPermanentDelete');
    const deleteSubmissionId = document.getElementById('deleteSubmissionId');
    
    permanentDeleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            deleteSubmissionId.value = id;
            permanentDeleteModal.show();
        });
    });
    
    confirmPermanentDelete.addEventListener('click', function() {
        const id = deleteSubmissionId.value;
        
        fetch('permanent_delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Submission deleted permanently!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'danger');
        });
        
        permanentDeleteModal.hide();
    });
    
    // Restore functionality
    const restoreBtns = document.querySelectorAll('.restore-btn');
    const restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
    const confirmRestore = document.getElementById('confirmRestore');
    const restoreSubmissionId = document.getElementById('restoreSubmissionId');
    
    restoreBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            restoreSubmissionId.value = id;
            restoreModal.show();
        });
    });
    
    confirmRestore.addEventListener('click', function() {
        const id = restoreSubmissionId.value;
        
        fetch('restore_submission.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Submission restored successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast('Error: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'danger');
        });
        
        restoreModal.hide();
    });
</script>
</body>
</html>