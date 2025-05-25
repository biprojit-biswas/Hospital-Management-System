<?php
require_once 'config.php'; // Use config.php for db connection and session

// No session_start() needed here, config.php handles it

if (isLoggedIn()) { // Redirect if already logged in
    header("Location: dashboard.php");
    exit();
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password before verification

    if (empty($email) || empty($password)) {
        $login_error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password_hash, role FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            // For simplicity, storing the whole user array for now, but specific keys are better
            $_SESSION['user'] = $user; // Retained for compatibility with existing files like dashboard

            header("Location: dashboard.php");
            exit();
        } else {
            $login_error = "Invalid email or password, or account inactive.";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="login.css"> </head>
<body>
<div class="login-container">
    <form method="POST" action="">
        <h2>Login</h2>
        <?php if (!empty($login_error)): ?>
            <p style="color:red;"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>
        <input type="email" name="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </form>
</div>
</body>
</html>