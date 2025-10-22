<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

// Get faculty ID from URL parameter
 $facultyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($facultyId <= 0) {
    header("Location: manage_users.php");
    exit();
}

// Fetch faculty data from database
 $query = "SELECT * FROM users WHERE id = $facultyId AND role = 'faculty' LIMIT 1";
 $result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: manage_users.php");
    exit();
}

 $faculty = mysqli_fetch_assoc($result);

// Parse contract extension if it exists
 $extendAmount = '';
 $extendPeriod = '';
 $contractExtend = $faculty['contract_extend'] ?? '';
if (!empty($contractExtend)) {
    $parts = explode(' ', $contractExtend, 2);
    if (count($parts) === 2) {
        $extendAmount = $parts[0];
        $extendPeriod = $parts[1];
    }
}

// Get profile picture with proper default handling
 $profilePicture = '../uploads/1.png'; // Default profile picture
if (!empty($faculty['profile_picture'])) {
    $profilePicture = '../uploads/' . $faculty['profile_picture'];
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Faculty Profile - IPCR Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
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
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
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
            max-width: 1200px;
            margin-right: auto;
            margin-left: auto;
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
        
        .pcc-logo {
            width: 40px;
            height: auto;
            border-radius: 10px;
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
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 0;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            border-radius: 16px;
            background-color: var(--card-light);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }
        
        [data-theme='dark'] .profile-header {
            background-color: var(--card-dark);
        }
        
        .profile-info {
            margin-left: 20px;
        }
        
        .profile-info h2 {
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .profile-info p {
            margin-bottom: 0;
            color: #6c757d;
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
        
        .info-label {
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        .document-preview {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            background-color: rgba(58, 110, 165, 0.1);
        }
        
        .contract-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .contract-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .contract-expired {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .contract-expiring-soon {
            background-color: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
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
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-info {
                margin-left: 0;
                margin-top: 15px;
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
                        <li class="breadcrumb-item active text-white">Faculty Information</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="card p-4">
            <div class="profile-header">
                <img src="<?= $profilePicture ?>" alt="Profile" class="profile-img">
                <div class="profile-info">
                    <h2><?= htmlspecialchars($faculty['full_name'] ?? $faculty['first_name'] . ' ' . $faculty['last_name']) ?></h2>
                    <p><?= htmlspecialchars($faculty['department'] ?? 'Not specified') ?> Department</p>
                    <p><?= htmlspecialchars($faculty['email']) ?></p>
                    <?php 
                    // Determine contract status
                    $contractStatus = '';
                    $contractEnd = $faculty['contract_end'] ?? '';
                    
                    if (!empty($contractEnd)) {
                        $today = new DateTime();
                        $endDate = new DateTime($contractEnd);
                        $interval = $today->diff($endDate);
                        $daysLeft = $interval->days;
                        
                        if ($today > $endDate) {
                            $contractStatus = '<span class="contract-status contract-expired">Expired</span>';
                        } elseif ($daysLeft <= 30) {
                            $contractStatus = '<span class="contract-status contract-expiring-soon">Expiring Soon</span>';
                        } else {
                            $contractStatus = '<span class="contract-status contract-active">Active</span>';
                        }
                    } else {
                        $contractStatus = '<span class="contract-status">Not Specified</span>';
                    }
                    
                    echo $contractStatus;
                    ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card p-3 mb-4">
                        <div class="section-title">
                            <i class="fas fa-user"></i> Personal Information
                        </div>
                        
                        <div class="info-section">    
                            <div class="info-label">Last Name</div>
                            <div class="info-value"><?= htmlspecialchars($faculty['last_name'] ?? 'Not specified') ?></div>

                            <div class="info-label">First Name</div>
                            <div class="info-value"><?= htmlspecialchars($faculty['first_name'] ?? 'Not specified') ?></div>
                            
                            <div class="info-label">Middle Name</div>
                            <div class="info-value"><?= htmlspecialchars($faculty['middle_name'] ?? 'Not specified') ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card p-3 mb-4">
                        <div class="section-title">
                            <i class="fas fa-phone"></i> Contact Information
                        </div>
                        
                        <div class="info-section">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= htmlspecialchars($faculty['email'] ?? 'Not specified') ?></div>
                            
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?= htmlspecialchars($faculty['contact_number'] ?? 'Not specified') ?></div>
                            
                            <div class="info-label">Department</div>
                            <div class="info-value"><?= htmlspecialchars($faculty['department'] ?? 'Not specified') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card p-3 mb-4">
                <div class="section-title">
                    <i class="fas fa-file-contract"></i> Contract Information
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-section">
                            <div class="info-label">Start of Contract</div>
                            <div class="info-value"><?= !empty($faculty['contract_start']) ? date('F d, Y', strtotime($faculty['contract_start'])) : 'Not specified' ?></div>
                            
                            <div class="info-label">End of Contract</div>
                            <div class="info-value"><?= !empty($faculty['contract_end']) ? date('F d, Y', strtotime($faculty['contract_end'])) : 'Not specified' ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="info-section">
                            <div class="info-label">Contract Extension</div>
                            <div class="info-value"><?= !empty($faculty['contract_extend']) ? htmlspecialchars($faculty['contract_extend']) : 'No extension' ?></div>
                            
                            <div class="info-label">Units</div>
                            <div class="info-value"><?= !empty($faculty['units']) ? htmlspecialchars($faculty['units']) : 'Not specified' ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card p-3">
                <div class="section-title">
                    <i class="fas fa-file-upload"></i> Documents
                </div>
                
                <div class="info-section">
                    <?php if (!empty($faculty['document_path'])): ?>
                        <div class="document-preview">
                            <p class="mb-2">Faculty Document:</p>
                            <a href="../uploads/<?= htmlspecialchars($faculty['document_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-alt me-1"></i> View Document
                            </a>
                        </div>
                    <?php else: ?>
                        <p>No documents uploaded.</p>
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

        toggleTheme.addEventListener('click', () => {
            const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            htmlTag.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    </script>
</body>
</html>