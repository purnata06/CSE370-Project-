<?php 
include 'DBconnect.php';
session_start();

// Only partners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'partner') {
    die("<div style='padding:1rem; color:red;'>Access denied. You must be a partner to view this page.</div>");
}

$partner_id = (int)$_SESSION['user_id'];

// Get partner info
$partnerInfoRes = $conn->query("SELECT full_name, user_name FROM users WHERE user_id=$partner_id LIMIT 1");
if ($partnerInfoRes && $partnerInfoRes->num_rows > 0) {
    $partnerInfo = $partnerInfoRes->fetch_assoc();
} else {
    die("<div style='padding:1rem; color:red;'>Partner not found.</div>");
}

// Get the linked patient
$patientRes = $conn->query("SELECT linked_patient_id FROM partner WHERE partner_id=$partner_id LIMIT 1");
if ($patientRes && $patientRes->num_rows > 0) {
    $linked = $patientRes->fetch_assoc();
    $linked_patient_id = $linked['linked_patient_id'];
    if (!$linked_patient_id) {
        die("<div style='padding:1rem; color:red;'>No patient linked to this partner.</div>");
    }
} else {
    die("<div style='padding:1rem; color:red;'>No patient linked to this partner.</div>");
}

// Fetch linked patient details
$userRes = $conn->query("SELECT full_name FROM users WHERE user_id=$linked_patient_id LIMIT 1");
if ($userRes && $userRes->num_rows > 0) {
    $user = $userRes->fetch_assoc();
} else {
    die("<div style='padding:1rem; color:red;'>Patient not found.</div>");
}

// Current month/year
$month = (int)date('m');
$year = (int)date('Y');

