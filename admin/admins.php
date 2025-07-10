<?php
require_once '../config.php';

// Check if admin is logged in, if not redirect to login page
if (!isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/login.php');
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$query = "SELECT * FROM admin_users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Check if current admin is super_admin
$is_super_admin = ($admin['role'] === 'super_admin');

// If not super_admin, redirect to dashboard
if (!$is_super_admin) {
    set_flash_message('error', 'You do not have permission to access this page.', 'alert alert-danger');
    redirect(SITE_URL . '/admin/index.php');
}

// Initialize variables
$errors = [];
$success_message = '';

// Process admin actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new admin
    if (isset($_POST['action']) && $_POST['action'] === 'add_admin') {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $full_name = sanitize_input($_POST['full_name']);
        $role = sanitize_input($_POST['role']);
        
        // Validate inputs
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        // Check if username or email already exists
        $check_query = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors['general'] = 'Username or email already exists';
        }
        
        // If no errors, add the admin
        if (empty($errors)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert admin
            $insert_query = "INSERT INTO admin_users (username, email, password, full_name, role, is_active) 
                             VALUES (?, ?, ?, ?, ?, 1)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sssss", $username, $email, $hashed_password, $full_name, $role);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $new_admin_id = mysqli_insert_id($conn);
                
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'admin_created', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Created admin user: $username (ID: $new_admin_id) with role: $role";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "Admin user created successfully!";
            } else {
                $errors['general'] = "Error creating admin user: " . mysqli_error($conn);
            }
        }
    }
    
    // Update admin
    if (isset($_POST['action']) && $_POST['action'] === 'edit_admin') {
        $edit_admin_id = (int)$_POST['admin_id'];
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $full_name = sanitize_input($_POST['full_name']);
        $role = sanitize_input($_POST['role']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $new_password = $_POST['new_password'];
        
        // Validate inputs
        if (empty($username)) {
            $errors['edit_username'] = 'Username is required';
        }
        
        if (empty($email)) {
            $errors['edit_email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['edit_email'] = 'Invalid email format';
        }
        
        // Check if username or email already exists for other admins
        $check_query = "SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ssi", $username, $email, $edit_admin_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors['edit_general'] = 'Username or email already exists';
        }
        
        // If no errors, update the admin
        if (empty($errors)) {
            // If password is provided, update it too
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_query = "UPDATE admin_users SET 
                                 username = ?, 
                                 email = ?, 
                                 password = ?, 
                                 full_name = ?, 
                                 role = ?, 
                                 is_active = ? 
                                 WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "sssssii", $username, $email, $hashed_password, $full_name, $role, $is_active, $edit_admin_id);
            } else {
                $update_query = "UPDATE admin_users SET 
                                 username = ?, 
                                 email = ?, 
                                 full_name = ?, 
                                 role = ?, 
                                 is_active = ? 
                                 WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ssssii", $username, $email, $full_name, $role, $is_active, $edit_admin_id);
            }
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'admin_updated', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Updated admin user: $username (ID: $edit_admin_id)";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "Admin user updated successfully!";
            } else {
                $errors['edit_general'] = "Error updating admin user: " . mysqli_error($conn);
            }
        }
    }
    
    // Delete admin
    if (isset($_POST['action']) && $_POST['action'] === 'delete_admin') {
        $delete_admin_id = (int)$_POST['admin_id'];
        
        // Don't allow deleting self
        if ($delete_admin_id === $admin_id) {
            $errors['delete'] = "You cannot delete your own account.";
        } else {
            // Get admin info before deletion for logging
            $admin_query = "SELECT username FROM admin_users WHERE id = ?";
            $admin_stmt = mysqli_prepare($conn, $admin_query);
            mysqli_stmt_bind_param($admin_stmt, "i", $delete_admin_id);
            mysqli_stmt_execute($admin_stmt);
            $admin_result = mysqli_stmt_get_result($admin_stmt);
            $admin_info = mysqli_fetch_assoc($admin_result);
            $username = $admin_info['username'];
            
            // Delete admin
            $delete_query = "DELETE FROM admin_users WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $delete_admin_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'admin_deleted', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Deleted admin user: $username (ID: $delete_admin_id)";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "Admin user deleted successfully!";
            } else {
                $errors['delete'] = "Error deleting admin user: " . mysqli_error($conn);
            }
        }
    }
}

// Get all admin users
$admins_query = "SELECT * FROM admin_users ORDER BY id ASC";
$admins_result = mysqli_query($conn, $admins_query);

