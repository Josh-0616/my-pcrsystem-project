<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include '../db/connection.php';

// Handle form submission
 $message = '';
 $messageType = '';

// Handle delete code request via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_code') {
    $code_id = intval($_POST['code_id']);
    $delete_stmt = $conn->prepare("DELETE FROM codes_registration WHERE id = ?");
    $delete_stmt->bind_param("i", $code_id);
    
    header('Content-Type: application/json');
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expiry_days = isset($_POST['expiry_days']) ? (int)$_POST['expiry_days'] : 7;
    $code_count = isset($_POST['code_count']) ? (int)$_POST['code_count'] : 1;
    
    if ($expiry_days > 0 && $code_count > 0) {
        $generated_codes = [];
        $success_count = 0;
        
        // Calculate expiry date
        $expiry_date = date('Y-m-d H:i:s', strtotime("+$expiry_days days"));
        
        // Generate and insert codes
        for ($i = 0; $i < $code_count; $i++) {
            // Generate a random code
            $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
            
            // Insert into database - using the correct table name
            $stmt = $conn->prepare("INSERT INTO codes_registration (code, expiry_date) VALUES (?, ?)");
            $stmt->bind_param("ss", $code, $expiry_date);
            
            if ($stmt->execute()) {
                $generated_codes[] = $code;
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $message = "Successfully generated $success_count registration code(s) that expire on " . date('F j, Y', strtotime($expiry_date));
            $messageType = "success";
        } else {
            $message = "Failed to generate registration codes. Please try again.";
            $messageType = "danger";
        }
    } else {
        $message = "Please enter valid values for expiry days and code count.";
        $messageType = "warning";
    }
}

// Get existing codes - using the correct table name
 $existing_codes = [];
 $stmt = $conn->prepare("SELECT * FROM codes_registration ORDER BY created_at DESC LIMIT 50");
 $stmt->execute();
 $result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $existing_codes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Generate Registration Codes - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="../images/pcc1.png">
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="../js/bootstrap.bundle.min.js"></script>
  <style>
    :root {
      --bg-light: #f0f2f5;
      --text-light: #333;
      --card-light: #ffffff;
      --sidebar-bg-light: #3a6ea5;
      --bg-dark: #1e1e2f;
      --text-dark: #dcdcdc;
      --card-dark: #2c2c3e;
      --sidebar-bg-dark: #252536;
    }
    html[data-theme='dark'] body {
      background-color: var(--bg-dark);
      color: var(--text-dark);
    }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: var(--bg-light);
      color: var(--text-light);
    }
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      height: 70px;
      background-color: var(--sidebar-bg-light);
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 15px;
      z-index: 1000;
    }
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    [data-theme='dark'] .topbar {
      background-color: var(--sidebar-bg-dark);
    }
    .main-content {
      margin-left: 0;
      margin-top: 70px;
      padding: 20px;
      transition: margin-left 0.3s ease;
    }
    .card {
      background-color: var(--card-light);
      border-radius: 16px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      padding: 20px;
      margin-bottom: 20px;
    }
    [data-theme='dark'] .card {
      background-color: var(--card-dark);
    }
    .code-card {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 15px;
      border-left: 4px solid #3a6ea5;
    }
    [data-theme='dark'] .code-card {
      background-color: #2c2c3e;
    }
    .code-value {
      font-family: monospace;
      font-size: 1.2rem;
      font-weight: bold;
      color: #3a6ea5;
      letter-spacing: 1px;
    }
    .back-btn {
      color: white;
      font-size: 1.2rem;
      cursor: pointer;
      transition: all 0.2s;
    }
    .back-btn:hover {
      transform: scale(1.1);
    }
    .delete-btn {
      color: #dc3545;
      cursor: pointer;
      transition: all 0.2s;
    }
    .delete-btn:hover {
      transform: scale(1.1);
    }
    .table-responsive {
      overflow-y: auto;
      max-height: calc(100vh - 300px);
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
      .main-content {
        padding: 15px;
      }
      .topbar {
        padding: 0 10px;
      }
      .topbar-left {
        gap: 10px;
      }
      .table-responsive {
        max-height: calc(100vh - 250px);
      }
    }
  </style>
