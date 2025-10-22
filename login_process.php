<?php
session_start();
include 'db/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare query
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['department'] = $row['department'];
            $_SESSION['user_id'] = $row['id'];

            // Redirect based on role
            if ($row['role'] == 'admin') {
                header("Location: ../admin/admin_dashboard.php");
                exit();
            } else {
                header("Location: ../faculty/faculty_dashboard.php");
                exit();
            }
        } else {
            // Invalid password
            header("Location: ../login.php?error=invalid");
            exit();
        }
    } else {
        // Username not found
        header("Location: ../login.php?error=notfound");
        exit();
    }
} else {
    // Not POST method
    header("Location: ../login.php");
    exit();
}
