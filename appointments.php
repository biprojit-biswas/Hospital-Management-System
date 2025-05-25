<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
$message = '';
$error_message = '';

// Fetch patients and doctors for dropdowns
$patients_result = $conn->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name");
$doctors_result = $conn->query("SELECT id, first_name, last_name FROM doctors ORDER BY last_name, first_name");


// Handle Create
if (isset($_POST['add'])) {
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
    $datetime_local = sanitizeInput($_POST['appointment_datetime']); // Combined datetime
    $reason = sanitizeInput($_POST['reason']);
    $status = sanitizeInput($_POST['status']); // Added status
    // $notes = sanitizeInput($_POST['notes']); // Added notes

    if (empty($patient_id) || empty($doctor_id) || empty($datetime_local) || empty($status)) {
        $error_message = "Patient, Doctor, Date/Time, and Status are required.";
    } else {
        // Split datetime-local into DATE and TIME
        $datetime_obj = new DateTime($datetime_local);
        $appointment_date = $datetime_obj->format('Y-m-d');
        $appointment_time = $datetime_obj->format('H:i:s');

        $stmt = $conn->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?)");
        // Corrected bind_param types: ii ssss
        $stmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $status);
        if ($stmt->execute()) {
            $message = "Appointment scheduled successfully!";
        } else {
            $error_message = "Error scheduling appointment: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $id);
         if ($stmt->execute()) {
            $message = "Appointment deleted successfully!";
        } else {
            $error_message = "Error deleting appointment: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = "Invalid appointment ID for deletion.";
    }
    header("Location: appointments.php" . ($message ? "?message=" . urlencode($message) : "") . ($error_message ? "?error_message=" . urlencode($error_message) : ""));
    exit();
}

// Get All Appointments with patient and doctor names
$query = "SELECT a.id, p.first_name as patient_first, p.last_name as patient_last, 
                 d.first_name as doctor_first, d.last_name as doctor_last, 
                 a.appointment_date, a.appointment_time, a.reason, a.status
          FROM appointments a
          JOIN patients p ON a.patient_id = p.id
          JOIN doctors d ON a.doctor_id = d.id
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $conn->query($query);

if(isset($_GET['message'])) $message = htmlspecialchars($_GET['message']);
if(isset($_GET['error_message'])) $error_message = htmlspecialchars($_GET['error_message']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Appointments</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Manage Appointments</h2>

        <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error_message): ?><p style="color:red;"><?php echo $error_message; ?></p><?php endif; ?>

        <h3>Schedule New Appointment</h3>
        <form method="POST" action="appointments.php">
            <select name="patient_id" required>
                <option value="" disabled selected>Select Patient</option>
                <?php if ($patients_result && $patients_result->num_rows > 0): ?>
                    <?php while($p_row = $patients_result->fetch_assoc()): ?>
                        <option value="<?php echo $p_row['id']; ?>"><?php echo htmlspecialchars($p_row['first_name'] . ' ' . $p_row['last_name']); ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <select name="doctor_id" required>
                <option value="" disabled selected>Select Doctor</option>
                 <?php if ($doctors_result && $doctors_result->num_rows > 0): ?>
                    <?php while($d_row = $doctors_result->fetch_assoc()): ?>
                        <option value="<?php echo $d_row['id']; ?>"><?php echo htmlspecialchars($d_row['first_name'] . ' ' . $d_row['last_name'] . ($d_row['specialization'] ? ' ('.$d_row['specialization'].')' : '')); ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <input type="datetime-local" name="appointment_datetime" required>
            <input type="text" name="reason" placeholder="Reason for Appointment">
            <select name="status" required>
                <option value="scheduled">Scheduled</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button type="submit" name="add">Schedule Appointment</button>
        </form>

        <h3 style="margin-top:30px;">Scheduled Appointments</h3>
        <table border="1" cellpadding="10" style="margin-top:10px; width:100%; border-collapse:collapse;">
            <tr>
                <th>ID</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Actions</th>
            </tr>
             <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                    <td><?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['appointment_date']))); ?></td>
                    <td><?php echo htmlspecialchars(date('h:i A', strtotime($row['appointment_time']))); ?></td>
                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($row['status'])); ?></td>
                    <td>
                        <a href="appointments.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this appointment?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No appointments found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php $conn->close(); ?>
</body>
</html>