<?php
require_once '../config.php';

// Set page title and active page
$page_title = 'My Profile';
$active_page = 'profile';

// Include header
require_once 'includes/header.php';

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin = null;

$query = "SELECT * FROM admin_users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $admin = $row;
} else {
    // Redirect if admin not found
    redirect(SITE_URL . '/admin/logout.php');
}

mysqli_stmt_close($stmt);

// Process profile update
$message = '';
$message_type = '';

if (isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    // Check if email or username already exists (excluding current admin)
    if (!empty($email) && $email !== $admin['email']) {
        $check_query = "SELECT id FROM admin_users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $admin_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "Email is already in use by another admin";
        }
        
        mysqli_stmt_close($check_stmt);
    }
    
    if (!empty($username) && $username !== $admin['username']) {
        $check_query = "SELECT id FROM admin_users WHERE username = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $username, $admin_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "Username is already in use by another admin";
        }
        
        mysqli_stmt_close($check_stmt);
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $update_query = "UPDATE admin_users SET full_name = ?, email = ?, username = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssi", $full_name, $email, $username, $admin_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Profile updated successfully.";
            $message_type = 'success';
            
            // Update session variables
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_name'] = $full_name;
            
            // Refresh admin data
            $admin['full_name'] = $full_name;
            $admin['email'] = $email;
            $admin['username'] = $username;
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "Admin updated their profile";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, 'profile_updated', $details, $ip);
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
    if (empty($errors) && !password_verify($current_password, $admin['password'])) {
        $errors[] = "Current password is incorrect";
    }
    
    // If no errors, update password
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE admin_users SET password = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $admin_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "Password changed successfully.";
            $message_type = 'success';
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "Admin changed their password";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, 'password_changed', $details, $ip);
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
$log_query = "SELECT * FROM system_logs WHERE admin_id = ? ORDER BY timestamp DESC LIMIT 10";
$log_stmt = mysqli_prepare($conn, $log_query);
mysqli_stmt_bind_param($log_stmt, "i", $admin_id);
mysqli_stmt_execute($log_stmt);
$log_result = mysqli_stmt_get_result($log_stmt);

if ($log_result) {
    while ($row = mysqli_fetch_assoc($log_result)) {
        $activity_logs[] = $row;
    }
}

mysqli_stmt_close($log_stmt);
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
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
                <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['full_name']); ?>&background=4e73df&color=fff&size=128" 
                         class="img-profile rounded-circle" style="width: 128px; height: 128px;" alt="Profile Image">
                    <h4 class="mt-3"><?php echo htmlspecialchars($admin['full_name']); ?></h4>
                    <p class="text-muted">
                        <span class="badge badge-primary">
                            <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                        </span>
                    </p>
                </div>
                
                <div class="mb-3">
                    <h6 class="font-weight-bold">Username</h6>
                    <p><?php echo htmlspecialchars($admin['username']); ?></p>
                </div>
                
                <div class="mb-3">
                    <h6 class="font-weight-bold">Email</h6>
                    <p><?php echo htmlspecialchars($admin['email']); ?></p>
                </div>
                
                <div class="mb-3">
                    <h6 class="font-weight-bold">Account Created</h6>
                    <p><?php echo date('F j, Y', strtotime($admin['created_at'])); ?></p>
                </div>
                
                <div class="mb-3">
                    <h6 class="font-weight-bold">Last Login</h6>
                    <p><?php echo $admin['last_login'] ? date('F j, Y g:i A', strtotime($admin['last_login'])) : 'Never'; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Card -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Profile</h6>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
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
                <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
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
        
        <!-- Recent Activity Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
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
                                    if (strpos($log['action'], 'added') !== false) $action_class = 'primary';
                                    if (strpos($log['action'], 'deleted') !== false) $action_class = 'danger';
                                    ?>
                                    <span class="badge badge-<?php echo $action_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($log['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($activity_logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No activity logs found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>