</head>
<body>
<div class="topbar">
  <div class="topbar-left">
    <a href="manage_users.php" class="back-btn" title="Back to Manage Users">
      <i class="fas fa-arrow-left"></i>
    </a>
    <span style="font-weight: bold;">Generate Registration Codes</span>
  </div>
</div>

<div class="main-content">
  <h2 class="mb-4">Generate Registration Codes</h2>
  
  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
      <?= $message ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  
  <div class="card">
    <h4 class="mb-3">Create New Registration Codes</h4>
    <form method="POST" action="">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="expiry_days" class="form-label">Expiry Days</label>
          <input type="number" class="form-control" id="expiry_days" name="expiry_days" min="1" max="365" value="7" required>
          <div class="form-text">Number of days until the code expires</div>
        </div>
        <div class="col-md-6 mb-3">
          <label for="code_count" class="form-label">Number of Codes</label>
          <input type="number" class="form-control" id="code_count" name="code_count" min="1" max="50" value="1" required>
          <div class="form-text">How many codes to generate</div>
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Generate Codes</button>
    </form>
  </div>
  
  <?php if (isset($generated_codes) && !empty($generated_codes)): ?>
    <div class="card">
      <h4 class="mb-3">Generated Registration Codes</h4>
      <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Please save these codes securely. They will not be shown again.
      </div>
      <?php foreach ($generated_codes as $code): ?>
        <div class="code-card">
          <div class="d-flex justify-content-between align-items-center">
            <span class="code-value"><?= $code ?></span>
            <button class="btn btn-sm btn-outline-secondary" onclick="copyCode('<?= $code ?>')">
              <i class="fas fa-copy me-1"></i> Copy
            </button>
          </div>
          <small class="text-muted">Expires on <?= date('F j, Y', strtotime("+$expiry_days days")) ?></small>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  
  <div class="card">
    <h4 class="mb-3">Existing Registration Codes</h4>
    <?php if (empty($existing_codes)): ?>
      <p class="text-muted">No registration codes found.</p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Code</th>
              <th>Status</th>
              <th>Expiry Date</th>
              <th>Created</th>
              <th>Used By</th>
              <th>Used Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="codesTableBody">
            <?php foreach ($existing_codes as $code): ?>
              <tr id="code-row-<?= $code['id'] ?>">
                <td class="code-value"><?= $code['code'] ?></td>
                <td>
                  <span class="badge bg-<?= 
                    $code['is_used'] ? 'success' : 
                    (strtotime($code['expiry_date']) < time() ? 'danger' : 'primary') 
                  ?>">
                    <?= 
                      $code['is_used'] ? 'Used' : 
                      (strtotime($code['expiry_date']) < time() ? 'Expired' : 'Active') 
                    ?>
                  </span>
                </td>
                <td><?= date('M j, Y', strtotime($code['expiry_date'])) ?></td>
                <td><?= date('M j, Y', strtotime($code['created_at'])) ?></td>
                <td><?= $code['used_by'] ? getUserById($conn, $code['used_by']) : '-' ?></td>
                <td><?= $code['used_date'] ? date('M j, Y', strtotime($code['used_date'])) : '-' ?></td>
                <td>
                  <a href="#" class="delete-btn" title="Delete Code" onclick="deleteCode(<?= $code['id'] ?>)">
                    <i class="fas fa-trash-alt"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Copy code to clipboard
  function copyCode(code) {
    navigator.clipboard.writeText(code).then(function() {
      // Successfully copied - no notification needed
    }, function(err) {
      console.error('Could not copy code: ', err);
    });
  }
  
  // Delete code using AJAX
  function deleteCode(codeId) {
    // Store current scroll position
    const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
    
    const formData = new FormData();
    formData.append('action', 'delete_code');
    formData.append('code_id', codeId);
    
    fetch('generate_code_registration.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove the row from the table
        const row = document.getElementById('code-row-' + codeId);
        if (row) {
          // Get row height before removal
          const rowHeight = row.offsetHeight;
          
          // Remove the row
          row.remove();
          
          // Restore scroll position
          window.scrollTo(0, scrollPosition);
          
          // Check if table is empty
          const tbody = document.getElementById('codesTableBody');
          if (tbody.children.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No registration codes found.</td></tr>';
          }
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
    });
  }
</script>

<?php
// Helper function to get user by ID
function getUserById($conn, $userId) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return 'Unknown';
}
?>
</body>
</html>