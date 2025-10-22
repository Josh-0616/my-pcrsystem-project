<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');
$facultyName = $_SESSION['full_name'];
$query = $conn->prepare("SELECT * FROM ipcr_submissions WHERE faculty_name = ? ORDER BY date_submitted DESC");
$query->bind_param("s", $facultyName);
$query->execute();
$result = $query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Submissions - PCR</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/all.min.css">
  <script src="../js/bootstrap.bundle.min.js"></script>
  <style>
    body {
      padding: 40px;
      background: #f9f9f9;
    }
    .accordion-button:not(.collapsed) {
      background-color: #4a6cf7;
      color: white;
    }
    .action-buttons {
      display: flex;
      gap: 10px;
    }
  </style>
</head>
<body>
<div class="container">
  <h3 class="mb-4">📄 My PCR Submissions</h3>
  
  <!-- Add a table view of submissions with direct links -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">My Submissions</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Period</th>
              <th>Department</th>
              <th>Date Submitted</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['period']) ?></td>
              <td><?= htmlspecialchars($row['department']) ?></td>
              <td><?= date('F d, Y', strtotime($row['date_submitted'])) ?></td>
              <td>
                <span class="badge bg-<?= 
                  $row['status'] === 'Pending' ? 'warning' : 
                  ($row['status'] === 'Approved' ? 'success' : 
                  ($row['status'] === 'Declined' ? 'danger' : 'info')) 
                ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <a href="../admin/view_submission.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> View
                  </a>
                  <a href="../ipcr_print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-print"></i> Print
                  </a>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  
  <!-- Keep the accordion view as an alternative -->
  <h4 class="mb-3">Detailed View</h4>
  <div class="accordion" id="submissionsAccordion">
    <?php
    // Reset the result pointer
    $result->data_seek(0);
    $i = 0;
    while ($row = $result->fetch_assoc()):
      $indicators = json_decode($row['indicators'], true);
    ?>
    <div class="accordion-item">
      <h2 class="accordion-header" id="heading<?= $i ?>">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>">
          Submission #<?= $row['id'] ?> - <?= htmlspecialchars($row['period']) ?> 
          <span class="badge bg-<?= 
            $row['status'] === 'Pending' ? 'warning' : 
            ($row['status'] === 'Approved' ? 'success' : 
            ($row['status'] === 'Declined' ? 'danger' : 'info')) 
          ?> ms-2">
            <?= htmlspecialchars($row['status']) ?>
          </span>
          <div class="ms-auto action-buttons">
            <a href="../admin/view_submission.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
              <i class="fas fa-eye"></i> View
            </a>
            <span class="btn btn-sm btn-outline-primary" onclick="window.open('../ipcr_print.php?id=<?= $row['id'] ?>', '_blank')">
              <i class="fas fa-print"></i> Print
            </span>
          </div>
        </button>
      </h2>
      <div id="collapse<?= $i ?>" class="accordion-collapse collapse" data-bs-parent="#submissionsAccordion">
        <div class="accordion-body">
          <p><strong>Department:</strong> <?= htmlspecialchars($row['department']) ?></p>
          <p><strong>Date Submitted:</strong> <?= date('F d, Y', strtotime($row['date_submitted'])) ?></p>
          <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
              <tr>
                <th>No.</th>
                <th>Success Indicator</th>
                <th>Q</th>
                <th>E</th>
                <th>T</th>
                <th>A</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($indicators as $key => $item): ?>
                <tr>
                  <td><?= $key + 1 ?></td>
                  <td><?= htmlspecialchars($item['indicator']) ?></td>
                  <td><?= htmlspecialchars($item['q']) ?></td>
                  <td><?= htmlspecialchars($item['e']) ?></td>
                  <td><?= htmlspecialchars($item['t']) ?></td>
                  <td><?= htmlspecialchars($item['a']) ?></td>
                  <td><?= htmlspecialchars($item['remarks']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <p><strong>Final Rating:</strong> <?= htmlspecialchars($row['final_avg_rating']) ?> | 
             <strong>Adjectival Rating:</strong> <?= htmlspecialchars($row['adjectival_rating']) ?></p>
        </div>
      </div>
    </div>
    <?php $i++; endwhile; ?>
  </div>
</div>
</body>
</html>