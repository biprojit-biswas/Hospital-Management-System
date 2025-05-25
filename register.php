<?php
require_once 'config.php'; // Use config.php for db connection and session

// No session_start() needed here, config.php handles it

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']); // Changed from name to username
    $email = sanitizeInput($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = sanitizeInput($_POST['role']);

    // Validate role against allowed enum values
    $allowed_roles = ['admin', 'doctor', 'nurse']; // Align with SQL ENUM, assuming patients register differently
    if (!in_array($role, $allowed_roles)) {
        echo "Error: Invalid role selected.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password, $role);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            echo "Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
$conn->close();
?>
<form method="POST" action="">
    <h2>Register</h2>
    <input type="text" name="username" placeholder="Username" required> <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role" required>
        <option value="" disabled selected>Select Role</option>
        <option value="admin">Admin</option>
        <option value="doctor">Doctor</option>
        <option value="nurse">Nurse</option>
        </select>
    <button type="submit">Register</button>
</form>