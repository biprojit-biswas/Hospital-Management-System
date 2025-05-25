<?php
require_once 'config.php'; // Use config.php

// No session_start() needed here, config.php handles it

if (!isLoggedIn()) { // Use isLoggedIn function from config.php
    header("Location: login.php");
    exit();
}

// $user array should be available from $_SESSION['user'] as set in login.php
// Or use getCurrentUser() if you prefer more structured access
$user = $_SESSION['user']; // Or $user = getCurrentUser();

// Fetch counts (placeholder logic)
$totalPatients = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch_assoc()['count'] ?? 0;
$totalDoctors = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'] ?? 0;
$today = date("Y-m-d");
$appointmentsToday = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today'")->fetch_assoc()['count'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard.css"> </head>
<body>
    <div class="dashboard-container">
        <?php include 'sidebar.php'; // Ensure sidebar.php exists and paths are correct ?>

        <div class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</h1> <p>Role: <?php echo htmlspecialchars(ucfirst($user['user_role'])); ?></p> <div>
                <h3>Overview</h3>
                <ul>
                    <li>Total Patients: <?php echo $totalPatients; ?></li>
                    <li>Total Doctors: <?php echo $totalDoctors; ?></li>
                    <li>Appointments Today: <?php echo $appointmentsToday; ?></li>
                </ul>
            </div>
            </div>
    </div>
</body>
</html>