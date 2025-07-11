<?php
// Check if admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect if not logged in
if (!is_admin_logged_in() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    redirect(SITE_URL . '/admin/login.php');
}

// Get admin information if logged in
$admin = null;
if (is_admin_logged_in()) {
    $admin_id = $_SESSION['admin_id'];
    $query = "SELECT * FROM admin_users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $admin_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Get counts for dashboard
$users_count = 0;
$tokens_used = 0;
$files_count = 0;

$query = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $users_count = $row['count'];
}

$query = "SELECT SUM(tokens_used) as total FROM token_usage";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $tokens_used = $row['total'] ? $row['total'] : 0;
}

$query = "SELECT COUNT(*) as count FROM uploaded_files";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $files_count = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Admin Panel' : 'Admin Panel'; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Sidebar */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            color: #fff;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            z-index: 1;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand h2 {
            color: #fff;
            font-size: 1.2rem;
            margin: 0;
            font-weight: 700;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left-color: #fff;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 15px 0;
        }
        
        .sidebar-heading {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1rem;
            padding: 0 20px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        /* Topbar */
        .topbar {
            background-color: #fff;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            margin-bottom: 24px;
            border-radius: 5px;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            color: #4e73df;
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
        }
        
        .admin-profile .dropdown-toggle {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #5a5c69;
        }
        
        .admin-profile .dropdown-toggle::after {
            display: none;
        }
        
        .admin-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .admin-profile .admin-info {
            text-align: right;
        }
        
        .admin-profile .admin-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #5a5c69;
            margin: 0;
        }
        
        .admin-profile .admin-role {
            font-size: 0.75rem;
            color: #858796;
            margin: 0;
        }
        
        .admin-profile .dropdown-menu {
            min-width: 200px;
            padding: 10px 0;
            margin-top: 10px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .admin-profile .dropdown-item {
            padding: 8px 20px;
            font-size: 0.85rem;
        }
        
        .admin-profile .dropdown-item i {
            margin-right: 10px;
            color: #4e73df;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 5px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 24px;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h6 {
            font-weight: 700;
            font-size: 1rem;
            color: #4e73df;
            margin: 0;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }
        
        .dataTable {
            width: 100% !important;
        }
        
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter, 
        .dataTables_wrapper .dataTables_info, 
        .dataTables_wrapper .dataTables_processing, 
        .dataTables_wrapper .dataTables_paginate {
            color: #5a5c69;
            margin-bottom: 15px;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #4e73df;
            color: #fff !important;
            border: 1px solid #4e73df;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.active {
                margin-left: 250px;
            }
            
            .toggle-sidebar {
                display: block;
            }
        }
    </style>
    <?php if (isset($additional_css)) echo $additional_css; ?>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <h2><i class="fas fa-chart-line mr-2"></i> RiemInsights</h2>
            </div>
            
            <div class="sidebar-menu">
                <div class="sidebar-heading">Core</div>
                <ul>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="<?php echo isset($active_page) && $active_page === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                </ul>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-heading">Management</div>
                <ul>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/users.php" class="<?php echo isset($active_page) && $active_page === 'users' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/tokens.php" class="<?php echo isset($active_page) && $active_page === 'tokens' ? 'active' : ''; ?>">
                            <i class="fas fa-coins"></i> Token Packs
                        </a>
                    </li>
                </ul>
                
                <div class="sidebar-divider"></div>
                <div class="sidebar-heading">Administration</div>
                <ul>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/admins.php" class="<?php echo isset($active_page) && $active_page === 'admins' ? 'active' : ''; ?>">
                            <i class="fas fa-user-shield"></i> Admin Users
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/profile.php" class="<?php echo isset($active_page) && $active_page === 'profile' ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle"></i> My Profile
                        </a>
                    </li>
                </ul>
                
                <div class="sidebar-divider"></div>
                <ul>
                    <li>
                        <a href="<?php echo SITE_URL; ?>/admin/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Site
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Topbar -->
            <div class="topbar">
                <button class="toggle-sidebar" id="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="admin-profile">
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle" id="adminDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['full_name'] ?? 'Admin User'); ?>&background=4e73df&color=fff" alt="Admin">
                            <div class="admin-info">
                                <p class="admin-name"><?php echo htmlspecialchars($admin['full_name'] ?? 'Admin User'); ?></p>
                                <p class="admin-role"><?php echo ucfirst(str_replace('_', ' ', $admin['role'] ?? 'admin')); ?></p>
                            </div>
                            <i class="fas fa-chevron-down ml-2"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="adminDropdown">
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/profile.php">
                                <i class="fas fa-user-circle"></i> My Profile
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="container-fluid">