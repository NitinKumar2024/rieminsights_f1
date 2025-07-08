<?php
require_once '../config.php';

// Initialize variables
$token = $email = $password = $confirm_password = '';
$errors = [];
$valid_token = false;
$reset_success = false;

// Check if token and email are provided
if (isset($_GET['token']) && isset($_GET['email'])) {
    $token = sanitize_input($_GET['token']);
    $email = sanitize_input($_GET['email']);
    
    // Validate token
    $query = "SELECT pr.id, pr.user_id, pr.expires_at, u.user_name 
              FROM password_resets pr 
              JOIN users u ON pr.user_id = u.id 
              WHERE pr.token = ? AND pr.email = ? AND pr.expires_at > NOW()";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $token, $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($reset_data = mysqli_fetch_assoc($result)) {
        $valid_token = true;
        $user_id = $reset_data['user_id'];
        $user_name = $reset_data['user_name'];
    } else {
        $errors['token'] = 'Invalid or expired token. Please request a new password reset link.';
    }
    
    mysqli_stmt_close($stmt);
} else {
    $errors['token'] = 'Token and email are required.';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Get form data
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no errors, proceed with password reset
    if (empty($errors)) {
        // Hash new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Delete all password reset tokens for this user
            $delete_query = "DELETE FROM password_resets WHERE user_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
            
            // Set success flag
            $reset_success = true;
            
            // Set flash message for login page
            set_flash_message('reset_success', 'Your password has been reset successfully. You can now log in with your new password.', 'alert alert-success');
        } else {
            $errors['db_error'] = 'Failed to reset password: ' . mysqli_error($conn);
        }
        
        mysqli_stmt_close($update_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            padding-top: 50px;
        }
        .reset-password-container {
            max-width: 450px;
        }
        .btn-reset {
            width: 100%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-password-container">
            <div class="logo">
                <h1>RiemInsights</h1>
                <p>AI-Powered Data Analytics</p>
            </div>
            
            <?php if (isset($errors['token'])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors['token']; ?>
                    <p class="mt-3"><a href="forgot-password.php" class="btn btn-primary">Request New Reset Link</a></p>
                </div>
            <?php elseif ($reset_success): ?>
                <div class="alert alert-success">
                    <p>Your password has been reset successfully!</p>
                    <p class="mt-3"><a href="login.php" class="btn btn-primary">Login with New Password</a></p>
                </div>
            <?php else: ?>
                <?php if (isset($errors['db_error'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['db_error']; ?></div>
                <?php endif; ?>
                
                <p class="mb-4">Enter your new password below to reset your account password.</p>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . $token . '&email=' . urlencode($email); ?>" method="post">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <?php if (isset($errors['password'])): ?>
                            <div class="error"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="error"><?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-reset">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>