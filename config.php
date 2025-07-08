<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password
define('DB_NAME', 'rieminsights_mvp');

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to ensure proper encoding
mysqli_set_charset($conn, "utf8mb4");

// Site Configuration
define('SITE_NAME', 'RiemInsights – AI-Powered Data Analytics');
define('SITE_URL', 'http://localhost/f1'); // Change this to your actual URL

// Email Configuration for Password Reset
define('MAIL_FROM', 'noreply@rieminsights.com');
define('MAIL_FROM_NAME', 'RiemInsights');

// Session Configuration
session_start();

/**
 * Function to sanitize user input
 * @param string $data - The data to be sanitized
 * @return string - Sanitized data
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

/**
 * Function to generate a random token
 * @param int $length - Length of the token
 * @return string - Generated token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Function to check if user is logged in
 * @return bool - True if logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Function to redirect user
 * @param string $location - URL to redirect to
 */
function redirect($location) {
    header("Location: $location");
    exit();
}

/**
 * Function to display flash messages
 * @param string $name - Name of the message
 * @param string $message - The message
 * @param string $class - CSS class for styling
 */
function set_flash_message($name, $message, $class = 'alert alert-success') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][$name] = [
        'message' => $message,
        'class' => $class
    ];
}

/**
 * Function to display flash messages
 * @param string $name - Name of the message
 * @return string - HTML for the flash message
 */
function display_flash_message($name) {
    if (!isset($_SESSION['flash_messages'][$name])) {
        return '';
    }
    
    $flash_message = $_SESSION['flash_messages'][$name];
    unset($_SESSION['flash_messages'][$name]);
    
    return '<div class="' . $flash_message['class'] . '">' . $flash_message['message'] . '</div>';
}
?>