<?php
session_start();
require_once('../tcpdf/tcpdf.php');
include('../db/connection.php');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch latest submission
$sql = "SELECT * FROM ipcr_submissions WHERE user_id = ? ORDER BY date_submitted DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die('No IPCR data found.');
}

// Extract data
$faculty_name = $row['faculty_name'];
$department = $row['department'];
$period = $row['period'];
$indicators = json_decode($row['indicators'], true);
$final_avg = $row['final_avg_rating'];
$adj_rating = $row['adjectival_rating'];
$ratee_name = $row['ratee_name'];
$ratee_position = $row['ratee_position'];
$ratee_date = $row['ratee_date'];
$rater_name = $row['rater_name'];
$rater_position = $row['rater_position'];
$rater_date = $row['rater_date'];
$ph_rating = $row['final_rating_program_head'];
$ph_position = $row['ph_position'];
$ph_date = $row['ph_date'];

// TCPDF config
$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 15, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// HEADER
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Republic of the Philippines', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'PHILIPPINE COUNTRYVILLE COLLEGE', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, 'OFFICE OF THE ACADEMIC AFFAIRS', 0, 1, 'C');
$pdf->Ln(4);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)', 0, 1, 'C');
$pdf->Ln(3);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(90, 6, 'Name of Faculty: ' . $faculty_name, 0, 0, 'L');
$pdf->Cell(100, 6, 'Department: ' . $department, 0, 0, 'L');
$pdf->Cell(0, 6, 'Rating Period: ' . $period, 0, 1, 'L');
$pdf->Ln(3);

// TABLE HEADER
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(40, 10, 'KRA', 1, 0, 'C');
$pdf->Cell(50, 10, 'Objectives', 1, 0, 'C');
$pdf->Cell(55, 10, 'Success Indicators', 1, 0, 'C');
$pdf->Cell(55, 10, 'Actual Accomplishments', 1, 0, 'C');
$pdf->Cell(12, 10, 'Q', 1, 0, 'C');
$pdf->Cell(12, 10, 'E', 1, 0, 'C');
$pdf->Cell(12, 10, 'T', 1, 0, 'C');
$pdf->Cell(15, 10, 'Aver.', 1, 0, 'C');
$pdf->Cell(0, 10, 'Remarks', 1, 1, 'C');

// TABLE CONTENT
$pdf->SetFont('helvetica', '', 9);
if ($indicators && is_array($indicators)) {
    foreach ($indicators as $row) {
        $pdf->MultiCell(40, 10, $row['kra'], 1, 'L', 0, 0);
        $pdf->MultiCell(50, 10, $row['objectives'], 1, 'L', 0, 0);
        $pdf->MultiCell(55, 10, $row['success_indicators'], 1, 'L', 0, 0);
        $pdf->MultiCell(55, 10, $row['actual_accomplishments'], 1, 'L', 0, 0);
        $pdf->Cell(12, 10, $row['q'], 1, 0, 'C');
        $pdf->Cell(12, 10, $row['e'], 1, 0, 'C');
        $pdf->Cell(12, 10, $row['t'], 1, 0, 'C');
        $pdf->Cell(15, 10, $row['average'], 1, 0, 'C');
        $pdf->MultiCell(0, 10, $row['remarks'], 1, 'L', 1);
    }
} else {
    $pdf->Cell(0, 10, 'No performance indicators available.', 1, 1, 'C');
}

$pdf->Ln(6);

// FINAL RATINGS
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(70, 7, 'Final Average Rating: ' . $final_avg, 0, 0, 'L');
$pdf->Cell(70, 7, 'Adjectival Rating: ' . $adj_rating, 0, 1, 'L');
$pdf->Ln(6);

// SIGNATURE SECTION
$pdf->SetFont('helvetica', '', 9);
$pdf->MultiCell(90, 18, "Ratee:\n$ratee_name\nPosition: $ratee_position\nDate: $ratee_date", 1, 'L', 0, 0);
$pdf->MultiCell(90, 18, "Rater:\n$rater_name\nPosition: $rater_position\nDate: $rater_date", 1, 'L', 0, 0);
$pdf->MultiCell(90, 18, "Approved by:\n$ph_rating\nPosition: $ph_position\nDate: $ph_date", 1, 'L', 1);

// Output
$pdf->Output('ipcr_form.pdf', 'I');
