<?php
session_start();
include('../db/connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get the submission ID
 $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit();
}

// Get the deleted submission details
 $query = "SELECT * FROM deleted_submissions WHERE id = ?";
 $stmt = $conn->prepare($query);
 $stmt->bind_param("i", $id);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Submission not found in recycle bin']);
    exit();
}

 $deletedSubmission = $result->fetch_assoc();

// Start transaction
 $conn->begin_transaction();

try {
    // Insert the submission back into the ipcr_submissions table
    $insertQuery = "INSERT INTO ipcr_submissions (
        faculty_id, faculty_name, department, period, 
        core_function_1, core_function_2, core_function_3, core_function_4,
        support_function_1, support_function_2, support_function_3, support_function_4,
        strategic_function_1, strategic_function_2, strategic_function_3, strategic_function_4,
        final_avg_rating, adjectival_rating, status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $insertStmt = $conn->prepare($insertQuery);
    
    $insertStmt->bind_param(
        "issssssssssssssssssss",
        $deletedSubmission['faculty_id'],
        $deletedSubmission['faculty_name'],
        $deletedSubmission['department'],
        $deletedSubmission['period'],
        $deletedSubmission['core_function_1'],
        $deletedSubmission['core_function_2'],
        $deletedSubmission['core_function_3'],
        $deletedSubmission['core_function_4'],
        $deletedSubmission['support_function_1'],
        $deletedSubmission['support_function_2'],
        $deletedSubmission['support_function_3'],
        $deletedSubmission['support_function_4'],
        $deletedSubmission['strategic_function_1'],
        $deletedSubmission['strategic_function_2'],
        $deletedSubmission['strategic_function_3'],
        $deletedSubmission['strategic_function_4'],
        $deletedSubmission['final_avg_rating'],
        $deletedSubmission['adjectival_rating'],
        $deletedSubmission['status'],
        $deletedSubmission['created_at'],
        date('Y-m-d H:i:s')
    );
    
    $insertStmt->execute();
    
    // Delete the submission from the recycle bin
    $deleteQuery = "DELETE FROM deleted_submissions WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $id);
    $deleteStmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    header("Content-Type: application/json");
    echo json_encode(['success' => true, 'message' => 'Submission restored successfully']);
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Error restoring submission: ' . $e->getMessage()]);
}
?>