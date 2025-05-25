<?php
// Start session at the beginning
session_start();

// Database configuration
$host = 'localhost';
$username = 'root'; // Change this to your database username
$password = '';     // Change this to your database password
$database = 'hospital_management'; // Change this to your database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

/**
 * Check if user has required role
 */
function checkRole($allowedRoles = []) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        header("Location: login.php");
        exit();
    }
    
    if (!empty($allowedRoles) && !in_array($_SESSION['user_role'], $allowedRoles)) {
        http_response_code(403);
        die("Access denied. You don't have permission to access this page.");
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data); // Basic sanitation
    return $data;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'email' => $_SESSION['email'] ?? '', // Ensure these session variables are set on login
            'role' => $_SESSION['user_role']
        ];
    }
    return null;
}

/**
 * Logout user
 */
function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>