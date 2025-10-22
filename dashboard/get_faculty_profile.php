<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.0 403 Forbidden");
    exit();
}
include '../db/connection.php';

 $facultyId = $_GET['id'] ?? 0;
if (!is_numeric($facultyId) || $facultyId <= 0) {
    echo '<div class="alert alert-danger">Invalid faculty ID</div>';
    exit();
}

// Get faculty details
 $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'faculty'");
 $stmt->bind_param("i", $facultyId);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Faculty not found</div>';
    exit();
}

 $faculty = $result->fetch_assoc();
 $stmt->close();

// Get faculty department
 $departmentName = 'Not assigned';
if (!empty($faculty['department_id'])) {
    $deptStmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
    $deptStmt->bind_param("i", $faculty['department_id']);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    if ($deptRow = $deptResult->fetch_assoc()) {
        $departmentName = $deptRow['name'];
    }
    $deptStmt->close();
}

// Get faculty IPCR submissions count
 $submissionsCount = 0;
 $submissionsStmt = $conn->prepare("SELECT COUNT(*) as count FROM ipcr_submissions WHERE faculty_id = ?");
 $submissionsStmt->bind_param("i", $facultyId);
 $submissionsStmt->execute();
 $submissionsResult = $submissionsStmt->get_result();
if ($submissionsRow = $submissionsResult->fetch_assoc()) {
    $submissionsCount = $submissionsRow['count'];
}
 $submissionsStmt->close();

// Get profile picture
 $profilePicture = '../uploads/1.png'; // Default
if (!empty($faculty['profile_picture'])) {
    $profilePicture = '../uploads/' . $faculty['profile_picture'];
}
?>

<div class="row">
    <div class="col-md-4 text-center">
        <img src="<?= htmlspecialchars($profilePicture) ?>" class="img-thumbnail rounded-circle mb-3" alt="Faculty Profile" style="width: 150px; height: 150px; object-fit: cover;">
        <h4><?= htmlspecialchars($faculty['full_name']) ?></h4>
        <p class="text-muted">@<?= htmlspecialchars($faculty['username']) ?></p>
        <span class="badge bg-<?= $faculty['status'] === 'active' ? 'success' : 'danger' ?>">
            <?= ucfirst($faculty['status']) ?>
        </span>
    </div>
    <div class="col-md-8">
        <h5>Faculty Information</h5>
        <table class="table table-borderless">
            <tr>
                <th width="30%">Email:</th>
                <td><?= htmlspecialchars($faculty['email']) ?></td>
            </tr>
            <tr>
                <th>Department:</th>
                <td><?= htmlspecialchars($departmentName) ?></td>
            </tr>
            <tr>
                <th>Employee ID:</th>
                <td><?= htmlspecialchars($faculty['employee_id'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Position:</th>
                <td><?= htmlspecialchars($faculty['position'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Date Joined:</th>
                <td><?= !empty($faculty['created_at']) ? date('M d, Y', strtotime($faculty['created_at'])) : 'N/A' ?></td>
            </tr>
            <tr>
                <th>IPCR Submissions:</th>
                <td><?= $submissionsCount ?></td>
            </tr>
        </table>
        
        <div class="d-flex gap-2 mt-3">
            <a href="manage_users.php?action=edit&id=<?= htmlspecialchars($facultyId) ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> Edit Profile
            </a>
            <a href="view_ipcr_submissions.php?faculty_id=<?= htmlspecialchars($facultyId) ?>" class="btn btn-info">
                <i class="fas fa-file-alt me-1"></i> View Submissions
            </a>
            <a href="mailto:<?= htmlspecialchars($faculty['email']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-envelope me-1"></i> Send Email
            </a>
        </div>
    </div>
</div>