<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">RiemInsights</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item active">
                        <a class="nav-link" href="#">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Data Analysis</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Upload Data</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <?php echo htmlspecialchars($user['user_name']); ?>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="#">Profile</a>
                            <a class="dropdown-item" href="#">Settings</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php">Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container dashboard-container">
        <?php echo display_flash_message('login_success'); ?>
        <?php echo display_flash_message('signup_success'); ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card welcome-card mb-4">
                    <div class="card-body">
                        <h2>Welcome, <?php echo htmlspecialchars($user['user_name']); ?>!</h2>
                        <p>You're now using RiemInsights, an AI-powered data analytics platform designed to help you extract valuable insights from your data.</p>
                        <p>Get started by uploading your data files and asking questions in natural language.</p>
                        <a href="#" class="btn btn-light">Upload Data</a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        Recent Activity
                    </div>
                    <div class="card-body">
                        <p class="text-center text-muted">No recent activity to display.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card stats-card mb-4">
                    <div class="card-body">
                        <div class="stat-value"><?php echo number_format($user['tokens_remaining']); ?></div>
                        <div class="stat-label">Tokens Remaining</div>
                    </div>
                </div>
                
                <div class="card stats-card mb-4">
                    <div class="card-body">
                        <div class="stat-value"><?php echo ucfirst($user['plan_type']); ?></div>
                        <div class="stat-label">Current Plan</div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        Quick Tips
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Upload CSV or Excel files for analysis</li>
                            <li class="list-group-item">Ask questions in natural language</li>
                            <li class="list-group-item">Visualize data with interactive charts</li>
                            <li class="list-group-item">Export insights to various formats</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>