// Page title
$page_title = "Admin Users";
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

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><?php echo SITE_NAME; ?> - Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($admin['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tokens.php">
                            <i class="fas fa-coins me-2"></i> Token Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admins.php">
                            <i class="fas fa-user-shield me-2"></i> Admin Users
                        </a>
                    </li>

                     <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-cog"></i> Profile
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                
                 
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Users</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fas fa-user-plus"></i> Add New Admin
                </button>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errors['general']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($errors['delete'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $errors['delete']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Admin Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($admins_result) > 0): ?>
                                    <?php while ($admin_user = mysqli_fetch_assoc($admins_result)): ?>
                                        <tr>
                                            <td><?php echo $admin_user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($admin_user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($admin_user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($admin_user['full_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $admin_user['role'] === 'super_admin' ? 'danger' : ($admin_user['role'] === 'admin' ? 'primary' : 'info'); ?>">
                                                    <?php echo str_replace('_', ' ', ucwords($admin_user['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($admin_user['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $admin_user['last_login'] ? date('M d, Y H:i', strtotime($admin_user['last_login'])) : 'Never'; ?></td>
                                            <td class="admin-actions">
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editAdminModal<?php echo $admin_user['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($admin_user['id'] !== $admin_id): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAdminModal<?php echo $admin_user['id']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Admin Modal -->
                                        <div class="modal fade" id="editAdminModal<?php echo $admin_user['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="action" value="edit_admin">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin_user['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Admin User</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="edit_username<?php echo $admin_user['id']; ?>" class="form-label">Username</label>
                                                                <input type="text" class="form-control <?php echo isset($errors['edit_username']) ? 'is-invalid' : ''; ?>" id="edit_username<?php echo $admin_user['id']; ?>" name="username" value="<?php echo htmlspecialchars($admin_user['username']); ?>" required>
                                                                <?php if (isset($errors['edit_username'])): ?>
                                                                    <div class="invalid-feedback"><?php echo $errors['edit_username']; ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_email<?php echo $admin_user['id']; ?>" class="form-label">Email</label>
                                                                <input type="email" class="form-control <?php echo isset($errors['edit_email']) ? 'is-invalid' : ''; ?>" id="edit_email<?php echo $admin_user['id']; ?>" name="email" value="<?php echo htmlspecialchars($admin_user['email']); ?>" required>
                                                                <?php if (isset($errors['edit_email'])): ?>
                                                                    <div class="invalid-feedback"><?php echo $errors['edit_email']; ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_full_name<?php echo $admin_user['id']; ?>" class="form-label">Full Name</label>
                                                                <input type="text" class="form-control" id="edit_full_name<?php echo $admin_user['id']; ?>" name="full_name" value="<?php echo htmlspecialchars($admin_user['full_name'] ?? ''); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_role<?php echo $admin_user['id']; ?>" class="form-label">Role</label>
                                                                <select class="form-select" id="edit_role<?php echo $admin_user['id']; ?>" name="role">
                                                                    <option value="super_admin" <?php echo $admin_user['role'] === 'super_admin' ? 'selected' : ''; ?>>Super Admin</option>
                                                                    <option value="admin" <?php echo $admin_user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                    <option value="moderator" <?php echo $admin_user['role'] === 'moderator' ? 'selected' : ''; ?>>Moderator</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_new_password<?php echo $admin_user['id']; ?>" class="form-label">New Password (leave blank to keep current)</label>
                                                                <input type="password" class="form-control" id="edit_new_password<?php echo $admin_user['id']; ?>" name="new_password">
                                                                <div class="form-text">Minimum 6 characters. Leave blank to keep current password.</div>
                                                            </div>
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" class="form-check-input" id="edit_is_active<?php echo $admin_user['id']; ?>" name="is_active" <?php echo $admin_user['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="edit_is_active<?php echo $admin_user['id']; ?>">Active</label>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Admin Modal -->
                                        <?php if ($admin_user['id'] !== $admin_id): ?>
                                            <div class="modal fade" id="deleteAdminModal<?php echo $admin_user['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="post" action="">
                                                            <input type="hidden" name="action" value="delete_admin">
                                                            <input type="hidden" name="admin_id" value="<?php echo $admin_user['id']; ?>">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Delete Admin User</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete the admin user <strong><?php echo htmlspecialchars($admin_user['username']); ?></strong>?</p>
                                                                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Delete Admin</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No admin users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="add_admin">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Admin User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="super_admin">Super Admin</option>
                            <option value="admin" selected>Admin</option>
                            <option value="moderator">Moderator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Admin JS -->
<script src="../assets/js/admin.js"></script>

</body>
</html>