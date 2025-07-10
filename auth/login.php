<?php
require_once '../config.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect(SITE_URL . '/index.php');
}

// Initialize variables
$email = $password = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no errors, proceed with login
    if (empty($errors)) {
        // Check if user exists
        $query = "SELECT id, user_name, email, password, is_active, email_verified FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['is_active']) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['user_name'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Update last login time
                    $update_query = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "i", $user['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                    
                    // Set flash message
                    set_flash_message('login_success', 'Login successful! Welcome back.', 'alert alert-success');
                    
                    // Redirect to home page
                    redirect(SITE_URL . '/index.php');
                } else {
                    $errors['account'] = 'Your account is inactive. Please contact support.';
                }
            } else {
                $errors['password'] = 'Invalid password';
            }
        } else {
            $errors['email'] = 'Email not found';
        }
        
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-container login-container">
            <div class="logo">
                <h1>RiemInsights</h1>
                <p>AI-Powered Data Analytics</p>
            </div>
            
            <?php echo display_flash_message('reset_success'); ?>
            
            <?php if (isset($errors['account'])): ?>
                <div class="alert alert-danger"><?php echo $errors['account']; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your email">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?php echo $errors['email']; ?></div>
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
                
                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">Sign In <i class="fas fa-sign-in-alt ml-2"></i></button>
                
                <div class="text-center mt-4">
                    <p>Don't have an account? <a href="signup.php">Create Account</a></p>
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