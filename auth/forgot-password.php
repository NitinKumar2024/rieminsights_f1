<?php
require_once '../config.php';

// Initialize variables
$email = '';
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize_input($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email exists in the database
        $query = "SELECT id, user_name FROM users WHERE email = ? AND is_active = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) === 0) {
            $errors['email'] = 'Email not found or account is inactive';
        } else {
            mysqli_stmt_bind_result($stmt, $user_id, $user_name);
            mysqli_stmt_fetch($stmt);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // If no errors, proceed with password reset
    if (empty($errors)) {
        // Generate reset token
        $reset_token = generate_token();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database (create a password_resets table if needed)
        // For simplicity, we'll use a temporary solution
        // In a real application, you would create a password_resets table
        
        // Create password_resets table if it doesn't exist
        $create_table_query = "CREATE TABLE IF NOT EXISTS password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(100) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_email (email),
            INDEX idx_token (token)
        )";
        
        mysqli_query($conn, $create_table_query);
        
        // Delete any existing tokens for this user
        $delete_query = "DELETE FROM password_resets WHERE user_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
        
        // Insert new token
        $insert_query = "INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, "isss", $user_id, $email, $reset_token, $expiry);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            // Send password reset email
            $reset_link = SITE_URL . '/auth/reset-password.php?token=' . $reset_token . '&email=' . urlencode($email);
            
            // Email content
            $to = $email;
            $subject = "Password Reset - " . SITE_NAME;
            $message = "<html><body>";
            $message .= "<h2>Password Reset Request</h2>";
            $message .= "<p>Hello $user_name,</p>";
            $message .= "<p>We received a request to reset your password. Click the link below to reset your password:</p>";
            $message .= "<p><a href='$reset_link'>Reset Password</a></p>";
            $message .= "<p>This link will expire in 1 hour.</p>";
            $message .= "<p>If you did not request a password reset, please ignore this email.</p>";
            $message .= "<p>Regards,<br>" . SITE_NAME . " Team</p>";
            $message .= "</body></html>";
            
            // Headers
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
            
            // Send email
            // Uncomment the line below in a production environment
             mail($to, $subject, $message, $headers);
            
            // For development, we'll just show success message
            $success = true;
        } else {
            $errors['db_error'] = 'Failed to process password reset request: ' . mysqli_error($conn);
        }
        
        mysqli_stmt_close($insert_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-container forgot-password-container">
            <div class="logo">
                <h1>RiemInsights</h1>
                <p>AI-Powered Data Analytics</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p>Password reset instructions have been sent to your email.</p>
                    <p>Please check your inbox and follow the instructions to reset your password.</p>
                    <p class="mt-4"><a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt mr-2"></i> Return to Login</a></p>
                </div>
            <?php else: ?>
                <?php if (isset($errors['db_error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $errors['db_error']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="auth-intro mb-4">
                    <h4>Forgot Your Password?</h4>
                    <p>Enter your email address below and we'll send you instructions to reset your password.</p>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" placeholder="Enter your registered email">
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-reset">Send Reset Link <i class="fas fa-paper-plane ml-2"></i></button>
                    
                    <div class="text-center mt-4">
                        <p><a href="login.php"><i class="fas fa-arrow-left mr-1"></i> Back to Login</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Add animation to the form
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            if (form) {
                form.classList.add('fadeIn');
            }
        });
    </script>
</body>
</html>