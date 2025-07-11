<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect(SITE_URL . '/landing.php');
}

// Set active page for header
$active_page = 'dashboard';
$page_title = 'Dashboard';

// Include header
require_once 'includes/header.php';
?>

<div class="container dashboard-container">
    <?php if (isset($_SESSION['flash_messages']['login_success']) || isset($_SESSION['flash_messages']['signup_success'])): ?>
    <div class="row">
        <div class="col-12">
            <?php echo display_flash_message('login_success'); ?>
            <?php echo display_flash_message('signup_success'); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card welcome-card mb-4">
                <div class="card-body">
                    <h2><i class="fas fa-chart-line mr-2"></i>Welcome, <?php echo htmlspecialchars($user['user_name']); ?>!</h2>
                    <p class="lead">You're now using RiemInsights, an AI-powered data analytics platform designed to help you extract valuable insights from your data.</p>
                    <p>Get started by uploading your data files and asking questions in natural language.</p>
                    <a href="<?php echo SITE_URL; ?>/data_analysis.php" class="btn btn-light">
                        <i class="fas fa-upload mr-2"></i>Upload Data
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history mr-2"></i>Recent Activity</span>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="text-center py-5">
                        <i class="fas fa-chart-area fa-3x text-muted mb-3"></i>
                        <p class="mb-0">No recent activity yet.</p>
                        <p class="text-muted">Upload your first dataset to get started!</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-lightbulb mr-2 text-warning"></i>
                    <span>Quick Tips</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">Upload CSV or Excel files for analysis</li>
                        <li class="list-group-item">Ask questions in natural language</li>
                        <li class="list-group-item">Visualize data with interactive charts</li>
                        <li class="list-group-item">Export insights to various formats</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="fas fa-rocket mr-2 text-primary"></i>
                    <span>Getting Started</span>
                </div>
                <div class="card-body">
                    <div class="getting-started-step mb-3">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h6>Upload Your Data</h6>
                            <p class="text-muted small">Upload CSV or Excel files to begin analysis</p>
                        </div>
                    </div>
                    <div class="getting-started-step mb-3">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h6>Ask Questions</h6>
                            <p class="text-muted small">Use natural language to query your data</p>
                        </div>
                    </div>
                    <div class="getting-started-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h6>Get Insights</h6>
                            <p class="text-muted small">View visualizations and export results</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.lead {
    font-size: 1.1rem;
    font-weight: 500;
}

.getting-started-step {
    display: flex;
    align-items: flex-start;
}

.step-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 12px;
    flex-shrink: 0;
}

.step-content {
    flex-grow: 1;
}

.step-content h6 {
    margin-bottom: 2px;
    font-weight: 600;
}
</style>

<?php
// No footer included as per requirements
?>