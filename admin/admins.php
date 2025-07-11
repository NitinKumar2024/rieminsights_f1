<?php
require_once '../config.php';

// Set page title and active page
$page_title = 'Admin Users';
$active_page = 'admins';

// Include header
require_once 'includes/header.php';

// Check if current admin has super_admin role
$is_super_admin = ($_SESSION['admin_role'] === 'super_admin');

// If not super admin, redirect to dashboard
if (!$is_super_admin) {
    // Set flash message
    $_SESSION['flash_message'] = 'You do not have permission to access the Admin Users page.';
    $_SESSION['flash_message_type'] = 'danger';
    
    // Redirect to dashboard
    redirect(SITE_URL . '/admin/dashboard.php');
    exit;
}

// Process admin status toggle
if (isset($_POST['toggle_status'])) {
    $admin_id = (int)$_POST['admin_id'];
    $current_status = (int)$_POST['current_status'];
    $new_status = $current_status ? 0 : 1;
    
    // Don't allow deactivating your own account
    if ($admin_id == $_SESSION['admin_id']) {
        $_SESSION['flash_message'] = 'You cannot deactivate your own account.';
        $_SESSION['flash_message_type'] = 'danger';
    } else {
        $query = "UPDATE admin_users SET is_active = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $new_status, $admin_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $status_text = $new_status ? 'activated' : 'deactivated';
            $_SESSION['flash_message'] = "Admin user has been {$status_text} successfully.";
            $_SESSION['flash_message_type'] = 'success';
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "Admin {$status_text} admin user ID: {$admin_id}";
            $action = $new_status ? 'admin_activated' : 'admin_deactivated';
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "isss", $_SESSION['admin_id'], $action, $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $_SESSION['flash_message'] = 'Error updating admin status: ' . mysqli_error($conn);
            $_SESSION['flash_message_type'] = 'danger';
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Redirect to refresh the page
    redirect(SITE_URL . '/admin/admins.php');
    exit;
}

// Process admin deletion
if (isset($_POST['delete_admin'])) {
    $admin_id = (int)$_POST['admin_id'];
    
    // Don't allow deleting your own account
    if ($admin_id == $_SESSION['admin_id']) {
        $_SESSION['flash_message'] = 'You cannot delete your own account.';
        $_SESSION['flash_message_type'] = 'danger';
    } else {
        // First check if this is the last super admin
        $check_query = "SELECT COUNT(*) as count FROM admin_users WHERE role = 'super_admin' AND is_active = 1";
        $check_result = mysqli_query($conn, $check_query);
        $super_admin_count = mysqli_fetch_assoc($check_result)['count'];
        
        // Get the role of the admin to be deleted
        $role_query = "SELECT role FROM admin_users WHERE id = ?";
        $role_stmt = mysqli_prepare($conn, $role_query);
        mysqli_stmt_bind_param($role_stmt, "i", $admin_id);
        mysqli_stmt_execute($role_stmt);
        $role_result = mysqli_stmt_get_result($role_stmt);
        $admin_role = mysqli_fetch_assoc($role_result)['role'];
        mysqli_stmt_close($role_stmt);
        
        // If this is the last super admin, don't allow deletion
        if ($super_admin_count <= 1 && $admin_role === 'super_admin') {
            $_SESSION['flash_message'] = 'Cannot delete the last super admin account.';
            $_SESSION['flash_message_type'] = 'danger';
        } else {
            // Delete the admin
            $query = "DELETE FROM admin_users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $admin_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['flash_message'] = 'Admin user has been deleted successfully.';
                $_SESSION['flash_message_type'] = 'success';
                
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                              VALUES (?, ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Admin deleted admin user ID: {$admin_id}";
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($log_stmt, "isss", $_SESSION['admin_id'], 'admin_deleted', $details, $ip);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                $_SESSION['flash_message'] = 'Error deleting admin: ' . mysqli_error($conn);
                $_SESSION['flash_message_type'] = 'danger';
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    // Redirect to refresh the page
    redirect(SITE_URL . '/admin/admins.php');
    exit;
}

// Process add new admin
if (isset($_POST['add_admin'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $role = sanitize_input($_POST['role']);
    
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
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (empty($role) || !in_array($role, ['admin', 'super_admin'])) {
        $errors[] = "Invalid role selected";
    }
    
    // Check if email or username already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM admin_users WHERE email = ? OR username = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ss", $email, $username);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "Email or username already exists";
        }
        
        mysqli_stmt_close($check_stmt);
    }
    
    // If no errors, add the admin
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO admin_users (full_name, email, username, password, role, is_active) 
                  VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $full_name, $email, $username, $hashed_password, $role);
        
        if (mysqli_stmt_execute($stmt)) {
            $new_admin_id = mysqli_insert_id($conn);
            $_SESSION['flash_message'] = 'New admin user has been added successfully.';
            $_SESSION['flash_message_type'] = 'success';
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "Admin added new admin user ID: {$new_admin_id}, Role: {$role}";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "isss", $_SESSION['admin_id'], 'admin_added', $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $_SESSION['flash_message'] = 'Error adding admin: ' . mysqli_error($conn);
            $_SESSION['flash_message_type'] = 'danger';
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['flash_message'] = 'Please fix the following errors: ' . implode(", ", $errors);
        $_SESSION['flash_message_type'] = 'danger';
    }
    
    // Redirect to refresh the page
    redirect(SITE_URL . '/admin/admins.php');
    exit;
}

// Get all admin users
$admins = [];
$query = "SELECT * FROM admin_users ORDER BY id ASC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $admins[] = $row;
    }
    mysqli_free_result($result);
}

// Display flash message if any
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_message_type'];
    
    // Clear the flash message
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
}
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Admin Users</h1>
    <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addAdminModal">
        <i class="fas fa-user-plus fa-sm text-white-50 mr-1"></i> Add New Admin
    </button>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- Admin Users Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Admin Users</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo $admin['id']; ?></td>
                        <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $admin['role'] === 'super_admin' ? 'danger' : 'primary'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $admin['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $admin['last_login'] ? date('M d, Y g:i A', strtotime($admin['last_login'])) : 'Never'; ?>
                        </td>
                        <td>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                            <form method="post" class="d-inline-block mr-1">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $admin['is_active']; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-sm btn-<?php echo $admin['is_active'] ? 'warning' : 'success'; ?>" 
                                        data-toggle="tooltip" title="<?php echo $admin['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $admin['is_active'] ? 'ban' : 'check'; ?>"></i>
                                </button>
                            </form>
                            
                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" 
                                    data-target="#deleteAdminModal" data-admin-id="<?php echo $admin['id']; ?>" 
                                    data-admin-name="<?php echo htmlspecialchars($admin['full_name']); ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php else: ?>
                            <span class="text-muted">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No admin users found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <small class="form-text text-muted">
                            Super Admin can manage other admins. Regular Admin can manage everything else.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_admin" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Admin Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" role="dialog" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAdminModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the admin user <span id="deleteAdminName" class="font-weight-bold"></span>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="post">
                    <input type="hidden" name="admin_id" id="deleteAdminId">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_admin" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Set admin ID and name in delete modal
    $('#deleteAdminModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var adminId = button.data('admin-id');
        var adminName = button.data('admin-name');
        
        var modal = $(this);
        modal.find('#deleteAdminId').val(adminId);
        modal.find('#deleteAdminName').text(adminName);
    });
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>