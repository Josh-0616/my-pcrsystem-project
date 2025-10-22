<?php
session_start();
include('../db/connection.php');

// Check if user is logged in and has faculty role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: ../login.php");
    exit();
}

// Get faculty ID
 $faculty_id = $_SESSION['user_id'] ?? 0;
if ($faculty_id === 0) {
    $_SESSION['error'] = "Invalid session";
    header("Location: ../login.php");
    exit();
}

// Get submission ID
 $submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($submission_id <= 0) {
    $_SESSION['error'] = "Invalid submission ID";
    header("Location: my_submissions.php");
    exit();
}

// Verify this submission belongs to the current faculty
 $verifyQuery = "SELECT * FROM ipcr_submissions WHERE id = ? AND faculty_id = ?";
 $verifyStmt = $conn->prepare($verifyQuery);
 $verifyStmt->bind_param("ii", $submission_id, $faculty_id);
 $verifyStmt->execute();
 $verifyResult = $verifyStmt->get_result();

if ($verifyResult->num_rows === 0) {
    $_SESSION['error'] = "You don't have permission to delete this submission";
    header("Location: my_submissions.php");
    exit();
}

 $submission = $verifyResult->fetch_assoc();

// Start transaction
 $conn->begin_transaction();

try {
    // Insert the submission into the deleted_submissions table
    $insertQuery = "INSERT INTO deleted_submissions (
        original_id, faculty_id, faculty_name, department, period, 
        core_function_1, core_function_2, core_function_3, core_function_4,
        support_function_1, support_function_2, support_function_3, support_function_4,
        strategic_function_1, strategic_function_2, strategic_function_3, strategic_function_4,
        final_avg_rating, adjectival_rating, status, created_at, updated_at, 
        deleted_at, deleted_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $insertStmt = $conn->prepare($insertQuery);
    
    $deleted_at = date('Y-m-d H:i:s');
    $deleted_by = $_SESSION['user_id'];
    
    $insertStmt->bind_param(
        "iisssssssssssssssssssssi",
        $submission['id'],
        $submission['faculty_id'],
        $submission['faculty_name'],
        $submission['department'],
        $submission['period'],
        $submission['core_function_1'],
        $submission['core_function_2'],
        $submission['core_function_3'],
        $submission['core_function_4'],
        $submission['support_function_1'],
        $submission['support_function_2'],
        $submission['support_function_3'],
        $submission['support_function_4'],
        $submission['strategic_function_1'],
        $submission['strategic_function_2'],
        $submission['strategic_function_3'],
        $submission['strategic_function_4'],
        $submission['final_avg_rating'],
        $submission['adjectival_rating'],
        $submission['status'],
        $submission['created_at'],
        $submission['updated_at'],
        $deleted_at,
        $deleted_by
    );
    
    $insertStmt->execute();
    
    // Delete the submission from the ipcr_submissions table
    $deleteQuery = "DELETE FROM ipcr_submissions WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $submission_id);
    $deleteStmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    $_SESSION['success'] = "Submission moved to recycle bin successfully";
    header("Location: my_submissions.php");
    exit();
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    
    $_SESSION['error'] = "Error deleting submission: " . $e->getMessage();
    header("Location: my_submissions.php");
    exit();
}
?>