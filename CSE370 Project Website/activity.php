<?php
/***********************
 * Activity Tracker
 * - Prepared statements for add/edit/delete
 * - Valid confirm row markup
 * - CSRF protection
 * - Output escaping
 ***********************/
include 'DBconnect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Login required.";
    exit();
}

$uid = (int)$_SESSION['user_id'];

/* ---------------- Utilities ---------------- */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function ensure_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf_or_fail() {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['msg'] = "Invalid request. Please try again.";
        header("Location: activity.php");
        exit();
    }
}

function clamp_int($v, $min, $max, $default) {
    if ($v === '' || $v === null) return $default;
    $v = (int)$v;
    if ($v < $min) $v = $min;
    if ($v > $max) $v = $max;
    return $v;
}

function validate_date_or_today($d) {
    $t = DateTime::createFromFormat('Y-m-d', $d);
    $errors = DateTime::getLastErrors();
    if ($t && $errors['warning_count'] === 0 && $errors['error_count'] === 0) {
        return $d;
    }
    return date('Y-m-d');
}

/* -------------- Flash message -------------- */
if (!isset($_SESSION['msg'])) $_SESSION['msg'] = "";

/* -------- Cancel deletion (GET/POST) ------- */
if (isset($_POST['cancel_delete'])) {
    // POST path (has CSRF)
    check_csrf_or_fail();
    unset($_SESSION['confirm_delete']);
    header("Location: activity.php");
    exit();
}
if (isset($_GET['cancel_delete'])) {
    // Safe to allow GET to clear a UI flag
    unset($_SESSION['confirm_delete']);
    header("Location: activity.php");
    exit();
}

/* ----------------- Delete ------------------ */
if (isset($_POST['confirm_delete'])) {
    check_csrf_or_fail();

    $log_id = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;

    // If explicit confirmation set to yes, perform delete
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $stmt = $conn->prepare("DELETE FROM activity_log WHERE activity_id=? AND patient_id=?");
        $stmt->bind_param("ii", $log_id, $uid);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION['confirm_delete']);
        $_SESSION['msg'] = "Activity deleted successfully.";
        header("Location: activity.php");
        exit();
    } else {
        // Show confirm row for this id
        $_SESSION['confirm_delete'] = $log_id;
        header("Location: activity.php");
        exit();
    }
}

/* ------------------ Edit ------------------- */
if (isset($_POST['confirm_edit'])) {
    check_csrf_or_fail();

    $log_id = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;

    $date = validate_date_or_today($_POST['date'] ?? '');
    // Accept array + custom (like “Add” flow), or plain text if you only render text fields in the edit row
    $desc = '';
    if (!empty($_POST['activity_description']) && is_array($_POST['activity_description'])) {
        $desc = implode(', ', array_map('trim', $_POST['activity_description']));
    }
    $custom_activity = trim((string)($_POST['custom_activity'] ?? ''));
    if ($custom_activity !== '') {
        $desc .= ($desc === '' ? '' : ', ') . $custom_activity;
    }

    $mood = '';
    if (!empty($_POST['mood_description']) && is_array($_POST['mood_description'])) {
        $mood = implode(', ', array_map('trim', $_POST['mood_description']));
    }
    $custom_mood = trim((string)($_POST['custom_mood'] ?? ''));
    if ($custom_mood !== '') {
        $mood .= ($mood === '' ? '' : ', ') . $custom_mood;
    }

    $prod = clamp_int($_POST['productivity_score'] ?? '', 1, 10, 1);

    $stmt = $conn->prepare(
        "UPDATE activity_log 
         SET date=?, activity_description=?, mood_description=?, productivity_score=?
         WHERE activity_id=? AND patient_id=?"
    );
    $stmt->bind_param("sssiii", $date, $desc, $mood, $prod, $log_id, $uid);
    $stmt->execute();
    $stmt->close();

    $_SESSION['msg'] = "Activity updated successfully.";
    header("Location: activity.php");
    exit();
}

/* ----------------- Add new ----------------- */
if (isset($_POST['add_activity'])) {
    check_csrf_or_fail();

    $date = validate_date_or_today($_POST['date'] ?? '');

    $desc = '';
    if (!empty($_POST['activity_description']) && is_array($_POST['activity_description'])) {
        $desc = implode(', ', array_map('trim', $_POST['activity_description']));
    }
    $custom_activity = trim((string)($_POST['custom_activity'] ?? ''));
    if ($custom_activity !== '') {
        $desc .= ($desc === '' ? '' : ', ') . $custom_activity;
    }

    $mood = '';
    if (!empty($_POST['mood_description']) && is_array($_POST['mood_description'])) {
        $mood = implode(', ', array_map('trim', $_POST['mood_description']));
    }
    $custom_mood = trim((string)($_POST['custom_mood'] ?? ''));
    if ($custom_mood !== '') {
        $mood .= ($mood === '' ? '' : ', ') . $custom_mood;
    }

    $prod = clamp_int($_POST['productivity_score'] ?? '', 1, 10, 1);

    $stmt = $conn->prepare(
        "INSERT INTO activity_log (patient_id, date, activity_description, mood_description, productivity_score)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssi", $uid, $date, $desc, $mood, $prod);
    $stmt->execute();
    $stmt->close();

    $_SESSION['msg'] = "Activity added successfully.";
    header("Location: activity.php");
    exit();
}

/* ------------- Fetch Activities ASC -------- */
$activities = [];
$stmt = $conn->prepare(
    "SELECT activity_id, date, activity_description, mood_description, productivity_score
     FROM activity_log
     WHERE patient_id=?
     ORDER BY date ASC, activity_id ASC"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $activities[] = $row;
}
$stmt->close();

