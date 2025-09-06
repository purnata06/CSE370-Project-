<?php
include 'DBconnect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = (int) $_SESSION['user_id'];

// --- Handle AJAX request for reminders ---
if (isset($_GET['action']) && $_GET['action'] === 'check_reminders') {
    $res = $conn->query("SELECT * FROM reminders WHERE patient_id=$uid AND is_sent=0 AND date_time <= NOW()");
    $reminders = [];
    while ($r = $res->fetch_assoc()) {
        $reminders[] = $r;
        // Mark reminder as sent
        $conn->query("UPDATE reminders SET is_sent=1 WHERE reminder_id=" . $r['reminder_id']);
    }
    header('Content-Type: application/json');
    echo json_encode($reminders);
    exit();
}

// Fetch user info
$user = $conn->query("SELECT full_name FROM users WHERE user_id=$uid")->fetch_assoc();

// Month/year navigation
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// Fetch all cycles
$cyclesRes = $conn->query("SELECT * FROM cycles WHERE patient_id=$uid ORDER BY start_date ASC");

// Arrays for coloring
$periodDays = [];
$ovulationDays = [];
$predictionDays = [];

$lastCycle = null;
$cycleLengths = [];

// Process past cycles
while ($c = $cyclesRes->fetch_assoc()) {
    $start = $c['start_date'];
    $end = $c['end_date'];

    // Store period days
    if ($start && $end) {
        $dt = new DateTime($start);
        $endDt = new DateTime($end);
        while ($dt <= $endDt) {
            $periodDays[] = $dt->format('Y-m-d');
            $dt->modify('+1 day');
        }

        // Ovulation = end_date + 14 days for past months
        $ovDate = new DateTime($end);
        $ovDate->modify('+14 days');
        if ((int)$ovDate->format('Y') < date('Y') || ((int)$ovDate->format('Y') == date('Y') && (int)$ovDate->format('m') < date('m'))) {
            $ovulationDays[] = $ovDate->format('Y-m-d');
        }
    }

    // Calculate cycle lengths
    if ($lastCycle) {
        $len = (strtotime($start) - strtotime($lastCycle['start_date'])) / 86400;
        $cycleLengths[] = $len;
    }

    $lastCycle = $c;
}

// Average cycle length (default 28)
$avgCycle = count($cycleLengths) ? round(array_sum($cycleLengths)/count($cycleLengths)) : 28;

// Predicted period for current month if last cycle exists
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

// Calendar setup
$firstDay = new DateTime("$year-$month-01");
$daysInMonth = (int)$firstDay->format('t');
$startDay = (int)$firstDay->format('N'); // 1=Mon,7=Sun
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
<title>Dashboard</title>
<style>
body { font-family: Arial, sans-serif; margin:0; background:#f9f9fb; color:#333; }
header { background:#6b46c1; color:white; padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; }
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

.welcome-msg { background:#e0e7ff; padding:1rem; border-radius:8px; margin-bottom:1rem; border-left:4px solid #6b46c1; }
.month-year { color:#6b46c1; font-size:1.5rem; font-weight:bold; }
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
     <a href="logout.php">Logout</a>
</nav>
</header>

<div class="container">
<div class="welcome-msg">
  Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!
</div>

<div class="calendar-header">
  <div class="month-year"><?php echo date('F Y', mktime(0,0,0,$month,1,$year)); ?></div>
  <div class="calendar-nav">
    <a href="?month=<?php echo $prevMonth;?>&year=<?php echo $prevYear;?>" class="nav-btn">← Previous</a>
    <a href="dashboard.php" class="nav-btn">Today</a>
    <a href="?month=<?php echo $nextMonth;?>&year=<?php echo $nextYear;?>" class="nav-btn">Next →</a>
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
    $classes = ['day','other-month'];
    if($dateStr==$today) $classes[]='today';
    if(in_array($dateStr,$periodDays)) $classes[]='period';
    echo "<div class='".implode(' ',$classes)."'><span>$day</span></div>";
}

// Current month
for ($day=1;$day<=$daysInMonth;$day++) {
    $dateStr = sprintf("%04d-%02d-%02d",$year,$month,$day);
    $classes = ['day','current-month'];
    if($dateStr==$today) $classes[]='today';
    if(in_array($dateStr,$periodDays)) $classes[]='period';
    if(in_array($dateStr,$ovulationDays)) $classes[]='ovulation';
    if(in_array($dateStr,$predictionDays)) $classes[]='prediction';
    echo "<div class='".implode(' ',$classes)."'><span>$day</span></div>";
}

// Next month
for ($day=1;$day<=$daysFromNextMonth;$day++) {
    $dateStr = sprintf("%04d-%02d-%02d",$nextYear,$nextMonth,$day);
    $classes = ['day','other-month'];
    if($dateStr==$today) $classes[]='today';
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

<script>
// Check reminders every 3 seconds
function checkReminders() {
    fetch('dashboard.php?action=check_reminders')
      .then(res => res.json())
      .then(reminders => {
          reminders.forEach(rem => {
              alert(`⏰ Reminder: ${rem.type}\nTime: ${rem.date_time}`);
          });
      })
      .catch(err => console.error(err));
}

// Initial check
checkReminders();
setInterval(checkReminders, 3000); // every 3 seconds
</script>
</body>
</html>