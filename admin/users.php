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

// Initialize variables
$errors = [];
$success_message = '';

// Process user actions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $email = sanitize_input($_POST['email']);
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        $plan_type = sanitize_input($_POST['plan_type']);
        $tokens = (int)$_POST['tokens'];
        
        // Validate inputs
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors['email'] = 'Email already exists';
        }
        
        // If no errors, add the user
        if (empty($errors)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert_query = "INSERT INTO users (email, password, user_name, plan_type, tokens_remaining, total_tokens_purchased, is_active, email_verified) 
                             VALUES (?, ?, ?, ?, ?, ?, 1, 1)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ssssii", $email, $hashed_password, $username, $plan_type, $tokens, $tokens);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $user_id = mysqli_insert_id($conn);
                
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'user_created', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Created user: $username (ID: $user_id)";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "User created successfully!";
            } else {
                $errors['general'] = "Error creating user: " . mysqli_error($conn);
            }
        }
    }
    
    // Update user
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $user_id = (int)$_POST['user_id'];
        $email = sanitize_input($_POST['email']);
        $username = sanitize_input($_POST['username']);
        $plan_type = sanitize_input($_POST['plan_type']);
        $tokens = (int)$_POST['tokens'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $email_verified = isset($_POST['email_verified']) ? 1 : 0;
        
        // Validate inputs
        if (empty($email)) {
            $errors['edit_email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['edit_email'] = 'Invalid email format';
        }
        
        if (empty($username)) {
            $errors['edit_username'] = 'Username is required';
        }
        
        // Check if email already exists for other users
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors['edit_email'] = 'Email already exists';
        }
        
        // If no errors, update the user
        if (empty($errors)) {
            // Update user
            $update_query = "UPDATE users SET 
                             email = ?, 
                             user_name = ?, 
                             plan_type = ?, 
                             tokens_remaining = ?, 
                             is_active = ?, 
                             email_verified = ? 
                             WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sssiiii", $email, $username, $plan_type, $tokens, $is_active, $email_verified, $user_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'user_updated', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Updated user: $username (ID: $user_id)";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "User updated successfully!";
            } else {
                $errors['general'] = "Error updating user: " . mysqli_error($conn);
            }
        }
    }
    
    // Delete user
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $user_id = (int)$_POST['user_id'];
        
        // Get user info before deletion for logging
        $user_query = "SELECT user_name FROM users WHERE id = ?";
        $user_stmt = mysqli_prepare($conn, $user_query);
        mysqli_stmt_bind_param($user_stmt, "i", $user_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_info = mysqli_fetch_assoc($user_result);
        $username = $user_info['user_name'];
        
        // Delete user
        $delete_query = "DELETE FROM users WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                          VALUES (?, 'user_deleted', ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "Deleted user: $username (ID: $user_id)";
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
            mysqli_stmt_execute($log_stmt);
            
            $success_message = "User deleted successfully!";
        } else {
            $errors['general'] = "Error deleting user: " . mysqli_error($conn);
        }
    }
    
    // Add tokens to user
    if (isset($_POST['action']) && $_POST['action'] === 'add_tokens') {
        $user_id = (int)$_POST['user_id'];
        $tokens = (int)$_POST['tokens_to_add'];
        $pack_id = (int)$_POST['pack_id'];
        $amount = (float)$_POST['amount'];
        
        // Get user info for logging
        $user_query = "SELECT user_name, tokens_remaining FROM users WHERE id = ?";
        $user_stmt = mysqli_prepare($conn, $user_query);
        mysqli_stmt_bind_param($user_stmt, "i", $user_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_info = mysqli_fetch_assoc($user_result);
        $username = $user_info['user_name'];
        $current_tokens = $user_info['tokens_remaining'];
        
        // Update user tokens
        $update_query = "UPDATE users SET 
                         tokens_remaining = tokens_remaining + ?, 
                         total_tokens_purchased = total_tokens_purchased + ? 
                         WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "iii", $tokens, $tokens, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            // Record the token purchase
            $purchase_query = "INSERT INTO token_purchases 
                              (user_id, pack_id, tokens_purchased, amount_paid, payment_method, payment_status, confirmed_by, confirmed_date) 
                              VALUES (?, ?, ?, ?, 'manual', 'confirmed', ?, NOW())";
            $purchase_stmt = mysqli_prepare($conn, $purchase_query);
            mysqli_stmt_bind_param($purchase_stmt, "iiidi", $user_id, $pack_id, $tokens, $amount, $admin_id);
            mysqli_stmt_execute($purchase_stmt);
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                          VALUES (?, 'tokens_added', ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $details = "Added $tokens tokens to user: $username (ID: $user_id). Previous balance: $current_tokens";
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
            mysqli_stmt_execute($log_stmt);
            
            $success_message = "Tokens added successfully!";
        } else {
            $errors['general'] = "Error adding tokens: " . mysqli_error($conn);
        }
    }
}

