<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../db/connection.php';

// Handle Profile Data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Admin';
 $email = $_SESSION['email'] ?? '';

// Debug function to log information
function debug_log($message) {
    $logFile = 'debug.log';
    // Check if file exists and is writable
    if (is_writable($logFile) || (!file_exists($logFile) && is_writable('.'))) {
        file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
    }
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
        // Get user ID from session
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $username = $_SESSION['username'];
        $currentPassword = $_POST['currentPassword'];
        $newPassword = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);
        
        // Check if we have user_id or username
        if ($userId > 0 && in_array('id', $existingColumns)) {
            $query = "SELECT password FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $userId);
        } else {
            $query = "SELECT password FROM users WHERE $usernameColumn = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $username);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && $row = mysqli_fetch_assoc($result)) {
            // Verify current password
            if (password_verify($currentPassword, $row['password'])) {
                // Update password using prepared statement
                if ($userId > 0 && in_array('id', $existingColumns)) {
                    $updateQuery = "UPDATE users SET password = ? WHERE id = ?";
                    $updateStmt = mysqli_prepare($conn, $updateQuery);
                    mysqli_stmt_bind_param($updateStmt, "si", $newPassword, $userId);
                } else {
                    $updateQuery = "UPDATE users SET password = ? WHERE $usernameColumn = ?";
                    $updateStmt = mysqli_prepare($conn, $updateQuery);
                    mysqli_stmt_bind_param($updateStmt, "ss", $newPassword, $username);
                }
                
                if (mysqli_stmt_execute($updateStmt)) {
                    $successMessage = "Password changed successfully!";
                } else {
                    error_log("Password update error: " . mysqli_error($conn));
                    $errors[] = "Error updating password. Please try again.";
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - PCR Admin</title>
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
        
        [data-theme='dark'] .password-requirements {
            color: #a0a0a0;
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
        
        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }
        
        [data-theme='dark'] .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }
        
        [data-theme='dark'] .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .topbar {
                padding: 0 10px;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="ms-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="admin_dashboard.php" class="text-white">Dashboard</a></li>
                    <li class="breadcrumb-item active text-white">Change Password</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="main-content">
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
        // Check and apply theme from parent page or localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const htmlTag = document.documentElement;
            const savedTheme = localStorage.getItem('theme');
            
            // Only set the theme if it's not already set by the parent page
            if (!htmlTag.hasAttribute('data-theme')) {
                if (savedTheme) {
                    htmlTag.setAttribute('data-theme', savedTheme);
                } else {
                    // Default to dark mode if no theme is saved
                    htmlTag.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                }
            }
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