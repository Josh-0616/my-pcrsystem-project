<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token if you have it implemented
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
    
    $id = $_POST['id'];
    
    // Validate ID
    if (!is_numeric($id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit();
    }
    
    // Start transaction to ensure atomicity
    mysqli_begin_transaction($conn);
    
    try {
        // Get the deleted submission details
        $query = "SELECT * FROM deleted_submissions WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result->num_rows === 0) {
            throw new Exception("Submission not found in recycle bin");
        }
        
        $row = mysqli_fetch_assoc($result);
        
        // Insert back into the original submissions table (using current date for created_at)
        $insertQuery = "INSERT INTO ipcr_submissions (faculty_name, department, period, final_avg_rating, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        
        mysqli_stmt_bind_param($insertStmt, 'sssss', 
            $row['faculty_name'],
            $row['department'],
            $row['period'],
            $row['final_avg_rating'],
            $row['status']
        );
        
        if (!mysqli_stmt_execute($insertStmt)) {
            throw new Exception("Failed to restore submission: " . mysqli_error($conn));
        }
        
        // Remove from deleted_submissions
        $deleteQuery = "DELETE FROM deleted_submissions WHERE id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, 'i', $id);
        
        if (!mysqli_stmt_execute($deleteStmt)) {
            throw new Exception("Failed to remove from recycle bin: " . mysqli_error($conn));
        }
        
        // If we got here, both operations succeeded
        mysqli_commit($conn);
        echo json_encode(['success' => true]);
        
        // Close statements
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($insertStmt);
        mysqli_stmt_close($deleteStmt);
        
    } catch (Exception $e) {
        // Roll back the transaction in case of any error
        mysqli_rollback($conn);
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        
        // Close statements if they were opened
        if (isset($stmt)) mysqli_stmt_close($stmt);
        if (isset($insertStmt)) mysqli_stmt_close($insertStmt);
        if (isset($deleteStmt)) mysqli_stmt_close($deleteStmt);
    }
} else {
    // Not a POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>