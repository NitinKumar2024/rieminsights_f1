<?php
require_once '../config.php';

// Set page title and active page
$page_title = 'Manage Users';
$active_page = 'users';

// Include header
require_once 'includes/header.php';

// Process user actions
$message = '';
$message_type = '';

// Handle user status toggle (activate/deactivate)
if (isset($_POST['toggle_status']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $current_status = intval($_POST['current_status']);
    $new_status = $current_status ? 0 : 1;
    
    $query = "UPDATE users SET is_active = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $status_text = $new_status ? 'activated' : 'deactivated';
        $message = "User has been $status_text successfully.";
        $message_type = 'success';
        
        // Log the action
        $action = $new_status ? 'user_activated' : 'user_deactivated';
        $log_query = "INSERT INTO system_logs (admin_id, user_id, action, details, ip_address) 
                      VALUES (?, ?, ?, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_query);
        $admin_id = $_SESSION['admin_id'];
        $details = "Admin changed user status to $status_text";
        $ip = $_SERVER['REMOTE_ADDR'];
        mysqli_stmt_bind_param($log_stmt, "iisss", $admin_id, $user_id, $action, $details, $ip);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    } else {
        $message = "Error updating user status: " . mysqli_error($conn);
        $message_type = 'danger';
    }
    
    mysqli_stmt_close($stmt);
}

// Handle token top-up or reduction
if (isset($_POST['modify_tokens']) && isset($_POST['user_id']) && isset($_POST['token_amount']) && isset($_POST['token_action'])) {
    $user_id = intval($_POST['user_id']);
    $token_amount = intval($_POST['token_amount']);
    $token_action = sanitize_input($_POST['token_action']);
    
    if ($token_amount <= 0) {
        $message = "Token amount must be greater than zero.";
        $message_type = 'warning';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Check current token balance if reducing tokens
            if ($token_action === 'reduce') {
                $check_query = "SELECT tokens_remaining FROM users WHERE id = ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "i", $user_id);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_bind_result($check_stmt, $current_tokens);
                mysqli_stmt_fetch($check_stmt);
                mysqli_stmt_close($check_stmt);
                
                if ($current_tokens < $token_amount) {
                    throw new Exception("User only has $current_tokens tokens. Cannot reduce by $token_amount tokens.");
                }
            }
            
            // Update user tokens
            if ($token_action === 'add') {
                $query = "UPDATE users SET tokens_remaining = tokens_remaining + ?, 
                          total_tokens_purchased = total_tokens_purchased + ? 
                          WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iii", $token_amount, $token_amount, $user_id);
            } else { // reduce
                $query = "UPDATE users SET tokens_remaining = tokens_remaining - ? 
                          WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $token_amount, $user_id);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Record the manual token transaction if adding tokens
            if ($token_action === 'add') {
                $query = "INSERT INTO token_purchases (user_id, pack_id, tokens_purchased, amount_paid, 
                          payment_method, payment_status, confirmed_by, confirmed_date) 
                          VALUES (?, 1, ?, 0.00, 'manual', 'confirmed', ?, CURRENT_TIMESTAMP)";
                $stmt = mysqli_prepare($conn, $query);
                $admin_id = $_SESSION['admin_id'];
                mysqli_stmt_bind_param($stmt, "iii", $user_id, $token_amount, $admin_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, user_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $admin_id = $_SESSION['admin_id'];
            $action = $token_action === 'add' ? 'token_topup' : 'token_reduction';
            $details = "Admin " . ($token_action === 'add' ? "added" : "reduced") . " $token_amount tokens manually";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "iisss", $admin_id, $user_id, $action, $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            $message = "$token_amount tokens have been " . ($token_action === 'add' ? "added to" : "reduced from") . " the user successfully.";
            $message_type = 'success';
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $message = "Error modifying tokens: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Handle change plan
if (isset($_POST['change_plan']) && isset($_POST['user_id']) && isset($_POST['plan_type'])) {
    $user_id = intval($_POST['user_id']);
    $plan_type = sanitize_input($_POST['plan_type']);
    
    // Verify plan exists
    $plan_query = "SELECT * FROM plans WHERE plan_type = ?";
    $plan_stmt = mysqli_prepare($conn, $plan_query);
    mysqli_stmt_bind_param($plan_stmt, "s", $plan_type);
    mysqli_stmt_execute($plan_stmt);
    $plan_result = mysqli_stmt_get_result($plan_stmt);
    
    if ($plan = mysqli_fetch_assoc($plan_result)) {
        // Update user plan
        $query = "UPDATE users SET plan_type = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $plan_type, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "User plan has been updated to {$plan['plan_name']} successfully.";
            $message_type = 'success';
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, user_id, action, details, ip_address) 
                          VALUES (?, ?, 'plan_changed', ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $admin_id = $_SESSION['admin_id'];
            $details = "Admin changed user plan to {$plan['plan_name']}";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "iiss", $admin_id, $user_id, $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $message = "Error updating user plan: " . mysqli_error($conn);
            $message_type = 'danger';
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $message = "Invalid plan selected.";
        $message_type = 'danger';
    }
    
    mysqli_stmt_close($plan_stmt);
}

// Get all users with plan information
$users = [];
$query = "SELECT u.*, p.plan_name FROM users u 
          LEFT JOIN plans p ON u.plan_type = p.plan_type 
          ORDER BY u.created_at DESC";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// Get all available plans for dropdown
$plans = [];
$query = "SELECT * FROM plans WHERE is_active = 1";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $plans[] = $row;
    }
}

// Additional JavaScript for DataTables
$additional_js = <<<EOT
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            order: [[6, 'desc']],
            responsive: true
        });
        
        // Initialize modals
        $('.token-modal-btn').click(function() {
            var userId = $(this).data('user-id');
            var userName = $(this).data('user-name');
            $('#tokenUserId').val(userId);
            $('#tokenUserName').text(userName);
        });
        
        $('.plan-modal-btn').click(function() {
            var userId = $(this).data('user-id');
            var userName = $(this).data('user-name');
            var currentPlan = $(this).data('current-plan');
            $('#planUserId').val(userId);
            $('#planUserName').text(userName);
            $('#planType').val(currentPlan);
        });
    });
