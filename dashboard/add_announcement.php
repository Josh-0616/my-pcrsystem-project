<?php
include '../db/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $deadline = mysqli_real_escape_string($conn, $_POST['deadline']);

    $sql = "INSERT INTO announcements (title, content, deadline) 
            VALUES ('$title', '$content', '$deadline')";
    mysqli_query($conn, $sql);

    header("Location: announcements.php");
    exit();
}
?>
