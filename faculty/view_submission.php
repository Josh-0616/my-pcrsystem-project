<?php
session_start();
include('../db/connection.php');

// Check if user is logged in and has faculty role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

// Verify faculty exists in database - using users table instead of faculty table
 $faculty_id = $_SESSION['user_id'] ?? 0;
if ($faculty_id === 0) {
    $_SESSION['error'] = "Invalid session. Please login again.";
    header("Location: ../login.php");
    exit();
}

// Get faculty details from users table
 $stmt = $conn->prepare("SELECT full_name, email, profile_picture FROM users WHERE id = ? AND role = 'faculty'");
 $stmt->bind_param("i", $faculty_id);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Faculty account not found. Please contact administrator.";
    header("Location: ../login.php");
    exit();
}

// Update session variables if needed
 $facultyData = $result->fetch_assoc();
if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $facultyData['full_name'];
}
if (!isset($_SESSION['email'])) {
    $_SESSION['email'] = $facultyData['email'];
}
if (!isset($_SESSION['profile_picture'])) {
    $_SESSION['profile_picture'] = $facultyData['profile_picture'];
}

// Handle Profile Data
 $profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
 $fullName = $_SESSION['full_name'] ?? 'Faculty';
 $email = $_SESSION['email'] ?? '';

// Handle form submission for editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_submission'])) {
    $id = $_POST['id'];
    
    // Verify this submission belongs to the current faculty
    $verifySql = "SELECT faculty_id FROM ipcr_submissions WHERE id = ?";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->bind_param("i", $id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    $verifyData = $verifyResult->fetch_assoc();
    
    if (!$verifyData || $verifyData['faculty_id'] != $faculty_id) {
        $_SESSION['error'] = "You don't have permission to edit this submission.";
        header("Location: my_submissions.php");
        exit();
    }
    
    // Update basic information
    $faculty_name = $_POST['faculty_name'];
    $department = $_POST['department'];
    $period = $_POST['period'];
    $final_avg_rating = $_POST['final_avg_rating'];
    $adjectival_rating = $_POST['adjectival_rating'];
    $ratee_name = $_POST['ratee_name'];
    $ratee_position = $_POST['ratee_position'];
    $ratee_date = $_POST['ratee_date'];
    $final_rating_program_head = $_POST['final_rating_program_head'];
    $ph_position = $_POST['ph_position'];
    $ph_date = $_POST['ph_date'];
    
    // Process indicators
    $indicators = [];
    if (isset($_POST['indicator']) && is_array($_POST['indicator'])) {
        foreach ($_POST['indicator'] as $index => $value) {
            // Only include rows that have at least an indicator
            if (!empty(trim($value))) {
                $indicators[] = [
                    'indicator' => $_POST['indicator'][$index] ?? '',
                    'accomplishment' => $_POST['accomplishment'][$index] ?? '',
                    'q' => $_POST['q'][$index] ?? '',
                    'e' => $_POST['e'][$index] ?? '',
                    't' => $_POST['t'][$index] ?? '',
                    'a' => $_POST['a'][$index] ?? '',
                    'remarks' => $_POST['remarks'][$index] ?? ''
                ];
            }
        }
    }
    
    // Convert indicators to JSON
    $indicators_json = json_encode($indicators);
    
    // Update the submission
    $updateSql = "UPDATE ipcr_submissions SET 
        faculty_name = ?, 
        department = ?, 
        period = ?, 
        indicators_json = ?, 
        final_avg_rating = ?, 
        adjectival_rating = ?, 
        ratee_name = ?, 
        ratee_position = ?, 
        ratee_date = ?, 
        final_rating_program_head = ?, 
        ph_position = ?, 
        ph_date = ? 
        WHERE id = ? AND faculty_id = ?";
    
    $stmt = $conn->prepare($updateSql);
    // Fixed: Added proper type definition string with 14 parameters (12 strings and 2 integers)
    $stmt->bind_param("ssssssssssssii", 
        $faculty_name, 
        $department, 
        $period, 
        $indicators_json, 
        $final_avg_rating, 
        $adjectival_rating, 
        $ratee_name, 
        $ratee_position, 
        $ratee_date, 
        $final_rating_program_head, 
        $ph_position, 
        $ph_date, 
        $id, 
        $faculty_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Submission updated successfully!";
        header("Location: view_submission.php?id=$id");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update submission: " . $conn->error;
    }
}

