<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

include('../db/connection.php');

// Get submission ID from URL if provided, otherwise get the latest submission
 $submissionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get faculty ID from session
 $facultyId = $_SESSION['user_id'] ?? 0;

if ($submissionId > 0) {
    // Get specific submission
    $sql = "SELECT * FROM ipcr_submissions WHERE id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $submissionId, $facultyId);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();
} else {
    // Get the latest submission for this faculty
    $period = date('Y');
    $sql = "SELECT * FROM ipcr_submissions WHERE faculty_id = ? AND period = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $facultyId, $period);
    $stmt->execute();
    $result = $stmt->get_result();
    $submission = $result->fetch_assoc();
}

if (empty($submission)) {
    // No submission found, redirect to submit form
    header("Location: Submit_IPCR.php");
    exit();
}

// Get indicators data
 $indicators = [];
if (!empty($submission['indicators_json'])) {
    $indicators = json_decode($submission['indicators_json'], true);
} elseif (!empty($submission['submission_data'])) {
    $submissionData = json_decode($submission['submission_data'], true);
    if (isset($submissionData['indicator'])) {
        $count = count($submissionData['indicator']);
        for ($i = 0; $i < $count; $i++) {
            $indicators[] = [
                'indicator' => $submissionData['indicator'][$i] ?? '',
                'accomplishment' => $submissionData['accomplishment'][$i] ?? '',
                'q' => $submissionData['q'][$i] ?? '',
                'e' => $submissionData['e'][$i] ?? '',
                't' => $submissionData['t'][$i] ?? '',
                'a' => $submissionData['a'][$i] ?? '',
                'remarks' => $submissionData['remarks'][$i] ?? ''
            ];
        }
    }
}

// Get faculty information
 $facultySql = "SELECT * FROM users WHERE id = ?";
 $facultyStmt = $conn->prepare($facultySql);
 $facultyStmt->bind_param("i", $facultyId);
 $facultyStmt->execute();
 $facultyResult = $facultyStmt->get_result();
 $faculty = $facultyResult->fetch_assoc();

// Include TCPDF library
require_once('../tcpdf/tcpdf.php');

