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
    
    // Delete the specific submission from the recycle bin
    $query = "DELETE FROM deleted_submissions WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    
    mysqli_stmt_close($stmt);
} else {
    // Not a POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>