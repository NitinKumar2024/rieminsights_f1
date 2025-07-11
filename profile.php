<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

// Set page title and active page
$page_title = 'My Profile';
$active_page = 'profile';

// Include header
require_once 'includes/header.php';

// Get user information
$user_id = $_SESSION['user_id'];
$user = null;

$query = "SELECT u.*, p.plan_name FROM users u 
          LEFT JOIN plans p ON u.plan_type = p.plan_type 
          WHERE u.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $user = $row;
} else {
    // Redirect if user not found
    redirect(SITE_URL . '/auth/logout.php');
}

mysqli_stmt_close($stmt);

// Process profile update
$message = '';
$message_type = '';

if (isset($_POST['update_profile'])) {
    $user_name = sanitize_input($_POST['user_name']);
    $email = sanitize_input($_POST['email']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($user_name)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists (excluding current user)
    if (!empty($email) && $email !== $user['email']) {
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "Email is already in use by another user";
        }
        
        mysqli_stmt_close($check_stmt);
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $update_query = "UPDATE users SET user_name = ?, email = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssi", $user_name, $email, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Profile updated successfully.";
            $message_type = 'success';
            
            // Update session variables if needed
            $_SESSION['user_email'] = $email;
            
            // Refresh user data
            $user['user_name'] = $user_name;
            $user['email'] = $email;
            
            // Log the action
            $log_query = "INSERT INTO system_logs (user_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "User updated their profile";
            $ip = $_SERVER['REMOTE_ADDR'];
            $status = "profile_updated";
            mysqli_stmt_bind_param($log_stmt, "isss", $user_id, $status, $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $message = "Error updating profile: " . mysqli_error($conn);
            $message_type = 'danger';
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        $message = "Please fix the following errors: " . implode(", ", $errors);
        $message_type = 'danger';
    }
}

// Process password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($current_password)) {
        $errors[] = "Current password is required";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters long";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    // Verify current password
    if (empty($errors) && !password_verify($current_password, $user['password'])) {
        $errors[] = "Current password is incorrect";
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Password changed successfully.";
            $message_type = 'success';
            
            // Log the action
            $log_query = "INSERT INTO system_logs (user_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "User changed their password";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "isss", $user_id, 'password_changed', $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $message = "Error changing password: " . mysqli_error($conn);
            $message_type = 'danger';
        }
        
        mysqli_stmt_close($update_stmt);
    } else {
        $message = "Please fix the following errors: " . implode(", ", $errors);
        $message_type = 'danger';
    }
}

// Get recent activity logs
$activity_logs = [];
$log_query = "SELECT * FROM system_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10";
$log_stmt = mysqli_prepare($conn, $log_query);
mysqli_stmt_bind_param($log_stmt, "i", $user_id);
mysqli_stmt_execute($log_stmt);
$log_result = mysqli_stmt_get_result($log_stmt);

if ($log_result) {
    while ($row = mysqli_fetch_assoc($log_result)) {
        $activity_logs[] = $row;
    }
}

mysqli_stmt_close($log_stmt);

// Get token usage statistics
$token_usage = [];
$token_query = "SELECT action_type, SUM(tokens_used) as total_tokens 
               FROM token_usage 
               WHERE user_id = ? 
               GROUP BY action_type";
$token_stmt = mysqli_prepare($conn, $token_query);
mysqli_stmt_bind_param($token_stmt, "i", $user_id);
mysqli_stmt_execute($token_stmt);
$token_result = mysqli_stmt_get_result($token_stmt);

if ($token_result) {
    while ($row = mysqli_fetch_assoc($token_result)) {
        $token_usage[$row['action_type']] = $row['total_tokens'];
    }
}

mysqli_stmt_close($token_stmt);

// Calculate total token usage
$total_token_usage = array_sum($token_usage);
$token_percentage = ($total_token_usage > 0 && $user['total_tokens_purchased'] > 0) 
                    ? ($total_token_usage / $user['total_tokens_purchased']) * 100 
                    : 0;
?>

<div class="container py-5">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">My Profile</h1>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Information Card -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Profile Information</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['user_name']); ?>&background=4e73df&color=fff&size=128" 
                             class="img-profile rounded-circle" style="width: 128px; height: 128px;" alt="Profile Image">
                        <h4 class="mt-3"><?php echo htmlspecialchars($user['user_name']); ?></h4>
                        <p class="text-muted">
                            <span class="badge badge-primary">
                                <?php echo ucfirst($user['plan_name'] ?? $user['plan_type']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Email</h6>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Account Created</h6>
                        <p><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Last Login</h6>
                        <p><?php echo $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Tokens</h6>
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: <?php echo min(100, $token_percentage); ?>%" 
                                 aria-valuenow="<?php echo $token_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($token_percentage); ?>%
                            </div>
                        </div>
                        <p>
                            <strong><?php echo number_format($user['tokens_remaining']); ?></strong> tokens remaining out of 
                            <strong><?php echo number_format($user['total_tokens_purchased']); ?></strong>
                        </p>
                        <?php if ($user['plan_type'] == 'free'): ?>
                        <a href="<?php echo SITE_URL; ?>/upgrade_plan.php" class="btn btn-sm btn-primary btn-block">
                            <i class="fas fa-arrow-circle-up mr-1"></i> Upgrade Plan
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Profile Card -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Edit Profile</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="userName">Username</label>
                            <input type="text" class="form-control" id="userName" name="user_name" value="<?php echo htmlspecialchars($user['user_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Change Password</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="currentPassword">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key mr-1"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Token Usage Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Token Usage</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Usage Type</th>
                                    <th>Tokens Used</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $usage_types = [
                                    'ai_query' => 'AI Queries',
                                    'data_analysis' => 'Data Analysis',
                                    'file_processing' => 'File Processing'
                                ];
                                
                                foreach ($usage_types as $type => $label): 
                                    $type_usage = isset($token_usage[$type]) ? $token_usage[$type] : 0;
                                    $type_percentage = ($total_token_usage > 0) ? ($type_usage / $total_token_usage) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?php echo $label; ?></td>
                                    <td><?php echo number_format($type_usage); ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $type_percentage; ?>%" 
                                                 aria-valuenow="<?php echo $type_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                <?php echo round($type_percentage); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if ($total_token_usage == 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No token usage data available</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity_logs as $log): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $action_class = 'secondary';
                                        if (strpos($log['action'], 'login') !== false) $action_class = 'success';
                                        if (strpos($log['action'], 'updated') !== false) $action_class = 'info';
                                        if (strpos($log['action'], 'uploaded') !== false) $action_class = 'primary';
                                        if (strpos($log['action'], 'deleted') !== false) $action_class = 'danger';
                                        ?>
                                        <span class="badge badge-<?php echo $action_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($log['timestamp'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($activity_logs)): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No activity logs found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>