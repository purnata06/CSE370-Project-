<?php 
include 'DBconnect.php'; 
session_start(); 

// Normalize DB connection variable (try common names)
if (!isset($conn)) {
    if (isset($con)) $conn = $con;
    elseif (isset($mysqli)) $conn = $mysqli;
}

// Basic connection check
$connection_ok = true;
$db_error = '';
if (!isset($conn) || $conn === null) {
    $connection_ok = false;
    $db_error = "Database connection not found. Please check DBconnect.php.";
} else {
    // If mysqli, check for connect error
    if (class_exists('mysqli') && $conn instanceof mysqli) {
        if ($conn->connect_errno) {
            $connection_ok = false;
            $db_error = "DB connection error: " . htmlspecialchars($conn->connect_error);
        }
    }
    // If PDO you can add checks here (optional)
}

// Preserve the selected support type across reloads (so section remains visible after POST)
$current_type = $_POST['support_type'] ?? $_GET['show'] ?? '';

$msg = "";

// Handle psychiatrist connection safely
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only allow connection if user is logged in
    if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
        $uid = intval($_SESSION['user_id']);
        $support_type = $_POST['support_type'] ?? '';
        $current_type = $support_type; // keep for UI

        if ($support_type === 'psychiatrist') {
            $psy_id = intval($_POST['psychiatrist_id'] ?? 0);
            if ($psy_id > 0) {
                if ($connection_ok && $conn instanceof mysqli) {
                    // Use prepared statement
                    $stmt = $conn->prepare("INSERT IGNORE INTO patient_support_contacts (patient_id, psychiatrist_id) VALUES (?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ii", $uid, $psy_id);
                        if ($stmt->execute()) {
                            $msg = "✅ Connected to psychiatrist successfully!";
                        } else {
                            $msg = "⚠️ Could not connect — DB error executing query.";
                        }
                        $stmt->close();
                    } else {
                        $msg = "⚠️ Could not connect — DB error preparing statement.";
                    }
                } else {
                    $msg = "⚠️ Database connection not available. Connection failed.";
                }
            } else {
                $msg = "⚠️ Please select a valid psychiatrist to connect.";
            }
        }
    } else {
        $msg = "⚠️ You must be logged in to connect with a psychiatrist. Please login first.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Support</title>
  <style>
    body { font-family: Arial, sans-serif; margin:0; background:#f9f9fb; color:#333; }
    header { background:#6b46c1; color:white; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; }
    nav a { color:#fff; margin:0 .6rem; text-decoration:none; font-weight:bold; }
    nav a:hover { text-decoration:underline; }
    .container { max-width:1100px; margin:1.5rem auto; background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.05); }
    button { padding:.6rem 1rem; border:0; border-radius:10px; background:#6b46c1; color:white; font-weight:bold; cursor:pointer; }
    button:hover { background:#55309e; }
    table { width:100%; border-collapse:collapse; margin-top:1rem; }
    th, td { border:1px solid #eee; padding:.6rem; text-align:left; }
    th { background:#faf7ff; }
    .success { background:#e6ffed; border-left:4px solid #22c55e; padding:.6rem; border-radius:6px; margin:.5rem 0; }
    .warning { background:#fff6e6; border-left:4px solid #f59e0b; padding:.6rem; border-radius:6px; margin:.5rem 0; }
    .error { background:#ffe6e6; border-left:4px solid #ef4444; padding:.6rem; border-radius:6px; margin:.5rem 0; }
    ul { list-style-type: disc; padding-left: 1.2rem; }
  </style>
  <script>
    function showFields() {
      var typeElem = document.getElementById('support_type');
      if (!typeElem) return;
      var type = typeElem.value;
      var bookSection = document.getElementById('book_section');
      var psySection = document.getElementById('psy_section');
      if (bookSection) bookSection.style.display = (type === 'books') ? 'block' : 'none';
      if (psySection) psySection.style.display = (type === 'psychiatrist') ? 'block' : 'none';
    }
    // Ensure the proper section is visible on page load (helps after a POST)
    document.addEventListener('DOMContentLoaded', function() {
      showFields();
    });
  </script>
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

<?php 
if ($msg) {
    // success-ish detection
    $cls = (strpos($msg, '✅') !== false) ? 'success' : 'warning';
    echo "<div class='$cls'>$msg</div>";
}
if (!$connection_ok) {
    echo "<div class='error'>⚠️ $db_error</div>";
}
?>

<h3>Mental Health Support</h3>

<label>Select Support Type</label>
<select id="support_type" name="support_type" onchange="showFields()">
    <option value="">-- Select --</option>
    <option value="books" <?php echo ($current_type === 'books') ? 'selected' : ''; ?>>Books / Resources</option>
    <option value="psychiatrist" <?php echo ($current_type === 'psychiatrist') ? 'selected' : ''; ?>>Psychiatrist</option>
</select>

<!-- Books Section -->
<div id="book_section" style="display:none; margin-top:1rem;">
    <h4>Available Books / Resources (PDFs)</h4>
    <ul>
        <?php
        if ($connection_ok) {
            $res = $conn->query("SELECT * FROM books ORDER BY uploaded_at DESC");
            if ($res && $res->num_rows > 0) {
                while($row = $res->fetch_assoc()){
                    $title = htmlspecialchars($row['title'] ?? 'Untitled');
                    $filename = htmlspecialchars($row['filename'] ?? '');
                    // Basic sanity: only show link if filename present
                    if ($filename) {
                        echo "<li><a href='books/$filename' target='_blank' rel='noopener noreferrer'>$title</a></li>";
                    } else {
                        echo "<li>$title (file missing)</li>";
                    }
                }
            } else {
                echo "<li>No books available yet.</li>";
            }
        } else {
            echo "<li>Book list unavailable while DB connection is down.</li>";
        }
        ?>
    </ul>
</div>

<!-- Psychiatrist Section -->
<div id="psy_section" style="display:none; margin-top:1rem;">
    <h4>Available Psychiatrists</h4>

    <?php
    $connected_ids = [];
    $uid = $_SESSION['user_id'] ?? 0;
    if ($uid && $connection_ok) {
        $res = $conn->query("SELECT psychiatrist_id FROM patient_support_contacts WHERE patient_id=" . intval($uid));
        if ($res) {
            while($row = $res->fetch_assoc()){
                $connected_ids[] = intval($row['psychiatrist_id']);
            }
        }
    }
    ?>

    <form method="post">
        <input type="hidden" name="support_type" value="psychiatrist">
        <table>
            <tr><th>Select</th><th>Name</th><th>Specialization</th><th>Email</th><th>Mobile</th><th>Schedule</th><th>Location</th></tr>
            <?php 
            if ($connection_ok) {
                $ps = $conn->query("SELECT * FROM psychiatrists");
                if($ps && $ps->num_rows > 0){
                    while($p = $ps->fetch_assoc()){
                        $id = intval($p['id'] ?? 0);
                        $name = htmlspecialchars($p['name'] ?? '');
                        $spec = htmlspecialchars($p['specialization'] ?? '');
                        $email = htmlspecialchars($p['email'] ?? 'N/A');
                        $mobile = htmlspecialchars($p['mobile_number'] ?? 'N/A');
                        $schedule = htmlspecialchars($p['schedule'] ?? 'N/A');
                        $location = htmlspecialchars($p['location'] ?? 'N/A');

                        // disable radio if already connected
                        $disabled = in_array($id, $connected_ids) ? 'disabled' : '';
                        $note = in_array($id, $connected_ids) ? ' (Connected)' : '';
                        echo "<tr>
                                <td><input type='radio' name='psychiatrist_id' value='$id' $disabled required></td>
                                <td>$name$note</td>
                                <td>$spec</td>
                                <td>$email</td>
                                <td>$mobile</td>
                                <td>$schedule</td>
                                <td>$location</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No psychiatrists available.</td></tr>";
                }
            } else {
                echo "<tr><td colspan='7'>Psychiatrist list unavailable while DB connection is down.</td></tr>";
            }
            ?>
        </table>

        <div style="margin-top:0.8rem;">
            <?php if (!isset($_SESSION['user_id']) || !$_SESSION['user_id']): ?>
                <div class="warning">You must be logged in to connect with a psychiatrist. <a href="login.php">Login</a></div>
            <?php else: ?>
                <button type="submit">Connect</button>
            <?php endif; ?>
        </div>
    </form>

    <!-- Connected Psychiatrists Section -->
    <?php 
    if ($uid && $connection_ok) {
        $lst = $conn->query("SELECT p.* FROM psychiatrists p 
                             JOIN patient_support_contacts c 
                             ON p.id = c.psychiatrist_id 
                             WHERE c.patient_id = " . intval($uid));
        if ($lst && $lst->num_rows > 0) {
            echo "<h4>Your Connected Psychiatrists</h4><ul>";
            while ($x = $lst->fetch_assoc()) {
                $name = htmlspecialchars($x['name'] ?? '');
                $spec = htmlspecialchars($x['specialization'] ?? '');
                $email = htmlspecialchars($x['email'] ?? 'N/A');
                $mobile = htmlspecialchars($x['mobile_number'] ?? 'N/A');
                $schedule = htmlspecialchars($x['schedule'] ?? 'N/A');
                $location = htmlspecialchars($x['location'] ?? 'N/A');
                echo "<li>$name - $spec (Email: $email, Mobile: $mobile, Schedule: $schedule, Location: $location)</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>You haven't connected with any psychiatrists yet.</p>";
        }
    } elseif (!$uid) {
        // not logged in
        echo "<p><em>Log in to connect with psychiatrists and see your connected contacts.</em></p>";
    }
    ?>
</div>

</div>
</body>
</html>