<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

$profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
$fullName = $_SESSION['full_name'] ?? 'Faculty';
$email = $_SESSION['email'] ?? '';
$contactNumber = $_SESSION['contact_number'] ?? '';
$department = $_SESSION['department'] ?? '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate inputs
    if (empty($_POST['fullName'])) {
        $errors[] = "Full name is required";
    }
    
    if (empty($_POST['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Handle profile picture upload
    $profilePicturePath = $_SESSION['profile_picture'];
    if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profilePicture']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = '../uploads/';
            $fileName = time() . '_' . basename($_FILES['profilePicture']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profilePicture']['tmp_name'], $targetPath)) {
                $profilePicturePath = $fileName;
                
                // Delete old profile picture if it's not the default
                if ($_SESSION['profile_picture'] !== '1.png' && file_exists('../uploads/' . $_SESSION['profile_picture'])) {
                    unlink('../uploads/' . $_SESSION['profile_picture']);
                }
            } else {
                $errors[] = "Error uploading profile picture";
            }
        } else {
            $errors[] = "Only JPG, JPEG, PNG, and GIF images are allowed";
        }
    }
    
    // If no errors, update the database
    if (empty($errors)) {
        $userId = (int)$_SESSION['user_id'];
        $fullName = mysqli_real_escape_string($conn, $_POST['fullName']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $contactNumber = mysqli_real_escape_string($conn, $_POST['contactNumber']);
        $department = mysqli_real_escape_string($conn, $_POST['department']);
        
        $updateQuery = "UPDATE users SET 
                        full_name = '$fullName', 
                        email = '$email', 
                        contact_number = '$contactNumber', 
                        department = '$department',
                        profile_picture = '$profilePicturePath'
                        WHERE id = $userId";
        
        if (mysqli_query($conn, $updateQuery)) {
            // Update session variables
            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;
            $_SESSION['contact_number'] = $contactNumber;
            $_SESSION['department'] = $department;
            $_SESSION['profile_picture'] = $profilePicturePath;
            
            $successMessage = "Profile updated successfully!";
            // Refresh profile picture path
            $profilePicture = '../uploads/' . $profilePicturePath;
        } else {
            $errors[] = "Error updating profile: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>My Account - IPCR</title>
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
            max-width: 900px;
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
            width: 120px;
            height: 120px;
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
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 110, 165, 0.25);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
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
        
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--text-light);
        }
        
        [data-theme='dark'] .form-label {
            color: var(--text-dark);
        }
        
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }
        
        [data-theme='dark'] .form-control {
            background-color: var(--card-dark);
            border-color: #444;
            color: var(--text-dark);
        }
        
        .profile-upload {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-upload img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            margin-bottom: 10px;
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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
            <img src="../images/pcc1.png" alt="PCC Logo" class="pcc-logo">
            <div class="ms-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-white">Dashboard</a></li>
                        <li class="breadcrumb-item active text-white">My Account</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="topbar-right">
            <span class="dark-toggle" id="toggleTheme" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </span>
            <img src="<?= $profilePicture ?>" class="profile-img-sm" alt="Profile">
        </div>
    </div>

    <div class="main-content">
        <div class="profile-header">
            <img src="<?= $profilePicture ?>" class="profile-img" alt="Profile">
            <div class="profile-info">
                <h2><?= htmlspecialchars($fullName) ?></h2>
                <p><?= htmlspecialchars($department) ?> • <?= htmlspecialchars($email) ?></p>
            </div>
        </div>

        <div class="card p-4">
            <h4 class="mb-4">Personal Information</h4>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data">
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i> Basic Information
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" name="fullName" value="<?= htmlspecialchars($fullName) ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" class="form-control" id="department" name="department" value="<?= htmlspecialchars($department) ?>">
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-phone"></i> Contact Information
                    </div>
                    
                    <div class="mb-3">
                        <label for="contactNumber" class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" id="contactNumber" name="contactNumber" value="<?= htmlspecialchars($contactNumber) ?>">
                    </div>
                </div>
                
                <div class="info-section">
                    <div class="section-title">
                        <i class="fas fa-camera"></i> Profile Picture
                    </div>
                    
                    <div class="profile-upload">
                        <img src="<?= $profilePicture ?>" id="previewProfile" alt="Profile">
                        <div>
                            <label for="profilePicture" class="btn btn-sm btn-outline-primary">Change Photo</label>
                            <input type="file" class="d-none" id="profilePicture" name="profilePicture" accept="image/*">
                        </div>
                        <small class="text-muted">JPG, PNG or GIF. Max size 2MB.</small>
                    </div>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
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

        // Profile picture preview
        const profilePictureInput = document.getElementById('profilePicture');
        const previewProfile = document.getElementById('previewProfile');

        if (profilePictureInput) {
            profilePictureInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewProfile.src = e.target.result;
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    </script>
</body>
</html>