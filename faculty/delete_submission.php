<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

include '../db/connection.php';

// Get submission ID from URL parameter
 $submissionId = (int)($_GET['id'] ?? 0);

if ($submissionId > 0) {
    // Delete the submission permanently
    $stmt = $conn->prepare("DELETE FROM ipcr_submissions WHERE id = ? AND faculty_id = ?");
    $facultyId = (int)($_SESSION['user_id'] ?? 0);
    $stmt->bind_param("ii", $submissionId, $facultyId);
    
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            // Redirect back to submissions page with success message
            $_SESSION['message'] = "Submission deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            // No rows were affected, meaning the submission doesn't exist or doesn't belong to this user
            $_SESSION['message'] = "Submission not found or you don't have permission to delete it.";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        // Redirect back with error message
        $_SESSION['message'] = "Error deleting submission.";
        $_SESSION['message_type'] = "danger";
    }
    
    $stmt->close();
}

header("Location: my_submissions.php");
exit();
?>