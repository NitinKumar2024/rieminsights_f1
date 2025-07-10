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

// Page title
$page_title = "Admin Dashboard";
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
                        <a class="nav-link active" href="index.php">
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
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                        <i class="fas fa-calendar"></i> This week
                    </button>
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="row">
                <?php
                // Get total users count
                $users_query = "SELECT COUNT(*) as total FROM users";
                $users_result = mysqli_query($conn, $users_query);
                $users_count = mysqli_fetch_assoc($users_result)['total'];

                // Get total tokens purchased
                $tokens_query = "SELECT SUM(tokens_purchased) as total FROM token_purchases WHERE payment_status = 'confirmed'";
                $tokens_result = mysqli_query($conn, $tokens_query);
                $tokens_purchased = mysqli_fetch_assoc($tokens_result)['total'] ?? 0;

                // Get total token purchases (revenue)
                $revenue_query = "SELECT SUM(amount_paid) as total FROM token_purchases WHERE payment_status = 'confirmed'";
                $revenue_result = mysqli_query($conn, $revenue_query);
                $revenue = mysqli_fetch_assoc($revenue_result)['total'] ?? 0;

                // Get active users in last 7 days
                $active_users_query = "SELECT COUNT(DISTINCT user_id) as total FROM user_sessions WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $active_users_result = mysqli_query($conn, $active_users_query);
                $active_users = mysqli_fetch_assoc($active_users_result)['total'] ?? 0;
                ?>

                <!-- Total Users -->
                <div class="col-md-6 col-xl-3">
                    <div class="card-counter bg-primary">
                        <i class="fas fa-users"></i>
                        <span class="count-numbers"><?php echo number_format($users_count); ?></span>
                        <span class="count-name">Total Users</span>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="col-md-6 col-xl-3">
                    <div class="card-counter bg-success">
                        <i class="fas fa-user-check"></i>
                        <span class="count-numbers"><?php echo number_format($active_users); ?></span>
                        <span class="count-name">Active Users (7d)</span>
                    </div>
                </div>

                <!-- Total Tokens -->
                <div class="col-md-6 col-xl-3">
                    <div class="card-counter bg-warning">
                        <i class="fas fa-coins"></i>
                        <span class="count-numbers"><?php echo number_format($tokens_purchased); ?></span>
                        <span class="count-name">Tokens Purchased</span>
                    </div>
                </div>

                <!-- Revenue -->
                <div class="col-md-6 col-xl-3">
                    <div class="card-counter bg-danger">
                        <i class="fas fa-dollar-sign"></i>
                        <span class="count-numbers">$<?php echo number_format($revenue, 2); ?></span>
                        <span class="count-name">Total Revenue</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get recent system logs
                                        $logs_query = "SELECT l.*, u.user_name, a.username as admin_username 
                                                      FROM system_logs l 
                                                      LEFT JOIN users u ON l.user_id = u.id 
                                                      LEFT JOIN admin_users a ON l.admin_id = a.id 
                                                      ORDER BY l.timestamp DESC LIMIT 10";
                                        $logs_result = mysqli_query($conn, $logs_query);

                                        if (mysqli_num_rows($logs_result) > 0) {
                                            while ($log = mysqli_fetch_assoc($logs_result)) {
                                                echo '<tr>';
                                                echo '<td>' . date('M d, Y H:i', strtotime($log['timestamp'])) . '</td>';
                                                echo '<td>' . ($log['user_name'] ?? $log['admin_username'] ?? 'System') . '</td>';
                                                echo '<td>' . htmlspecialchars($log['action']) . '</td>';
                                                echo '<td>' . htmlspecialchars(substr($log['details'], 0, 100)) . (strlen($log['details']) > 100 ? '...' : '') . '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="4" class="text-center">No recent activity</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Admin JS -->
<script src="../assets/js/admin.js"></script>

</body>
</html>