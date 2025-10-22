<?php
include('db/connection.php');

$form_id = $_POST['form_id'];
$rating = $_POST['rating'];

$stmt = $conn->prepare("UPDATE ipcr_forms SET rating = ? WHERE id = ?");
$stmt->bind_param("ii", $rating, $form_id);
if ($stmt->execute()) {
    echo "<script>alert('Rating saved successfully!'); window.location.href='rate_staff.php';</script>";
} else {
    echo "<script>alert('Failed to save rating.'); window.location.href='rate_staff.php';</script>";
}
?>
