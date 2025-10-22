<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start(); // if you're using session, don't forget this

include('../db/connection.php');
include('../db/connection.php');



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the user ID from the session
    $user_id = $_SESSION['user_id'];

    // Collect data from the form
    $employee_name = $_POST['employee_name'];
    $position = $_POST['position'];
    $department = $_POST['department'];
    $kra = $_POST['kra'];
    $objectives = $_POST['objectives'];
    $indicators = $_POST['indicators'];
    $accomplishments = $_POST['accomplishments'];
    $q_rating = $_POST['q_rating'];
    $e_rating = $_POST['e_rating'];
    $t_rating = $_POST['t_rating'];
    $remarks = $_POST['remarks'];

    // Compute A rating (average of Q, E, T)
    $a_rating = round((($q_rating + $e_rating + $t_rating) / 3), 2);

    // For now, final_average = a_rating (you can later compute average of all A_ratings in view file)
    $final_average = $a_rating;

    // Determine adjectival rating
    if ($final_average >= 4.50) {
        $adjectival_rating = "Outstanding";
    } elseif ($final_average >= 3.50) {
        $adjectival_rating = "Very Satisfactory";
    } elseif ($final_average >= 2.50) {
        $adjectival_rating = "Satisfactory";
    } elseif ($final_average >= 1.50) {
        $adjectival_rating = "Unsatisfactory";
    } else {
        $adjectival_rating = "Poor";
    }

    $rater_comments = ""; // Leave blank for now or allow rater to edit later
    $rating = 0; // Default, to be updated when rated

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO ipcr_forms (
        user_id, employee_name, position, department, kra, objectives, indicators,
        accomplishments, q_rating, e_rating, t_rating, a_rating, final_average,
        adjectival_rating, rater_comments, rating, remarks
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "isssssssssssdsisi",
        $user_id, $employee_name, $position, $department, $kra, $objectives, $indicators,
        $accomplishments, $q_rating, $e_rating, $t_rating, $a_rating, $final_average,
        $adjectival_rating, $rater_comments, $rating, $remarks
    );

    if ($stmt->execute()) {
        echo "<script>alert('IPCR form submitted successfully.'); window.location.href='my_ipcr.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
