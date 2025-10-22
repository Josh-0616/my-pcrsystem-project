<?php
session_start();
include '../db/connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_SESSION['username'];
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('New passwords do not match!'); window.history.back();</script>";
        exit();
    }

    // Get current password from DB
    $query = "SELECT password FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $hashedPassword);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Verify current password
    if (!password_verify($currentPassword, $hashedPassword)) {
        echo "<script>alert('Current password is incorrect!'); window.history.back();</script>";
        exit();
    }

    // Hash and update new password
    $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateQuery = "UPDATE users SET password = ? WHERE username = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "ss", $newHashedPassword, $username);

    if (mysqli_stmt_execute($updateStmt)) {
        echo "<script>alert('Password updated successfully!'); window.location.href = 'admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Something went wrong. Please try again.'); window.history.back();</script>";
    }

    mysqli_stmt_close($updateStmt);
    mysqli_close($conn);
} else {
    header("Location: change_password.php");
    exit();
}
