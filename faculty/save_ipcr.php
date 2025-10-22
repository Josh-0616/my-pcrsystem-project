<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

include('../db/connection.php');

// Initialize response array
 $response = [
    'success' => false,
    'message' => ''
];

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Validate required fields
    $requiredFields = [
        'faculty_name', 'department', 'period', 'ratee_name', 'ratee_position', 
        'final_rating_program_head', 'ph_position'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/ipcr_attachments/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Prepare indicators data - FIX: Handle array-style inputs
    $indicators = [];
    
    // Check if indicators array exists and has data
    if (isset($_POST['indicator']) && is_array($_POST['indicator'])) {
        foreach ($_POST['indicator'] as $key => $indicator) {
            // Skip empty indicators
            if (empty(trim($indicator))) {
                continue;
            }
            
            // Get corresponding accomplishment
            $accomplishment = isset($_POST['accomplishment'][$key]) ? trim($_POST['accomplishment'][$key]) : '';
            
            // Both indicator and accomplishment are required
            if (empty($accomplishment)) {
                throw new Exception("Accomplishment is required for indicator: " . htmlspecialchars($indicator));
            }
            
            // Check if there is a file for this row
            $file_path = '';
            if (isset($_FILES['file']['name'][$key]) && !empty($_FILES['file']['name'][$key])) {
                $file = [
                    'name' => $_FILES['file']['name'][$key],
                    'type' => $_FILES['file']['type'][$key],
                    'tmp_name' => $_FILES['file']['tmp_name'][$key],
                    'error' => $_FILES['file']['error'][$key],
                    'size' => $_FILES['file']['size'][$key]
                ];
                
                // Validate file size (10MB max)
                $maxSize = 10 * 1024 * 1024; // 10MB in bytes
                if ($file['size'] > $maxSize) {
                    throw new Exception("File for indicator " . ($key + 1) . " exceeds 10MB limit.");
                }
                
                // Get file extension
                $fileInfo = pathinfo($file['name']);
                $fileExtension = strtolower($fileInfo['extension']);
                
                // Generate unique filename with original extension
                $fileName = 'ipcr_' . $_SESSION['id'] . '_indicator_' . $key . '_' . time() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;
                
                // Move uploaded file to destination
                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    throw new Exception("Failed to upload file for indicator " . ($key + 1));
                }
                
                $file_path = 'uploads/ipcr_attachments/' . $fileName;
            }
            
            $indicators[] = [
                'indicator' => $indicator,
                'accomplishment' => $accomplishment,
                'q' => isset($_POST['q'][$key]) ? (float)$_POST['q'][$key] : 0,
                'e' => isset($_POST['e'][$key]) ? (float)$_POST['e'][$key] : 0,
                't' => isset($_POST['t'][$key]) ? (float)$_POST['t'][$key] : 0,
                'a' => isset($_POST['a'][$key]) ? (float)$_POST['a'][$key] : 0,
                'remarks' => isset($_POST['remarks'][$key]) ? $_POST['remarks'][$key] : '',
                'file_path' => $file_path
            ];
        }
    }
    
    if (empty($indicators)) {
        throw new Exception("No valid indicators provided");
    }
    
    // Calculate average rating
    $totalRating = 0;
    $count = 0;
    foreach ($indicators as $indicator) {
        $avg = ($indicator['q'] + $indicator['e'] + $indicator['t']) / 3;
        $totalRating += $avg;
        $count++;
    }
    $finalAvgRating = $count > 0 ? round($totalRating / $count, 2) : 0;
    
    // Determine adjectival rating
    if ($finalAvgRating >= 4.5) {
        $adjectivalRating = "Outstanding";
    } elseif ($finalAvgRating >= 3.5) {
        $adjectivalRating = "Very Satisfactory";
    } elseif ($finalAvgRating >= 2.5) {
        $adjectivalRating = "Satisfactory";
    } elseif ($finalAvgRating >= 1.5) {
        $adjectivalRating = "Unsatisfactory";
    } else {
        $adjectivalRating = "Poor";
    }
    
    // Prepare data for insertion
    $facultyId = $_SESSION['id'];
    $facultyName = $_POST['faculty_name'];
    $department = $_POST['department'];
    $period = $_POST['period'];
    $indicatorsJson = json_encode($indicators);
    $attachmentPath = NULL; // We're storing file paths in the indicators JSON
    $rateeName = $_POST['ratee_name'];
    $rateePosition = $_POST['ratee_position'];
    $rateeDate = $_POST['ratee_date'] ?? date('Y-m-d');
    $raterName = $_POST['rater_name'] ?? ''; 
    $raterPosition = $_POST['rater_position'] ?? ''; 
    $raterDate = $_POST['rater_date'] ?? date('Y-m-d');
    $finalRatingProgramHead = $_POST['final_rating_program_head'];
    $phPosition = $_POST['ph_position'];
    $phDate = $_POST['ph_date'] ?? date('Y-m-d');
    $status = 'Pending';
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO ipcr_submissions (
        faculty_id, faculty_name, department, period, indicators_json, 
        final_avg_rating, adjectival_rating, attachment_path,
        ratee_name, ratee_position, ratee_date,
        rater_name, rater_position, rater_date,
        final_rating_program_head, ph_position, ph_date,
        status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->bind_param(
        "issssddsssssssssss",
        $facultyId, $facultyName, $department, $period, $indicatorsJson,
        $finalAvgRating, $adjectivalRating, $attachmentPath,
        $rateeName, $rateePosition, $rateeDate,
        $raterName, $raterPosition, $raterDate,
        $finalRatingProgramHead, $phPosition, $phDate,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Database error: " . $stmt->error);
    }
    
    // Create notification for admin
    $notificationStmt = $conn->prepare("INSERT INTO admindashboard_notification (
        submission_id, sender_id, message, status, created_at
    ) VALUES (?, ?, ?, 'unread', NOW())");
    
    $submissionId = $stmt->insert_id;
    $message = "New IPCR submission from $facultyName in $department department";
    $notificationStmt->bind_param("iis", $submissionId, $facultyId, $message);
    $notificationStmt->execute();
    
    // Set success response
    $response['success'] = true;
    $response['message'] = "IPCR submitted successfully!";
    
} catch (Exception $e) {
    // Set error response
    $response['message'] = $e->getMessage();
    
    // Log error
    error_log("IPCR Submission Error: " . $e->getMessage());
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>