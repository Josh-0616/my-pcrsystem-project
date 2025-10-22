<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db/connection.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Update the notification status to 'read'
    $updateSql = "UPDATE admindashboard_notification SET status = 'read' WHERE id = $id";
    
    if (mysqli_query($conn, $updateSql)) {
        $_SESSION['success'] = "Notification marked as read successfully.";
    } else {
        $_SESSION['error'] = "Failed to mark notification as read: " . mysqli_error($conn);
    }
} else {
    $_SESSION['error'] = "Invalid notification ID.";
}

header("Location: notifications.php");
exit();
?>