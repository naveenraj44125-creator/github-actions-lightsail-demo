<?php
session_start();
require_once 'includes/auth.php';

// Destroy the session
session_destroy();

// Redirect to login page with success message
header('Location: login.php?message=logged_out');
exit();
?>
