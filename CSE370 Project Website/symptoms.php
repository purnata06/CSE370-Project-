<?php
include 'DBconnect.php';
session_start();

if(!isset($_SESSION['user_id'])){
    echo "Login required.";
    exit();
}

$uid = $_SESSION['user_id'];
$msg = "";

// ===== Delete =====
if(isset($_POST['confirm_delete'])){
    $log_id = intval($_POST['log_id']);

    if(isset($_POST['confirm']) && $_POST['confirm']=='yes'){
        // Final delete
        $conn->query("DELETE FROM symptom_log WHERE log_id=$log_id AND patient_id=$uid");
        $_SESSION['msg'] = "Symptom deleted successfully.";
        unset($_SESSION['confirm_delete']);
        header("Location: symptoms.php");
        exit();
    }
    elseif(isset($_POST['confirm']) && $_POST['confirm']=='no'){
        // Cancel delete
        unset($_SESSION['confirm_delete']);
        header("Location: symptoms.php");
        exit();
    }
    else {
        // Step-1: ask confirmation
        $_SESSION['confirm_delete'] = $log_id;
        header("Location: symptoms.php");
        exit();
    }
}

// ===== Edit =====
if(isset($_POST['confirm_edit'])){
    $log_id = intval($_POST['log_id']);
    $symptom_name = $conn->real_escape_string($_POST['symptom_name']);
    $severity = intval($_POST['severity_level']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $date = $_POST['date'];

    // Ensure symptom exists
    $res = $conn->query("SELECT symptom_id FROM symptoms WHERE symptom_name='$symptom_name'");
    if($res->num_rows > 0){
        $symptom_id = $res->fetch_assoc()['symptom_id'];
    } else {
        $conn->query("INSERT INTO symptoms(symptom_name) VALUES ('$symptom_name')");
        $symptom_id = $conn->insert_id;
    }

    $conn->query("UPDATE symptom_log 
                  SET symptom_id=$symptom_id, severity_level=$severity, notes='$notes', date='$date' 
                  WHERE log_id=$log_id AND patient_id=$uid");

    $_SESSION['msg'] = "Symptom updated successfully.";
    header("Location: symptoms.php");
    exit();
}

// ===== Add new =====
if(isset($_POST['add_symptom'])){
    $symptom_name = $conn->real_escape_string($_POST['symptom_name']);
    $severity = intval($_POST['severity_level']);
    $notes = $conn->real_escape_string($_POST['notes']);
    $date = $_POST['date'];

    // Ensure symptom exists
    $res = $conn->query("SELECT symptom_id FROM symptoms WHERE symptom_name='$symptom_name'");
    if($res->num_rows > 0){
        $symptom_id = $res->fetch_assoc()['symptom_id'];
    } else {
        $conn->query("INSERT INTO symptoms(symptom_name) VALUES ('$symptom_name')");
        $symptom_id = $conn->insert_id;
    }

    $conn->query("INSERT INTO symptom_log(patient_id, symptom_id, date, severity_level, notes) 
                  VALUES ($uid, $symptom_id, '$date', $severity, '$notes')");
    $_SESSION['msg'] = "Symptom added successfully.";
    header("Location: symptoms.php");
    exit();
}

// ===== Fetch Symptoms (Grouped by Date) =====
$res = $conn->query("SELECT sl.*, s.symptom_name 
                     FROM symptom_log sl 
                     JOIN symptoms s ON sl.symptom_id = s.symptom_id 
                     WHERE sl.patient_id=$uid 
                     ORDER BY sl.date DESC, sl.log_id ASC");

$symptoms = [];
while($row = $res->fetch_assoc()){
    $symptoms[$row['date']][] = $row; // group by date
}

if(isset($_SESSION['msg'])){
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Symptom Tracker</title>
<style>
body {font-family:Arial,sans-serif;margin:0;background:#f9f9fb;color:#333;}
header {background:#6b46c1;color:white;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;}
nav a {color:#fff;margin:0 .6rem;text-decoration:none;font-weight:bold;}
nav a:hover {text-decoration:underline;}
.container {max-width:900px;margin:2rem auto;background:white;padding:1.5rem;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,.05);}
input, select, textarea {padding:.6rem;border:1px solid #ddd;border-radius:8px;width:100%;margin:.4rem 0;}
button {padding:.6rem 1rem;border:0;border-radius:10px;background:#6b46c1;color:white;cursor:pointer;margin-right:0.4rem;}
button:hover{background:#553c9a;}
table{width:100%;border-collapse:collapse;margin-top:1rem;}
th, td{border:1px solid #eee;padding:.6rem;text-align:left;vertical-align:top;}
th{background:#faf7ff;}
.success{background:#e6ffed;border-left:4px solid #22c55e;padding:.5rem;margin:.5rem 0;border-radius:6px;}
.inline{display:inline;}
.action-box{display:flex;gap:4px;}
.edit-btn,.delete-btn{padding:0.3rem 0.6rem;border-radius:6px;background:#6b46c1;color:white;cursor:pointer;border:0;}
.edit-btn.cancel{background:#9ca3af;text-decoration:none;display:inline-block;}
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
<h2>Symptom Tracker</h2>
<?php if($msg) echo "<div class='success'>$msg</div>"; ?>

<!-- Add Symptom Form -->
<form method="post">
    <label>Symptom:</label>
    <input list="symptoms_list" name="symptom_name" required>
    <datalist id="symptoms_list">
        <?php
        $res = $conn->query("SELECT symptom_name FROM symptoms ORDER BY symptom_name ASC");
        while($row = $res->fetch_assoc()){
            echo "<option value='{$row['symptom_name']}'>";
        }
        ?>
    </datalist>

    <label>Severity (1-5):</label>
    <input type="number" name="severity_level" min="1" max="5" required>

    <label>Notes:</label>
    <textarea name="notes" rows="2"></textarea>

    <label>Date:</label>
    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>

    <button name="add_symptom">Add Symptom</button>
</form>

<!-- Symptoms Grouped by Date -->
<h3 style="color:#6b46c1">Existing Symptoms</h3>

<?php foreach($symptoms as $date => $entries): ?>
    <h4><?= $date ?></h4>
    <table>
    <tr><th>Symptom</th><th>Severity</th><th>Notes</th><th>Action</th></tr>
    <?php foreach($entries as $entry): ?>
    <tr>
        <?php if(isset($_GET['edit_id']) && $_GET['edit_id']==$entry['log_id']): ?>
        <form method="post">
            <td><input type="text" name="symptom_name" value="<?= $entry['symptom_name'] ?>" required></td>
            <td><input type="number" name="severity_level" min="1" max="5" value="<?= $entry['severity_level'] ?>" required></td>
            <td><input type="text" name="notes" value="<?= $entry['notes'] ?>"></td>
            <td>
                <input type="hidden" name="date" value="<?= $entry['date'] ?>">
                <input type="hidden" name="log_id" value="<?= $entry['log_id'] ?>">
                <button name="confirm_edit" class="edit-btn">Save</button>
                <a href="symptoms.php" class="edit-btn cancel">Cancel</a>
            </td>
        </form>
        <?php elseif(isset($_SESSION['confirm_delete']) && $_SESSION['confirm_delete']==$entry['log_id']): ?>
        <!-- Confirmation row -->
        <form method="post">
            <input type="hidden" name="log_id" value="<?= $entry['log_id'] ?>">
            <td colspan="4" style="text-align:center;">
                Are you sure you want to delete this symptom? 
                <button type="submit" name="confirm_delete" value="1" onclick="this.form.confirm.value='yes'">Yes</button>
                <button type="submit" name="confirm_delete" value="1" onclick="this.form.confirm.value='no'">No</button>
                <input type="hidden" name="confirm" value="">
            </td>
        </form>
        <?php else: ?>
        <td><?= $entry['symptom_name'] ?></td>
        <td><?= $entry['severity_level'] ?></td>
        <td><?= $entry['notes'] ?></td>
        <td>
            <div class="action-box">
                <form method="get" class="inline">
                    <input type="hidden" name="edit_id" value="<?= $entry['log_id'] ?>">
                    <button class="edit-btn">Edit</button>
                </form>
                <form method="post" class="inline">
                    <input type="hidden" name="log_id" value="<?= $entry['log_id'] ?>">
                    <button type="submit" name="confirm_delete" class="delete-btn">Delete</button>
                </form>
            </div>
        </td>
        <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endforeach; ?>

</div>
</body>
</html>