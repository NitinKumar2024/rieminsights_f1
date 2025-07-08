<?php
// Check if user is logged in
$is_logged_in = is_logged_in();

// Get user information if logged in
$user = null;
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT u.*, p.plan_name FROM users u 
              LEFT JOIN plans p ON u.plan_type = p.plan_type 
              WHERE u.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <?php if (isset($additional_css)) echo $additional_css; ?>
    <script src="<?php echo SITE_URL; ?>/assets/js/fontawesome.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">RiemInsights</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'dashboard' ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>">Dashboard</a>
                        </li>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'data_analysis' ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/data_analysis.php">Data Analysis</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'login' ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'signup' ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/signup.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <?php if ($is_logged_in): ?>
                <div class="profile-section d-flex align-items-center">
                    <div class="token-display mr-3">
                        <span class="token-count"><?php echo number_format($user['tokens_remaining']); ?></span>
                        <span class="token-label">tokens</span>
                    </div>
                    
                    <?php if ($user['plan_type'] == 'free'): ?> <!-- Free plan users can upgrade -->
                    <a href="#" class="btn btn-sm btn-upgrade mr-3">Upgrade</a>
                    <?php endif; ?>
                    
                    <div class="dropdown">
                        <a class="profile-dropdown dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="profile-name"><?php echo htmlspecialchars($user['user_name']); ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                            <div class="dropdown-header">
                                <strong><?php echo htmlspecialchars($user['plan_name'] ?? 'Free Plan'); ?></strong>
                            </div>
                            <a class="dropdown-item" href="#">Profile</a>
                            <a class="dropdown-item" href="#">Settings</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php">Logout</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Bootstrap and jQuery dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>