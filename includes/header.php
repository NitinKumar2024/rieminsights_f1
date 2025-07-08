<?php
// Check if user is logged in
$is_logged_in = is_logged_in();

// Get user information if logged in
$user = null;
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = ?";
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
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">RiemInsights</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'dashboard' ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>">Dashboard</a>
                        </li>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'data_analysis' ? 'active' : ''; ?>">
                            <a class="nav-link" href="#">Data Analysis</a>
                        </li>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'upload_data' ? 'active' : ''; ?>">
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
                    <?php else: ?>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'login' ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item <?php echo isset($active_page) && $active_page === 'signup' ? 'active' : ''; ?>">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/auth/signup.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>