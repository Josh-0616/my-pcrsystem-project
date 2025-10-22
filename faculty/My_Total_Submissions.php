<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

include '../db/connection.php';

// Get faculty ID from session
 $facultyId = (int)($_SESSION['user_id'] ?? 0);

// Check if is_deleted column exists
 $result = $conn->query("SHOW COLUMNS FROM ipcr_submissions LIKE 'is_deleted'");
 $columnExists = $result->num_rows > 0;

// Get all submissions for this faculty member
if ($columnExists) {
    $stmt = $conn->prepare("SELECT * FROM ipcr_submissions WHERE faculty_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM ipcr_submissions WHERE faculty_id = ? ORDER BY created_at DESC");
}
 $stmt->bind_param("i", $facultyId);
 $stmt->execute();
 $result = $stmt->get_result();
 $submissions = [];
while ($row = $result->fetch_assoc()) {
    $submissions[] = $row;
}

// Handle profile data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Faculty';
 $email = $_SESSION['email'] ?? '';

// Display message if set
 $message = $_SESSION['message'] ?? '';
 $messageType = $_SESSION['message_type'] ?? 'info';
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>My Total Submissions - IPCR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
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
        .card {
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background-color: var(--card-light);
        }
        [data-theme='dark'] .card {
            background-color: var(--card-dark);
        }
        .table {
            color: var(--text-light);
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .badge {
            font-size: 0.75rem;
            padding: 0.4em 0.6em;
        }
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-right: 0.25rem;
        }
        
        /* Custom confirmation modal */
        .confirm-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }
        .confirm-modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        [data-theme='dark'] .confirm-modal-content {
            background-color: #2e2e2e;
            color: white;
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
            .table-responsive {
                font-size: 0.875rem;
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
                <a href="Help_Support.php"><i class="fas fa-question-circle me-2"></i> Help & Support</a>
                <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
            </div>
        </div>
    </div>
</div>
<div class="sidebar" id="sidebar">
    <a href="faculty_dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
    <a href="submit_ipcr.php"><i class="fas fa-edit me-2"></i> Submit PCR</a>
    <a href="my_submissions.php"class="active"><i class="fas fa-folder-open me-2"></i> My Submissions</a>
    <a href="generatePDF.php"><i class="fas fa-download me-2"></i> Generate PDF</a>
    <a href="view_announcements.php"><i class="fas fa-bullhorn me-2"></i> View Announcements</a>
    <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
    <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
    </div>
</div>
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Total Submissions</h2>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!$columnExists): ?>
    
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">All My PCR Submissions</h5>
                </div>
                <div class="col-auto">
                    <span class="badge bg-primary"><?= count($submissions) ?> Total Submissions</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (count($submissions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Department</th>
                                <th>Period</th>
                                <th>Final Rating</th>
                                <th>Status</th>
                                <th>Date Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?= $submission['id'] ?></td>
                                    <td><?= htmlspecialchars($submission['department'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($submission['period'] ?? 'N/A') ?></td>
                                    <td><?= $submission['final_avg_rating'] ?? 'N/A' ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $submission['status'] === 'Approved' ? 'success' : 
                                            ($submission['status'] === 'Declined' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= htmlspecialchars($submission['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($submission['created_at'])) ?></td>
                                    <td>
                                        <a href="view_submission.php?id=<?= $submission['id'] ?>" class="btn btn-sm btn-outline-primary btn-action">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($columnExists): ?>
                                        <a href="move_to_recycle_bin.php?id=<?= $submission['id'] ?>" class="btn btn-sm btn-outline-warning btn-action" onclick="return confirm('Are you sure you want to move this submission to the recycle bin?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-action" onclick="showDeleteConfirm(<?= $submission['id'] ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No submissions found</h5>
                    <p class="text-muted">You haven't submitted any PCR forms yet.</p>
                    <a href="Submit_IPCR.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Create Your First Submission
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Custom Confirmation Modal -->
<div id="deleteConfirmModal" class="confirm-modal">
    <div class="confirm-modal-content">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-exclamation-triangle text-danger fa-2x me-3"></i>
            <h5 class="mb-0">Confirm Deletion</h5>
        </div>
        <p>Are you sure you want to permanently delete this submission? This action cannot be undone.</p>
        <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-secondary me-2" onclick="hideDeleteConfirm()">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
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
    
    // Delete confirmation modal
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    let submissionToDelete = null;
    
    function showDeleteConfirm(submissionId) {
        submissionToDelete = submissionId;
        deleteConfirmModal.style.display = 'flex';
    }
    
    function hideDeleteConfirm() {
        deleteConfirmModal.style.display = 'none';
        submissionToDelete = null;
    }
    
    confirmDeleteBtn.addEventListener('click', function() {
        if (submissionToDelete) {
            window.location.href = 'delete_submission.php?id=' + submissionToDelete;
        }
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === deleteConfirmModal) {
            hideDeleteConfirm();
        }
    });
</script>
</body>
</html>