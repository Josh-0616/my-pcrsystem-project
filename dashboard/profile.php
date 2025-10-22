<?php
session_start();
include '../config/db.php'; // adjust if necessary

// Get admin user ID
$admin_id = $_SESSION['user_id'];

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Admin Profile</h3>
    <form action="update_profile.php" method="POST">
        <input type="hidden" name="id" value="<?= $admin['id'] ?>">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="fullname" class="form-control" value="<?= $admin['fullname'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= $admin['email'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" value="<?= $admin['username'] ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Change Password</label>
            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>
</body>
</html>
