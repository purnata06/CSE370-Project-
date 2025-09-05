<?php
include 'DBconnect.php';
session_start();
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full = trim($_POST['full_name']);
    $user = trim($_POST['user_name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Password validation
    $errors = [];
    if (strlen($pass) < 8) $errors[] = "Password must be at least 8 characters long.";
    if (!preg_match('/[A-Z]/', $pass)) $errors[] = "Password must include at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $pass)) $errors[] = "Password must include at least one lowercase letter.";
    if (!preg_match('/[0-9]/', $pass)) $errors[] = "Password must include at least one number.";
    if ($pass !== $confirm_pass) $errors[] = "Passwords do not match.";

    // Check duplicate username/email
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_name=? OR email=?");
    $stmt->bind_param("ss", $user, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) $errors[] = "Username or Email already exists.";

    if (!empty($errors)) {
        $msg = "<div class='error'>";
        foreach ($errors as $err) {
            $msg .= $err . "<br>";
        }
        $msg .= "</div>";
    } else {
        // Hash password
        $hashedPass = password_hash($pass, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (full_name, user_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $full, $user, $email, $hashedPass, $role);

        if ($stmt->execute()) {
            $uid = $conn->insert_id;

            // Insert into patient or partner table using prepared statements
            if ($role === 'user') {
                $stmt2 = $conn->prepare("INSERT INTO patient (patient_id) VALUES (?)");
                $stmt2->bind_param("i", $uid);
                $stmt2->execute();
                $stmt2->close();
            } else {
                $stmt2 = $conn->prepare("INSERT INTO partner (partner_id) VALUES (?)");
                $stmt2->bind_param("i", $uid);
                $stmt2->execute();
                $stmt2->close();
            }

            $_SESSION['user_id'] = $uid;
            $_SESSION['role'] = $role;
            
            // Redirect based on role
            if ($role === 'user') {
                header("Location: dashboard.php");
            } else {
                header("Location: partner.php");
            }
            exit();
        } else {
            $msg = "<div class='error'>Registration failed: " . $conn->error . "</div>";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Register</title>
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
            margin: 0 .6rem; 
            text-decoration: none; 
            font-weight: bold; 
        }
        .container { 
            max-width: 600px; 
            margin: 2rem auto; 
            background: white; 
            padding: 2rem; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,.08); 
        }
        h2 { 
            color: #6b46c1; 
            margin-bottom: 1rem; 
        }
        input, select { 
            padding: .7rem; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            width: 100%; 
            margin: .5rem 0 1rem; 
            box-sizing: border-box;
        }
        button { 
            padding: .7rem 1.2rem; 
            border: 0; 
            border-radius: 10px; 
            background: #6b46c1; 
            color: white; 
            font-weight: bold; 
            cursor: pointer; 
            width: 100%; 
            transition: background .3s; 
        }
        button:hover { 
            background: #553c9a; 
        }
        .error { 
            background: #fee2e2; 
            border-left: 4px solid #ef4444; 
            padding: .6rem; 
            border-radius: 6px; 
            margin-bottom: 1rem; 
            color: #dc2626;
        }
        .link { 
            text-align: center; 
            margin-top: 1rem; 
        }
        .link a { 
            color: #6b46c1; 
            font-weight: bold; 
            text-decoration: none; 
        }
        .link a:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>

<header>
    <div><strong>Period Tracker & Mental Health Support Portal</strong></div>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<div class="container">
    <h2>Create Account</h2>

    <?php if ($msg) echo $msg; ?>

    <form method="post">
        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>User Name</label>
        <input type="text" name="user_name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Gender</label>
         <select name="gender">
            <option value="Female">Female</option>
            <option value="Male">Male</option>
        </select>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" required>
        

        <label>Role:</label>
        <select name="role">
            <option value="user">User</option>
            <option value="partner">Partner</option>
        </select>

        <button type="submit">Register</button>
    </form>

    <p class="link">Already have an account? <a href="login.php">Login</a></p>
</div>

</body>
</html>