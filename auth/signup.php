<?php
require_once '../config.php';

// Initialize variables
$name = $email = $password = $confirm_password = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate name
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors['email'] = 'Email already exists';
        }
        mysqli_stmt_close($stmt);
    }
    
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
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate verification token
        $verification_token = generate_token();
        
        // Insert user into database
        $query = "INSERT INTO users (user_name, email, password) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            
            // Store verification token in a separate table or update the user record
            // For simplicity, we'll just set a session variable for now
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['email'] = $email;
            
            // Send verification email (in a real application)
            // send_verification_email($email, $verification_token);
            
            // Set flash message
            set_flash_message('signup_success', 'Registration successful! Welcome to RiemInsights.', 'alert alert-success');
            
            // Redirect to home page
            redirect(SITE_URL . '/index.php');
        } else {
            $errors['db_error'] = 'Registration failed: ' . mysqli_error($conn);
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
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body {
            padding-top: 50px;
        }
        .signup-container {
            max-width: 500px;
        }
        .btn-signup {
            width: 100%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <div class="logo">
                <h1>RiemInsights</h1>
                <p>AI-Powered Data Analytics</p>
            </div>
            
            <?php if (isset($errors['db_error'])): ?>
                <div class="alert alert-danger"><?php echo $errors['db_error']; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>">
                    <?php if (isset($errors['name'])): ?>
                        <div class="error"><?php echo $errors['name']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>">
                    <?php if (isset($errors['email'])): ?>
                        <div class="error"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <?php if (isset($errors['password'])): ?>
                        <div class="error"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="error"><?php echo $errors['confirm_password']; ?></div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-signup">Sign Up</button>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Log In</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>