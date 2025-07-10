<?php
require_once '../config.php';

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    // Log the logout action
    $admin_id = $_SESSION['admin_id'];
    $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                  VALUES (?, 'admin_logout', 'Admin logout', ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    mysqli_stmt_bind_param($log_stmt, "iss", $admin_id, $ip, $user_agent);
    mysqli_stmt_execute($log_stmt);
    
    // Unset all admin session variables
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_role']);
}

// Redirect to login page
redirect(SITE_URL . '/admin/login.php');