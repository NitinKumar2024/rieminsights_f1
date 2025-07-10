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

// Process token actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new token pack
    if (isset($_POST['action']) && $_POST['action'] === 'add_token_pack') {
        $name = sanitize_input($_POST['name']);
        $tokens = (int)$_POST['tokens'];
        $price = (float)$_POST['price'];
        $description = sanitize_input($_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate inputs
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }
        
        if ($tokens <= 0) {
            $errors['tokens'] = 'Tokens must be greater than 0';
        }
        
        if ($price < 0) {
            $errors['price'] = 'Price cannot be negative';
        }
        
        // If no errors, add the token pack
        if (empty($errors)) {
            $insert_query = "INSERT INTO token_packs (pack_name, tokens, price, description, is_active) 
                             VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sidsi", $name, $tokens, $price, $description, $is_active);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $new_pack_id = mysqli_insert_id($conn);
                
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'token_pack_created', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Created token pack: $name (ID: $new_pack_id) with $tokens tokens at $$price";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "Token pack created successfully!";
            } else {
                $errors['general'] = "Error creating token pack: " . mysqli_error($conn);
            }
        }
    }
    
    // Update token pack
    if (isset($_POST['action']) && $_POST['action'] === 'edit_token_pack') {
        $pack_id = (int)$_POST['pack_id'];
        $name = sanitize_input($_POST['name']);
        $tokens = (int)$_POST['tokens'];
        $price = (float)$_POST['price'];
        $description = sanitize_input($_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate inputs
        if (empty($name)) {
            $errors['edit_name'] = 'Name is required';
        }
        
        if ($tokens <= 0) {
            $errors['edit_tokens'] = 'Tokens must be greater than 0';
        }
        
        if ($price < 0) {
            $errors['edit_price'] = 'Price cannot be negative';
        }
        
        // If no errors, update the token pack
        if (empty($errors)) {
            $update_query = "UPDATE token_packs SET 
                             pack_name = ?, 
                             tokens = ?, 
                             price = ?, 
                             description = ?, 
                             is_active = ? 
                             WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "sidsii", $name, $tokens, $price, $description, $is_active, $pack_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'token_pack_updated', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Updated token pack: $name (ID: $pack_id)";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "Token pack updated successfully!";
            } else {
                $errors['edit_general'] = "Error updating token pack: " . mysqli_error($conn);
            }
        }
    }
    
    // Delete token pack
    if (isset($_POST['action']) && $_POST['action'] === 'delete_token_pack') {
        $pack_id = (int)$_POST['pack_id'];
        
        // Check if token pack is in use
        $check_query = "SELECT COUNT(*) as count FROM token_purchases WHERE pack_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "i", $pack_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check_data = mysqli_fetch_assoc($check_result);
        
        if ($check_data['count'] > 0) {
            $errors['delete'] = "Cannot delete token pack because it is associated with user purchases.";
        } else {
            // Get token pack info before deletion for logging
            $pack_query = "SELECT pack_name FROM token_packs WHERE id = ?";
            $pack_stmt = mysqli_prepare($conn, $pack_query);
            mysqli_stmt_bind_param($pack_stmt, "i", $pack_id);
            mysqli_stmt_execute($pack_stmt);
            $pack_result = mysqli_stmt_get_result($pack_stmt);
            $pack_info = mysqli_fetch_assoc($pack_result);
            $pack_name = $pack_info['pack_name'];
            
            // Delete token pack
            $delete_query = "DELETE FROM token_packs WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "i", $pack_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'token_pack_deleted', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Deleted token pack: $pack_name (ID: $pack_id)";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                $success_message = "Token pack deleted successfully!";
            } else {
                $errors['delete'] = "Error deleting token pack: " . mysqli_error($conn);
            }
        }
    }
    
    // Add tokens to user
    if (isset($_POST['action']) && $_POST['action'] === 'add_tokens_to_user') {
        $user_id = (int)$_POST['user_id'];
        $tokens = (int)$_POST['tokens'];
        $notes = sanitize_input($_POST['notes']);
        
        // Validate inputs
        if ($tokens <= 0) {
            $errors['add_tokens'] = 'Tokens must be greater than 0';
        }
        
        // If no errors, add tokens to user
        if (empty($errors)) {
            // Start transaction
            mysqli_begin_transaction($conn);
            
            try {
                // Get user info
                $user_query = "SELECT email, tokens_remaining FROM users WHERE id = ?";
                $user_stmt = mysqli_prepare($conn, $user_query);
                mysqli_stmt_bind_param($user_stmt, "i", $user_id);
                mysqli_stmt_execute($user_stmt);
                $user_result = mysqli_stmt_get_result($user_stmt);
                $user_info = mysqli_fetch_assoc($user_result);
                $current_tokens = $user_info['tokens_remaining'];
                $user_email = $user_info['email'];
                
                // Update user tokens
                $update_query = "UPDATE users SET 
                                 tokens_remaining = tokens_remaining + ?, 
                                 total_tokens_purchased = total_tokens_purchased + ? 
                                 WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "iii", $tokens, $tokens, $user_id);
                mysqli_stmt_execute($update_stmt);
                
                // Create manual token purchase record
                $purchase_query = "INSERT INTO token_purchases 
                                  (user_id, pack_id, tokens_purchased, amount_paid, payment_method, payment_reference, payment_status, confirmed_by, purchase_date, confirmed_date) 
                                  VALUES (?, 0, ?, 0, 'manual', ?, 'completed', ?, NOW(), NOW())";
                $purchase_stmt = mysqli_prepare($conn, $purchase_query);
                $reference = "Manual addition by admin ID: $admin_id - $notes";
                mysqli_stmt_bind_param($purchase_stmt, "issi", $user_id, $tokens, $reference, $admin_id);
                mysqli_stmt_execute($purchase_stmt);
                
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address, user_agent) 
                              VALUES (?, 'tokens_added', ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $details = "Added $tokens tokens to user ID: $user_id ($user_email). Previous balance: $current_tokens";
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $details, $ip, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                // Commit transaction
                mysqli_commit($conn);
                
                $success_message = "$tokens tokens added to user successfully!";
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $errors['add_tokens'] = "Error adding tokens: " . $e->getMessage();
            }
        }
    }
}

