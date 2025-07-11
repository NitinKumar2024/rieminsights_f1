<?php
require_once '../config.php';

// Check if admin is already logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect if already logged in
if (is_admin_logged_in()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

// Initialize variables
$username = $password = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    // Validate username
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no errors, proceed with login
    if (empty($errors)) {
        // Check if admin exists
        $query = "SELECT id, username, email, password, full_name, role, is_active FROM admin_users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($admin = mysqli_fetch_assoc($result)) {
            // Verify password
            if (password_verify($password, $admin['password'])) {
                // Check if account is active
                if ($admin['is_active']) {
                    // Set session variables
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    
                    // Update last login time
                    $update_query = "UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "i", $admin['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                    
                    // Log the login action
                    $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                                  VALUES (?, 'admin_login', 'Admin login successful', ?, ?)";
                    $log_stmt = mysqli_prepare($conn, $log_query);
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    mysqli_stmt_bind_param($log_stmt, "iss", $admin['id'], $ip, $user_agent);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                    
                    // Redirect to admin dashboard
                    redirect(SITE_URL . '/admin/dashboard.php');
                } else {
                    $errors['account'] = 'Your account is inactive. Please contact super admin.';
                }
            } else {
                $errors['password'] = 'Invalid password';
            }
        } else {
            $errors['username'] = 'Username not found';
        }
        
        mysqli_stmt_close($stmt);
    }
}

$page_title = "Admin Login";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .admin-login-container {
            border-top: 5px solid #4e73df;
        }
        .admin-badge {
            background-color: #4e73df;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container admin-login-container">
            <div class="logo">
                <h1>RiemInsights <span class="admin-badge">ADMIN</span></h1>
                <p>Admin Control Panel</p>
            </div>
            
            <?php if (isset($errors['account'])): ?>
                <div class="alert alert-danger"><?php echo $errors['account']; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" placeholder="Enter your username">
                    <?php if (isset($errors['username'])): ?>
                        <div class="error"><?php echo $errors['username']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
                        <i class="password-toggle fas fa-eye-slash" onclick="togglePasswordVisibility('password')"></i>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="error"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-login">Sign In <i class="fas fa-sign-in-alt ml-2"></i></button>
                
                <div class="text-center mt-4">
                    <a href="<?php echo SITE_URL; ?>" class="small"><i class="fas fa-arrow-left mr-1"></i> Back to Main Site</a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
    </script>
</body>
</html>