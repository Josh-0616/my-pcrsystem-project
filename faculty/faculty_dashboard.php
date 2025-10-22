<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

// Debug mode - set to false in production
 $debug = false;

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Refresh profile picture from database if it exists in session but might have been updated
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $refreshSql = "SELECT profile_picture FROM users WHERE id = ?";
    $refreshStmt = mysqli_prepare($conn, $refreshSql);
    if ($refreshStmt) {
        mysqli_stmt_bind_param($refreshStmt, "i", $userId);
        mysqli_stmt_execute($refreshStmt);
        $refreshResult = mysqli_stmt_get_result($refreshStmt);
        
        if ($refreshRow = mysqli_fetch_assoc($refreshResult)) {
            $_SESSION['profile_picture'] = $refreshRow['profile_picture'];
        }
        mysqli_stmt_close($refreshStmt);
    }
}

 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture'] . '?v=' . time()
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Faculty';
 $email = $_SESSION['email'] ?? '';
 $facultyId = (int)($_SESSION['user_id'] ?? 0);

// If faculty ID is 0, try to get it from email (since we see email is in the users table)
if ($facultyId === 0 && isset($_SESSION['email'])) {
    $userSql = "SELECT id FROM users WHERE email = ?";
    $userStmt = mysqli_prepare($conn, $userSql);
    if ($userStmt) {
        mysqli_stmt_bind_param($userStmt, "s", $_SESSION['email']);
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);
        
        if ($userRow = mysqli_fetch_assoc($userResult)) {
            $facultyId = (int)$userRow['id'];
            $_SESSION['user_id'] = $facultyId;
        }
        mysqli_stmt_close($userStmt);
    }
}

// Debug information
if ($debug) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>DEBUG INFORMATION:</strong><br>";
    echo "Session User ID: " . $facultyId . "<br>";
    echo "Session Email: " . htmlspecialchars($_SESSION['email'] ?? 'NOT SET') . "<br>";
    echo "Session Full Name: " . htmlspecialchars($fullName) . "<br>";
    echo "</div>";
}

// Get all submissions for this faculty
 $facultySubmissions = [];
if ($facultyId > 0) {
    $submissionsSql = "SELECT * FROM ipcr_submissions WHERE faculty_id = ?";
    $submissionsStmt = mysqli_prepare($conn, $submissionsSql);
    if ($submissionsStmt) {
        mysqli_stmt_bind_param($submissionsStmt, "i", $facultyId);
        mysqli_stmt_execute($submissionsStmt);
        $submissionsResult = mysqli_stmt_get_result($submissionsStmt);
        
        while ($row = mysqli_fetch_assoc($submissionsResult)) {
            $facultySubmissions[] = $row;
        }
        mysqli_stmt_close($submissionsStmt);
    }
}

if ($debug) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>FACULTY SUBMISSIONS (ID: $facultyId):</strong><br>";
    if (count($facultySubmissions) > 0) {
        foreach ($facultySubmissions as $sub) {
            echo "ID: " . $sub['id'] . ", Status: " . $sub['status'] . ", Date: " . $sub['created_at'] . "<br>";
        }
    } else {
        echo "No submissions found for this faculty ID.<br>";
    }
    echo "</div>";
}

// Calculate dashboard statistics
 $totalSubmissions = count($facultySubmissions);
 $approvedSubmissions = 0;
 $pendingReviews = 0;
 $submittedSubmissions = 0;
 $draftSubmissions = 0;
 $finalRating = '0.00';

foreach ($facultySubmissions as $submission) {
    $status = $submission['status'] ?? '';
    
    if ($status === 'Approved') {
        $approvedSubmissions++;
    } elseif ($status === 'Pending') {
        $pendingReviews++;
    } elseif ($status === 'Submitted') {
        $submittedSubmissions++;
    } elseif ($status === 'Draft') {
        $draftSubmissions++;
    }
}

// Calculate average rating from approved submissions
 $approvedRatings = [];
foreach ($facultySubmissions as $submission) {
    if ($submission['status'] === 'Approved' && isset($submission['final_avg_rating']) && $submission['final_avg_rating'] > 0) {
        $approvedRatings[] = (float)$submission['final_avg_rating'];
    }
}

if (count($approvedRatings) > 0) {
    $finalRating = number_format(array_sum($approvedRatings) / count($approvedRatings), 2);
}

// Get data for performance chart
 $chartData = [
    'labels' => [],
    'data' => []
];

 $statusCounts = [];
