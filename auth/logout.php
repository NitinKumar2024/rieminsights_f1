<?php
require_once '../config.php';

// Check if user is logged in
if (is_logged_in()) {
    // Clear all session variables
    $_SESSION = [];
    
    // If it's desired to kill the session, also delete the session cookie.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session.
    session_destroy();
}

// Redirect to login page
redirect(SITE_URL . '/auth/login.php');
?>