// Get all users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE email LIKE '%$search%' OR user_name LIKE '%$search%'";
}

$count_query = "SELECT COUNT(*) as total FROM users $search_condition";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_users = $count_row['total'];
$total_pages = ceil($total_users / $limit);

$users_query = "SELECT * FROM users $search_condition ORDER BY id DESC LIMIT $offset, $limit";
$users_result = mysqli_query($conn, $users_query);

// Get token packs for dropdown
$packs_query = "SELECT * FROM token_packs WHERE is_active = 1 ORDER BY tokens ASC";
$packs_result = mysqli_query($conn, $packs_query);
$token_packs = [];
while ($pack = mysqli_fetch_assoc($packs_result)) {
    $token_packs[] = $pack;
}

// Page title
$page_title = "Manage Users";
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
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tokens.php">
                            <i class="fas fa-coins me-2"></i> Token Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admins.php">
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
                <h1 class="h2">Manage Users</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add New User
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

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" action="" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search by email or username" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <?php if (!empty($search)): ?>
                                <a href="users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear Search
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Plan</th>
                                    <th>Tokens</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($users_result) > 0): ?>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['user_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['plan_type'] === 'free' ? 'secondary' : ($user['plan_type'] === 'starter' ? 'info' : ($user['plan_type'] === 'pro' ? 'primary' : 'success')); ?>">
                                                    <?php echo ucfirst($user['plan_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($user['tokens_remaining']); ?></td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                            <td class="user-actions">
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewUserModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addTokensModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-coins"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- View User Modal -->
                                        <div class="modal fade" id="viewUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">User Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                                                                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['user_name'] ?? 'N/A'); ?></p>
                                                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                                                <p><strong>Plan:</strong> <?php echo ucfirst($user['plan_type']); ?></p>
                                                                <p><strong>Status:</strong> <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Tokens Remaining:</strong> <?php echo number_format($user['tokens_remaining']); ?></p>
                                                                <p><strong>Total Tokens Purchased:</strong> <?php echo number_format($user['total_tokens_purchased']); ?></p>
                                                                <p><strong>Email Verified:</strong> <?php echo $user['email_verified'] ? 'Yes' : 'No'; ?></p>
                                                                <p><strong>Created:</strong> <?php echo date('F d, Y H:i:s', strtotime($user['created_at'])); ?></p>
                                                                <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('F d, Y H:i:s', strtotime($user['last_login'])) : 'Never'; ?></p>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Recent Token Purchases -->
                                                        <?php
                                                        $purchases_query = "SELECT tp.*, t.pack_name 
                                                                           FROM token_purchases tp 
                                                                           LEFT JOIN token_packs t ON tp.pack_id = t.id 
                                                                           WHERE tp.user_id = ? 
                                                                           ORDER BY tp.purchase_date DESC LIMIT 5";
                                                        $purchases_stmt = mysqli_prepare($conn, $purchases_query);
                                                        mysqli_stmt_bind_param($purchases_stmt, "i", $user['id']);
                                                        mysqli_stmt_execute($purchases_stmt);
                                                        $purchases_result = mysqli_stmt_get_result($purchases_stmt);
                                                        ?>
                                                        
                                                        <h5 class="mt-4">Recent Token Purchases</h5>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Date</th>
                                                                        <th>Pack</th>
                                                                        <th>Tokens</th>
                                                                        <th>Amount</th>
                                                                        <th>Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php if (mysqli_num_rows($purchases_result) > 0): ?>
                                                                        <?php while ($purchase = mysqli_fetch_assoc($purchases_result)): ?>
                                                                            <tr>
                                                                                <td><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></td>
                                                                                <td><?php echo htmlspecialchars($purchase['pack_name'] ?? 'Custom'); ?></td>
                                                                                <td><?php echo number_format($purchase['tokens_purchased']); ?></td>
                                                                                <td>$<?php echo number_format($purchase['amount_paid'], 2); ?></td>
                                                                                <td>
                                                                                    <span class="badge bg-<?php echo $purchase['payment_status'] === 'confirmed' ? 'success' : ($purchase['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                                                        <?php echo ucfirst($purchase['payment_status']); ?>
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endwhile; ?>
                                                                    <?php else: ?>
                                                                        <tr>
                                                                            <td colspan="5" class="text-center">No token purchases found</td>
                                                                        </tr>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        
                                                        <!-- Recent Activity -->
                                                        <?php
                                                        $activity_query = "SELECT * FROM token_usage 
                                                                          WHERE user_id = ? 
                                                                          ORDER BY timestamp DESC LIMIT 5";
                                                        $activity_stmt = mysqli_prepare($conn, $activity_query);
                                                        mysqli_stmt_bind_param($activity_stmt, "i", $user['id']);
                                                        mysqli_stmt_execute($activity_stmt);
                                                        $activity_result = mysqli_stmt_get_result($activity_stmt);
                                                        ?>
                                                        
                                                        <h5 class="mt-4">Recent Activity</h5>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Date</th>
                                                                        <th>Action</th>
                                                                        <th>Tokens Used</th>
                                                                        <th>Query</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php if (mysqli_num_rows($activity_result) > 0): ?>
                                                                        <?php while ($activity = mysqli_fetch_assoc($activity_result)): ?>
                                                                            <tr>
                                                                                <td><?php echo date('M d, Y H:i', strtotime($activity['timestamp'])); ?></td>
                                                                                <td><?php echo str_replace('_', ' ', ucfirst($activity['action_type'])); ?></td>
                                                                                <td><?php echo number_format($activity['tokens_used']); ?></td>
                                                                                <td><?php echo htmlspecialchars(substr($activity['query_text'] ?? '', 0, 50)) . (strlen($activity['query_text'] ?? '') > 50 ? '...' : ''); ?></td>
                                                                            </tr>
                                                                        <?php endwhile; ?>
                                                                    <?php else: ?>
                                                                        <tr>
                                                                            <td colspan="4" class="text-center">No activity found</td>
                                                                        </tr>
                                                                    <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Edit User Modal -->
                                        <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="action" value="edit_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit User</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="edit_email<?php echo $user['id']; ?>" class="form-label">Email</label>
                                                                <input type="email" class="form-control" id="edit_email<?php echo $user['id']; ?>" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_username<?php echo $user['id']; ?>" class="form-label">Username</label>
                                                                <input type="text" class="form-control" id="edit_username<?php echo $user['id']; ?>" name="username" value="<?php echo htmlspecialchars($user['user_name'] ?? ''); ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_plan<?php echo $user['id']; ?>" class="form-label">Plan</label>
                                                                <select class="form-select" id="edit_plan<?php echo $user['id']; ?>" name="plan_type">
                                                                    <option value="free" <?php echo $user['plan_type'] === 'free' ? 'selected' : ''; ?>>Free</option>
                                                                    <option value="starter" <?php echo $user['plan_type'] === 'starter' ? 'selected' : ''; ?>>Starter</option>
                                                                    <option value="pro" <?php echo $user['plan_type'] === 'pro' ? 'selected' : ''; ?>>Pro</option>
                                                                    <option value="teams" <?php echo $user['plan_type'] === 'teams' ? 'selected' : ''; ?>>Teams</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_tokens<?php echo $user['id']; ?>" class="form-label">Tokens Remaining</label>
                                                                <input type="number" class="form-control" id="edit_tokens<?php echo $user['id']; ?>" name="tokens" value="<?php echo $user['tokens_remaining']; ?>" min="0">
                                                            </div>
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" class="form-check-input" id="edit_is_active<?php echo $user['id']; ?>" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="edit_is_active<?php echo $user['id']; ?>">Active</label>
                                                            </div>
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" class="form-check-input" id="edit_email_verified<?php echo $user['id']; ?>" name="email_verified" <?php echo $user['email_verified'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="edit_email_verified<?php echo $user['id']; ?>">Email Verified</label>
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
                                        
                                        <!-- Add Tokens Modal -->
                                        <div class="modal fade" id="addTokensModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="action" value="add_tokens">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Add Tokens</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Current tokens: <strong><?php echo number_format($user['tokens_remaining']); ?></strong></p>
                                                            
                                                            <div class="mb-3">
                                                                <label for="token_pack<?php echo $user['id']; ?>" class="form-label">Token Pack</label>
                                                                <select class="form-select" id="token_pack<?php echo $user['id']; ?>" name="pack_id" onchange="updateTokenAmount(<?php echo $user['id']; ?>)">
                                                                    <option value="0" data-tokens="0" data-price="0">Custom Amount</option>
                                                                    <?php foreach ($token_packs as $pack): ?>
                                                                        <option value="<?php echo $pack['id']; ?>" data-tokens="<?php echo $pack['tokens']; ?>" data-price="<?php echo $pack['price']; ?>">
                                                                            <?php echo htmlspecialchars($pack['pack_name']); ?> (<?php echo number_format($pack['tokens']); ?> tokens - $<?php echo number_format($pack['price'], 2); ?>)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="tokens_to_add<?php echo $user['id']; ?>" class="form-label">Tokens to Add</label>
                                                                <input type="number" class="form-control" id="tokens_to_add<?php echo $user['id']; ?>" name="tokens_to_add" value="0" min="0" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="amount<?php echo $user['id']; ?>" class="form-label">Amount Paid ($)</label>
                                                                <input type="number" class="form-control" id="amount<?php echo $user['id']; ?>" name="amount" value="0" min="0" step="0.01" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Add Tokens</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete User Modal -->
                                        <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete User</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete the user <strong><?php echo htmlspecialchars($user['user_name'] ?? $user['email']); ?></strong>?</p>
                                                            <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will delete all data associated with this user.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Delete User</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="plan_type" class="form-label">Plan</label>
                        <select class="form-select" id="plan_type" name="plan_type">
                            <option value="free">Free</option>
                            <option value="starter">Starter</option>
                            <option value="pro">Pro</option>
                            <option value="teams">Teams</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tokens" class="form-label">Initial Tokens</label>
                        <input type="number" class="form-control" id="tokens" name="tokens" value="20000" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Function to update token amount based on selected pack
    function updateTokenAmount(userId) {
        const packSelect = document.getElementById('token_pack' + userId);
        const tokensInput = document.getElementById('tokens_to_add' + userId);
        const amountInput = document.getElementById('amount' + userId);
        
        const selectedOption = packSelect.options[packSelect.selectedIndex];
        const tokens = selectedOption.getAttribute('data-tokens');
        const price = selectedOption.getAttribute('data-price');
        
        tokensInput.value = tokens;
        amountInput.value = price;
    }
</script>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Admin JS -->
<script src="../assets/js/admin.js"></script>

</body>
</html>