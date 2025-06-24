<?php
session_start();

// Log the logout action if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once '../config/database.php';
    require_once '../includes/functions.php';
    
    log_action($conn, $_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?>
