<?php
include 'DBconnect.php';
session_start();
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_or_user = trim($_POST['email_or_user']);
    $pass = $_POST['password'];

    // Allow login by email or username
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? OR user_name=?");
    $stmt->bind_param("ss", $email_or_user, $email_or_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $dbPass = $user['password'];
        $valid = false;

        // ✅ Check hashed password first
        if (password_verify($pass, $dbPass)) {
            $valid = true;
        } 
        // ✅ Fallback: plain text check for old accounts
        elseif ($pass === $dbPass) {
            $valid = true;
            // Upgrade old plain password to hashed
            $newHash = password_hash($pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $upd->bind_param("si", $newHash, $user['user_id']);
            $upd->execute();
            $upd->close();
        }

        if ($valid) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'user') {
                header("Location: dashboard.php");
                exit();
            } elseif ($user['role'] === 'partner') {
                // ✅ Fetch linked patient from partner table
                $res = $conn->query("
                    SELECT linked_patient_id 
                    FROM partner 
                    WHERE partner_id=" . (int)$user['user_id'] . " 
                    LIMIT 1
                ");
                if ($res && $res->num_rows > 0) {
                    $_SESSION['linked_patient_id'] = $res->fetch_assoc()['linked_patient_id'];
                } else {
                    $_SESSION['linked_patient_id'] = null; // no patient linked
                }
                header("Location: partner.php");
                exit();
            } else {
                header("Location: index.php");
                exit();
            }
        } else {
            $msg = "<div class='error'>Invalid email/username or password</div>";
        }
    } else {
        $msg = "<div class='error'>Invalid email/username or password</div>";
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin:0; background:#f9f9fb; color:#333; }
        header { background: #6b46c1; color:white; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; }
        nav a { color:#fff; margin:0 .6rem; text-decoration:none; font-weight:bold; }
        .container { max-width:500px; margin:2rem auto; background:white; padding:1.5rem; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.05); }
        h2 { color:#6b46c1; margin-bottom:1rem; }
        input { padding:.6rem; border:1px solid #ddd; border-radius:8px; width:100%; margin:.4rem 0; box-sizing:border-box; }
        button { padding:.6rem 1rem; border:0; border-radius:10px; background:#6b46c1; color:white; font-weight:bold; cursor:pointer; width:100%; margin-top:.8rem; }
        button:hover { background:#553c9a; }
        .error { background:#fee2e2; border-left:4px solid #ef4444; padding:.6rem; border-radius:6px; margin:.5rem 0; color:#dc2626; }
        .link { text-align:center; margin-top:1rem; }
        .link a { color:#6b46c1; font-weight:bold; text-decoration:none; }
        .link a:hover { text-decoration:underline; }
    </style>
</head>
<body>

<header>
    <div><strong>Period Tracker & Mental Health Support Portal</strong></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<div class="container">
    <h2>Login</h2>
    <?php if ($msg) echo $msg; ?>

    <form method="post">
        <label>Email or Username</label>
        <input type="text" name="email_or_user" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p class="link">No account? <a href="register.php">Register</a></p>
</div>

</body>
</html>
