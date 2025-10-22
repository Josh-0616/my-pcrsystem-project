<?php
include '../db/connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    mysqli_query($conn, "DELETE FROM announcements WHERE id = $id");
}
header("Location: announcements.php");
exit();
?>
