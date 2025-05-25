<?php
require_once 'config.php'; // Use config.php

// if (!isLoggedIn()) {
//     header("Location: login.php");
//     exit();
// }

$user = $_SESSION['user']; // Or $user = getCurrentUser();
$message = '';
$error_message = '';

// Handle Create
if (isset($_POST['add'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    // Optional fields from SQL not in basic form: user_id, emergency_contact_name, emergency_contact_phone, blood_group, allergies

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($gender) || empty($phone)) {
        $error_message = "Please fill all required fields.";
    } else {
        // Assuming patient user_id is null or handled differently for now
        $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, date_of_birth, gender, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
        // Corrected bind_param types: sss sss (gender is ENUM, treated as string)
        $stmt->bind_param("ssssss", $first_name, $last_name, $date_of_birth, $gender, $phone, $address);
        if ($stmt->execute()) {
            $message = "Patient added successfully!";
        } else {
            $error_message = "Error adding patient: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Patient deleted successfully!";
        } else {
            $error_message = "Error deleting patient: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $error_message = "Invalid patient ID for deletion.";
    }
     // Redirect to clean URL after delete
    header("Location: patients.php" . ($message ? "?message=" . urlencode($message) : "") . ($error_message ? "?error_message=" . urlencode($error_message) : ""));
    exit();
}

// Get All Patients
$result = $conn->query("SELECT id, first_name, last_name, date_of_birth, gender, phone, address FROM patients ORDER BY last_name, first_name");

if(isset($_GET['message'])) $message = htmlspecialchars($_GET['message']);
if(isset($_GET['error_message'])) $error_message = htmlspecialchars($_GET['error_message']);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Patients</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Manage Patients</h2>

        <?php if ($message): ?><p style="color:green;"><?php echo $message; ?></p><?php endif; ?>
        <?php if ($error_message): ?><p style="color:red;"><?php echo $error_message; ?></p><?php endif; ?>

        <h3>Add New Patient</h3>
        <form method="POST" action="patients.php">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="date" name="date_of_birth" placeholder="Date of Birth" required>
            <select name="gender" required>
                <option value="" disabled selected>Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
            <input type="text" name="phone" placeholder="Phone (e.g., 01xxxxxxxxx)" required>
            <input type="text" name="address" placeholder="Address">
            <button type="submit" name="add">Add Patient</button>
        </form>

        <h3 style="margin-top:30px;">Existing Patients</h3>
        <table border="1" cellpadding="10" style="margin-top:10px; width: 100%; border-collapse: collapse;">
            <tr>
                <th>ID</th><th>Full Name</th><th>Date of Birth</th><th>Gender</th><th>Phone</th><th>Address</th><th>Actions</th>
            </tr>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['date_of_birth']); ?></td>
                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td>
                        <a href="patients.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this patient?');">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No patients found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php $conn->close(); ?>
</body>
</html>