foreach ($facultySubmissions as $submission) {
    $status = $submission['status'] ?? 'Unknown';
    if (!isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }
    $statusCounts[$status]++;
}

foreach ($statusCounts as $status => $count) {
    $chartData['labels'][] = $status;
    $chartData['data'][] = $count;
}

// If no data found, add placeholder data
if (empty($chartData['labels'])) {
    $chartData['labels'] = ['No Data'];
    $chartData['data'] = [1];
}

// Get announcements
 $announcements = [];
 $announcementSql = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
 $announcementResult = mysqli_query($conn, $announcementSql);
if ($announcementResult) {
    while ($row = mysqli_fetch_assoc($announcementResult)) {
        $announcements[] = $row;
    }
    mysqli_free_result($announcementResult);
}

// Get recent activities
 $recentActivities = [];
foreach ($facultySubmissions as $submission) {
    $status = $submission['status'] ?? '';
    $action = "Submitted PCR"; // Changed from "Submitted IPCR"
    
    if ($status === 'Approved') {
        $action = "PCR Approved"; // Changed from "IPCR Approved"
    } elseif ($status === 'Declined') {
        $action = "PCR Declined"; // Changed from "IPCR Declined"
    } elseif ($status === 'Pending') {
        $action = "PCR Under Review"; // Changed from "IPCR Under Review"
    }
    
    $recentActivities[] = [
        'action' => $action,
        'details' => "ID: " . $submission['id'],
        'created_at' => $submission['created_at']
    ];
}

// Sort activities by date
usort($recentActivities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get only the 5 most recent activities
 $recentActivities = array_slice($recentActivities, 0, 5);

// Get upcoming deadlines
 $upcomingDeadlines = [];
 $deadlinesSql = "SELECT title, deadline FROM announcements WHERE deadline >= CURDATE() ORDER BY deadline ASC LIMIT 3";
 $deadlinesResult = mysqli_query($conn, $deadlinesSql);
if ($deadlinesResult) {
    while ($row = mysqli_fetch_assoc($deadlinesResult)) {
        $upcomingDeadlines[] = $row;
    }
    mysqli_free_result($deadlinesResult);
}

// Close database connection
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<title>Faculty Dashboard - PCR</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="../images/pcc1.png">
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="../js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    --primary-color: #3a6ea5;
    --secondary-color: #6a9df7;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
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
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0);
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
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
    position: relative;
    z-index: 1;
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
    transition: transform 0.2s ease;
}
.notification-bell:hover {
    transform: scale(1.1);
}
.notification-bell .badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: var(--danger-color);
    color: white;
    border-radius: 55%;
    padding: 2px 6px;
    font-size: 0.7rem;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
.dark-toggle {
    cursor: pointer;
    font-size: 1.2rem;
    color: white;
    transition: transform 0.2s ease;
}
.dark-toggle:hover {
    transform: rotate(30deg);
}
.settings {
    position: relative;
    display: inline-block;
}
.settings-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 50px;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    min-width: 280px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    z-index: 1050;
    animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
[data-theme='dark'] .settings-menu {
    background-color: #2e2e2e;
    color: white;
}
.settings-header {
    background-color: var(--primary-color);
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
    transition: all 0.2s ease;
}
.settings-menu a:hover {
    background-color: #f7f7f7;
    padding-left: 25px;
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
    cursor: pointer;
    transition: transform 0.2s ease;
}
.profile-img-sm:hover {
    transform: scale(1.05);
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

/* Welcome Banner Styles */
.welcome-banner {
    position: fixed;
    top: 70px;
    left: 0;
    right: 0;
    background: linear-gradient(90deg, #4a6cf7, #6a9df7);
    color: white;
    padding: 15px;
    z-index: 998;
    display: flex;
    justify-content: center;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(0);
    transition: transform 0.5s ease-in-out;
    height: auto;
    min-height: 60px;
}

.welcome-banner.hide {
    transform: translateY(-100%);
}

.welcome-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    max-width: 80%;
}

.welcome-message {
    font-size: 1rem;
    font-weight: 100;
    margin-bottom: 4px;
}

.welcome-date {
    font-size: 0.9rem;
    opacity: 0.9;
}

.banner-close {
    position: absolute;
    right: 15px;
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease;
}

.banner-close:hover {
    background: rgba(255,255,255,0.3);
}

/* Adjust main content when banner is visible */
.main-content.banner-visible {
    margin-top: 130px;
}

/* Page-specific styles */
.stats-card {
    border-radius: 16px;
    padding: 15px;
    color: white;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    height: 100%;
    position: relative;
    overflow: hidden;
    cursor: pointer;
}
.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
}
.stats-card.primary::before {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
}
.stats-card.success::before {
    background: linear-gradient(135deg, #28a745, #5cb85c);
}
.stats-card.warning::before {
    background: linear-gradient(135deg, #ffc107, #ffdb6d);
}
.stats-card.danger::before {
    background: linear-gradient(135deg, #dc3545, #f86c6b);
}
.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
}
.stats-card i {
    background-color: rgba(255, 255, 255, 0.2);
    padding: 10px;
    border-radius: 10px;
    font-size: 1.5rem;
}
.stats-card h3 {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
}
.stats-card small {
    font-size: 0.85rem;
    opacity: 0.9;
}

.card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    background-color: var(--card-light);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
}
[data-theme='dark'] .card {
    background-color: var(--card-dark);
}

.list-group-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
    border: none;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    background-color: var(--card-light);
    transition: all 0.2s ease;
}
.list-group-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}
[data-theme='dark'] .list-group-item {
    background-color: var(--card-dark);
}

