<?php
session_start();
include('db/connection.php');

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $username = $_SESSION['username'];

    // Check if the record belongs to the logged-in user
    $check = mysqli_query($conn, "SELECT * FROM ipcr_forms WHERE id = $id AND staff_name = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $delete = mysqli_query($conn, "DELETE FROM ipcr_forms WHERE id = $id AND staff_name = '$username'");
    }
}

header("Location: my_ipcr.php");
exit();