// Extend TCPDF class to create custom header and footer
class MYPDF extends TCPDF {
    // Page header
    public function Header() {
        // Set font
        $this->SetFont('helvetica', 'B', 12);
        
        // Title
        $this->Cell(0, 15, 'Performance Commitment and Review (PCR)', 0, true, 'C', 0, '', 0, false, 'M', 'M');
        
        // Set font back to normal
        $this->SetFont('helvetica', '', 10);
        
        // Draw a line
        $this->Line(15, 25, $this->getPageWidth()-15, 25);
    }
    
    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getPage(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
 $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
 $pdf->SetCreator('Philippine Countryville College, Inc.');
 $pdf->SetAuthor('PCC IPCR System');
 $pdf->SetTitle('Performance Commitment and Review');
 $pdf->SetSubject('IPCR Report');
 $pdf->SetKeywords('IPCR, PCR, Performance Review');

// Set default header data
 $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// Set header and footer fonts
 $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
 $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
 $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
 $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
 $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
 $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
 $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
 $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Set some language-dependent strings (optional)
if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
    require_once(dirname(__FILE__).'/lang/eng.php');
    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// Set font
 $pdf->SetFont('helvetica', '', 10);

// Add a page
 $pdf->AddPage();

// Faculty information section
 $pdf->SetFont('helvetica', '', 10);
 $html = '<p>I, <strong>' . htmlspecialchars($faculty['full_name'] ?? 'Faculty Name') . '</strong>, of the <strong>' . htmlspecialchars($submission['department'] ?? 'Department') . '</strong> of Philippine Countryville College, Inc., commit to deliver and agree to be rated on the following targets in accordance with the indicated measure for the period of <strong>' . htmlspecialchars($submission['period'] ?? date('Y')) . '</strong>.</p>';
 $pdf->writeHTML($html, true, false, true, false, '');

 $pdf->Ln(5);
 $pdf->Cell(0, 0, 'Reviewed by: _____________________', 0, 1);
 $pdf->Ln(5);

// Rating Scale section
 $pdf->SetFont('helvetica', 'B', 10);
 $pdf->Cell(0, 0, 'Rating Scale:', 0, 1);
 $pdf->SetFont('helvetica', '', 10);
 $pdf->Ln(2);

 $html = '<p>The following scale is used to rate performance:</p>
<ul>
    <li><strong>5 – Outstanding</strong></li>
    <li><strong>4 – Very Satisfactory</strong></li>
    <li><strong>3 – Satisfactory</strong></li>
    <li><strong>2 – Unsatisfactory</strong></li>
    <li><strong>1 – Poor</strong></li>
</ul>';
 $pdf->writeHTML($html, true, false, true, false, '');

 $pdf->Ln(5);

// Create table for success indicators
 $pdf->SetFont('helvetica', 'B', 9);
 $pdf->Cell(50, 7, 'SUCCESS INDICATORS', 1, 0, 'C');
 $pdf->Cell(30, 7, 'Accomplishment', 1, 0, 'C');
 $pdf->Cell(20, 7, 'Quality', 1, 0, 'C');
 $pdf->Cell(20, 7, 'Efficiency', 1, 0, 'C');
 $pdf->Cell(20, 7, 'Time', 1, 0, 'C');
 $pdf->Cell(20, 7, 'Average', 1, 0, 'C');
 $pdf->Cell(30, 7, 'Remarks', 1, 1, 'C');

 $pdf->SetFont('helvetica', '', 8);

// Populate table with data
if (!empty($indicators)) {
    foreach ($indicators as $row) {
        $pdf->Cell(50, 7, htmlspecialchars($row['indicator'] ?? ''), 1, 0, 'L');
        $pdf->Cell(30, 7, htmlspecialchars($row['accomplishment'] ?? ''), 1, 0, 'L');
        $pdf->Cell(20, 7, htmlspecialchars($row['q'] ?? ''), 1, 0, 'C');
        $pdf->Cell(20, 7, htmlspecialchars($row['e'] ?? ''), 1, 0, 'C');
        $pdf->Cell(20, 7, htmlspecialchars($row['t'] ?? ''), 1, 0, 'C');
        $pdf->Cell(20, 7, htmlspecialchars($row['a'] ?? ''), 1, 0, 'C');
        $pdf->Cell(30, 7, htmlspecialchars($row['remarks'] ?? ''), 1, 1, 'L');
    }
}

 $pdf->Ln(10);

// Final ratings section - exactly as in the screenshot
 $pdf->SetFont('helvetica', 'B', 10);
 $pdf->Cell(0, 7, 'Final Average Rating:', 0, 1);
 $pdf->SetFont('helvetica', '', 10);
 $pdf->Cell(0, 7, htmlspecialchars($submission['final_avg_rating'] ?? ''), 0, 1);

 $pdf->SetFont('helvetica', 'B', 10);
 $pdf->Cell(0, 7, 'Adjectival Rating:', 0, 1);
 $pdf->SetFont('helvetica', '', 10);

// Calculate adjectival rating if not already available
 $adjectivalRating = $submission['adjectival_rating'] ?? '';
if (empty($adjectivalRating) && !empty($submission['final_avg_rating'])) {
    $rating = floatval($submission['final_avg_rating']);
    if ($rating >= 4.5) $adjectivalRating = "Outstanding";
    elseif ($rating >= 3.5) $adjectivalRating = "Very Satisfactory";
    elseif ($rating >= 2.5) $adjectivalRating = "Satisfactory";
    elseif ($rating >= 1.5) $adjectivalRating = "Unsatisfactory";
    else $adjectivalRating = "Poor";
}

 $pdf->Cell(0, 7, htmlspecialchars($adjectivalRating), 0, 1);

 $pdf->Ln(15);

// Signature section - exactly as in the screenshot (stacked format)
 $pdf->SetFont('helvetica', 'B', 10);
 $pdf->Cell(0, 7, 'Name and Signature of Ratee:', 0, 1);
 $pdf->SetFont('helvetica', '', 10);
 $pdf->Cell(0, 7, 'Name: ' . htmlspecialchars($submission['ratee_name'] ?? ''), 0, 1);
 $pdf->Cell(0, 7, 'Position: ' . htmlspecialchars($submission['ratee_position'] ?? ''), 0, 1);
 $pdf->Cell(0, 7, 'Date: ' . htmlspecialchars($submission['ratee_date'] ?? ''), 0, 1);

 $pdf->Ln(10);

 $pdf->SetFont('helvetica', 'B', 10);
 $pdf->Cell(0, 7, 'Final Rating by Program Head:', 0, 1);
 $pdf->SetFont('helvetica', '', 10);
 $pdf->Cell(0, 7, 'Rating: ' . htmlspecialchars($submission['final_rating_program_head'] ?? ''), 0, 1);
 $pdf->Cell(0, 7, 'Position: ' . htmlspecialchars($submission['ph_position'] ?? ''), 0, 1);
 $pdf->Cell(0, 7, 'Date: ' . htmlspecialchars($submission['ph_date'] ?? ''), 0, 1);

 $pdf->Ln(10);


// ---------------------------------------------------------

// Close and output PDF document
 $facultyName = $faculty['full_name'] ?? 'Faculty';
 $pdf->Output('IPCR_' . $facultyName . '_' . date('Y-m-d') . '.pdf', 'I');
?>