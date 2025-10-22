<?php
session_start();
include('db/connection.php');

$email = $_POST['email'];
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['id'] = $user['id']; 
        $_SESSION['full_name'] = $user['name']; 
        $_SESSION['email'] = $user['email']; 

        if ($user['role'] == 'admin') {
            header("Location: dashboard/admin_dashboard.php");
        } elseif ($user['role'] == 'faculty') {
            header("Location: faculty/faculty_dashboard.php"); // ✅ NEW
        } else {
            header("Location: dashboard/staff_dashboard.php");
        }
        exit();
    } else {
        echo "<script>alert('Incorrect password.'); window.location.href='login.php';</script>";
    }
} else {
    echo "<script>alert('No user found with that email.'); window.location.href='login.php';</script>";
}
?>