</script>
EOT;
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Users</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">All Users</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Tokens</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['user_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['plan_type'] === 'free' ? 'secondary' : 'primary'; ?>">
                                <?php echo htmlspecialchars($user['plan_name']); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($user['tokens_remaining']); ?></td>
                        <td>
                            <?php if ($user['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Actions
                                </button>
                                <div class="dropdown-menu">
                                    <!-- Modify Tokens -->
                                    <a href="#" class="dropdown-item token-modal-btn" data-toggle="modal" data-target="#addTokensModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['user_name'] ?: $user['email']); ?>">
                                        <i class="fas fa-coins mr-2"></i> Modify Tokens
                                    </a>
                                    
                                    <!-- Change Plan -->
                                    <a href="#" class="dropdown-item plan-modal-btn" data-toggle="modal" data-target="#changePlanModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['user_name'] ?: $user['email']); ?>" data-current-plan="<?php echo $user['plan_type']; ?>">
                                        <i class="fas fa-exchange-alt mr-2"></i> Change Plan
                                    </a>
                                    
                                    <div class="dropdown-divider"></div>
                                    
                                    <!-- Toggle Status -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $user['is_active']; ?>">
                                        <button type="submit" name="toggle_status" class="dropdown-item">
                                            <?php if ($user['is_active']): ?>
                                            <i class="fas fa-user-slash mr-2 text-danger"></i> Deactivate
                                            <?php else: ?>
                                            <i class="fas fa-user-check mr-2 text-success"></i> Activate
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modify Tokens Modal -->
<div class="modal fade" id="addTokensModal" tabindex="-1" role="dialog" aria-labelledby="addTokensModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTokensModalLabel">Modify Tokens</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <p>Modify tokens for <strong id="tokenUserName"></strong></p>
                    <input type="hidden" name="user_id" id="tokenUserId">
                    <div class="form-group">
                        <label for="tokenAction">Action</label>
                        <select class="form-control" id="tokenAction" name="token_action" required>
                            <option value="add">Add Tokens</option>
                            <option value="reduce">Reduce Tokens</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tokenAmount">Token Amount</label>
                        <input type="number" class="form-control" id="tokenAmount" name="token_amount" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="modify_tokens" class="btn btn-primary">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Plan Modal -->
<div class="modal fade" id="changePlanModal" tabindex="-1" role="dialog" aria-labelledby="changePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePlanModalLabel">Change Plan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <p>Change plan for <strong id="planUserName"></strong></p>
                    <input type="hidden" name="user_id" id="planUserId">
                    <div class="form-group">
                        <label for="planType">Select Plan</label>
                        <select class="form-control" id="planType" name="plan_type" required>
                            <?php foreach ($plans as $plan): ?>
                            <option value="<?php echo $plan['plan_type']; ?>">
                                <?php echo htmlspecialchars($plan['plan_name']); ?> - $<?php echo number_format($plan['price'], 2); ?>/month
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_plan" class="btn btn-primary">Change Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>