// Check if submission ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid Request.";
    header("Location: my_submissions.php");
    exit();
}

 $id = $_GET['id'];

// Get submission details and verify ownership
 $stmt = $conn->prepare("SELECT * FROM ipcr_submissions WHERE id = ? AND faculty_id = ?");
 $stmt->bind_param("ii", $id, $faculty_id);
 $stmt->execute();
 $result = $stmt->get_result();
 $data = $result->fetch_assoc();

if (!$data) {
    $_SESSION['error'] = "Submission not found or you don't have permission to view this submission.";
    header("Location: my_submissions.php");
    exit();
}

// Check if we're in edit mode
 $editMode = isset($_GET['edit']) && $_GET['edit'] == 'true';

// Faculty can only edit if status is Pending
if ($editMode && $data['status'] !== 'Pending') {
    $_SESSION['error'] = "You can only edit submissions with 'Pending' status.";
    header("Location: view_submission.php?id=$id");
    exit();
}

// Function to get all indicators from the submission
function getIndicators($conn, $submissionData) {
    $indicators = [];
    
    // Check if indicators_json exists and is not empty
    if (!empty($submissionData['indicators_json'])) {
        $indicators = json_decode($submissionData['indicators_json'], true);
        if (is_array($indicators)) {
            return $indicators;
        }
    }
    
    // If no JSON data, extract indicators from form data
    $index = 0;
    while (isset($submissionData["indicator{$index}"])) {
        $indicators[] = [
            'indicator' => $submissionData["indicator{$index}"] ?? '',
            'accomplishment' => $submissionData["accomplishment{$index}"] ?? '',
            'q' => $submissionData["q{$index}"] ?? '',
            'e' => $submissionData["e{$index}"] ?? '',
            't' => $submissionData["t{$index}"] ?? '',
            'a' => $submissionData["a{$index}"] ?? '',
            'remarks' => $submissionData["remarks{$index}"] ?? ''
        ];
        $index++;
    }
    
    // If still no indicators found, check for the old format (single row)
    if (empty($indicators) && isset($submissionData['indicator0'])) {
        $indicators[] = [
            'indicator' => $submissionData['indicator0'] ?? '',
            'accomplishment' => $submissionData['accomplishment0'] ?? '',
            'q' => $submissionData['q0'] ?? '',
            'e' => $submissionData['e0'] ?? '',
            't' => $submissionData['t0'] ?? '',
            'a' => $submissionData['a0'] ?? '',
            'remarks' => $submissionData['remarks0'] ?? ''
        ];
    }
    
    return $indicators;
}

// Get indicators for this submission
 $indicators = getIndicators($conn, $data);

// Function to calculate adjectival rating based on final average rating
function getAdjectivalRating($rating) {
    $rating = floatval($rating);
    if ($rating >= 4.5) return "Outstanding";
    if ($rating >= 3.5) return "Very Satisfactory";
    if ($rating >= 2.5) return "Satisfactory";
    if ($rating >= 1.5) return "Unsatisfactory";
    return "Poor"; // This covers everything below 1.5, including 0
}

// Get the final average rating and calculate the adjectival rating
 $finalAverageRating = $data['final_avg_rating'];
 $adjectivalRating = getAdjectivalRating($finalAverageRating);