/* --------- Options for dropdowns ----------- */
$activity_options = ['Exercise', 'Work', 'Study', 'Leisure', 'Meditation', 'Other'];
$mood_options     = ['Happy', 'Sad', 'Anxious', 'Excited', 'Tired', 'Neutral'];

/* ---------------- Flash message ------------ */
$msg = $_SESSION['msg'] ?? '';
$_SESSION['msg'] = "";

/* ---------------- Page state --------------- */
$csrf = ensure_csrf_token();
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$confirm_delete_id = $_SESSION['confirm_delete'] ?? null;
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
input, select, textarea {padding:0.6rem; border:1px solid #ddd; border-radius:8px; width:100%; margin-top:0.4rem; box-sizing: border-box;}
button {padding:0.6rem 1rem; border:0; border-radius:10px; background:#6b46c1; color:white; font-weight:bold; cursor:pointer;}
table {width:100%; border-collapse:collapse; margin-top:1rem;}
th, td {border:1px solid #eee; padding:0.6rem; text-align:left; vertical-align: top;}
th {background:#faf7ff;}
.success {background:#e6ffed; border-left:4px solid #22c55e; padding:0.6rem; border-radius:6px; margin:1rem 0;}
.inline {display:inline;}
.action-box {display:flex; gap:6px; align-items:center;}
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
  <?php if ($msg): ?>
    <div class="success"><?= h($msg) ?></div>
  <?php endif; ?>

  <!-- Add Activity Form -->
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
    <label>Date:</label>
    <input type="date" name="date" value="<?= h(date('Y-m-d')) ?>" required>

    <label>Activities:</label>
    <div class="checkbox-group">
      <?php foreach ($activity_options as $act): ?>
        <label><input type="checkbox" name="activity_description[]" value="<?= h($act) ?>"> <?= h($act) ?></label>
      <?php endforeach; ?>
    </div>
    <input type="text" name="custom_activity" placeholder="Other activity">

    <label>Mood:</label>
    <div class="checkbox-group">
      <?php foreach ($mood_options as $m): ?>
        <label><input type="checkbox" name="mood_description[]" value="<?= h($m) ?>"> <?= h($m) ?></label>
      <?php endforeach; ?>
    </div>
    <input type="text" name="custom_mood" placeholder="Other mood">

    <label>Productivity (1-10):</label>
    <input type="number" name="productivity_score" min="1" max="10">

    <button type="submit" name="add_activity">Add Activity</button>
  </form>

  <!-- Activities Table -->
  <h3>Existing Activities</h3>
  <table>
    <tr><th>Date</th><th>Activities</th><th>Mood</th><th>Productivity</th><th>Action</th></tr>

    <?php foreach ($activities as $entry): ?>
      <?php
        $row_id = (int)$entry['activity_id'];
        $is_editing = ($edit_id === $row_id);
        $is_confirming_delete = ($confirm_delete_id !== null && (int)$confirm_delete_id === $row_id);
      ?>
      <?php if ($is_editing): ?>
        <tr>
          <td colspan="5">
            <form method="post" class="inline">
              <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
              <input type="hidden" name="log_id" value="<?= $row_id ?>">
              <div style="display:grid; grid-template-columns: 1fr 2fr; gap:12px; align-items:center;">
                <label for="date_<?= $row_id ?>">Date</label>
                <input id="date_<?= $row_id ?>" type="date" name="date" value="<?= h($entry['date']) ?>" required>

                <label for="act_<?= $row_id ?>">Activities</label>
                <input id="act_<?= $row_id ?>" type="text" name="custom_activity" value="<?= h($entry['activity_description']) ?>">

                <label for="mood_<?= $row_id ?>">Mood</label>
                <input id="mood_<?= $row_id ?>" type="text" name="custom_mood" value="<?= h($entry['mood_description']) ?>">

                <label for="prod_<?= $row_id ?>">Productivity</label>
                <input id="prod_<?= $row_id ?>" type="number" name="productivity_score" min="1" max="10" value="<?= h($entry['productivity_score']) ?>">
              </div>
              <div class="action-box" style="margin-top:10px;">
                <button type="submit" name="confirm_edit" class="edit-btn">Save</button>
                <a href="activity.php" class="edit-btn cancel">Cancel</a>
              </div>
            </form>
          </td>
        </tr>

      <?php elseif ($is_confirming_delete): ?>
        <tr>
          <td colspan="5" style="text-align:center;">
            <form method="post" class="inline" style="display:inline-block;">
              <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
              <input type="hidden" name="log_id" value="<?= $row_id ?>">
              <input type="hidden" name="confirm" value="yes">
              Are you sure you want to delete this activity?
              <button type="submit" name="confirm_delete" class="delete-btn">Yes</button>
              <button type="submit" name="cancel_delete" class="edit-btn cancel">No</button>
              <!-- Or use a GET link:
              <a class="edit-btn cancel" href="activity.php?cancel_delete=1">No</a>
              -->
            </form>
          </td>
        </tr>

      <?php else: ?>
        <tr>
          <td><?= h($entry['date']) ?></td>
          <td><?= h($entry['activity_description']) ?></td>
          <td><?= h($entry['mood_description']) ?></td>
          <td><?= h($entry['productivity_score']) ?></td>
          <td>
            <div class="action-box">
              <!-- Edit (GET) -->
              <form method="get" class="inline">
                <input type="hidden" name="edit_id" value="<?= $row_id ?>">
                <button class="edit-btn">Edit</button>
              </form>

              <!-- Delete (POST) -->
              <form method="post" class="inline">
                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                <input type="hidden" name="log_id" value="<?= $row_id ?>">
                <button type="submit" name="confirm_delete" class="delete-btn">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endif; ?>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
