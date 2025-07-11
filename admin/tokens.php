<?php
require_once '../config.php';

// Set page title and active page
$page_title = 'Manage Token Packs & Plans';
$active_page = 'tokens';

// Include header
require_once 'includes/header.php';

// Process token pack actions
$message = '';
$message_type = '';

// Handle token pack status toggle (activate/deactivate)
if (isset($_POST['toggle_status']) && isset($_POST['pack_id'])) {
    $pack_id = intval($_POST['pack_id']);
    $current_status = intval($_POST['current_status']);
    $new_status = $current_status ? 0 : 1;
    
    $query = "UPDATE token_packs SET is_active = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $pack_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $status_text = $new_status ? 'activated' : 'deactivated';
        $message = "Token pack has been $status_text successfully.";
        $message_type = 'success';
        
        // Log the action
        $action = $new_status ? 'token_pack_activated' : 'token_pack_deactivated';
        $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                      VALUES (?, ?, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_query);
        $admin_id = $_SESSION['admin_id'];
        $details = "Admin changed token pack ID $pack_id status to $status_text";
        $ip = $_SERVER['REMOTE_ADDR'];
        mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $details, $ip);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    } else {
        $message = "Error updating token pack status: " . mysqli_error($conn);
        $message_type = 'danger';
    }
    
    mysqli_stmt_close($stmt);
}

// Handle add new token pack
if (isset($_POST['add_token_pack'])) {
    $pack_name = sanitize_input($_POST['pack_name']);
    $tokens = intval($_POST['tokens']);
    $price = floatval($_POST['price']);
    $pack_type = sanitize_input($_POST['pack_type']);
    $description = sanitize_input($_POST['description']);
    
    if (empty($pack_name) || $tokens <= 0 || $price < 0) {
        $message = "Please fill all required fields with valid values.";
        $message_type = 'warning';
    } else {
        $query = "INSERT INTO token_packs (pack_name, tokens, price, pack_type, description) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sidss", $pack_name, $tokens, $price, $pack_type, $description);
        
        if (mysqli_stmt_execute($stmt)) {
            $pack_id = mysqli_insert_id($conn);
            $message = "New token pack has been added successfully.";
            $message_type = 'success';
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $admin_id = $_SESSION['admin_id'];
            $details = "Admin added new token pack: $pack_name";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, 'token_pack_added', $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $message = "Error adding token pack: " . mysqli_error($conn);
            $message_type = 'danger';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Handle edit token pack
if (isset($_POST['edit_token_pack'])) {
    $pack_id = intval($_POST['pack_id']);
    $pack_name = sanitize_input($_POST['pack_name']);
    $tokens = intval($_POST['tokens']);
    $price = floatval($_POST['price']);
    $pack_type = sanitize_input($_POST['pack_type']);
    $description = sanitize_input($_POST['description']);
    
    if (empty($pack_name) || $tokens <= 0 || $price < 0) {
        $message = "Please fill all required fields with valid values.";
        $message_type = 'warning';
    } else {
        $query = "UPDATE token_packs SET pack_name = ?, tokens = ?, price = ?, 
                  pack_type = ?, description = ?, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sidssi", $pack_name, $tokens, $price, $pack_type, $description, $pack_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "Token pack has been updated successfully.";
            $message_type = 'success';
            
            // Log the action
            $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                          VALUES (?, ?, ?, ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            $admin_id = $_SESSION['admin_id'];
            $details = "Admin updated token pack ID $pack_id: $pack_name";
            $ip = $_SERVER['REMOTE_ADDR'];
            mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, 'token_pack_updated', $details, $ip);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $message = "Error updating token pack: " . mysqli_error($conn);
            $message_type = 'danger';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get all token packs
$token_packs = [];
$query = "SELECT * FROM token_packs ORDER BY pack_type, price";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $token_packs[] = $row;
    }
}

// Handle plan status toggle (activate/deactivate)
if (isset($_POST['toggle_plan_status']) && isset($_POST['plan_id'])) {
    $plan_id = intval($_POST['plan_id']);
    $current_status = intval($_POST['current_status']);
    $new_status = $current_status ? 0 : 1;
    
    $query = "UPDATE plans SET is_active = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $new_status, $plan_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $status_text = $new_status ? 'activated' : 'deactivated';
        $message = "Plan has been $status_text successfully.";
        $message_type = 'success';
        
        // Log the action
        $action = $new_status ? 'plan_activated' : 'plan_deactivated';
        $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                      VALUES (?, ?, ?, ?)";
        $log_stmt = mysqli_prepare($conn, $log_query);
        $admin_id = $_SESSION['admin_id'];
        $details = "Admin changed plan ID $plan_id status to $status_text";
        $ip = $_SERVER['REMOTE_ADDR'];
        mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $details, $ip);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    } else {
        $message = "Error updating plan status: " . mysqli_error($conn);
        $message_type = 'danger';
    }
    
    mysqli_stmt_close($stmt);
}