/* Chart container styling */
.chart-container {
    position: relative;
    height: 250px;
    width: 100%;
}

/* Activity feed styling */
.activity-item {
    padding: 10px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.activity-item:last-child {
    border-bottom: none;
}
.activity-time {
    font-size: 0.75rem;
    color: #6c757d;
}

/* Deadline styling */
.deadline-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.deadline-item:last-child {
    border-bottom: none;
}
.deadline-date {
    font-weight: bold;
    color: var(--primary-color);
}

/* Debug styles */
.debug-info {
    background-color: #f8f9fa;
    border-left: 4px solid #3a6ea5;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}
.debug-info h5 {
    margin-top: 0;
    color: #3a6ea5;
}
.debug-info pre {
    background-color: #f1f1f1;
    padding: 10px;
    border-radius: 4px;
    overflow-x: auto;
    font-size: 12px;
}

/* No submissions message */
.no-submissions {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
}
.no-submissions h4 {
    color: #856404;
    margin-bottom: 10px;
}
.no-submissions p {
    color: #856404;
    margin-bottom: 15px;
}
.no-submissions .btn {
    background-color: #3a6ea5;
    border-color: #3a6ea5;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .stats-card h3 {
        font-size: 1.3rem;
    }
    .stats-card small {
        font-size: 0.8rem;
    }
    .chart-container {
        height: 200px;
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
    .stats-card h3 {
        font-size: 1.2rem;
    }
    .stats-card i {
        padding: 8px;
        font-size: 1.2rem;
    }
    .chart-container {
        height: 180px;
    }
    .welcome-message {
        font-size: 0.9rem;
    }
    .welcome-date {
        font-size: 0.8rem;
    }
    .main-content.banner-visible {
        margin-top: 120px;
    }
}

@media (max-width: 576px) {
    .stats-card {
        padding: 12px;
    }
    .stats-card h3 {
        font-size: 1.1rem;
    }
    .stats-card small {
        font-size: 0.75rem;
    }
    .card {
        padding: 15px;
    }
    .list-group-item {
        padding: 10px;
    }
    .chart-container {
        height: 150px;
    }
    .welcome-content {
        max-width: 90%;
    }
    .main-content.banner-visible {
        margin-top: 110px;
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

<!-- Welcome Banner -->
<div class="welcome-banner" id="welcomeBanner">
    <div class="welcome-content">
        <div class="welcome-message">Welcome, <?= htmlspecialchars($fullName) ?>! Manage and complete your tasks with greater ease through this system</div>
        <div class="welcome-date">It's <?= date('F j, Y') ?></div>
    </div>
    <button class="banner-close" id="bannerClose">
        <i class="fas fa-times"></i>
    </button>
</div>

<div class="sidebar" id="sidebar">
    <a href="faculty_dashboard.php" class="active"><i class="fas fa-home me-2"></i> Home</a>
    <a href="submit_ipcr.php"><i class="fas fa-edit me-2"></i> Submit PCR</a>
    <a href="my_submissions.php"><i class="fas fa-folder-open me-2"></i> My Submissions</a>
    <a href="generatePDF.php" id="pdfLink"><i class="fas fa-download me-2"></i> Generate PDF</a>
    <a href="view_announcements.php"><i class="fas fa-bullhorn me-2"></i> View Announcements</a>
    <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
    <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
</div>
</div>
<div class="main-content banner-visible" id="mainContent">
    <!-- Debug Info Section -->
    <?php if ($debug): ?>
    <div class="debug-info">
        <h5><i class="fas fa-bug me-2"></i> Debug Information</h5>
        <p><strong>Faculty ID:</strong> <?= $facultyId ?></p>
        <p><strong>Total Submissions:</strong> <?= $totalSubmissions ?></p>
        <p><strong>Pending Reviews:</strong> <?= $pendingReviews ?></p>
        <p><strong>Approved Submissions:</strong> <?= $approvedSubmissions ?></p>
        <p><strong>Final Rating:</strong> <?= $finalRating ?></p>
    </div>
    <?php endif; ?>

    <!-- No submissions message -->
    <?php if ($totalSubmissions === 0): ?>
    <div class="no-submissions">
        <h4><i class="fas fa-info-circle me-2"></i> No Submissions Found</h4>
        <p>You haven't submitted any PCR forms yet. Click the button below to submit your first PCR.</p>
        <a href="submit_ipcr.php" class="btn btn-primary">Submit PCR</a>
    </div>
    <?php endif; ?>

    <!-- Welcome Message -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Welcome, <?= htmlspecialchars($fullName) ?></h2>
            <p class="text-muted">Manage and complete your tasks with greater ease through this system.</p>
        </div>
        <div>
            <span class="badge bg-primary p-2">
                <i class="fas fa-calendar-day me-1"></i> <?= date('F j, Y') ?>
            </span>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
            <div class="stats-card primary text-white rounded shadow h-100" onclick="window.location.href='My_Total_Submissions.php'">
                <div class="d-flex align-items-center">
                    <i class="fas fa-file-alt fa-2x me-3"></i>
                    <div>
                        <h3 class="mb-0"><?= $totalSubmissions ?></h3>
                        <small>Total Submissions</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card success text-white rounded shadow h-100" onclick="window.location.href='My_Approved_IPCRs.php'">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x me-3"></i>
                    <div>
                        <h3 class="mb-0"><?= $approvedSubmissions ?></h3>
                        <small>Approved PCRs</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card warning text-white rounded shadow h-100" onclick="window.location.href='My_Pending_Reviews.php'">
                <div class="d-flex align-items-center">
                    <i class="fas fa-hourglass-half fa-2x me-3"></i>
                    <div>
                        <h3 class="mb-0"><?= $pendingReviews ?></h3>
                        <small>Pending Reviews</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card danger text-white rounded shadow h-100" onclick="window.location.href='#'">
                <div class="d-flex align-items-center">
                    <i class="fas fa-star fa-2x me-3"></i>
                    <div>
                        <h3 class="mb-0"><?= $finalRating ?></h3>
                        <small>Final Rating</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart and Activities Row -->
    <div class="row g-4 mb-4">
        <!-- Chart -->
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <h5 class="mb-3">Performance Chart</h5>
                <div class="chart-container">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-lg-4">
            <div class="card p-4 h-100">
                <h5 class="mb-3">Recent Activities</h5>
                <?php if (count($recentActivities) > 0): ?>
                    <div class="activity-feed">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between">
                                    <div><?= htmlspecialchars($activity['action']) ?></div>
                                    <div class="activity-time"><?= date("M j, g:i A", strtotime($activity['created_at'])) ?></div>
                                </div>
                                <small class="text-muted"><?= htmlspecialchars($activity['details']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent activities.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Announcements and Deadlines Row -->
    <div class="row g-4">
        <!-- Announcement Section -->
        <div class="col-lg-8">
            <div class="card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Latest Announcements</h5>
                    <a href="view_announcements.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <?php if (count($announcements) > 0): ?>
                    <ul class="list-group">
                        <?php foreach ($announcements as $announcement): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                <small><?= date("F j, Y", strtotime($announcement['created_at'])) ?> | Deadline: <?= date("F j", strtotime($announcement['deadline'])) ?></small>
                                <span><?= mb_strimwidth(strip_tags($announcement['content']), 0, 100, "...") ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No announcements found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Deadlines -->
        <div class="col-lg-4">
            <div class="card p-4">
                <h5 class="mb-3">Upcoming Deadlines</h5>
                <?php if (count($upcomingDeadlines) > 0): ?>
                    <div class="deadlines">
                        <?php foreach ($upcomingDeadlines as $deadline): ?>
                            <div class="deadline-item">
                                <div><?= htmlspecialchars($deadline['title']) ?></div>
                                <div class="deadline-date"><?= date("M j", strtotime($deadline['deadline'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No upcoming deadlines.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
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
if (savedTheme) {
    htmlTag.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function updateThemeIcon(theme) {
    const icon = toggleTheme.querySelector('i');
    if (theme === 'dark') {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    } else {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
    }
}

// Welcome Banner functionality
const welcomeBanner = document.getElementById('welcomeBanner');
const bannerClose = document.getElementById('bannerClose');
const mainContent = document.getElementById('mainContent');

// Set timeout to hide the banner after 5 seconds (5000 milliseconds)
const bannerTimeout = setTimeout(function() {
    hideBanner();
}, 5000);

// Store timeout ID so we can clear it if user closes manually
welcomeBanner.setAttribute('data-timeout-id', bannerTimeout);

// Function to hide the banner
function hideBanner() {
    welcomeBanner.classList.add('hide');
    mainContent.classList.remove('banner-visible');

    // Clear the timeout if it exists
    const timeoutId = welcomeBanner.getAttribute('data-timeout-id');
    if (timeoutId) {
        clearTimeout(parseInt(timeoutId));
    }
}

// Close banner when close button is clicked
bannerClose.addEventListener('click', hideBanner);

// Performance Chart
const ctx = document.getElementById("performanceChart").getContext("2d");

// Get the chart data
const chartLabels = <?= json_encode($chartData['labels']) ?>;
const chartDataValues = <?= json_encode($chartData['data']) ?>;

// Define status colors for light and dark themes
const statusColors = {
    light: {
        'Approved': '#28a745',    // Green
        'Pending': '#ffc107',     // Yellow
        'Declined': '#dc3545',    // Red
        'Submitted': '#17a2b8',   // Blue
        'Draft': '#6f42c1',       // Purple
        'Unknown': '#6c757d'      // Gray
    },
    dark: {
        'Approved': '#8de48a',    // Light green
        'Pending': '#ffe177',     // Light yellow
        'Declined': '#f86c6b',    // Light red
        'Submitted': '#7ec8e3',   // Light blue
        'Draft': '#b19cd9',       // Light purple
        'Unknown': '#adb5bd'      // Light gray
    }
};

// Create a function to get colors based on labels
function getColorsForLabels(labels, theme) {
    const colors = [];
    const borderColors = [];
    
    for (const label of labels) {
        // Use the color for this specific status, defaulting to 'Unknown' if not found
        const color = statusColors[theme][label] || statusColors[theme]['Unknown'];
        colors.push(color);
        borderColors.push(color + '80'); // Add transparency for border
    }
    
    return { colors, borderColors };
}

// Create a function to update chart colors based on theme
function updateChartColors() {
    const currentTheme = htmlTag.getAttribute('data-theme') || 'light';
    const textColor = currentTheme === 'dark' ? '#dcdcdc' : '#333';
    const { colors, borderColors } = getColorsForLabels(chartLabels, currentTheme);

    // Update chart data colors
    performanceChart.data.datasets[0].backgroundColor = colors;
    performanceChart.data.datasets[0].borderColor = borderColors;

    // Update chart options
    performanceChart.options.plugins.legend.labels.color = textColor;

    // Update the chart with animation disabled to prevent jumping
    performanceChart.update('none');
}

// Get initial colors based on current theme
const initialTheme = htmlTag.getAttribute('data-theme') || 'light';
const { colors: initialColors, borderColors: initialBorderColors } = getColorsForLabels(chartLabels, initialTheme);

// Create the chart with real data from PHP
const performanceChart = new Chart(ctx, {
    type: "doughnut",
    data: {
        labels: chartLabels,
        datasets: [{
            data: chartDataValues,
            backgroundColor: initialColors,
            borderColor: initialBorderColors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 0 // Disable animations to prevent jumping
        },
        scales: {
            y: {
                display: false
            },
            x: {
                display: false
            }
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: initialTheme === 'dark' ? '#dcdcdc' : '#333',
                    font: {
                        size: 11
                    },
                    padding: 20
                }
            }
        }
    }
});

// Initialize chart colors based on saved theme
updateChartColors();

toggleTheme.addEventListener('click', () => {
    const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    htmlTag.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    updateThemeIcon(next);

    // Update chart colors when theme changes
    updateChartColors();
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