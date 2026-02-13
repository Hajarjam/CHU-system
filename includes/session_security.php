<?php
// Session security configuration
function init_secure_session() {
    // Set secure session parameters
    $secure = true; // Only transmit over HTTPS
    $httponly = true; // Prevent JavaScript access
    
    session_set_cookie_params([
        'lifetime' => 3600, // 1 hour
        'path' => '/',
        'secure' => $secure,
        'httponly' => $httponly
    ]);
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically (every 30 minutes)
    if (isset($_SESSION['last_regeneration']) && 
        time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

function check_session_security() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && 
        time() - $_SESSION['last_activity'] > 1800) {
        // Session expired
        session_unset();
        session_destroy();
        header("Location: index.php?error=session_expired");
        exit();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Verify user role for restricted pages
    $current_page = basename($_SERVER['PHP_SELF']);
    if (strpos($current_page, 'dashboard-medecin') !== false && $_SESSION['role'] !== 'medecin') {
        header("Location: index.php?error=unauthorized");
        exit();
    }
    if (strpos($current_page, 'dashboard-pharmacien') !== false && $_SESSION['role'] !== 'pharmacien') {
        header("Location: index.php?error=unauthorized");
        exit();
    }
}

// Function to safely destroy session
function destroy_session() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}
?>
