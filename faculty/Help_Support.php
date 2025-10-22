<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
include('../db/connection.php');

$profilePicture = isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] !== ''
    ? '../uploads/' . $_SESSION['profile_picture']
    : '../uploads/1.png';
$fullName = $_SESSION['full_name'] ?? 'Faculty';

// Process support form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate inputs
    if (empty($_POST['subject'])) {
        $errors[] = "Subject is required";
    }
    
    if (empty($_POST['message'])) {
        $errors[] = "Message is required";
    }
    
    // If no errors, save support request
    if (empty($errors)) {
        $userId = (int)$_SESSION['user_id'];
        $subject = mysqli_real_escape_string($conn, $_POST['subject']);
        $message = mysqli_real_escape_string($conn, $_POST['message']);
        $createdAt = date('Y-m-d H:i:s');
        
        // Check if support_requests table exists, create if not
        $tableCheck = "SHOW TABLES LIKE 'support_requests'";
        $tableResult = mysqli_query($conn, $tableCheck);
        
        if (mysqli_num_rows($tableResult) == 0) {
            // Create support_requests table
            $createTable = "CREATE TABLE support_requests (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                status VARCHAR(20) DEFAULT 'open'
            )";
            mysqli_query($conn, $createTable);
        }
        
        $insertQuery = "INSERT INTO support_requests (user_id, subject, message, created_at, status) 
                        VALUES ($userId, '$subject', '$message', '$createdAt', 'open')";
        
        $insertResult = mysqli_query($conn, $insertQuery);
        if ($insertResult) {
            $successMessage = "Your support request has been submitted successfully. We'll get back to you soon!";
        } else {
            $errors[] = "Error submitting support request: " . mysqli_error($conn);
        }
    }
}

// Check if faqs table exists
$faqs = [];
$tableCheck = "SHOW TABLES LIKE 'faqs'";
$tableResult = mysqli_query($conn, $tableCheck);

