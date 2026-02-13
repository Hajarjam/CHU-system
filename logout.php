<?php
require_once 'includes/session_security.php';

// Securely destroy the session
session_start();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>