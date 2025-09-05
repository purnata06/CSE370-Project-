<?php
include 'DBconnect.php';
session_start();

if(!isset($_SESSION['user_id'])) { 
    header("Location: login.php");
    exit();
}

$uid = (int)$_SESSION['user_id'];
$msg = "";
$msgClass = "success";

// ======= Delete =======
if(isset($_GET['delete_id'])){
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM reminders WHERE reminder_id=$del_id AND patient_id=$uid");
    header("Location: reminders.php");
    exit();
}

// ======= Update =======
if(isset($_POST['update'])){
    $id = intval($_POST['id']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $date_time = mysqli_real_escape_string($conn, $_POST['date_time']);

    $conn->query("UPDATE reminders 
                  SET type='$type', date_time='$date_time'
                  WHERE reminder_id=$id AND patient_id=$uid");
    header("Location: reminders.php");
    exit();
}

// ======= Add new =======
if(isset($_POST['add'])){
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $date_time = mysqli_real_escape_string($conn, $_POST['date_time']);

    $sql = "INSERT INTO reminders (patient_id, type, date_time, is_sent) 
            VALUES ($uid, '$type', '$date_time', 0)";
    
    $msg = $conn->query($sql) ? "✅ Reminder added successfully!" : "❌ Error: ".$conn->error;
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reminders</title>
  <style>
    body { font-family: Arial, sans-serif; margin:0; background:#f9f9fb; color:#333; }
    header { background:#6b46c1; color:white; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; }
    nav a { color:#fff; margin:0 .6rem; text-decoration:none; font-weight:bold; }
    nav a:hover { text-decoration:underline; }
    .container { max-width:800px; margin:1.5rem auto; background:white; padding:1.5rem; border-radius:12px; box-shadow:0 4px 14px rgba(0,0,0,.08); }
    h2, h3 { color: #4c1d95; }
    input { padding:.6rem; border:1px solid #ddd; border-radius:8px; width:100%; margin:.5rem 0 1rem; }
    button { padding:.7rem 1.2rem; border:0; border-radius:10px; background:#6b46c1; color:white; font-weight:bold; cursor:pointer; transition: background .2s; }
    button:hover { background:#5530a6; }
    table { width:100%; border-collapse:collapse; margin-top:1rem; }
    th, td { border:1px solid #eee; padding:.6rem; text-align:left; }
    th { background:#faf7ff; color:#4c1d95; }
    .success { background:#e6ffed; border-left:4px solid #22c55e; padding:.6rem; border-radius:6px; margin:.8rem 0; }
    .error { background:#fee2e2; border-left:4px solid #ef4444; padding:.6rem; border-radius:6px; margin:.8rem 0; }
    a { color:#6b46c1; text-decoration:none; font-weight:bold; }
    a:hover { text-decoration:underline; }
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
<?php if($msg) echo "<div class='$msgClass'>$msg</div>"; ?>

<h2>Reminders</h2>

<!-- ===== Add / Edit Form ===== -->
<?php
if(isset($_GET['edit_id'])){
    $id = intval($_GET['edit_id']);
    $res = $conn->query("SELECT * FROM reminders WHERE reminder_id=$id AND patient_id=$uid");
    $row = $res->fetch_assoc();
    if($row):
?>
<h3>Edit Reminder</h3>
<form method="post">
    <input type="hidden" name="id" value="<?php echo $row['reminder_id']; ?>">

    <label>Reminder Type</label>
    <input name="type" value="<?php echo htmlspecialchars($row['type']); ?>" required>

    <label>Date & Time</label>
    <input type="datetime-local" name="date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($row['date_time'])); ?>" required>

    <button name="update">Update</button>
</form>
<?php
    endif;
} else {
?>
<h3>Add New Reminder</h3>
<form method="post">
    <label>Reminder Type</label>
    <input name="type" placeholder="e.g., Medication, Exercise" required>

    <label>Date & Time</label>
    <input type="datetime-local" name="date_time" required>

    <button name="add">+ Add Reminder</button>
</form>
<?php } ?>

<h3>Your Reminders</h3>
<table>
  <tr>
    <th>Reminder Type</th>
    <th>Date & Time</th>
    <th>Status</th>
    <th>Actions</th>
  </tr>
  <?php 
  $res = $conn->query("SELECT * FROM reminders WHERE patient_id=$uid ORDER BY date_time ASC");
  $now = date('Y-m-d H:i:s');
  while($r = $res->fetch_assoc()): 
      $status = ($r['is_sent']) ? '✅ Sent' : ((strtotime($r['date_time']) > strtotime($now)) ? '⏳ Pending' : '⏳ Pending');
  ?>
  <tr>
    <td><?php echo htmlspecialchars($r['type']); ?></td>
    <td><?php echo $r['date_time']; ?></td>
    <td><?php echo $status; ?></td>
    <td>
        <a href="reminders.php?edit_id=<?php echo $r['reminder_id']; ?>">Edit</a> | 
        <a href="reminders.php?delete_id=<?php echo $r['reminder_id']; ?>" onclick="return confirm('Are you sure you want to delete this reminder?');">Delete</a>
    </td>
  </tr>
  <?php endwhile; ?>
</table>
</div>
</body>
</html>