if ($tableResult && mysqli_num_rows($tableResult) > 0) {
    // Table exists, get FAQs from database
    $faqQuery = "SELECT * FROM faqs ORDER BY display_order ASC";
    $faqResult = mysqli_query($conn, $faqQuery);

    if ($faqResult && mysqli_num_rows($faqResult) > 0) {
        while ($row = mysqli_fetch_assoc($faqResult)) {
            $faqs[] = $row;
        }
    }
} else {
    // Table doesn't exist, create some default FAQs
    $createTable = "CREATE TABLE faqs (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        question VARCHAR(255) NOT NULL,
        answer TEXT NOT NULL,
        display_order INT(11) DEFAULT 0
    )";
    
    if (mysqli_query($conn, $createTable)) {
        // Insert default FAQs
        $defaultFaqs = [
            [
                'question' => 'How do I submit my IPCR?',
                'answer' => 'To submit your IPCR, navigate to the "Submit IPCR" section from the dashboard. Fill in all required fields including performance indicators, ratings, and comments. Upload any necessary documents and click submit.',
                'display_order' => 1
            ],
            [
                'question' => 'How can I check the status of my submission?',
                'answer' => 'You can check the status of your submissions by going to "My Submissions" in the dashboard. There you will see all your submissions with their current status (pending, approved, or rejected).',
                'display_order' => 2
            ],
            [
                'question' => 'How do I generate a PDF report?',
                'answer' => 'To generate a PDF report, go to the "Generate PDF" section. Select the submission period you want to include in the report and click the generate button. The PDF will be downloaded automatically.',
                'display_order' => 3
            ],
            [
                'question' => 'How do I change my password?',
                'answer' => 'To change your password, click on your profile picture in the top right corner and select "Change Password". Enter your current password and then your new password twice to confirm.',
                'display_order' => 4
            ]
        ];
        
        foreach ($defaultFaqs as $faq) {
            $question = mysqli_real_escape_string($conn, $faq['question']);
            $answer = mysqli_real_escape_string($conn, $faq['answer']);
            $order = $faq['display_order'];
            
            $insertFaq = "INSERT INTO faqs (question, answer, display_order) VALUES ('$question', '$answer', $order)";
            mysqli_query($conn, $insertFaq);
            
            // Add to our faqs array for display
            $faqs[] = [
                'question' => $faq['question'],
                'answer' => $faq['answer']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Help & Support - IPCR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/pcc1.png">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/all.min.css">
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
            --primary-color: #3a6ea5;
            --secondary-color: #6a9df7;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        html[data-theme='dark'] body {
            background-color: var(--bg-dark);
            color: var(--text-dark);
        }
        
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        [data-theme='dark'] .topbar {
            background-color: var(--sidebar-bg-dark);
        }
        
        .main-content {
            margin-left: 0;
            margin-top: 70px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin-right: auto;
            margin-left: auto;
        }
        
        .dark-toggle {
            cursor: pointer;
            font-size: 1.2rem;
            color: white;
            transition: transform 0.2s ease;
        }
        
        .dark-toggle:hover {
            transform: rotate(30deg);
        }
        
        .profile-img-sm {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .profile-img-sm:hover {
            transform: scale(1.05);
        }
        
        .pcc-logo {
            width: 40px;
            height: auto;
            border-radius: 10px;
        }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background-color: var(--card-light);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme='dark'] .card {
            background-color: var(--card-dark);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 110, 165, 0.25);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: var(--primary-color);
            color: white;
        }
        
        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(58, 110, 165, 0.25);
        }
        
        .contact-card {
            text-align: center;
            padding: 20px;
            border-radius: 16px;
            background-color: var(--card-light);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }
        
        [data-theme='dark'] .contact-card {
            background-color: var(--card-dark);
        }
        
        .contact-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .guide-step {
            display: flex;
            margin-bottom: 20px;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .step-content {
            flex-grow: 1;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: var(--primary-color);
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .topbar {
                padding: 0 10px;
            }
            
            .profile-img-sm {
                width: 30px;
                height: 30px;
            }
            
            .pcc-logo {
                width: 35px;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div style="display: flex; align-items: center;">
            <img src="../images/pcc1.png" alt="PCC Logo" class="pcc-logo">
            <div class="ms-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="faculty_dashboard.php" class="text-white">Dashboard</a></li>
                        <li class="breadcrumb-item active text-white">Help & Support</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="topbar-right">
            <span class="dark-toggle" id="toggleTheme" title="Toggle Dark Mode">
                <i class="fas fa-moon"></i>
            </span>
            <img src="<?= $profilePicture ?>" class="profile-img-sm" alt="Profile">
        </div>
    </div>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Help & Support</h2>
            <span class="badge bg-primary p-2">
                <i class="fas fa-calendar-day me-1"></i> <?= date('F j, Y') ?>
            </span>
        </div>

        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card p-4">
                    <h4 class="mb-4">Frequently Asked Questions</h4>
                    
                    <?php if (count($faqs) > 0): ?>
                        <div class="accordion" id="faqAccordion">
                            <?php foreach ($faqs as $index => $faq): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $index ?>">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" <?= $index > 0 ? 'aria-expanded="false"' : 'aria-expanded="true"' ?>>
                                            <?= htmlspecialchars($faq['question']) ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body">
                                            <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No FAQs available at the moment. Please check back later or contact support.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card p-4">
                    <h4 class="mb-4">User Guide</h4>
                    
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h5>Submitting IPCR</h5>
                            <p>Navigate to "Submit IPCR" from the dashboard. Fill in all required fields including performance indicators, ratings, and comments. Upload any necessary documents and submit for review.</p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h5>Viewing Submissions</h5>
                            <p>Go to "My Submissions" to view all your previous and current submissions. You can check the status of each submission (pending, approved, rejected) and view feedback from reviewers.</p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h5>Generating PDF Reports</h5>
                            <p>Use the "Generate PDF" feature to create downloadable reports of your approved submissions. Select the submission period and click generate to download the PDF file.</p>
                        </div>
                    </div>
                    
                    <div class="guide-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h5>Managing Your Account</h5>
                            <p>Update your profile information, change your password, and manage your account settings through the "My Account" and "Change Password" options in the dashboard.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card p-4 mb-4">
                    <h4 class="mb-4">Contact Support</h4>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </div>
                    </form>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5>Email Support</h5>
                    <p>support@ipcr.pcc.edu.ph</p>
                </div>
                
                <div class="contact-card mt-3">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h5>Hotline</h5>
                    <p>(02) 1234-5678</p>
                </div>
                
                <div class="contact-card mt-3">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Support Hours</h5>
                    <p>Monday - Friday<br>8:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle
        const toggleTheme = document.getElementById('toggleTheme');
        const htmlTag = document.documentElement;
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            htmlTag.setAttribute('data-theme', savedTheme);
            updateThemeIcon(savedTheme);
        }

        function updateThemeIcon(theme) {
            const icon = toggleTheme.querySelector('i');
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }

        toggleTheme.addEventListener('click', () => {
            const next = htmlTag.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            htmlTag.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
            updateThemeIcon(next);
        });
    </script>
</body>
</html>