// Handle add new plan
if (isset($_POST['add_plan'])) {
    $plan_name = sanitize_input($_POST['plan_name']);
    $plan_type = sanitize_input($_POST['plan_type']);
    $monthly_tokens = intval($_POST['monthly_tokens']);
    $price = floatval($_POST['price']);
    $description = sanitize_input($_POST['description']);
    $features = isset($_POST['features']) ? json_encode($_POST['features']) : NULL;
    
    if (empty($plan_name) || empty($plan_type) || $monthly_tokens < 0 || $price < 0) {
        $message = "Please fill all required fields with valid values.";
        $message_type = 'warning';
    } else {
        // Check if plan_type already exists
        $check_query = "SELECT id FROM plans WHERE plan_type = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $plan_type);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $message = "A plan with this type already exists.";
            $message_type = 'warning';
        } else {
            $query = "INSERT INTO plans (plan_name, plan_type, monthly_tokens, price, description, features) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssidss", $plan_name, $plan_type, $monthly_tokens, $price, $description, $features);
            
            if (mysqli_stmt_execute($stmt)) {
                $plan_id = mysqli_insert_id($conn);
                $message = "New plan has been added successfully.";
                $message_type = 'success';
                
                // Log the action
                $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                              VALUES (?, ?, ?, ?)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                $admin_id = $_SESSION['admin_id'];
                $details = "Admin added new plan: $plan_name";
                $ip = $_SERVER['REMOTE_ADDR'];
                mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, 'plan_added', $details, $ip);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                $message = "Error adding plan: " . mysqli_error($conn);
                $message_type = 'danger';
            }
            
            mysqli_stmt_close($stmt);
        }
        
        mysqli_stmt_close($check_stmt);
    }
}

