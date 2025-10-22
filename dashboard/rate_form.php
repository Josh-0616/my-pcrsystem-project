<?php
include '../db_connect.php';
session_start();

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'];
$query = "SELECT * FROM ipcr_forms WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Rate PCR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h4>Rating for <?= $data['employee_name'] ?></h4>
    <form action="save_rating.php" method="POST">
        <input type="hidden" name="id" value="<?= $data['id'] ?>">
        <div class="mb-3">
            <label class="form-label">Rating (1-5)</label>
            <input type="number" name="rating" class="form-control" min="1" max="5" value="<?= $data['rating'] ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save Rating</button>
    </form>
</div>
</body>
</html>
