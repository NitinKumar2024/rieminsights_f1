<?php
require_once '../config.php';

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/index.php');
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
        $query = "SELECT id, username, password, role, is_active FROM admin_users WHERE username = ?";
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
                    $_SESSION['admin_role'] = $admin['role'];
                    
                    // Update last login time
                    $update_query = "UPDATE admin_users SET last_login = NOW() WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "i", $admin['id']);
                    mysqli_stmt_execute($update_stmt);
                    
                    // Log the login action
                    $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                                  VALUES (?, 'admin_login', 'Admin login successful', ?, ?)";
                    $log_stmt = mysqli_prepare($conn, $log_query);
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];
                    mysqli_stmt_bind_param($log_stmt, "iss", $admin['id'], $ip, $user_agent);
                    mysqli_stmt_execute($log_stmt);
                    
                    // Redirect to admin dashboard
                    redirect(SITE_URL . '/admin/index.php');
                } else {
                    $errors['login'] = 'Your account is inactive. Please contact the system administrator.';
                }
            } else {
                $errors['login'] = 'Invalid username or password';
            }
        } else {
            $errors['login'] = 'Invalid username or password';
        }
    }
}

// Page title
$page_title = "Admin Login";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="login-container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i><?php echo SITE_NAME; ?> Admin</h4>
        </div>
        <div class="card-body p-4">
            <?php if (isset($errors['login'])): ?>
                <div class="alert alert-danger"><?php echo $errors['login']; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username">
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" placeholder="Enter your password">
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center py-3">
            <a href="<?php echo SITE_URL; ?>" class="text-decoration-none">← Back to Website</a>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Admin JS -->
<script src="../assets/js/admin.js"></script>

</body>
</html>