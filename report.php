<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

include 'DBconnect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>Login required.</div>";
    exit();
}

$uid = $_SESSION['user_id'];

// Fetch symptom data
$res = $conn->query("SELECT sl.date, s.symptom_name, sl.severity_level, sl.notes
                     FROM symptom_log sl
                     JOIN symptoms s ON sl.symptom_id = s.symptom_id
                     WHERE sl.patient_id = $uid
                     ORDER BY sl.date DESC");

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[$row['date']][] = [
        'symptom_name' => $row['symptom_name'],
        'severity_level' => $row['severity_level'],
        'notes' => $row['notes']
    ];
}

// Handle PDF download
if (isset($_POST['download_pdf'])) {
    $dompdf = new Dompdf();

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Symptom Report PDF</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; }
            h2 { text-align: center; color: #6b46c1; }
            table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
            th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 12px; }
            th { background-color: #f0e6ff; font-weight: bold; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .date-cell { font-weight: bold; color: #6b46c1; width: 12%; }
            .page-break { page-break-after: always; }
        </style>
    </head>
    <body>
        <h2>Symptom Report</h2>
        <table>
            <tr><th>Date</th><th>Symptom</th><th>Severity</th><th>Notes</th></tr>
            <?php
            $rowCount = 0;
            foreach ($data as $date => $symptoms) {
                echo "<tr>";
                echo "<td class='date-cell'>{$date}</td>";
                echo "<td>";
                foreach ($symptoms as $s) echo htmlspecialchars($s['symptom_name'])."<br>";
                echo "</td>";
                echo "<td>";
                foreach ($symptoms as $s) echo htmlspecialchars($s['severity_level'])."<br>";
                echo "</td>";
                echo "<td>";
                foreach ($symptoms as $s) echo htmlspecialchars($s['notes'])."<br>";
                echo "</td>";
                echo "</tr>";

                $rowCount++;
                if ($rowCount % 25 === 0) {
                    echo "</table><div class='page-break'></div><table>";
                    echo "<tr><th>Date</th><th>Symptom</th><th>Severity</th><th>Notes</th></tr>";
                }
            }
            ?>
        </table>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // PDF generation
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("symptom_report.pdf", ["Attachment" => true]); // automatic download
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Symptom Report</title>
<style>
body { font-family: Arial, sans-serif; background: #f9f9fb; margin:0; padding:0; }
header { background: #6b46c1; color: white; padding: 1rem 2rem; display:flex; justify-content: space-between; align-items:center; }
nav a { color: #fff; margin:0 .6rem; text-decoration:none; font-weight:bold; }
.container { max-width:900px; margin:2rem auto; background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.05);}
table { width:100%; border-collapse:collapse; margin-top:1rem;}
th,td { border:1px solid #eee; padding:.6rem; text-align:left;}
th { background:#faf7ff;}
button { padding:.4rem .8rem; border:0; border-radius:6px; background:#6b46c1; color:white; cursor:pointer;}
button:hover { background:#553c9a;}
form { display:inline;}
</style>
</head>
<body>

<header>
  <div><strong>Period Tracker & Mental Health Support Portal</strong></div>
  <nav>
    <a href="index.php">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="cycles.php">Cycles</a>
    <a href="symptoms.php">Symptoms</a>
    <a href="activity.php">Activity</a>
    <a href="reminders.php">Reminders</a>
    <a href="community.php">Community</a>
    <a href="support.php">Support</a>
    <a href="report.php">Report</a>
    <?php if(isset($_SESSION['user_id'])): ?>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a>
      <a href="register.php">Register</a>
    <?php endif; ?>
  </nav>
</header>

<div class="container">
<h2>Symptom History</h2>

<form method="post">
    <button name="download_pdf">Download All Symptoms (PDF)</button>
</form>

<table>
<tr><th>Date</th><th>Symptom</th><th>Severity</th><th>Notes</th></tr>
<?php
foreach ($data as $date => $symptoms) {
    echo "<tr>";
    echo "<td>{$date}</td>";
    echo "<td>";
    foreach ($symptoms as $s) echo htmlspecialchars($s['symptom_name'])."<br>";
    echo "</td>";
    echo "<td>";
    foreach ($symptoms as $s) echo htmlspecialchars($s['severity_level'])."<br>";
    echo "</td>";
    echo "<td>";
    foreach ($symptoms as $s) echo htmlspecialchars($s['notes'])."<br>";
    echo "</td>";
    echo "</tr>";
}
?>
</table>

</div>
</body>
</html>