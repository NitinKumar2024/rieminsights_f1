<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(SITE_URL . '/auth/login.php');
}

// Set active page for header
$active_page = 'dashboard';
$page_title = 'Dashboard';

// Include header
require_once 'includes/header.php';
?>

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
                    <a href="<?php echo SITE_URL; ?>/data_analysis.php" class="btn btn-light">Upload Data</a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Your Uploaded Files
                </div>
                <div class="card-body">
                    <div id="uploaded-files-list">
                        <p class="text-center text-muted loading-message">Loading your files...</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
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

<?php
// No footer included as per requirements
?>