<?php
require_once 'config.php';

if (!isLoggedIn()) { // Assuming any logged-in user (esp. doctor) can manage. Refine role check if needed.
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];
$message = '';
$error_message = '';

// Fetch appointments, patients, and doctors for dropdowns
$appointments_result = $conn->query(
    "SELECT a.id, p.first_name as p_first, p.last_name as p_last, a.appointment_date 
     FROM appointments a 
     JOIN patients p ON a.patient_id = p.id 
     WHERE a.status = 'completed' OR a.status = 'confirmed' -- Example: Only for completed/confirmed appointments
     ORDER BY a.appointment_date DESC"
);
$patients_result = $conn->query("SELECT id, first_name, last_name FROM patients ORDER BY last_name, first_name");
$doctors_result = $conn->query("SELECT id, first_name, last_name FROM doctors ORDER BY last_name, first_name");


// Handle Create
if (isset($_POST['add'])) {
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT); // Can be NULL
    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT); // Assuming current logged in doctor or selected
    $medications = sanitizeInput($_POST['medications']);
    $dosage = sanitizeInput($_POST['dosage']);
    $instructions = sanitizeInput($_POST['instructions']);
    $prescribed_date = sanitizeInput($_POST['prescribed_date']);
    $valid_until = !empty($_POST['valid_until']) ? sanitizeInput($_POST['valid_until']) : NULL;
    $status = sanitizeInput($_POST['status']);

    if (empty($patient_id) || empty($doctor_id) || empty($medications) || empty($prescribed_date) || empty($status)) {
        $error_message = "Patient, Doctor, Medications, Prescribed Date, and Status are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, medications, dosage, instructions, prescribed_date, valid_until, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Corrected bind_param types: iiissssss
        $stmt->bind_param("iiissssss", $appointment_id, $patient_id, $doctor_id, $medications, $dosage, $instructions, $prescribed_date, $valid_until, $status);
        if ($stmt->execute()) {
            $message = "Prescription added successfully!";
        } else {
            $error_message = "Error adding prescription: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM prescriptions WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Prescription deleted successfully!";
        } else {
            $error_message = "Error deleting prescription: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = "Invalid prescription ID for deletion.";
    }
    header("Location: prescriptions.php" . ($message ? "?message=" . urlencode($message) : "") . ($error_message ? "?error_message=" . urlencode($error_message) : ""));
    exit();
}

// Get All Prescriptions
$query = "SELECT pr.id, p.first_name as patient_first, p.last_name as patient_last, 
                 d.first_name as doctor_first, d.last_name as doctor_last, 
                 pr.medications, pr.dosage, pr.instructions, pr.prescribed_date, pr.valid_until, pr.status,
                 pr.appointment_id
          FROM prescriptions pr
          JOIN patients p ON pr.patient_id = p.id
          JOIN doctors d ON pr.doctor_id = d.id
          ORDER BY pr.prescribed_date DESC";
$result = $conn->query($query);

if(isset($_GET['message'])) $message = htmlspecialchars($_GET['message']);
if(isset($_GET['error_message'])) $error_message = htmlspecialchars($_GET['error_message']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Prescriptions</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Manage Prescriptions</h2>

        <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error_message): ?><p style="color:red;"><?php echo $error_message; ?></p><?php endif; ?>

        <h3>Add New Prescription</h3>
        <form method="POST" action="prescriptions.php">
            <select name="appointment_id">
                <option value="">Select Related Appointment (Optional)</option>
                 <?php if ($appointments_result && $appointments_result->num_rows > 0): ?>
                    <?php while($app_row = $appointments_result->fetch_assoc()): ?>
                        <option value="<?php echo $app_row['id']; ?>">
                            Appt #<?php echo $app_row['id']; ?> - <?php echo htmlspecialchars($app_row['p_first'] . ' ' . $app_row['p_last']); ?> (<?php echo htmlspecialchars(date('M d, Y', strtotime($app_row['appointment_date']))); ?>)
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
            <select name="patient_id" required>
                <option value="" disabled selected>Select Patient *</option>
                 <?php if ($patients_result && $patients_result->num_rows > 0): ?>
                    <?php while($p_row = $patients_result->fetch_assoc()): ?>
                        <option value="<?php echo $p_row['id']; ?>"><?php echo htmlspecialchars($p_row['first_name'] . ' ' . $p_row['last_name']); ?></option>
                    <?php endwhile; ?>
                     <?php mysqli_data_seek($patients_result, 0); // Reset pointer for next potential use ?>
                <?php endif; ?>
            </select>
             <select name="doctor_id" required>
                <option value="" disabled selected>Select Doctor *</option>
                 <?php if ($doctors_result && $doctors_result->num_rows > 0): ?>
                    <?php while($d_row = $doctors_result->fetch_assoc()): ?>
                        <option value="<?php echo $d_row['id']; ?>" <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'doctor' && $_SESSION['user_data']['doctor_id_internal'] == $d_row['id']) echo 'selected'; ?> > <?php echo htmlspecialchars($d_row['first_name'] . ' ' . $d_row['last_name']); ?>
                        </option>
                    <?php endwhile; ?>
                    <?php mysqli_data_seek($doctors_result, 0); // Reset pointer ?>
                <?php endif; ?>
            </select>
            <textarea name="medications" placeholder="Medications (e.g., Paracetamol 500mg, Amoxicillin 250mg)" required></textarea>
            <input type="text" name="dosage" placeholder="Dosage (e.g., 1 tablet thrice daily, 1 teaspoon twice daily)">
            <textarea name="instructions" placeholder="Instructions (e.g., After food, For 5 days)"></textarea>
            <input type="date" name="prescribed_date" required>
            <input type="date" name="valid_until" placeholder="Valid Until (Optional)">
            <select name="status" required>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button type="submit" name="add">Add Prescription</button>
        </form>

        <h3 style="margin-top:30px;">Existing Prescriptions</h3>
        <table border="1" cellpadding="10" style="margin-top:10px; width:100%; border-collapse:collapse;">
            <tr>
                <th>ID</th><th>Appt ID</th><th>Patient</th><th>Doctor</th><th>Medications</th><th>Dosage</th><th>Instructions</th><th>Prescribed</th><th>Valid Until</th><th>Status</th><th>Actions</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['appointment_id'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                    <td><?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row['medications'])); ?></td>
                    <td><?php echo htmlspecialchars($row['dosage']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($row['instructions'])); ?></td>
                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['prescribed_date']))); ?></td>
                    <td><?php echo $row['valid_until'] ? htmlspecialchars(date('M d, Y', strtotime($row['valid_until']))) : 'N/A'; ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($row['status'])); ?></td>
                    <td>
                        <a href="prescriptions.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this prescription?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="11">No prescriptions found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php $conn->close(); ?>
</body>
</html>