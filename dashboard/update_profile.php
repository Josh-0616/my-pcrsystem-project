<?php
session_start();
include __DIR__ . '/../connection.php';


// Check if logged in
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
$fullName = $_POST['full_name'];
$email = $_POST['email'];
$profilePicture = $_FILES['profile_picture'];

$uploadDir = '../uploads/';
$profilePicPath = null;

// Handle file upload if a new picture is selected
if ($profilePicture['name']) {
    $fileName = uniqid() . "_" . basename($profilePicture["name"]);
    $targetFilePath = $uploadDir . $fileName;

    if (move_uploaded_file($profilePicture["tmp_name"], $targetFilePath)) {
        $profilePicPath = $targetFilePath;
    }
}

// Update user info
$query = "UPDATE users SET full_name = ?, email = ?" . ($profilePicPath ? ", profile_picture = ?" : "") . " WHERE username = ?";
$stmt = $conn->prepare($query);

if ($profilePicPath) {
    $stmt->bind_param("ssss", $fullName, $email, $profilePicPath, $username);
} else {
    $stmt->bind_param("sss", $fullName, $email, $username);
}

if ($stmt->execute()) {
    // Update session variables
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    if ($profilePicPath) {
        $_SESSION['profile_picture'] = $profilePicPath;
    }

    header("Location: admin_dashboard.php");
    exit();
} else {
    echo "Error updating profile: " . $stmt->error;
}
?>
