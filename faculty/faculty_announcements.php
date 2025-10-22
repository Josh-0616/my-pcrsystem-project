<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

include('../db/connection.php');
$facultyUsername = $_SESSION['username'];

$query = "
    SELECT a.*, 
           IF(r.id IS NULL, 0, 1) AS is_read
    FROM announcements a
    LEFT JOIN announcement_reads r 
        ON a.id = r.announcement_id AND r.faculty_username = '$facultyUsername'
    ORDER BY a.created_at DESC
";
$result = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Faculty Announcements</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/all.min.css">
  <script src="../js/bootstrap.bundle.min.js"></script>
  <style>
    body {
      background-color: #f0f2f5;
      font-family: Arial, sans-serif;
    }
    .container {
      max-width: 900px;
      margin-top: 60px;
    }
    .card {
      border-radius: 12px;
      margin-bottom: 20px;
    }
    .badge-deadline {
      background-color: #dc3545;
    }
    .unread {
      background-color: #fff3cd;
    }
    .read {
      background-color: #e9ecef;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2 class="mb-4"><i class="fas fa-bullhorn"></i> Announcements</h2>

    <?php if (mysqli_num_rows($result) > 0): ?>
      <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <div class="card <?= $row['is_read'] ? 'read' : 'unread' ?>">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($row['title']) ?></h5>
            <p class="card-text"><?= nl2br(htmlspecialchars($row['content'])) ?></p>
            <div class="d-flex justify-content-between">
              <small class="text-muted">📅 Posted: <?= date("F d, Y", strtotime($row['created_at'])) ?>
              </small>
              <span class="badge badge-deadline text-white">⏳ Deadline: <?= date("F d, Y", strtotime($row['deadline'])) ?></span>
            </div>
            <?php if (!$row['is_read']): ?>
              <a href="mark_announcement_read.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary mt-3">✉ Mark as Read</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="alert alert-info">No announcements found.</div>
    <?php endif; ?>

    <a href="faculty_dashboard.php" class="btn btn-secondary mt-4"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
  </div>
</body>
</html>