// Handle edit plan
if (isset($_POST['edit_plan'])) {
    $plan_id = intval($_POST['plan_id']);
    $plan_name = sanitize_input($_POST['plan_name']);
    $monthly_tokens = intval($_POST['monthly_tokens']);
    $price = floatval($_POST['price']);
    $description = sanitize_input($_POST['description']);
    $features = isset($_POST['features']) ? json_encode($_POST['features']) : NULL;
    
    if (empty($plan_name) || $monthly_tokens < 0 || $price < 0) {
        $message = "Please fill all required fields with valid values.";
        $message_type = 'warning';
    } else {
        $query = "UPDATE plans SET plan_name = ?, monthly_tokens = ?, price = ?, 
                  description = ?, features = ?, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sidssi", $plan_name, $monthly_tokens, $price, $description, $features, $plan_id);
        
        if (mysqli_stmt_execute($stmt)) {
    $message = "Plan has been updated successfully.";
    $message_type = 'success';
    
    // Log the action
    $log_query = "INSERT INTO system_logs (admin_id, action, details, ip_address) 
                  VALUES (?, ?, ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $admin_id = $_SESSION['admin_id'];
    $action = 'plan_updated'; // ✅ Create a variable instead of passing string literal
    $details = "Admin updated plan ID $plan_id: $plan_name";
    $ip = $_SERVER['REMOTE_ADDR'];
    
    mysqli_stmt_bind_param($log_stmt, "isss", $admin_id, $action, $details, $ip);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}
else {
            $message = "Error updating plan: " . mysqli_error($conn);
            $message_type = 'danger';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get all plans
$plans = [];
$query = "SELECT * FROM plans ORDER BY price";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $plans[] = $row;
    }
}

// Additional JavaScript for DataTables and modals
$additional_js = <<<EOT
<script>
    $(document).ready(function() {
        $('#tokenPacksTable').DataTable({
            responsive: true
        });
        
        $('#plansTable').DataTable({
            responsive: true
        });
        
        // Initialize token pack edit modal
        $('.edit-pack-btn').click(function() {
            var packId = $(this).data('pack-id');
            var packName = $(this).data('pack-name');
            var tokens = $(this).data('tokens');
            var price = $(this).data('price');
            var packType = $(this).data('pack-type');
            var description = $(this).data('description');
            
            $('#editPackId').val(packId);
            $('#editPackName').val(packName);
            $('#editTokens').val(tokens);
            $('#editPrice').val(price);
            $('#editPackType').val(packType);
            $('#editDescription').val(description);
        });
        
        // Initialize plan edit modal
        $('.edit-plan-btn').click(function() {
            var planId = $(this).data('plan-id');
            var planName = $(this).data('plan-name');
            var monthlyTokens = $(this).data('monthly-tokens');
            var price = $(this).data('price');
            var description = $(this).data('description');
            var features = $(this).data('features');
            
            $('#editPlanId').val(planId);
            $('#editPlanName').val(planName);
            $('#editMonthlyTokens').val(monthlyTokens);
            $('#editPlanPrice').val(price);
            $('#editPlanDescription').val(description);
            
            // Handle features JSON if present
            if (features) {
                try {
                    var featuresObj = JSON.parse(features);
                    // Reset all checkboxes first
                    $('#editFeatureFiles, #editFeatureSupport, #editFeatureAnalytics, #editFeatureAPI').prop('checked', false);
                    
                    // Check the appropriate feature checkboxes
                    if (Array.isArray(featuresObj)) {
                        featuresObj.forEach(function(feature) {
                            switch(feature) {
                                case 'unlimited_files':
                                    $('#editFeatureFiles').prop('checked', true);
                                    break;
                                case 'priority_support':
                                    $('#editFeatureSupport').prop('checked', true);
                                    break;
                                case 'advanced_analytics':
                                    $('#editFeatureAnalytics').prop('checked', true);
                                    break;
                                case 'api_access':
                                    $('#editFeatureAPI').prop('checked', true);
                                    break;
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error parsing features JSON:', e);
                }
            }
        });
        
        // Tab switching
        $('#tokenPacksTab, #plansTab').on('click', function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
</script>
EOT;
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Token Packs & Plans</h1>
    <div>
        <button class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2" data-toggle="modal" data-target="#addTokenPackModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Pack
        </button>
        <button class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm" data-toggle="modal" data-target="#addPlanModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Plan
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="tokenPacksTab" data-toggle="tab" href="#tokenPacks" role="tab" aria-controls="tokenPacks" aria-selected="true">
            <i class="fas fa-coins mr-1"></i> Token Packs
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="plansTab" data-toggle="tab" href="#plans" role="tab" aria-controls="plans" aria-selected="false">
            <i class="fas fa-layer-group mr-1"></i> Subscription Plans
        </a>
    </li>
</ul>

<!-- Tab content -->
<div class="tab-content">
    <!-- Token Packs Tab -->
    <div class="tab-pane fade show active" id="tokenPacks" role="tabpanel" aria-labelledby="tokenPacksTab">
        <!-- Token Packs Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Token Packs</h6>
            </div>
            <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="tokenPacksTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pack Name</th>
                        <th>Tokens</th>
                        <th>Price</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($token_packs as $pack): ?>
                    <tr>
                        <td><?php echo $pack['id']; ?></td>
                        <td><?php echo htmlspecialchars($pack['pack_name']); ?></td>
                        <td><?php echo number_format($pack['tokens']); ?></td>
                        <td>$<?php echo number_format($pack['price'], 2); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $pack['pack_type'] === 'regular' ? 'info' : 'warning'; ?>">
                                <?php echo ucfirst($pack['pack_type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($pack['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($pack['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Actions
                                </button>
                                <div class="dropdown-menu">
                                    <!-- Edit Pack -->
                                    <a href="#" class="dropdown-item edit-pack-btn" data-toggle="modal" data-target="#editTokenPackModal" 
                                       data-pack-id="<?php echo $pack['id']; ?>"
                                       data-pack-name="<?php echo htmlspecialchars($pack['pack_name']); ?>"
                                       data-tokens="<?php echo $pack['tokens']; ?>"
                                       data-price="<?php echo $pack['price']; ?>"
                                       data-pack-type="<?php echo $pack['pack_type']; ?>"
                                       data-description="<?php echo htmlspecialchars($pack['description'] ?: ''); ?>">
                                        <i class="fas fa-edit mr-2"></i> Edit
                                    </a>
                                    
                                    <div class="dropdown-divider"></div>
                                    
                                    <!-- Toggle Status -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="pack_id" value="<?php echo $pack['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $pack['is_active']; ?>">
                                        <button type="submit" name="toggle_status" class="dropdown-item">
                                            <?php if ($pack['is_active']): ?>
                                            <i class="fas fa-ban mr-2 text-danger"></i> Deactivate
                                            <?php else: ?>
                                            <i class="fas fa-check-circle mr-2 text-success"></i> Activate
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
    </div>
    
    <!-- Plans Tab -->
    <div class="tab-pane fade" id="plans" role="tabpanel" aria-labelledby="plansTab">
        <!-- Plans Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">All Subscription Plans</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="plansTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Plan Name</th>
                                <th>Plan Type</th>
                                <th>Monthly Tokens</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td><?php echo $plan['id']; ?></td>
                                <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        switch($plan['plan_type']) {
                                            case 'free': echo 'secondary'; break;
                                            case 'starter': echo 'info'; break;
                                            case 'pro': echo 'primary'; break;
                                            case 'teams': echo 'warning'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst($plan['plan_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($plan['monthly_tokens']); ?></td>
                                <td>$<?php echo number_format($plan['price'], 2); ?></td>
                                <td>
                                    <?php if ($plan['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu">
                                            <!-- Edit Plan -->
                                            <a href="#" class="dropdown-item edit-plan-btn" data-toggle="modal" data-target="#editPlanModal" 
                                               data-plan-id="<?php echo $plan['id']; ?>"
                                               data-plan-name="<?php echo htmlspecialchars($plan['plan_name']); ?>"
                                               data-monthly-tokens="<?php echo $plan['monthly_tokens']; ?>"
                                               data-price="<?php echo $plan['price']; ?>"
                                               data-description="<?php echo htmlspecialchars($plan['description'] ?: ''); ?>"
                                               data-features="<?php echo htmlspecialchars($plan['features'] ?: ''); ?>">
                                                <i class="fas fa-edit mr-2"></i> Edit
                                            </a>
                                            
                                            <div class="dropdown-divider"></div>
                                            
                                            <!-- Toggle Status -->
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $plan['is_active']; ?>">
                                                <button type="submit" name="toggle_plan_status" class="dropdown-item">
                                                    <?php if ($plan['is_active']): ?>
                                                    <i class="fas fa-ban mr-2 text-danger"></i> Deactivate
                                                    <?php else: ?>
                                                    <i class="fas fa-check-circle mr-2 text-success"></i> Activate
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
    </div>
</div>

<!-- Add Token Pack Modal -->
<div class="modal fade" id="addTokenPackModal" tabindex="-1" role="dialog" aria-labelledby="addTokenPackModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTokenPackModalLabel">Add New Token Pack</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="packName">Pack Name</label>
                        <input type="text" class="form-control" id="packName" name="pack_name" required>
                    </div>
                    <div class="form-group">
                        <label for="tokens">Number of Tokens</label>
                        <input type="number" class="form-control" id="tokens" name="tokens" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="packType">Pack Type</label>
                        <select class="form-control" id="packType" name="pack_type" required>
                            <option value="regular">Regular</option>
                            <option value="r1_advanced">R1 Advanced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_token_pack" class="btn btn-primary">Add Token Pack</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Token Pack Modal -->
<div class="modal fade" id="editTokenPackModal" tabindex="-1" role="dialog" aria-labelledby="editTokenPackModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTokenPackModalLabel">Edit Token Pack</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="pack_id" id="editPackId">
                    <div class="form-group">
                        <label for="editPackName">Pack Name</label>
                        <input type="text" class="form-control" id="editPackName" name="pack_name" required>
                    </div>
                    <div class="form-group">
                        <label for="editTokens">Number of Tokens</label>
                        <input type="number" class="form-control" id="editTokens" name="tokens" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="editPrice">Price ($)</label>
                        <input type="number" class="form-control" id="editPrice" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="editPackType">Pack Type</label>
                        <select class="form-control" id="editPackType" name="pack_type" required>
                            <option value="regular">Regular</option>
                            <option value="r1_advanced">R1 Advanced</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editDescription">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_token_pack" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Plan Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1" role="dialog" aria-labelledby="addPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPlanModalLabel">Add New Plan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="planName">Plan Name</label>
                        <input type="text" class="form-control" id="planName" name="plan_name" required>
                    </div>
                    <div class="form-group">
                        <label for="planType">Plan Type</label>
                        <select class="form-control" id="planType" name="plan_type" required>
                            <option value="free">Free</option>
                            <option value="starter">Starter</option>
                            <option value="pro">Pro</option>
                            <option value="teams">Teams</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="monthlyTokens">Monthly Tokens</label>
                        <input type="number" class="form-control" id="monthlyTokens" name="monthly_tokens" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="planPrice">Price ($)</label>
                        <input type="number" class="form-control" id="planPrice" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="planDescription">Description</label>
                        <textarea class="form-control" id="planDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Features (Optional)</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="unlimited_files" id="featureFiles">
                            <label class="form-check-label" for="featureFiles">Unlimited Files</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="priority_support" id="featureSupport">
                            <label class="form-check-label" for="featureSupport">Priority Support</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="advanced_analytics" id="featureAnalytics">
                            <label class="form-check-label" for="featureAnalytics">Advanced Analytics</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="api_access" id="featureAPI">
                            <label class="form-check-label" for="featureAPI">API Access</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_plan" class="btn btn-success">Add Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Plan Modal -->
<div class="modal fade" id="editPlanModal" tabindex="-1" role="dialog" aria-labelledby="editPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPlanModalLabel">Edit Plan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="plan_id" id="editPlanId">
                    <div class="form-group">
                        <label for="editPlanName">Plan Name</label>
                        <input type="text" class="form-control" id="editPlanName" name="plan_name" required>
                    </div>
                    <div class="form-group">
                        <label for="editMonthlyTokens">Monthly Tokens</label>
                        <input type="number" class="form-control" id="editMonthlyTokens" name="monthly_tokens" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="editPlanPrice">Price ($)</label>
                        <input type="number" class="form-control" id="editPlanPrice" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="editPlanDescription">Description</label>
                        <textarea class="form-control" id="editPlanDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Features (Optional)</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="unlimited_files" id="editFeatureFiles">
                            <label class="form-check-label" for="editFeatureFiles">Unlimited Files</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="priority_support" id="editFeatureSupport">
                            <label class="form-check-label" for="editFeatureSupport">Priority Support</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="advanced_analytics" id="editFeatureAnalytics">
                            <label class="form-check-label" for="editFeatureAnalytics">Advanced Analytics</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="features[]" value="api_access" id="editFeatureAPI">
                            <label class="form-check-label" for="editFeatureAPI">API Access</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_plan" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>