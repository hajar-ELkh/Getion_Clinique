<?php
// Database connection (connectDB.php)
include 'connectDB.php';
session_start();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['role'] = $user['role']; // Example roles: admin, doctor, secretary
        $_SESSION['username'] = $user['username'];

        // Redirect based on role
        switch ($user['role']) {
            case 'admin':
                header('Location: secretaire.php');
                break;
            case 'doctor':
                header('Location: doctor.php');
                break;
            case 'secretary':
                header('Location: secretaire.php');
                break;
            default:
                echo "Role not recognized.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!-- Login Form (index.php) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>

<!-- Admin Dashboard (dashboard_admin.php) -->
<?php
session_start();
if ($_SESSION['role'] !== 'secretaire') {
    header('Location: interface.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Welcome, Admin <?php echo $_SESSION['username']; ?>!</h1>
    <nav>
        <ul>
            <li><a href="manage_patients.php">Manage Patients</a></li>
            <li><a href="manage_staff.php">Manage Staff</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</body>
</html>

<!-- Doctor Dashboard (dashboard_doctor.php) -->
<?php
session_start();
if ($_SESSION['role'] !== 'doctor') {
    header('Location: interface.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Welcome, Dr. <?php echo $_SESSION['username']; ?>!</h1>
    <nav>
        <ul>
            <li><a href="view_patients.php">View Patients</a></li>
            <li><a href="add_consultation.php">Add Consultation</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</body>
</html>

<!-- Secretary Dashboard (dashboard_secretary.php) -->
<?php
session_start();
if ($_SESSION['role'] !== 'secretaire') {
    header('Location: interface.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Welcome, Secretary <?php echo $_SESSION['username']; ?>!</h1>
    <nav>
        <ul>
            <li><a href="schedule_appointments.php">Schedule Appointments</a></li>
            <li><a href="manage_patient_files.php">Manage Patient Files</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</body>
</html>

<!-- Logout Functionality (logout.php) -->
<?php
session_start();
session_unset();
session_destroy();
header('Location: interface.html');
exit;
?>
