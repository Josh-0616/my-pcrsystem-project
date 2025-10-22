<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Help & Support</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h2>Help & Support</h2>
        <p>If you need assistance, please contact the system developer or IT department.</p>
        <ul>
            <li>Email: support@yourcollege.edu.ph</li>
            <li>Phone: (123) 456-7890</li>
        </ul>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>
