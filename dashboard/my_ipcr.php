<?php
session_start();
include('db/connection.php');

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My IPCR Submissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">
<div class="container mt-5">
    <h3 class="text-primary mb-4">My IPCR Submissions</h3>
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-primary">
            <tr>
                <th>#</th>
                <th>Key Result Area</th>
                <th>Objectives</th>
                <th>Success Indicators</th>
                <th>Accomplishments</th>
                <th>Rating</th>
                <th>Remarks</th>
                <th>Rated By</th>
                <th>Date Submitted</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $query = "SELECT * FROM ipcr_forms WHERE Staff_name = '$username' ORDER BY submitted_at DESC";
        $result = mysqli_query($conn, $query);
        $count = 1;

        while ($row = mysqli_fetch_assoc($result)) {
            $ratedBy = !empty($row['rated_by']) ? $row['rated_by'] : '<span class="text-muted">Not yet rated</span>';
            echo "<tr>
                    <td>{$count}</td>
                    <td>{$row['kra']}</td>
                    <td>{$row['objectives']}</td>
                    <td>{$row['indicators']}</td>
                    <td>{$row['accomplishments']}</td>
                    <td>{$row['rating']}</td>
                    <td>{$row['remarks']}</td>
                    <td>{$ratedBy}</td>
                    <td>{$row['submitted_at']}</td>
                    <td>
                        <a href='edit_ipcr.php?id={$row['id']}' class='btn btn-sm btn-warning me-1' title='Edit'><i class='fas fa-edit'></i></a>
                        <a href='delete_ipcr.php?id={$row['id']}' class='btn btn-sm btn-danger me-1' title='Delete' onclick=\"return confirm('Are you sure you want to delete this IPCR?');\">
                            <i class='fas fa-trash-alt'></i>
                        </a>
                        <a href='generate_pdf.php?id={$row['id']}' class='btn btn-sm btn-secondary' title='Download PDF' target='_blank'>
                            <i class='fas fa-file-pdf'></i>
                        </a>
                    </td>
                </tr>";
            $count++;
        }
        ?>
        </tbody>
    </table>
    <a href="<?php echo $_SESSION['role'] === 'faculty' ? 'faculty_dashboard.php' : 'Staff_dashboard.php'; ?>" class="btn btn-secondary mt-3">Back</a>
</div>
</body>
</html>
