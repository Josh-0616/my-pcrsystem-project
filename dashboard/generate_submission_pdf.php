<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include('../db/connection.php');

// Get submission ID
if (!isset($_GET['id'])) {
    echo "Invalid Request.";
    exit();
}
$id = $_GET['id'];

// Fetch submission data
$stmt = $conn->prepare("SELECT * FROM ipcr_submissions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo "Submission not found.";
    exit();
}

$indicators = json_decode($data['indicators_json'], true);

// Include TCPDF library
require_once('../tcpdf/tcpdf.php');

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('IPCR System');
$pdf->SetAuthor('Philippine Countryville College, Inc.');
$pdf->SetTitle('IPCR Submission - ' . $data['faculty_name']);
$pdf->SetSubject('Individual Performance Commitment and Review');

// Add a page
$pdf->AddPage();

// Set content
$html = '
<div style="font-family: Arial; font-size: 12px;">
    <h2 style="text-align: center;">Individual Performance Commitment and Review (IPCR)</h2>
    <p style="margin-top: 20px;">
        I, <strong>' . htmlspecialchars($data['faculty_name']) . '</strong>,
        of the <strong>' . htmlspecialchars($data['department']) . '</strong>
        of Philippine Countryville College, Inc., commit to deliver and agree to be rated on the following targets in accordance with the indicated measure for the period of
        <strong>' . htmlspecialchars($data['period']) . '</strong>.
    </p>
    <p style="text-align: right; margin-top: 20px;">Instructor Signature: ____________________ Date: ___________</p>
    <div style="margin-top: 20px;">
        <strong>Reviewed by:</strong> _____________________<br>
        <strong>Program Head:</strong> _____________________<br>
        <strong>Date:</strong> ___________
    </div>
    <div style="margin-top: 20px;">
        <strong>Rating Scale:</strong>
        <ul>
            <li>5 – Outstanding</li>
            <li>4 – Very Satisfactory</li>
            <li>3 – Satisfactory</li>
            <li>2 – Unsatisfactory</li>
            <li>1 – Poor</li>
        </ul>
    </div>
    <table border="1" style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th style="padding: 8px; text-align: center;">SUCCESS INDICATORS</th>
                <th style="padding: 8px; text-align: center;">Accomplishment</th>
                <th style="padding: 8px; text-align: center;">Q</th>
                <th style="padding: 8px; text-align: center;">E</th>
                <th style="padding: 8px; text-align: center;">T</th>
                <th style="padding: 8px; text-align: center;">A</th>
                <th style="padding: 8px; text-align: center;">Remarks</th>
            </tr>
        </thead>
        <tbody>';

foreach ($indicators as $row) {
    $html .= '
            <tr>
                <td style="padding: 8px;">' . htmlspecialchars($row['indicator']) . '</td>
                <td style="padding: 8px;">' . htmlspecialchars($row['accomplishment']) . '</td>
                <td style="padding: 8px; text-align: center;">' . htmlspecialchars($row['q']) . '</td>
                <td style="padding: 8px; text-align: center;">' . htmlspecialchars($row['e']) . '</td>
                <td style="padding: 8px; text-align: center;">' . htmlspecialchars($row['t']) . '</td>
                <td style="padding: 8px; text-align: center;">' . htmlspecialchars($row['a']) . '</td>
                <td style="padding: 8px;">' . htmlspecialchars($row['remarks']) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    <div style="margin-top: 30px;">
        <div style="display: inline-block; width: 48%;">
            <strong>Final Average Rating:</strong> ' . htmlspecialchars($data['final_avg_rating']) . '
        </div>
        <div style="display: inline-block; width: 48%; margin-left: 4%;">
            <strong>Adjectival Rating:</strong> ' . htmlspecialchars($data['adjectival_rating']) . '
        </div>
    </div>
    <div style="margin-top: 40px;">
        <div style="display: inline-block; width: 48%;">
            <h6>Name and Signature of Ratee:</h6>
            <p><strong>Name:</strong> ' . htmlspecialchars($data['ratee_name']) . '</p>
            <p><strong>Position:</strong> ' . htmlspecialchars($data['ratee_position']) . '</p>
            <p><strong>Date:</strong> ' . htmlspecialchars($data['ratee_date']) . '</p>
        </div>
    </div>
    <div style="margin-top: 40px;">
        <h6>Final Rating by Program Head:</h6>
        <p><strong>Rating:</strong> ' . htmlspecialchars($data['final_rating_program_head']) . '</p>
        <p><strong>Position:</strong> ' . htmlspecialchars($data['ph_position']) . '</p>
        <p><strong>Date:</strong> ' . htmlspecialchars($data['ph_date']) . '</p>
    </div>
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        Generated on ' . date('F j, Y, g:i A') . '
    </div>
</div>';

// Write HTML content to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('IPCR_Submission_' . $id . '.pdf', 'D');
?>