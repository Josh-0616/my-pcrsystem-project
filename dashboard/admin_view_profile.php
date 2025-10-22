<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Database connection
require_once '../db/connection.php';

// Debug function to log information
function debug_log($message) {
    file_put_contents('debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Get admin data from database
 $username = $_SESSION['username'];

// Check if connection is successful
if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection error. Please try again later.");
}

// Check if the users table exists
 $tableCheck = "SHOW TABLES LIKE 'users'";
 $tableResult = mysqli_query($conn, $tableCheck);
if (!$tableResult || mysqli_num_rows($tableResult) == 0) {
    error_log("The 'users' table does not exist in the database.");
    die("System configuration error. Please contact administrator.");
}

// Get all columns in the users table
 $columnsQuery = "SHOW COLUMNS FROM users";
 $columnsResult = mysqli_query($conn, $columnsQuery);

if (!$columnsResult) {
    error_log("Error retrieving table columns: " . mysqli_error($conn));
    die("System error. Please try again later.");
}

 $existingColumns = [];
while ($column = mysqli_fetch_assoc($columnsResult)) {
    $existingColumns[] = $column['Field'];
}

// Log the columns we found for debugging
debug_log("Columns found in users table: " . implode(', ', $existingColumns));

// Determine the correct column name for username
 $usernameColumn = null;
 $alternatives = ['username', 'user_name', 'user', 'login', 'user_id', 'admin_name', 'name'];

foreach ($alternatives as $alt) {
    if (in_array($alt, $existingColumns)) {
        $usernameColumn = $alt;
        break;
    }
}

if ($usernameColumn === null) {
    error_log("No valid username column found. Available columns: " . implode(', ', $existingColumns));
    die("System configuration error. Please contact administrator.");
}

// Log the detected username column
debug_log("Using username column: " . $usernameColumn);

// Now build the query with the correct column name using prepared statement
 $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE $usernameColumn = ? AND role = 'admin' LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
 $result = mysqli_stmt_get_result($stmt);

if (!$result) {
    error_log("Query failed: " . mysqli_error($conn));
    die("System error. Please try again later.");
}

if (mysqli_num_rows($result) === 0) {
    // If admin not found, redirect to login
    header("Location: ../login.php");
    exit();
}

 $admin = mysqli_fetch_assoc($result);

// Get profile picture with proper default handling
 $profilePicture = '../uploads/1.png'; // Default profile picture
if (!empty($admin['profile_picture'])) {
    $profilePicture = '../uploads/' . htmlspecialchars($admin['profile_picture']);
}

// Format full name if we have separate name fields
 $fullName = '';
if (isset($admin['last_name']) || isset($admin['first_name'])) {
    $fullName = trim(($admin['last_name'] ?? '') . ' ' . ($admin['first_name'] ?? '') . ' ' . ($admin['middle_name'] ?? ''));
    $fullName = preg_replace('/\s+/', ' ', $fullName); // Remove extra spaces
} elseif (isset($admin['full_name'])) {
    $fullName = $admin['full_name'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>View Profile - PCR Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        [data-theme='dark'] .topbar {
            background-color: var(--sidebar-bg-dark);
        }
        
        .main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin-right: auto;
            margin-left: auto;
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
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 110, 165, 0.25);
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
        }
        
        .info-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        [data-theme='dark'] .info-section {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .info-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-light);
        }
        
        [data-theme='dark'] .form-label {
            color: var(--text-dark);
        }
        
        .form-control-plaintext {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            background-color: rgba(0,0,0,0.03);
        }
        
        [data-theme='dark'] .form-control-plaintext {
            background-color: rgba(255,255,255,0.05);
            border-color: #444;
            color: var(--text-dark);
        }
        
        .profile-img-container {
            position: relative;
            display: inline-block;
            margin-right: 15px;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        
        .profile-img:hover {
            transform: scale(1.05);
        }
        
        /* Modal styles for full-size image view */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            position: relative;
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 12px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: slideIn 0.4s;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        
        .modal-body {
            text-align: center;
        }
        
        .modal-body img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            object-fit: contain;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        @keyframes fadeIn {
            from {opacity: 0}
            to {opacity: 1}
        }
        
        @keyframes slideIn {
            from {transform: translateY(-50px); opacity: 0}
            to {transform: translateY(0); opacity: 1}
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .topbar {
                padding: 0 10px;
            }
            
            .modal-content {
                width: 95%;
                margin: 15% auto;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div style="display: flex; align-items: center;">
            <div class="ms-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-white">Dashboard</a></li>
                        <li class="breadcrumb-item active text-white">View Profile</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="card p-4">
            <h4 class="mb-4">My Profile</h4>
            
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-camera"></i> Profile Picture
                </div>
                
                <div class="text-center">
                    <div class="profile-img-container d-inline-block">
                        <img src="<?= $profilePicture ?>" id="previewProfile" alt="Profile" class="profile-img">
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Click on the image to view full size</small>
                    </div>
                </div>
            </div>
            
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-user"></i> Name Information
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Last Name</label>
                        <div class="form-control-plaintext"><?= htmlspecialchars($admin['last_name'] ?? 'Not specified') ?></div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">First Name</label>
                        <div class="form-control-plaintext"><?= htmlspecialchars($admin['first_name'] ?? 'Not specified') ?></div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Middle Name</label>
                        <div class="form-control-plaintext"><?= htmlspecialchars($admin['middle_name'] ?? 'Not specified') ?></div>
                    </div>
                </div>
                
                <?php if (!empty($fullName)): ?>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <div class="form-control-plaintext"><?= htmlspecialchars($fullName) ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-envelope"></i> Contact Information
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="form-control-plaintext"><?= htmlspecialchars($admin['email'] ?? 'Not specified') ?></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <div class="form-control-plaintext"><?= htmlspecialchars($admin['contact_number'] ?? 'Not specified') ?></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="form-control-plaintext"><?= htmlspecialchars($admin[$usernameColumn]) ?></div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <a href="my_account.php" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Profile
                </a>
            </div>
        </div>

    <!-- Modal for full-size image view -->
    <div id="imageModal" class="image-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Profile Picture</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <img id="modalProfileImage" src="" alt="Profile Picture">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="closeModalBtn">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize theme from localStorage
        const htmlTag = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            htmlTag.setAttribute('data-theme', savedTheme);
        }

        // Profile picture click to view full size
        const previewProfile = document.getElementById('previewProfile');
        const imageModal = document.getElementById('imageModal');
        const modalProfileImage = document.getElementById('modalProfileImage');
        const closeModal = document.querySelector('.close-modal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        // Add click event to profile image for viewing full size
        if (previewProfile) {
            previewProfile.addEventListener('click', function() {
                modalProfileImage.src = this.src;
                imageModal.style.display = 'block';
            });
        }

        // Close modal when clicking the close button
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                imageModal.style.display = 'none';
            });
        }

        // Close modal when clicking the close button in footer
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                imageModal.style.display = 'none';
            });
        }

        // Close modal when clicking outside the modal content
        if (imageModal) {
            imageModal.addEventListener('click', function(e) {
                if (e.target === imageModal) {
                    imageModal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>