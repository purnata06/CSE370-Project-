<?php
include 'DBconnect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Login required.";
    exit;
}

$uid = intval($_SESSION['user_id']);

/* ------------------------
   validate date
------------------------ */
function valid_date($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

/* ------------------------
   Delete cycle
------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del = intval($_POST['delete_id']);
    $conn->query("DELETE FROM cycles WHERE cycle_id=$del AND patient_id=$uid");
    header("Location: cycles.php");
    exit;
}

/* ------------------------
   Saving multiple cycles
------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cycles'])) {
    $start_dates = $_POST['start_date'] ?? [];
    $end_dates = $_POST['end_date'] ?? [];

    for ($i = 0; $i < count($start_dates); $i++) {
        $s = trim($start_dates[$i]);
        $e = trim($end_dates[$i] ?? '');

        if ($s === '' || !valid_date($s)) continue;
        $e_val = ($e && valid_date($e)) ? "'" . $conn->real_escape_string($e) . "'" : "NULL";
        $s_esc = $conn->real_escape_string($s);

        // Inserting if not exists
        $exists = $conn->query("SELECT cycle_id FROM cycles WHERE patient_id=$uid AND start_date='$s_esc'");
        if ($exists && $exists->num_rows == 0) {
            $conn->query("INSERT INTO cycles (patient_id, start_date, end_date) VALUES ($uid, '$s_esc', $e_val)");
        } else if ($exists && $exists->num_rows > 0 && $e_val != "NULL") {
            // updating end_date if already exists
            $conn->query("UPDATE cycles SET end_date=$e_val WHERE patient_id=$uid AND start_date='$s_esc'");
        }
    }

    header("Location: cycles.php");
    exit;
}

/* ------------------------
   Fetching saved cycles
------------------------ */
$cycles = [];
$res = $conn->query("SELECT cycle_id, start_date, end_date FROM cycles WHERE patient_id=$uid ORDER BY start_date ASC");
while($r = $res->fetch_assoc()) {
    $cycles[] = $r;
}

/* ------------------------
   Computing cycle lengths & ovulation
------------------------ */
$cycle_lengths = [];
$n = count($cycles);
for ($i=0; $i<$n-1; $i++) {
    $diff = (strtotime($cycles[$i+1]['start_date']) - strtotime($cycles[$i]['start_date']))/86400;
    $cycle_lengths[] = (int)$diff;
}

// Average cycle length
$avg_cycle = count($cycle_lengths) ? round(array_sum($cycle_lengths)/count($cycle_lengths)) : null;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Period Cycles</title>
<style>
body {
  font-family: Arial, sans-serif;
  margin: 0;
  background: #f9f9fb;
  color: #333;
}

header {
  background: #6b46c1;
  color: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

nav a {
  color: #fff;
  margin: 0 0.6rem;
  text-decoration: none;
  font-weight: bold;
}

.container {
  max-width: 1100px;
  margin: 1.5rem auto;
  background: white;
  padding: 1.5rem;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,.05);
}

input,
select,
textarea {
  padding: 0.6rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  width: 100%;
  margin: 0.4rem 0;
}

button {
  padding: 0.6rem 1rem;
  border: 0;
  border-radius: 10px;
  background: #6b46c1;
  color: white;
  font-weight: bold;
  cursor: pointer;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

th,
td {
  border: 1px solid #eee;
  padding: 0.6rem;
  text-align: left;
}

th {
  background: #faf7ff;
}
.muted {
  color: #777;
}

.success {
  background: #e6ffed;
  border-left: 4px solid #22c55e;
  padding: 0.6rem;
  border-radius: 6px;
  margin: 0.5rem 0;
}

.error {
  background: #fee2e2;
  border-left: 4px solid #ef4444;
  padding: 0.6rem;
  border-radius: 6px;
  margin: 0.5rem 0;
}
</style>
</head>
<body>
<header>
<div><strong>Period Tracker & Mental  Health Support Portal </strong></div>
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
<h2 style="color: #55309e;">Enter Period Data</h2>
<p>Enter period start and end dates for each month (at least 2 months required).</p>


<form method="post">
<?php for($i=0;$i<5;$i++): ?>
<div style="margin-bottom:.6rem;">
<label>Period <?php echo $i+1;?> Start</label>
<input type="date" name="start_date[]">
<label>Period <?php echo $i+1;?> End</label>
<input type="date" name="end_date[]">
</div>
<?php endfor; ?>
<button type="submit" name="save_cycles">Save</button>
</form>

<?php if($avg_cycle): ?>
<div class="success">
Average cycle length: <?php echo $avg_cycle;?> day(s)
</div>
<?php endif; ?>

<h3 style="color: #55309e;">Saved Cycles</h3>
<table>
<tr>
<th>Month</th>
<th>Start</th>
<th>End</th>
<th>Ovulation Date</th>
<th>Predicted Next Period</th>
<th>Cycle Length</th>
<th>Action</th>
</tr>
<?php
if(empty($cycles)) echo "<tr><td colspan='7'>No cycles saved.</td></tr>";
else {
    for($i=0;$i<$n;$i++){
        $start = $cycles[$i]['start_date'];
        $end = $cycles[$i]['end_date'];
        $month = date('F Y', strtotime($start));
        $period_len = $end ? (strtotime($end)-strtotime($start))/86400+1 : '';
        $ovulation = $end ? date('Y-m-d', strtotime($end.' +14 days')) : '';
        $cycle_len = ($i<$n-1) ? (strtotime($cycles[$i+1]['start_date'])-strtotime($start))/86400 : '';
        $pred_next = ($i<$n-1) ? $cycles[$i+1]['start_date'] : ($avg_cycle ? date('Y-m-d', strtotime($start." +$avg_cycle days")) : '');
        $id = $cycles[$i]['cycle_id'];

        echo "<tr>
        <td>$month</td>
        <td>$start</td>
        <td>$end</td>
        <td>$ovulation</td>
        <td>$pred_next</td>
        <td>$cycle_len</td>
        <td>
            <form method='post' onsubmit=\"return confirm('Delete this cycle?');\">
            <input type='hidden' name='delete_id' value='$id'>
            <button type='submit'>Delete</button>
            </form>
        </td>
        </tr>";
    }
}
?>
</table>
</div>
</body>
</html>
