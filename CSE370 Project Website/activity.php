<?php 
include 'DBconnect.php'; 
session_start(); 

if(!isset($_SESSION['user_id'])) { 
    echo "Login required.";
    exit();
}

$uid = $_SESSION['user_id'];

// Flash message
if(!isset($_SESSION['msg'])) $_SESSION['msg'] = "";

// ===== Delete =====
if(isset($_POST['confirm_delete'])) {
    $log_id = intval($_POST['log_id']);
    // Confirm delete via extra hidden field
    if(isset($_POST['confirm']) && $_POST['confirm']=='yes'){
        $conn->query("DELETE FROM activity_log WHERE activity_id=$log_id AND patient_id=$uid");
        $_SESSION['msg'] = "Activity deleted successfully.";
        header("Location: activity.php");
        exit();
    } else {
        $_SESSION['confirm_delete'] = $log_id;
        header("Location: activity.php");
        exit();
    }
}

// ===== Edit =====
if(isset($_POST['confirm_edit'])){
    $log_id = intval($_POST['log_id']);
    $date = $_POST['date']; 
    $desc = isset($_POST['activity_description']) ? implode(", ", $_POST['activity_description']) : "";
    if(!empty($_POST['custom_activity'])) $desc .= (empty($desc)?"":", ").$conn->real_escape_string($_POST['custom_activity']);

    $mood = isset($_POST['mood_description']) ? implode(", ", $_POST['mood_description']) : "";
    if(!empty($_POST['custom_mood'])) $mood .= (empty($mood)?"":", ").$conn->real_escape_string($_POST['custom_mood']);

    $prod = intval($_POST['productivity_score']);
    if($prod < 1) $prod = 1;
    if($prod > 10) $prod = 10;

    $conn->query("UPDATE activity_log 
                  SET date='$date', activity_description='$desc', mood_description='$mood', productivity_score=$prod
                  WHERE activity_id=$log_id AND patient_id=$uid");

    $_SESSION['msg'] = "Activity updated successfully.";
    header("Location: activity.php");
    exit();
}

// ===== Add new =====
if(isset($_POST['add_activity'])){
    $date = $_POST['date']; 

    $desc = isset($_POST['activity_description']) ? implode(", ", $_POST['activity_description']) : "";
    if(!empty($_POST['custom_activity'])) $desc .= (empty($desc)?"":", ").$conn->real_escape_string($_POST['custom_activity']);

    $mood = isset($_POST['mood_description']) ? implode(", ", $_POST['mood_description']) : "";
    if(!empty($_POST['custom_mood'])) $mood .= (empty($mood)?"":", ").$conn->real_escape_string($_POST['custom_mood']);

    $prod = intval($_POST['productivity_score']);
    if($prod < 1) $prod = 1;
    if($prod > 10) $prod = 10;

    $conn->query("INSERT INTO activity_log (patient_id, date, activity_description, mood_description, productivity_score) 
                  VALUES ($uid, '$date', '$desc', '$mood', $prod)");

    $_SESSION['msg'] = "Activity added successfully.";
    header("Location: activity.php");
    exit();
}

// ===== Fetch Activities ASC =====
$res = $conn->query("SELECT * FROM activity_log WHERE patient_id=$uid ORDER BY date ASC, activity_id ASC");
$activities = [];
while($row = $res->fetch_assoc()){
    $activities[] = $row;
}

// ===== Options for dropdowns =====
$activity_options = ['Exercise', 'Work', 'Study', 'Leisure', 'Meditation', 'Other'];
$mood_options = ['Happy', 'Sad', 'Anxious', 'Excited', 'Tired', 'Neutral'];

// Flash message
$msg = $_SESSION['msg'];
$_SESSION['msg'] = "";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Activity Tracker</title>
<style>
body {font-family: Arial, sans-serif; margin:0; background:#f9f9fb; color:#333;}
header {background:#6b46c1; color:white; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center;}
nav a {color:#fff; margin:0 0.6rem; text-decoration:none; font-weight:bold;}
nav a:hover {text-decoration:underline;}
.container {max-width:900px; margin:2rem auto; background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.05);}
h2, h3 {margin-bottom:1rem; color:#6b46c1;}
label {font-weight:bold; display:block; margin-top:1rem;}
input, select, textarea {padding:0.6rem; border:1px solid #ddd; border-radius:8px; width:100%; margin-top:0.4rem;}
button {padding:0.6rem 1rem; border:0; border-radius:10px; background:#6b46c1; color:white; font-weight:bold; cursor:pointer;}
table {width:100%; border-collapse:collapse; margin-top:1rem;}
th, td {border:1px solid #eee; padding:0.6rem; text-align:left;}
th {background:#faf7ff;}
.success {background:#e6ffed; border-left:4px solid #22c55e; padding:0.6rem; border-radius:6px; margin:1rem 0;}
.inline {display:inline;}
.action-box {display:flex; gap:4px;}
.edit-btn, .delete-btn {padding:0.3rem 0.6rem; border-radius:6px; background:#6b46c1; color:white; cursor:pointer; border:0;}
.edit-btn.cancel {background:#9ca3af; text-decoration:none; display:inline-block;}
.checkbox-group {display:flex; flex-wrap:wrap; gap:10px; margin-top:0.5rem;}
.checkbox-group label {font-weight:normal;}
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
<h2>Activity Tracker</h2>
<?php if($msg) echo "<div class='success'>$msg</div>"; ?>

<!-- Add Activity Form -->
<form method="post">
    <label>Date:</label>
    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>

    <label>Activities:</label>
    <div class="checkbox-group">
        <?php foreach($activity_options as $act): ?>
            <label><input type="checkbox" name="activity_description[]" value="<?= $act ?>"> <?= $act ?></label>
        <?php endforeach; ?>
    </div>
    <input type="text" name="custom_activity" placeholder="Other activity">

    <label>Mood:</label>
    <div class="checkbox-group">
        <?php foreach($mood_options as $m): ?>
            <label><input type="checkbox" name="mood_description[]" value="<?= $m ?>"> <?= $m ?></label>
        <?php endforeach; ?>
    </div>
    <input type="text" name="custom_mood" placeholder="Other mood">

    <label>Productivity (1-10):</label>
    <input type="number" name="productivity_score" min="1" max="10">

    <button name="add_activity">Add Activity</button>
</form>

<!-- Activities Table -->
<h3>Existing Activities</h3>
<table>
<tr><th>Date</th><th>Activities</th><th>Mood</th><th>Productivity</th><th>Action</th></tr>
<?php foreach($activities as $entry): ?>
<tr>
<?php if(isset($_GET['edit_id']) && $_GET['edit_id']==$entry['activity_id']): ?>
<form method="post">
    <td><input type="date" name="date" value="<?= $entry['date'] ?>" required></td>
    <td><input type="text" name="custom_activity" value="<?= $entry['activity_description'] ?>"></td>
    <td><input type="text" name="custom_mood" value="<?= $entry['mood_description'] ?>"></td>
    <td><input type="number" name="productivity_score" min="1" max="10" value="<?= $entry['productivity_score'] ?>"></td>
    <td>
        <input type="hidden" name="log_id" value="<?= $entry['activity_id'] ?>">
        <button name="confirm_edit" class="edit-btn">Save</button>
        <a href="activity.php" class="edit-btn cancel">Cancel</a>
    </td>
</form>
<?php elseif(isset($_SESSION['confirm_delete']) && $_SESSION['confirm_delete']==$entry['activity_id']): ?>
<form method="post">
    <input type="hidden" name="log_id" value="<?= $entry['activity_id'] ?>">
    <input type="hidden" name="confirm" value="yes">
    <td colspan="5" style="text-align:center;">
        Are you sure you want to delete this activity? 
        <button type="submit" name="confirm_delete">Yes</button>
        <a href="activity.php" class="edit-btn cancel">No</a>
    </td>
</form>
<?php else: ?>
    <td><?= $entry['date'] ?></td>
    <td><?= $entry['activity_description'] ?></td>
    <td><?= $entry['mood_description'] ?></td>
    <td><?= $entry['productivity_score'] ?></td>
    <td>
        <div class="action-box">
            <form method="get" class="inline">
                <input type="hidden" name="edit_id" value="<?= $entry['activity_id'] ?>">
                <button class="edit-btn">Edit</button>
            </form>
            <form method="post" class="inline">
                <input type="hidden" name="log_id" value="<?= $entry['activity_id'] ?>">
                <button name="confirm_delete" class="delete-btn">Delete</button>
            </form>
        </div>
    </td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>