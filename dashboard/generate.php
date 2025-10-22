<?php
session_start();
require_once('../tcpdf/tcpdf.php');
include('../db/connection.php');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

$sql = "SELECT * FROM ipcr_submissions WHERE user_id = ? ORDER BY date_submitted DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die('Wala pa kay IPCR submission.');
}

// Extract data
$faculty_name = $row['faculty_name'];
$department = $row['department'];
$period = $row['period'];
$indicators_json = $row['indicators'];
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
$date_submitted = date('F d, Y', strtotime($row['date_submitted']));

// Decode indicators
$indicators = json_decode($indicators_json, true);

// Create PDF
$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 15, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Title/Header
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'PHILIPPINE COUNTRYVILLE COLLEGE', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)', 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, "Department: $department", 0, 1, 'C');
$pdf->Ln(4);

// Employee Info
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(100, 6, "Faculty Name: $faculty_name", 0, 'L', 0, 0);
$pdf->MultiCell(100, 6, "Rating Period: $period", 0, 'L', 0, 0);
$pdf->MultiCell(80, 6, "Date Submitted: $date_submitted", 0, 'L', 1);
$pdf->Ln(2);

// Table Layout
$tbl = '
<style>
    th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }
    td {
        font-size: 9pt;
    }
</style>
<table border="1" cellpadding="3">
<thead>
<tr>
    <th width="12%">KRA</th>
    <th width="14%">Objectives</th>
    <th width="16%">Success Indicators</th>
    <th width="18%">Actual Accomplishments</th>
    <th width="5%">Q</th>
    <th width="5%">E</th>
    <th width="5%">T</th>
    <th width="5%">Ave</th>
    <th width="20%">Remarks</th>
</tr>
</thead>
<tbody>';

if ($indicators && is_array($indicators)) {
    foreach ($indicators as $item) {
        $tbl .= '<tr>
            <td>' . $item['kra'] . '</td>
            <td>' . $item['objectives'] . '</td>
            <td>' . $item['success_indicators'] . '</td>
            <td>' . $item['actual_accomplishments'] . '</td>
            <td align="center">' . $item['q'] . '</td>
            <td align="center">' . $item['e'] . '</td>
            <td align="center">' . $item['t'] . '</td>
            <td align="center">' . $item['average'] . '</td>
            <td>' . $item['remarks'] . '</td>
        </tr>';
    }
} else {
    $tbl .= '<tr><td colspan="9" align="center">No indicators submitted.</td></tr>';
}

$tbl .= '</tbody></table>';
$pdf->writeHTML($tbl, true, false, false, false, '');
$pdf->Ln(5);

// Summary
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 8, 'Final Average Rating: ' . $final_avg, 0, 1, 'L');
$pdf->Cell(0, 8, 'Adjectival Rating: ' . $adj_rating, 0, 1, 'L');
$pdf->Ln(8);

// Signature Section
$pdf->SetFont('helvetica', '', 10);
$pdf->MultiCell(90, 6, "Ratee:\n$ratee_name\nPosition: $ratee_position\nDate: $ratee_date", 1, 'L', 0, 0);
$pdf->MultiCell(90, 6, "Rater:\n$rater_name\nPosition: $rater_position\nDate: $rater_date", 1, 'L', 0, 0);
$pdf->MultiCell(90, 6, "Approved by:\n$ph_rating\nPosition: $ph_position\nDate: $ph_date", 1, 'L', 1);

// Output
$pdf->Output('ipcr_report.pdf', 'I');
