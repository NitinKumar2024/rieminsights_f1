<?php
require_once '../config.php';

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    // Log the logout action
    $admin_id = $_SESSION['admin_id'];
    $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                  VALUES (?, ?, ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $details = "Admin logged out";
    $ip = $_SERVER['REMOTE_ADDR'];
    $status = "logout";
    mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $status , $details, $ip);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}

// Unset all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);

// Set flash message
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_message_type'] = 'success';

// Redirect to login page
redirect(SITE_URL . '/admin/login.php');
exit;
?>