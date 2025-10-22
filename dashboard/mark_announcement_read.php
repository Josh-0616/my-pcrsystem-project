<?php
session_start();
include '../db/connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty = $_SESSION['username'];
$announcementId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($announcementId > 0) {
    // Check if already marked
    $check = mysqli_query($conn, "SELECT * FROM announcement_reads WHERE faculty_username = '$faculty' AND announcement_id = $announcementId");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO announcement_reads (announcement_id, faculty_username) VALUES ($announcementId, '$faculty')");
    }
}

// Redirect back to full announcements page or dashboard
header("Location: faculty_announcements.php");
exit();
?>