// Get all token packs
$token_packs_query = "SELECT * FROM token_packs ORDER BY tokens ASC";
$token_packs_result = mysqli_query($conn, $token_packs_query);

// Get recent token purchases
$purchases_query = "SELECT tp.*, u.email, u.user_name 
                   FROM token_purchases tp 
                   JOIN users u ON tp.user_id = u.id 
                   ORDER BY tp.purchase_date DESC 
                   LIMIT 10";
$purchases_result = mysqli_query($conn, $purchases_query);

// Get users for token addition
$users_query = "SELECT id, email, user_name, tokens_remaining FROM users ORDER BY id ASC";
$users_result = mysqli_query($conn, $users_query);

// Page title
$page_title = "Token Management";
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
                        <a class="nav-link active" href="tokens.php">
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
                <h1 class="h2">Token Management</h1>
                <div>
                    <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addTokensModal">
                        <i class="fas fa-plus-circle"></i> Add Tokens to User
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTokenPackModal">
                        <i class="fas fa-plus"></i> Add Token Pack
                    </button>
                </div>
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

            <!-- Token Packs Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i> Token Packs</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Tokens</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($token_packs_result) > 0): ?>
                                    <?php while ($pack = mysqli_fetch_assoc($token_packs_result)): ?>
                                        <tr>
                                            <td><?php echo $pack['id']; ?></td>
                                            <td><?php echo htmlspecialchars($pack['pack_name']); ?></td>
                                            <td><?php echo number_format($pack['tokens']); ?></td>
                                            <td>$<?php echo number_format($pack['price'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($pack['description'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if ($pack['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="token-actions">
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editTokenPackModal<?php echo $pack['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTokenPackModal<?php echo $pack['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Token Pack Modal -->
                                        <div class="modal fade" id="editTokenPackModal<?php echo $pack['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="action" value="edit_token_pack">
                                                        <input type="hidden" name="pack_id" value="<?php echo $pack['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Token Pack</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="edit_name<?php echo $pack['id']; ?>" class="form-label">Name</label>
                                                                <input type="text" class="form-control <?php echo isset($errors['edit_name']) ? 'is-invalid' : ''; ?>" id="edit_name<?php echo $pack['id']; ?>" name="name" value="<?php echo htmlspecialchars($pack['pack_name']); ?>" required>
                                                                <?php if (isset($errors['edit_name'])): ?>
                                                                    <div class="invalid-feedback"><?php echo $errors['edit_name']; ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_tokens<?php echo $pack['id']; ?>" class="form-label">Tokens</label>
                                                                <input type="number" class="form-control <?php echo isset($errors['edit_tokens']) ? 'is-invalid' : ''; ?>" id="edit_tokens<?php echo $pack['id']; ?>" name="tokens" value="<?php echo $pack['tokens']; ?>" min="1" required>
                                                                <?php if (isset($errors['edit_tokens'])): ?>
                                                                    <div class="invalid-feedback"><?php echo $errors['edit_tokens']; ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_price<?php echo $pack['id']; ?>" class="form-label">Price ($)</label>
                                                                <input type="number" class="form-control <?php echo isset($errors['edit_price']) ? 'is-invalid' : ''; ?>" id="edit_price<?php echo $pack['id']; ?>" name="price" value="<?php echo $pack['price']; ?>" min="0" step="0.01" required>
                                                                <?php if (isset($errors['edit_price'])): ?>
                                                                    <div class="invalid-feedback"><?php echo $errors['edit_price']; ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="edit_description<?php echo $pack['id']; ?>" class="form-label">Description</label>
                                                                <textarea class="form-control" id="edit_description<?php echo $pack['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($pack['description'] ?? ''); ?></textarea>
                                                            </div>
                                                            <div class="mb-3 form-check">
                                                                <input type="checkbox" class="form-check-input" id="edit_is_active<?php echo $pack['id']; ?>" name="is_active" <?php echo $pack['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="edit_is_active<?php echo $pack['id']; ?>">Active</label>
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
                                        
                                        <!-- Delete Token Pack Modal -->
                                        <div class="modal fade" id="deleteTokenPackModal<?php echo $pack['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post" action="">
                                                        <input type="hidden" name="action" value="delete_token_pack">
                                                        <input type="hidden" name="pack_id" value="<?php echo $pack['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Token Pack</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete the token pack <strong><?php echo htmlspecialchars($pack['pack_name']); ?></strong>?</p>
                                                            <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. You cannot delete token packs that have been purchased by users.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Delete Token Pack</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No token packs found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recent Token Purchases Section -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Recent Token Purchases</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Tokens</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($purchases_result) > 0): ?>
                                    <?php while ($purchase = mysqli_fetch_assoc($purchases_result)): ?>
                                        <tr>
                                            <td><?php echo $purchase['id']; ?></td>
                                            <td>
                                                <a href="users.php?action=view&id=<?php echo $purchase['user_id']; ?>">
                                                    <?php echo htmlspecialchars($purchase['user_name'] . ' (' . $purchase['email'] . ')'); ?>
                                                </a>
                                            </td>
                                            <td><?php echo number_format($purchase['tokens_purchased']); ?></td>
                                            <td>$<?php echo number_format($purchase['amount_paid'], 2); ?></td>
                                            <td><?php echo ucfirst($purchase['payment_method']); ?></td>
                                            <td>
                                                <?php if ($purchase['payment_status'] === 'completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($purchase['payment_status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($purchase['purchase_date'])); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($purchase['payment_reference']); ?>">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No token purchases found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <a href="purchases.php" class="btn btn-outline-primary">View All Purchases</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Token Pack Modal -->
<div class="modal fade" id="addTokenPackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="add_token_pack">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Token Pack</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="tokens" class="form-label">Tokens</label>
                        <input type="number" class="form-control <?php echo isset($errors['tokens']) ? 'is-invalid' : ''; ?>" id="tokens" name="tokens" min="1" required>
                        <?php if (isset($errors['tokens'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['tokens']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price ($)</label>
                        <input type="number" class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>" id="price" name="price" min="0" step="0.01" required>
                        <?php if (isset($errors['price'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['price']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Token Pack</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Tokens to User Modal -->
<div class="modal fade" id="addTokensModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="add_tokens_to_user">
                <div class="modal-header">
                    <h5 class="modal-title">Add Tokens to User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select" id="user_id" name="user_id" required>
                            <option value="">-- Select User --</option>
                            <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['user_name'] . ' (' . $user['email'] . ') - Current: ' . number_format($user['tokens_remaining']) . ' tokens'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="add_tokens" class="form-label">Number of Tokens</label>
                        <input type="number" class="form-control <?php echo isset($errors['add_tokens']) ? 'is-invalid' : ''; ?>" id="add_tokens" name="tokens" min="1" required>
                        <?php if (isset($errors['add_tokens'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['add_tokens']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Internal Reference)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        <div class="form-text">This will be stored as a reference for this transaction.</div>
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

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom Admin JS -->
<script src="../assets/js/admin.js"></script>
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

</body>
</html>