// Previous/next month for navigation
$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Fetch cycles of linked patient
$cycles = $conn->query("
    SELECT start_date, end_date, cycle_length 
    FROM cycles 
    WHERE patient_id=$linked_patient_id 
    ORDER BY start_date ASC
");

// Prepare marked days
$periodDays = [];
$ovulationDays = [];
$predictionDays = [];

if ($cycles && $cycles->num_rows > 0) {
    $lastCycle = null;
    $cycleLengths = [];

    while ($c = $cycles->fetch_assoc()) {
        // Period days
        if (!empty($c['start_date']) && !empty($c['end_date'])) {
            $start = new DateTime($c['start_date']);
            $end = new DateTime($c['end_date']);
            while ($start <= $end) {
                if ((int)$start->format('m') === $month && (int)$start->format('Y') === $year) {
                    $periodDays[] = $start->format('Y-m-d');
                }
                $start->modify('+1 day');
            }
        }

        // Ovulation days (start + cycle_length - 14)
        if (!empty($c['start_date']) && !empty($c['cycle_length'])) {
            $ovDate = new DateTime($c['start_date']);
            $ovDate->modify('+' . ((int)$c['cycle_length'] - 14) . ' days');
            if ((int)$ovDate->format('m') === $month && (int)$ovDate->format('Y') === $year) {
                $ovulationDays[] = $ovDate->format('Y-m-d');
            }
        }

        // Calculate average cycle length
        if ($lastCycle) {
            $len = (strtotime($c['start_date']) - strtotime($lastCycle['start_date'])) / 86400;
            $cycleLengths[] = $len;
        }
        $lastCycle = $c;
    }

    // Predicted period for current month
    $avgCycle = count($cycleLengths) ? round(array_sum($cycleLengths)/count($cycleLengths)) : 28;
    if ($lastCycle && $lastCycle['start_date']) {
        $predStart = new DateTime($lastCycle['start_date']);
        $predStart->modify("+$avgCycle days");
        $predEnd = clone $predStart;
        $predEnd->modify('+4 days'); // assume 5-day period

        $dt = clone $predStart;
        while ($dt <= $predEnd) {
            if ((int)$dt->format('m') === $month && (int)$dt->format('Y') === $year) {
                $predictionDays[] = $dt->format('Y-m-d');
            }
            $dt->modify('+1 day');
        }
    }
}

// Calendar calculations
$firstDayOfMonth = new DateTime("$year-$month-01");
$daysInMonth = (int)$firstDayOfMonth->format('t');
$startDay = (int)$firstDayOfMonth->format('N'); // 1=Mon
$prevMonthDays = (int)date('t', mktime(0,0,0,$prevMonth,1,$prevYear));
$daysFromPrevMonth = $startDay - 1;
$totalCellsUsed = $daysFromPrevMonth + $daysInMonth;
$weeksNeeded = ceil($totalCellsUsed / 7);
$totalCells = $weeksNeeded * 7;
$daysFromNextMonth = $totalCells - $totalCellsUsed;

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Partner Dashboard</title>
<style>
body { font-family: Arial, sans-serif; margin:0; background:#f9f9fb; color:#333; }
header { background:#6b46c1; color:white; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; }
nav a { color:#fff; margin:0 0.6rem; text-decoration:none; font-weight:bold; }
nav a:hover { text-decoration: underline; }
.container { max-width:1000px; margin:2rem auto; background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.05); }

.calendar-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
.calendar-nav a { background:#6b46c1; color:white; padding:.5rem 1rem; text-decoration:none; border-radius:6px; font-weight:bold; margin-left:.5rem; }
.calendar-nav a:hover { background:#553c9a; }

.calendar { display:grid; grid-template-columns:repeat(7,1fr); gap:5px; }
.day-name { font-weight:bold; text-align:center; padding:.5rem; background:#f3f0ff; border-radius:6px; }
.day { height:80px; padding:.5rem; text-align:right; border-radius:8px; background:#fafafa; box-shadow:inset 0 0 3px rgba(0,0,0,.05); position:relative; cursor:pointer; transition: all 0.2s ease; }
.day:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); }
.day span { position:absolute; top:5px; left:5px; font-size:.9rem; }
.current-month { background:#fff; }
.other-month { background:#f0f0f0; color:#999; opacity:0.6; }
.today { border:2px solid #6b46c1; font-weight:bold; }

.period { background:#f56576 !important; color:#fff; }
.ovulation { background:#3182ce !important; color:#fff; }
.prediction { background:#6b46c1 !important; color:#fff; }

.legend { margin-top:1rem; display:flex; gap:1rem; flex-wrap:wrap; }
.legend div { padding:0.4rem 0.8rem; border-radius:6px; font-size:.9rem; color:#fff; }
.legend .period { background:#f56576; }
.legend .ovulation { background:#3182ce; }
.legend .prediction { background:#6b46c1; }

.partner-name { color:#6b46c1; font-weight:bold; font-size:1.2rem; margin-bottom:0.5rem; }
.month-year { color:#6b46c1; font-weight:bold; font-size:1.1rem; margin-bottom:0.5rem; }
</style>
</head>
<body>
<header>
    <div><strong>Period Tracker & Mental Health Support Portal</strong></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="partner.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
<div class="partner-name">
    Welcome, <?php echo htmlspecialchars($partnerInfo['user_name']); ?>! 
    You are viewing <?php echo htmlspecialchars($user['full_name']); ?>'s dashboard.
</div>

<div class="calendar-header">
    <div class="month-year"><?php echo date('F Y', mktime(0,0,0,$month,1,$year)); ?></div>
    <div class="calendar-nav">
        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">← Previous</a>
        <a href="partner.php">Today</a>
        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">Next →</a>
    </div>
</div>

<div class="calendar">
<?php
foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d) {
    echo "<div class='day-name'>$d</div>";
}

// Previous month
for ($i=$daysFromPrevMonth;$i>=1;$i--) {
    $day = $prevMonthDays-$i+1;
    $dateStr = sprintf("%04d-%02d-%02d",$prevYear,$prevMonth,$day);
    $classes = ["day","other-month"];
    if ($dateStr==$today) $classes[]="today";
    echo "<div class='".implode(' ',$classes)."'><span>$day</span></div>";
}

// Current month
for ($day=1;$day<=$daysInMonth;$day++) {
    $dateStr = sprintf("%04d-%02d-%02d",$year,$month,$day);
    $classes = ["day","current-month"];
    if ($dateStr==$today) $classes[]="today";
    if (in_array($dateStr,$periodDays)) $classes[]="period";
    if (in_array($dateStr,$ovulationDays)) $classes[]="ovulation";
    if (in_array($dateStr,$predictionDays)) $classes[]="prediction";
    echo "<div class='".implode(' ',$classes)."'><span>$day</span></div>";
}

// Next month
for ($day=1;$day<=$daysFromNextMonth;$day++) {
    $dateStr = sprintf("%04d-%02d-%02d",$nextYear,$nextMonth,$day);
    $classes = ["day","other-month"];
    echo "<div class='".implode(' ',$classes)."'><span>$day</span></div>";
}
?>
</div>

<div class="legend">
    <div class="period">Period Days</div>
    <div class="ovulation">Ovulation Days</div>
    <div class="prediction">Predicted Period</div>
</div>
</div>
</body>
</html>
