<?php
require_once 'config.php';

// if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') { // Only admin can manage doctors here
//     // Or redirect to a specific error page: header("Location: unauthorized.php");
//     die("Access Denied. You do not have permission to manage doctors.");
// }

$user = $_SESSION['user'];
$message = '';
$error_message = '';

if (isset($_POST['add'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $specialization = sanitizeInput($_POST['specialization']);
    $phone = sanitizeInput($_POST['phone']);
    $qualifications = sanitizeInput($_POST['qualifications']);
    $available_days = sanitizeInput($_POST['available_days']); // "Monday,Wednesday,Friday"
    $available_hours = sanitizeInput($_POST['available_hours']); // "09:00-17:00"

    if (empty($first_name) || empty($last_name) || empty($specialization) || empty($phone)) {
        $error_message = "Please fill all required fields.";
    } else {
        $user_id_for_doctor = filter_input(INPUT_POST, 'user_id_for_doctor', FILTER_VALIDATE_INT);
         $stmt = $conn->prepare("INSERT INTO doctors (user_id, first_name, last_name, specialization, phone, qualifications, available_days, available_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $user_id_for_doctor, $first_name, $last_name, $specialization, $phone, $qualifications, $available_days, $available_hours);
        if ($stmt->execute()) {
            $message = "Doctor added successfully!";
        } else {
            $error_message = "Error adding doctor: " . htmlspecialchars($stmt->error) . ". Ensure User ID exists if provided.";
        }
        $stmt->close();
    }
}
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM doctors WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Doctor deleted successfully!";
        } else {
            $error_message = "Error deleting doctor: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = "Invalid doctor ID for deletion.";
    }
    header("Location: doctors.php" . ($message ? "?message=" . urlencode($message) : "") . ($error_message ? "?error_message=" . urlencode($error_message) : ""));
    exit();
}

// Get All Doctors
$result = $conn->query("SELECT d.id, d.first_name, d.last_name, d.specialization, d.phone, d.available_days, d.available_hours, u.username 
                        FROM doctors d 
                        LEFT JOIN users u ON d.user_id = u.id 
                        ORDER BY d.last_name, d.first_name");

if(isset($_GET['message'])) $message = htmlspecialchars($_GET['message']);
if(isset($_GET['error_message'])) $error_message = htmlspecialchars($_GET['error_message']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Doctors</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Manage Doctors</h2>

        <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error_message): ?><p style="color:red;"><?php echo $error_message; ?></p><?php endif; ?>

        <h3>Add New Doctor</h3>
        <form method="POST" action="doctors.php">
            <input type="number" name="user_id_for_doctor" placeholder="User ID (from users table)" required> <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="text" name="specialization" placeholder="Specialization" required>
            <input type="text" name="phone" placeholder="Phone" required>
            <textarea name="qualifications" placeholder="Qualifications (e.g., MBBS, FCPS)"></textarea>
            <input type="text" name="available_days" placeholder="Available Days (e.g., Mon,Wed,Fri)">
            <input type="text" name="available_hours" placeholder="Available Hours (e.g., 09:00-13:00,14:00-17:00)">
            <button type="submit" name="add">Add Doctor</button>
        </form>

        <h3 style="margin-top:30px;">Existing Doctors</h3>
        <table border="1" cellpadding="10" style="margin-top:10px; width:100%; border-collapse:collapse;">
            <tr>
                <th>ID</th><th>Name</th><th>Username (User)</th><th>Specialization</th><th>Phone</th><th>Available Days</th><th>Available Hours</th><th>Actions</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['username'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['available_days']); ?></td>
                    <td><?php echo htmlspecialchars($row['available_hours']); ?></td>
                    <td>
                        <a href="doctor_profile.php?view=<?php echo $row['id']; ?>" class="btn-edit">View/Edit Profile</a>
                        <a href="doctors.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this doctor?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No doctors found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php $conn->close(); ?>
</body>
</html>