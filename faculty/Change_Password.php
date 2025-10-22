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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate inputs
    if (empty($_POST['currentPassword'])) {
        $errors[] = "Current password is required";
    }
    
    if (empty($_POST['newPassword'])) {
        $errors[] = "New password is required";
    } elseif (strlen($_POST['newPassword']) < 8) {
        $errors[] = "New password must be at least 8 characters long";
    }
    
    if (empty($_POST['confirmPassword'])) {
        $errors[] = "Please confirm your new password";
    } elseif ($_POST['newPassword'] !== $_POST['confirmPassword']) {
        $errors[] = "New password and confirmation do not match";
    }
    
    // If no errors, verify current password and update
    if (empty($errors)) {
        $userId = (int)$_SESSION['user_id'];
        $currentPassword = $_POST['currentPassword'];
        $newPassword = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);
        
        // Get current password hash from database
        $query = "SELECT password FROM users WHERE id = $userId";
        $result = mysqli_query($conn, $query);
        
        if ($result && $row = mysqli_fetch_assoc($result)) {
            // Verify current password
            if (password_verify($currentPassword, $row['password'])) {
                // Update password
                $updateQuery = "UPDATE users SET password = '$newPassword' WHERE id = $userId";
                
                if (mysqli_query($conn, $updateQuery)) {
                    $successMessage = "Password changed successfully!";
                } else {
                    $errors[] = "Error updating password: " . mysqli_error($conn);
                }
            } else {
                $errors[] = "Current password is incorrect";
            }
        } else {
            $errors[] = "Error retrieving user data";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Change Password - IPCR</title>
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
            max-width: 800px;
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
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak {
            background-color: var(--danger-color);
            width: 33%;
        }
        
        .strength-medium {
            background-color: var(--warning-color);
            width: 66%;
        }
        
        .strength-strong {
            background-color: var(--success-color);
            width: 100%;
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
        
        .password-requirements {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .requirement {
            margin-bottom: 3px;
        }
        
        .requirement.met {
            color: var(--success-color);
        }
        
        .requirement.met i {
            color: var(--success-color);
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
                        <li class="breadcrumb-item active text-white">Change Password</li>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Change Password</h2>
            <span class="badge bg-primary p-2">
                <i class="fas fa-calendar-day me-1"></i> <?= date('F j, Y') ?>
            </span>
        </div>

        <div class="card p-4">
            <h4 class="mb-4">Update Your Password</h4>
            
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
            
            <form method="post">
                <div class="mb-3">
                    <label for="currentPassword" class="form-label">Current Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="newPassword" class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="password-requirements">
                        <div class="requirement" id="length">
                            <i class="fas fa-times-circle me-1"></i> At least 8 characters
                        </div>
                        <div class="requirement" id="uppercase">
                            <i class="fas fa-times-circle me-1"></i> At least one uppercase letter
                        </div>
                        <div class="requirement" id="number">
                            <i class="fas fa-times-circle me-1"></i> At least one number
                        </div>
                        <div class="requirement" id="special">
                            <i class="fas fa-times-circle me-1"></i> At least one special character
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirmPassword" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Change Password</button>
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

        // Password visibility toggle
        document.getElementById('toggleCurrentPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('currentPassword');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('newPassword');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirmPassword');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Password strength checker
        const newPasswordInput = document.getElementById('newPassword');
        const passwordStrength = document.getElementById('passwordStrength');
        const lengthReq = document.getElementById('length');
        const uppercaseReq = document.getElementById('uppercase');
        const numberReq = document.getElementById('number');
        const specialReq = document.getElementById('special');

        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            
            // Update requirement indicators
            updateRequirement(lengthReq, hasLength);
            updateRequirement(uppercaseReq, hasUppercase);
            updateRequirement(numberReq, hasNumber);
            updateRequirement(specialReq, hasSpecial);
            
            // Calculate strength
            let strength = 0;
            if (hasLength) strength++;
            if (hasUppercase) strength++;
            if (hasNumber) strength++;
            if (hasSpecial) strength++;
            
            // Update strength indicator
            passwordStrength.className = 'password-strength';
            if (strength <= 1) {
                passwordStrength.classList.add('strength-weak');
            } else if (strength <= 3) {
                passwordStrength.classList.add('strength-medium');
            } else {
                passwordStrength.classList.add('strength-strong');
            }
        });

        function updateRequirement(element, isMet) {
            if (isMet) {
                element.classList.add('met');
                element.querySelector('i').classList.remove('fa-times-circle');
                element.querySelector('i').classList.add('fa-check-circle');
            } else {
                element.classList.remove('met');
                element.querySelector('i').classList.remove('fa-check-circle');
                element.querySelector('i').classList.add('fa-times-circle');
            }
        }
    </script>
</body>
</html>