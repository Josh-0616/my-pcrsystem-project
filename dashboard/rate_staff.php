<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db/connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Staff - IPCR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Rate Staff Submissions</h3>
        <a href="admin_dashboard.php" class="btn btn-secondary">← Back</a>
    </div>

    <?php
    $query = "SELECT * FROM ipcr_forms WHERE rating IS NULL";
    $result = $conn->query($query);

    if ($result->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Objectives</th>
                    <th>Accomplishments</th>
                    <th>Rate (1-5)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <form method="POST" action="save_rating.php">
                        <tr>
                            <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['objectives']); ?></td>
                            <td><?php echo htmlspecialchars($row['accomplishments']); ?></td>
                            <td>
                                <select name="rating" class="form-select" required>
                                    <option value="">Select</option>
                                    <option value="5">5 - Outstanding</option>
                                    <option value="4">4 - Very Satisfactory</option>
                                    <option value="3">3 - Satisfactory</option>
                                    <option value="2">2 - Unsatisfactory</option>
                                    <option value="1">1 - Poor</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="form_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm">Save Rating</button>
                            </td>
                        </tr>
                    </form>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No unrated submissions found.</div>
    <?php endif; ?>
</div>
</body>
</html>
