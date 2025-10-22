<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

include '../db/connection.php';

// Get submission ID from URL
 $submissionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the submission belongs to the current faculty member
 $facultyId = (int)($_SESSION['user_id'] ?? 0);
 $stmt = $conn->prepare("SELECT * FROM ipcr_submissions WHERE id = ? AND faculty_id = ?");
 $stmt->bind_param("ii", $submissionId, $facultyId);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Submission not found or doesn't belong to this faculty
    $_SESSION['error'] = "Submission not found or you don't have permission to edit it.";
    header("Location: My_Total_Submissions.php");
    exit();
}

 $submission = $result->fetch_assoc();

// Check if submission can be edited (only pending submissions)
if ($submission['status'] !== 'Pending') {
    $_SESSION['error'] = "Only pending submissions can be edited.";
    header("Location: view_submission.php?id=" . $submissionId);
    exit();
}

// Get indicators data
 $indicators = [];
if (!empty($submission['indicators_json'])) {
    $indicators = json_decode($submission['indicators_json'], true);
} elseif (!empty($submission['submission_data'])) {
    $submissionData = json_decode($submission['submission_data'], true);
    if (isset($submissionData['indicator'])) {
        $count = count($submissionData['indicator']);
        for ($i = 0; $i < $count; $i++) {
            $indicators[] = [
                'indicator' => $submissionData['indicator'][$i] ?? '',
                'accomplishment' => $submissionData['accomplishment'][$i] ?? '',
                'q' => $submissionData['q'][$i] ?? '',
                'e' => $submissionData['e'][$i] ?? '',
                't' => $submissionData['t'][$i] ?? '',
                'a' => $submissionData['a'][$i] ?? '',
                'remarks' => $submissionData['remarks'][$i] ?? ''
            ];
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form data
    $period = $_POST['period'] ?? $submission['period'];
    $department = $_POST['department'] ?? $submission['department'];
    $rateeName = $_POST['ratee_name'] ?? $submission['ratee_name'];
    $rateePosition = $_POST['ratee_position'] ?? $submission['ratee_position'];
    $rateeDate = $_POST['ratee_date'] ?? $submission['ratee_date'];
    
    // Process indicators
    $indicatorsData = [];
    if (isset($_POST['indicator']) && is_array($_POST['indicator'])) {
        $count = count($_POST['indicator']);
        for ($i = 0; $i < $count; $i++) {
            $q = floatval($_POST['q'][$i] ?? 0);
            $e = floatval($_POST['e'][$i] ?? 0);
            $t = floatval($_POST['t'][$i] ?? 0);
            $average = ($q + $e + $t) / 3;
            
            $indicatorsData[] = [
                'indicator' => $_POST['indicator'][$i] ?? '',
                'accomplishment' => $_POST['accomplishment'][$i] ?? '',
                'q' => $q,
                'e' => $e,
                't' => $t,
                'a' => round($average, 2),
                'remarks' => $_POST['remarks'][$i] ?? ''
            ];
        }
    }
    
    // Calculate final average rating
    $totalAverage = 0;
    $validIndicators = 0;
    foreach ($indicatorsData as $indicator) {
        if ($indicator['a'] > 0) {
            $totalAverage += $indicator['a'];
            $validIndicators++;
        }
    }
    $finalAverageRating = $validIndicators > 0 ? round($totalAverage / $validIndicators, 2) : 0;
    
    // Prepare data for database
    $indicatorsJson = json_encode($indicatorsData);
    
    // Update the submission
    $updateStmt = $conn->prepare("UPDATE ipcr_submissions SET 
        period = ?, 
        department = ?, 
        ratee_name = ?, 
        ratee_position = ?, 
        ratee_date = ?, 
        indicators_json = ?, 
        final_avg_rating = ? 
        WHERE id = ? AND faculty_id = ?");
    
    $updateStmt->bind_param(
        "ssssssdii", 
        $period, 
        $department, 
        $rateeName, 
        $rateePosition, 
        $rateeDate, 
        $indicatorsJson, 
        $finalAverageRating, 
        $submissionId, 
        $facultyId
    );
    
    if ($updateStmt->execute()) {
        $_SESSION['success'] = "Submission updated successfully!";
        header("Location: view_submission.php?id=" . $submissionId);
        exit();
    } else {
        $_SESSION['error'] = "Error updating submission: " . $conn->error;
        header("Location: edit_submission.php?id=" . $submissionId);
        exit();
    }
}

// Handle profile data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Faculty';
 $email = $_SESSION['email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Edit Submission - IPCR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../js/bootstrap.bundle.min.js"></script>
    <style>
        /* Include the same styles as view_submission.php */
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
        
        /* Page-specific styles */
        .submission-container {
            background-color: var(--card-light);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 20px;
        }
        [data-theme='dark'] .submission-container {
            background-color: var(--card-dark);
        }
        .signature-block {
            margin-top: 40px;
        }
        .label {
            font-weight: bold;
        }
        table th, table td {
            vertical-align: middle;
            text-align: center;
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
        .action-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-add-row {
            margin-top: 10px;
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
            .submission-container {
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .submission-container {
                padding: 15px;
            }
            .table {
                font-size: 0.9rem;
            }
            .action-buttons {
                flex-direction: column;
            }
            .action-buttons .btn {
                width: 100%;
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
    <a href="Submit_IPCR.php"><i class="fas fa-edit me-2"></i> Submit IPCR</a>
    <a href="My_Total_Submissions.php" class="active"><i class="fas fa-folder-open me-2"></i> My Submissions</a>
    <a href="My_Approved_IPCRs.php"><i class="fas fa-check-circle me-2"></i> Approved IPCRs</a>
    <a href="My_Pending_Reviews.php"><i class="fas fa-hourglass-half me-2"></i> Pending Reviews</a>
    <a href="generate_pdf.php"><i class="fas fa-download me-2"></i> Generate PDF</a>
    <a href="view_announcements.php"><i class="fas fa-bullhorn me-2"></i> View Announcements</a>
    <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
    <div class="sidebar-footer mt-4 pt-2 border-top">
        <small>Version 1.0.0</small>
    </div>
</div>
<div class="main-content">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <div class="submission-container">
        <h3 class="mb-4">Edit Performance Commitment and Review (PCR)</h3>
        
        <form method="post" action="edit_submission.php?id=<?= $submissionId ?>">
            <div class="mb-4">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="period" class="form-label">Period:</label>
                        <input type="text" class="form-control" id="period" name="period" value="<?= htmlspecialchars($submission['period'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department:</label>
                        <input type="text" class="form-control" id="department" name="department" value="<?= htmlspecialchars($submission['department'] ?? 'BSIT') ?>" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">I, <strong><?= htmlspecialchars($fullName) ?></strong>, of the <strong><?= htmlspecialchars($submission['department'] ?? 'BSIT') ?></strong> faculty of Philippine Countryville College, Inc., commit to deliver and agree to be rated on the following targets in accordance with the indicated measure for the period of <strong><?= htmlspecialchars($submission['period'] ?? date('Y-m-d')) ?></strong>.</label>
                </div>
                
                <div class="mb-3">
                    <strong>Rating Scale:</strong>
                    <ul>
                        <li>5 – Outstanding</li>
                        <li>4 – Very Satisfactory</li>
                        <li>3 – Satisfactory</li>
                        <li>2 – Unsatisfactory</li>
                        <li>1 – Poor</li>
                    </ul>
                </div>
            </div>
            
            <div class="table-responsive mb-4">
                <table class="table table-bordered" id="indicatorsTable">
                    <thead class="table-light">
                        <tr>
                            <th>SUCCESS INDICATORS</th>
                            <th>Accomplishment</th>
                            <th>Quality</th>
                            <th>Efficiency</th>
                            <th>Time</th>
                            <th>Average</th>
                            <th>Remarks</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($indicators)): ?>
                            <?php foreach ($indicators as $index => $row): ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control" name="indicator[]" value="<?= htmlspecialchars($row['indicator'] ?? '') ?>" required>
                                </td>
                                <td>
                                    <textarea class="form-control" name="accomplishment[]" rows="2"><?= htmlspecialchars($row['accomplishment'] ?? '') ?></textarea>
                                </td>
                                <td>
                                    <select class="form-control" name="q[]" required>
                                        <option value="">Select</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($row['q'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="e[]" required>
                                        <option value="">Select</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($row['e'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="t[]" required>
                                        <option value="">Select</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($row['t'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($row['a'] ?? '') ?>" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="remarks[]" value="<?= htmlspecialchars($row['remarks'] ?? '') ?>">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <input type="text" class="form-control" name="indicator[]" required>
                                </td>
                                <td>
                                    <textarea class="form-control" name="accomplishment[]" rows="2"></textarea>
                                </td>
                                <td>
                                    <select class="form-control" name="q[]" required>
                                        <option value="">Select</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="e[]" required>
                                        <option value="">Select</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-control" name="t[]" required>
                                        <option value="">Select</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="remarks[]">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <button type="button" class="btn btn-success btn-sm btn-add-row" id="addRowBtn">
                    <i class="fas fa-plus me-1"></i> Add Row
                </button>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="ratee_name" class="form-label">Ratee Name:</label>
                    <input type="text" class="form-control" id="ratee_name" name="ratee_name" value="<?= htmlspecialchars($submission['ratee_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="ratee_position" class="form-label">Ratee Position:</label>
                    <input type="text" class="form-control" id="ratee_position" name="ratee_position" value="<?= htmlspecialchars($submission['ratee_position'] ?? '') ?>" required>
                </div>
                <div class="col-md-6 mt-3">
                    <label for="ratee_date" class="form-label">Ratee Date:</label>
                    <input type="date" class="form-control" id="ratee_date" name="ratee_date" value="<?= htmlspecialchars($submission['ratee_date'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
                <a href="view_submission.php?id=<?= $submissionId ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    // Theme toggle
    const toggleTheme = document.getElementById('toggleTheme');
    const htmlTag = document.documentElement;
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) htmlTag.setAttribute('data-theme', savedTheme);
    
    toggleTheme.addEventListener('click', () => {
        const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        htmlTag.setAttribute('data-theme', next);
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
    
    // Add new row to indicators table
    document.getElementById('addRowBtn').addEventListener('click', function() {
        const table = document.getElementById('indicatorsTable').getElementsByTagName('tbody')[0];
        const newRow = table.insertRow();
        
        // Get current row count to ensure unique names
        const rowCount = table.rows.length;
        
        newRow.innerHTML = `
            <td>
                <input type="text" class="form-control" name="indicator[]" required>
            </td>
            <td>
                <textarea class="form-control" name="accomplishment[]" rows="2"></textarea>
            </td>
            <td>
                <select class="form-control" name="q[]" required>
                    <option value="">Select</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </td>
            <td>
                <select class="form-control" name="e[]" required>
                    <option value="">Select</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </td>
            <td>
                <select class="form-control" name="t[]" required>
                    <option value="">Select</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" readonly>
            </td>
            <td>
                <input type="text" class="form-control" name="remarks[]">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        // Add event listener to the new remove button
        newRow.querySelector('.remove-row').addEventListener('click', function() {
            if (table.rows.length > 1) {
                newRow.remove();
            } else {
                alert('You must have at least one indicator row.');
            }
        });
    });
    
    // Remove row functionality
    document.querySelectorAll('.remove-row').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const table = document.getElementById('indicatorsTable').getElementsByTagName('tbody')[0];
            
            if (table.rows.length > 1) {
                row.remove();
            } else {
                alert('You must have at least one indicator row.');
            }
        });
    });
    
    // Calculate average when rating values change
    document.addEventListener('change', function(e) {
        if (e.target.name === 'q[]' || e.target.name === 'e[]' || e.target.name === 't[]') {
            const row = e.target.closest('tr');
            const q = parseFloat(row.querySelector('select[name="q[]"]').value) || 0;
            const e = parseFloat(row.querySelector('select[name="e[]"]').value) || 0;
            const t = parseFloat(row.querySelector('select[name="t[]"]').value) || 0;
            
            if (q > 0 && e > 0 && t > 0) {
                const average = (q + e + t) / 3;
                row.querySelector('td:nth-child(6) input').value = average.toFixed(2);
            } else {
                row.querySelector('td:nth-child(6) input').value = '';
            }
        }
    });
</script>
</body>
</html>