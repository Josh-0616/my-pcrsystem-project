<?php
include 'config.php';
session_start();

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM ipcr_forms WHERE id='$id'");
$data = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PCR Submission View</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .ipcr-box {
            max-width: 1000px;
            margin: auto;
            padding: 30px;
            border: 2px solid black;
            font-family: Arial, sans-serif;
        }
        .bordered {
            border: 1px solid black;
        }
        th, td {
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="ipcr-box">
    <h4 class="text-center fw-bold">PERFORMANCE COMMITMENT AND REVIEW (PCR)</h4>
    <p><b>I,</b> <?= $data['employee_name'] ?> of the <b><?= $data['position'] ?></b> of Philippine Countryville College, Inc., commit to deliver and agree to be rated on the following targets in accordance with the indicated measure for the period of <b><?= $data['period'] ?></b>.</p>

    <div class="row">
        <div class="col-6"><b>Name:</b> <?= $data['employee_name'] ?></div>
        <div class="col-6"><b>Date:</b> <?= $data['submission_date'] ?></div>
    </div>
    <hr>

    <table class="table table-bordered">
        <thead>
            <tr class="table-secondary">
                <th rowspan="2">SUCCESS INDICATORS <br><small>(Target + Measure)</small></th>
                <th rowspan="2">Accomplishment</th>
                <th colspan="4">Rating</th>
                <th rowspan="2">Remarks</th>
            </tr>
            <tr class="table-secondary">
                <th>Q</th>
                <th>E</th>
                <th>T</th>
                <th>A</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $items = json_decode($data['form_data'], true);
            $totalA = 0;
            $count = 0;
            foreach ($items as $row) {
                $a = number_format(($row['q'] + $row['e'] + $row['t']) / 3, 2);
                $totalA += $a;
                $count++;
                echo "<tr>
                    <td>{$row['indicator']}</td>
                    <td>{$row['accomplishment']}</td>
                    <td>{$row['q']}</td>
                    <td>{$row['e']}</td>
                    <td>{$row['t']}</td>
                    <td>{$a}</td>
                    <td>{$row['remarks']}</td>
                </tr>";
            }
            $final_avg = $count > 0 ? number_format($totalA / $count, 2) : '0.00';

            function getAdjectival($avg) {
                if ($avg >= 4.5) return "Outstanding";
                elseif ($avg >= 3.5) return "Very Satisfactory";
                elseif ($avg >= 2.5) return "Satisfactory";
                elseif ($avg >= 1.5) return "Unsatisfactory";
                else return "Poor";
            }

            $adj = getAdjectival($final_avg);
            ?>
        </tbody>
    </table>

    <h5>Final Rating</h5>
    <table class="table table-bordered">
        <tr>
            <th>Final Average Rating</th>
            <td><?= $final_avg ?></td>
            <th>Adjectival Rating</th>
            <td><?= $adj ?></td>
        </tr>
    </table>

    <p class="mt-5">The above rating has been discussed with me by Program Head</p>
    <table class="table table-bordered">
        <tr>
            <th>Name and Signature of Ratee:</th>
            <td><?= $data['employee_name'] ?></td>
            <th>Position:</th>
            <td><?= $data['position'] ?></td>
            <th>Date:</th>
            <td><?= $data['submission_date'] ?></td>
        </tr>
        <tr>
            <th>Name and Signature of Rater:</th>
            <td colspan="5"><?= $data['reviewer_name'] ?? '_________________' ?></td>
        </tr>
    </table>

</div>

</body>
</html>
