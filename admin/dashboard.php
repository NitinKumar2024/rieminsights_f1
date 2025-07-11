<?php
require_once '../config.php';

// Set page title and active page
$page_title = 'Dashboard';
$active_page = 'dashboard';

// Include header
require_once 'includes/header.php';

// Get statistics for dashboard
$total_users = 0;
$total_tokens_used = 0;
$total_files = 0;
$total_revenue = 0;

// Total users
$query = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $total_users = $row['count'];
}

// Total tokens used
$query = "SELECT SUM(tokens_used) as total FROM token_usage";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $total_tokens_used = $row['total'] ? number_format($row['total']) : 0;
}

// Total files uploaded
$query = "SELECT COUNT(*) as count FROM uploaded_files";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $total_files = $row['count'];
}

// Total revenue
$query = "SELECT SUM(amount_paid) as total FROM token_purchases WHERE payment_status = 'confirmed'";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $total_revenue = $row['total'] ? number_format($row['total'], 2) : '0.00';
}

// Get recent users
$recent_users = [];
$query = "SELECT u.*, p.plan_name FROM users u 
          LEFT JOIN plans p ON u.plan_type = p.plan_type 
          ORDER BY u.created_at DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_users[] = $row;
    }
}

// Get recent token purchases
$recent_purchases = [];
$query = "SELECT tp.*, u.email, u.user_name, t.pack_name 
          FROM token_purchases tp 
          JOIN users u ON tp.user_id = u.id 
          JOIN token_packs t ON tp.pack_id = t.id 
          ORDER BY tp.purchase_date DESC LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_purchases[] = $row;
    }
}

// Get user plan distribution
$plan_distribution = [];
$query = "SELECT p.plan_name, COUNT(u.id) as count 
          FROM users u 
          JOIN plans p ON u.plan_type = p.plan_type 
          GROUP BY u.plan_type";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $plan_distribution[] = $row;
    }
}

// Get token usage by action type
$token_usage_by_action = [];
$query = "SELECT action_type, SUM(tokens_used) as total 
          FROM token_usage 
          GROUP BY action_type";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $token_usage_by_action[] = $row;
    }
}

// Prepare data for charts
$plan_labels = [];
$plan_data = [];
foreach ($plan_distribution as $plan) {
    $plan_labels[] = $plan['plan_name'];
    $plan_data[] = $plan['count'];
}

$action_labels = [];
$action_data = [];
foreach ($token_usage_by_action as $action) {
    $action_labels[] = ucfirst(str_replace('_', ' ', $action['action_type']));
    $action_data[] = $action['total'];
}

// Additional JavaScript for charts
$additional_js = <<<EOT
<script>
    // Plan Distribution Chart
    var planCtx = document.getElementById('planDistributionChart').getContext('2d');
    var planChart = new Chart(planCtx, {
        type: 'doughnut',
        data: {
            labels: ['$plan_labels[0]', '$plan_labels[1]', '$plan_labels[2]', '$plan_labels[3]'],
            datasets: [{
                data: [$plan_data[0], $plan_data[1], $plan_data[2], $plan_data[3]],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: true,
                position: 'bottom'
            },
            cutoutPercentage: 70,
        },
    });
    
    // Token Usage by Action Chart
    var actionCtx = document.getElementById('tokenUsageChart').getContext('2d');
    var actionChart = new Chart(actionCtx, {
        type: 'bar',
        data: {
            labels: ['$action_labels[0]', '$action_labels[1]', '$action_labels[2]'],
            datasets: [{
                label: 'Tokens Used',
                data: [$action_data[0], $action_data[1], $action_data[2]],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                borderColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(1) + 'K';
                            } else {
                                return value;
                            }
                        }
                    }
                }]
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var value = tooltipItem.yLabel;
                        if (value >= 1000000) {
                            return (value / 1000000).toFixed(2) + ' Million Tokens';
                        } else if (value >= 1000) {
                            return (value / 1000).toFixed(2) + ' Thousand Tokens';
                        } else {
                            return value + ' Tokens';
                        }
                    }
                }
            }
        }
    });
</script>
EOT;
?>

<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-download fa-sm text-white-50"></i> Generate Report
    </a>
</div>

<!-- Content Row - Stats Cards -->
<div class="row">
    <!-- Total Users Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Tokens Used Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Tokens Used</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_tokens_used; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-coins fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Files Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Files Uploaded</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_files; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Revenue Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Revenue</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo $total_revenue; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row - Charts -->
<div class="row">
    <!-- Plan Distribution Chart -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">User Plan Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="planDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Token Usage Chart -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Token Usage by Action Type</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="tokenUsageChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Row - Tables -->
<div class="row">
    <!-- Recent Users Table -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['plan_type'] === 'free' ? 'secondary' : 'primary'; ?>">
                                        <?php echo htmlspecialchars($user['plan_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_users)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No users found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Purchases Table -->
    <div class="col-xl-6 col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Token Purchases</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Pack</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_purchases as $purchase): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($purchase['user_name'] ?: $purchase['email']); ?></td>
                                <td><?php echo htmlspecialchars($purchase['pack_name']); ?></td>
                                <td>$<?php echo number_format($purchase['amount_paid'], 2); ?></td>
                                <td>
                                    <?php if ($purchase['payment_status'] === 'confirmed'): ?>
                                    <span class="badge badge-success">Confirmed</span>
                                    <?php elseif ($purchase['payment_status'] === 'pending'): ?>
                                    <span class="badge badge-warning">Pending</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_purchases)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No purchases found</td>
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