// Get status badge class based on status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending': return 'status-Pending';
        case 'Reviewed': return 'status-Reviewed';
        case 'Approved': return 'status-Approved';
        case 'Declined': 
        case 'Rejected': return 'status-Declined'; // Handle both 'Rejected' and 'Declined' for consistency
        default: return 'status-Pending';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>View IPCR Submission - IPCR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="/asset/css/bootstrap.min.css">
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
    .status-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
    }
    .status-Pending {
      background-color: #fff3cd;
      color: #856404;
    }
    .status-Reviewed {
      background-color: #cfe2ff;
      color: #084298;
    }
    .status-Approved {
      background-color: #d1e7dd;
      color: #0f5132;
    }
    .status-Declined {
      background-color: #f8d7da;
      color: #842029;
    }
    .add-row-btn {
      margin-top: 10px;
    }
    
    /* Print Styles */
    @media print {
      body {
        background: white;
        color: black;
        font-size: 12pt;
        margin: 0;
        padding: 20px;
      }
      .topbar, .sidebar, .action-buttons, .dark-toggle, .settings, .add-row-btn {
        display: none !important;
      }
      .main-content {
        margin: 0 !important;
        padding: 0 !important;
      }
      .submission-container {
        box-shadow: none;
        padding: 0;
        max-width: 100%;
      }
      .table {
        width: 100%;
        border-collapse: collapse;
      }
      .table th, .table td {
        border: 1px solid #000;
        padding: 8px;
      }
      .signature-block {
        margin-top: 50px;
      }
      .form-control {
        border: none;
        background: transparent;
      }
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
<body<?php if (isset($_GET['print']) && $_GET['print'] == 'true') echo ' onload="window.print()"'; ?>>
<?php if (!isset($_GET['print']) || $_GET['print'] != 'true'): ?>
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
        <a href="my_account.php"><i class="fas fa-user-edit me-2"></i> My Account</a>
        <a href="faculty_view_profile.php"><i class="fas fa-eye me-2"></i> View Profile</a>
        <a href="Change_Password.php"><i class="fas fa-key me-2"></i> Change Password</a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
      </div>
    </div>
  </div>
</div>
<div class="sidebar" id="sidebar">
    <a href="faculty_dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
    <a href="Submit_IPCR.php"><i class="fas fa-edit me-2"></i> Submit PCR</a>
    <a href="my_submissions.php"class="active"><i class="fas fa-folder-open me-2"></i> My Submissions</a>
    <a href="generatePDF.php"><i class="fas fa-download me-2"></i> Generate PDF</a>
    <a href="view_announcements.php"><i class="fas fa-bullhorn me-2"></i> View Announcements</a>
    <a href="recycle_bin.php"><i class="fas fa-trash-alt me-2"></i> Recycle Bin</a>
    <a href="about.php"><i class="fas fa-info-circle me-2"></i> About</a>
  </div>
</div>
<?php endif; ?>
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
  
  <?php if ($editMode): ?>
  <form method="POST" action="view_submission.php" id="editForm">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="update_submission" value="1">
  <?php endif; ?>
  
  <div class="submission-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3>Performance Commitment and Review (PCR)</h3>
      <div>
        <span class="status-badge <?= getStatusBadgeClass($data['status']) ?>">
          <?= htmlspecialchars($data['status']) ?>
        </span>
        <?php if (!$editMode && !isset($_GET['print'])): ?>
          <a href="?id=<?= $id ?>&edit=true" class="btn btn-primary ms-2">
            <i class="fas fa-edit me-1"></i> Edit Submission
          </a>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="mb-4">
      <p>
        I, 
        <?php if ($editMode): ?>
          <input type="text" name="faculty_name" class="form-control d-inline w-auto" value="<?= htmlspecialchars($data['faculty_name']) ?>" required>,
        <?php else: ?>
          <strong><?= htmlspecialchars($data['faculty_name']) ?></strong>,
        <?php endif; ?>
        of the 
        <?php if ($editMode): ?>
          <input type="text" name="department" class="form-control d-inline w-auto" value="<?= htmlspecialchars($data['department']) ?>" required>
        <?php else: ?>
          <strong><?= htmlspecialchars($data['department']) ?></strong>
        <?php endif; ?>
        faculty of Philippine Countryville College, Inc., commit to deliver and agree to be rated on the following targets in accordance with the indicated measure for the period of 
        <?php if ($editMode): ?>
          <input type="text" name="period" class="form-control d-inline w-auto" value="<?= htmlspecialchars($data['period']) ?>" required>.
        <?php else: ?>
          <?= htmlspecialchars($data['period']) ?>.
        <?php endif; ?>
      </p>
      <div class="mb-4">
        <strong>Reviewed by:</strong> _____________________<br>
      </div>
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
    
    <div class="table-responsive">
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
            <?php if ($editMode): ?>
            <th>Action</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody id="indicatorsTableBody">
          <?php if (!empty($indicators)): ?>
            <?php foreach ($indicators as $index => $row): ?>
            <tr>
              <td>
                <?php if ($editMode): ?>
                  <input type="text" name="indicator[]" class="form-control" value="<?= htmlspecialchars($row['indicator']) ?>">
                <?php else: ?>
                  <?= htmlspecialchars($row['indicator']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($editMode): ?>
                  <input type="text" name="accomplishment[]" class="form-control" value="<?= htmlspecialchars($row['accomplishment']) ?>">
                <?php else: ?>
                  <?= htmlspecialchars($row['accomplishment']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($editMode): ?>
                  <input type="number" name="q[]" class="form-control rating-input" value="<?= htmlspecialchars($row['q']) ?>" min="1" max="5" step="0.01">
                <?php else: ?>
                  <?= htmlspecialchars($row['q']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($editMode): ?>
                  <input type="number" name="e[]" class="form-control rating-input" value="<?= htmlspecialchars($row['e']) ?>" min="1" max="5" step="0.01">
                <?php else: ?>
                  <?= htmlspecialchars($row['e']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($editMode): ?>
                  <input type="number" name="t[]" class="form-control rating-input" value="<?= htmlspecialchars($row['t']) ?>" min="1" max="5" step="0.01">
                <?php else: ?>
                  <?= htmlspecialchars($row['t']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($editMode): ?>
                  <input type="text" name="a[]" class="form-control average-input" value="<?= htmlspecialchars($row['a']) ?>" readonly>
                <?php else: ?>
                  <?= htmlspecialchars($row['a']) ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($editMode): ?>
                  <input type="text" name="remarks[]" class="form-control" value="<?= htmlspecialchars($row['remarks']) ?>">
                <?php else: ?>
                  <?= htmlspecialchars($row['remarks']) ?>
                <?php endif; ?>
              </td>
              <?php if ($editMode): ?>
              <td>
                <button type="button" class="btn btn-sm btn-danger remove-row">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="<?= $editMode ? 8 : 7 ?>" class="text-center text-muted py-3">
                No indicators found for this submission.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <?php if ($editMode): ?>
    <div class="add-row-btn">
      <button type="button" class="btn btn-success" id="addRowBtn">
        <i class="fas fa-plus me-1"></i> Add Row
      </button>
    </div>
    <?php endif; ?>
    
    <div class="row my-4">
      <div class="col-md-6">
        <label class="label">Final Average Rating:</label>
        <?php if ($editMode): ?>
          <input type="text" name="final_avg_rating" id="finalAverageRating" class="form-control" value="<?= htmlspecialchars($finalAverageRating) ?>" readonly>
        <?php else: ?>
          <div class="form-control"><?= htmlspecialchars($finalAverageRating) ?></div>
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="label">Adjectival Rating:</label>
        <?php if ($editMode): ?>
          <select name="adjectival_rating" id="adjectivalRating" class="form-control">
            <option value="Outstanding" <?= $adjectivalRating == 'Outstanding' ? 'selected' : '' ?>>Outstanding</option>
            <option value="Very Satisfactory" <?= $adjectivalRating == 'Very Satisfactory' ? 'selected' : '' ?>>Very Satisfactory</option>
            <option value="Satisfactory" <?= $adjectivalRating == 'Satisfactory' ? 'selected' : '' ?>>Satisfactory</option>
            <option value="Unsatisfactory" <?= $adjectivalRating == 'Unsatisfactory' ? 'selected' : '' ?>>Unsatisfactory</option>
            <option value="Poor" <?= $adjectivalRating == 'Poor' ? 'selected' : '' ?>>Poor</option>
          </select>
        <?php else: ?>
          <div class="form-control"><?= htmlspecialchars($adjectivalRating) ?></div>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="row signature-block">
      <div class="col-md-6">
        <h6>Name and Signature of Ratee:</h6>
        <p><strong>Name:</strong> 
          <?php if ($editMode): ?>
            <input type="text" name="ratee_name" class="form-control d-inline w-auto ms-2" value="<?= htmlspecialchars($data['ratee_name']) ?>">
          <?php else: ?>
            <?= htmlspecialchars($data['ratee_name']) ?>
          <?php endif; ?>
        </p>
        <p><strong>Position:</strong> 
          <?php if ($editMode): ?>
            <input type="text" name="ratee_position" class="form-control d-inline w-auto ms-2" value="<?= htmlspecialchars($data['ratee_position']) ?>">
          <?php else: ?>
            <?= htmlspecialchars($data['ratee_position']) ?>
          <?php endif; ?>
        </p>
        <p><strong>Date:</strong> 
          <?php if ($editMode): ?>
            <input type="date" name="ratee_date" class="form-control d-inline w-auto ms-2" value="<?= htmlspecialchars($data['ratee_date']) ?>">
          <?php else: ?>
            <?= htmlspecialchars($data['ratee_date']) ?>
          <?php endif; ?>
        </p>
      </div>
    </div>
    
    <div class="signature-block mt-4">
      <h6>Final Rating by Program Head:</h6>
      <p><strong>Rating:</strong> 
        <?php if ($editMode): ?>
          <input type="text" name="final_rating_program_head" class="form-control d-inline w-auto ms-2" value="<?= htmlspecialchars($data['final_rating_program_head']) ?>">
        <?php else: ?>
          <?= htmlspecialchars($data['final_rating_program_head']) ?>
        <?php endif; ?>
      </p>
      <p><strong>Position:</strong> 
        <?php if ($editMode): ?>
          <input type="text" name="ph_position" class="form-control d-inline w-auto ms-2" value="<?= htmlspecialchars($data['ph_position']) ?>">
        <?php else: ?>
          <?= htmlspecialchars($data['ph_position']) ?>
        <?php endif; ?>
      </p>
      <p><strong>Date:</strong> 
        <?php if ($editMode): ?>
          <input type="date" name="ph_date" class="form-control d-inline w-auto ms-2" value="<?= htmlspecialchars($data['ph_date']) ?>">
        <?php else: ?>
          <?= htmlspecialchars($data['ph_date']) ?>
        <?php endif; ?>
      </p>
    </div>
  </div>
  
  <?php if ($editMode): ?>
    <div class="action-buttons">
      <button type="submit" class="btn btn-success">
        <i class="fas fa-save me-1"></i> Save Changes
      </button>
      <a href="?id=<?= $id ?>" class="btn btn-secondary">
        <i class="fas fa-times me-1"></i> Cancel
      </a>
    </div>
    </form>
  <?php else: ?>
    <!-- Action buttons section with Print, Generate PDF, and Save PDF buttons -->
    <div class="action-buttons">
      <a href="?id=<?= $id ?>&print=true" class="btn btn-secondary">
        <i class="fas fa-print"></i> Print
      </a>
      <a href="generate_pdf.php?id=<?= $id ?>" class="btn btn-info">
        <i class="fas fa-file-pdf"></i> Generate PDF
      </a>
      <a href="save_pdf.php?id=<?= $id ?>" class="btn btn-success">
        <i class="fas fa-save"></i> Save PDF
      </a>
    </div>
  <?php endif; ?>
</div>
<?php if (!isset($_GET['print']) || $_GET['print'] != 'true'): ?>
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
  
  // Auto-calculation for ratings (only in edit mode)
  <?php if ($editMode): ?>
  function calculateRatings() {
    const table = document.getElementById('indicatorsTable');
    const rows = table.querySelectorAll('tbody tr');
    let totalAverage = 0;
    let validRows = 0;
    
    rows.forEach(row => {
      const qInput = row.querySelector('input[name="q[]"]');
      const eInput = row.querySelector('input[name="e[]"]');
      const tInput = row.querySelector('input[name="t[]"]');
      const aInput = row.querySelector('input[name="a[]"]');
      
      if (qInput && eInput && tInput && aInput) {
        const q = parseFloat(qInput.value) || 0;
        const e = parseFloat(eInput.value) || 0;
        const t = parseFloat(tInput.value) || 0;
        
        if (q > 0 && e > 0 && t > 0) {
          const average = (q + e + t) / 3;
          aInput.value = average.toFixed(2);
          totalAverage += average;
          validRows++;
        } else {
          aInput.value = '';
        }
      }
    });
    
    // Calculate final average rating
    const finalAverageInput = document.getElementById('finalAverageRating');
    if (validRows > 0) {
      const finalAverage = totalAverage / validRows;
      finalAverageInput.value = finalAverage.toFixed(2);
      
      // Update adjectival rating
      const adjectivalSelect = document.getElementById('adjectivalRating');
      if (finalAverage >= 4.5) {
        adjectivalSelect.value = 'Outstanding';
      } else if (finalAverage >= 3.5) {
        adjectivalSelect.value = 'Very Satisfactory';
      } else if (finalAverage >= 2.5) {
        adjectivalSelect.value = 'Satisfactory';
      } else if (finalAverage >= 1.5) {
        adjectivalSelect.value = 'Unsatisfactory';
      } else {
        adjectivalSelect.value = 'Poor';
      }
    } else {
      finalAverageInput.value = '';
      document.getElementById('adjectivalRating').value = '';
    }
  }
  
  // Add row functionality
  function addRow() {
    const tableBody = document.getElementById('indicatorsTableBody');
    const rowCount = tableBody.querySelectorAll('tr').length;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
      <td><input type="text" name="indicator[]" class="form-control" placeholder="Enter success indicator"></td>
      <td><input type="text" name="accomplishment[]" class="form-control" placeholder="Enter accomplishment"></td>
      <td><input type="number" name="q[]" class="form-control rating-input" min="1" max="5" step="0.01" placeholder="Q"></td>
      <td><input type="number" name="e[]" class="form-control rating-input" min="1" max="5" step="0.01" placeholder="E"></td>
      <td><input type="number" name="t[]" class="form-control rating-input" min="1" max="5" step="0.01" placeholder="T"></td>
      <td><input type="text" name="a[]" class="form-control average-input" readonly placeholder="A"></td>
      <td><input type="text" name="remarks[]" class="form-control" placeholder="Remarks"></td>
      <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="fas fa-trash"></i></button></td>
    `;
    
    tableBody.appendChild(newRow);
    
    // Add event listeners to the new row's inputs
    const newRatingInputs = newRow.querySelectorAll('.rating-input');
    newRatingInputs.forEach(input => {
      input.addEventListener('input', calculateRatings);
    });
    
    // Add event listener to the remove button
    const removeButton = newRow.querySelector('.remove-row');
    removeButton.addEventListener('click', function() {
      newRow.remove();
      calculateRatings(); // Recalculate after removing a row
    });
    
    // If this was the first row and it was the "No indicators found" row, remove it
    const noDataRow = tableBody.querySelector('.text-center');
    if (noDataRow) {
      noDataRow.parentElement.remove();
    }
  }
  
  // Remove row functionality
  function removeRow(button) {
    const row = button.closest('tr');
    row.remove();
    calculateRatings(); // Recalculate after removing a row
    
    // If no rows left, add a "No indicators" row
    const tableBody = document.getElementById('indicatorsTableBody');
    if (tableBody.querySelectorAll('tr').length === 0) {
      const emptyRow = document.createElement('tr');
      emptyRow.innerHTML = `
        <td colspan="8" class="text-center text-muted py-3">
          No indicators found for this submission.
        </td>
      `;
      tableBody.appendChild(emptyRow);
    }
  }
  
  // Add event listeners to existing remove buttons
  document.querySelectorAll('.remove-row').forEach(button => {
    button.addEventListener('click', function() {
      removeRow(this);
    });
  });
  
  // Add event listener to add row button
  document.getElementById('addRowBtn').addEventListener('click', addRow);
  
  // Add event listeners to rating inputs
  document.querySelectorAll('.rating-input').forEach(input => {
    input.addEventListener('input', calculateRatings);
  });
  
  // Calculate on page load
  document.addEventListener('DOMContentLoaded', calculateRatings);
  <?php endif; ?>
</script>
<?php endif; ?>
</body>
</html>