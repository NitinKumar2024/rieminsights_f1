<?php
/**
 * RiemInsights - AI Data Analysis Page
 * Allows users to upload CSV/Excel files for AI-powered analysis
 */

// Include configuration and database connection
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    redirect('auth/login.php', 'Please log in to access the AI data analysis tools');
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT u.*, p.plan_name FROM users u JOIN plans p ON u.plan_type = p.plan_type WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found, redirect to login
    redirect('auth/login.php', 'User not found. Please log in again.');
    exit;
}

// Get user data
$user = $result->fetch_assoc();
$stmt->close();

// Check if user has enough tokens
$has_enough_tokens = ($user['tokens_remaining'] > 0);

// Set active page for navigation
$active_page = 'ai_data_analysis';
$page_title = 'AI Data Analysis';

// Add custom CSS
$additional_css = '<link rel="stylesheet" href="assets/css/ai-data-analysis.css">'; 

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="container ai-data-analysis-container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">AI-Powered Data Analysis</h1>
            
            <!-- Alerts Container -->
            <div id="alerts-container"></div>
            
            <!-- Loading Container -->
            <div id="loading-container" class="d-none"></div>
            
            <?php if (!$has_enough_tokens): ?>
            <!-- Insufficient Tokens Warning -->
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Insufficient tokens!</strong> You need tokens to use the AI data analysis features. 
                <a href="<?php echo SITE_URL; ?>/upgrade_plan.php" class="alert-link">Upgrade your plan</a> to get more tokens.
            </div>
            <?php endif; ?>
            
            <!-- File Upload Section -->
            <div class="file-upload-section">
                <h3>Upload Your Data for AI Analysis</h3>
                <p class="text-muted">Upload a CSV or Excel file to get AI-powered insights, summaries, and visualizations.</p>
                
                <div class="file-upload-container">
                    <div class="file-upload-icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div class="file-upload-text">
                        <p>Drag and drop your file here or click to browse</p>
                        <p class="text-muted small">Supported formats: CSV, Excel (.xlsx, .xls)</p>
                    </div>
                    <form id="upload-form">
                        <div class="form-group">
                            <label for="file-upload" class="btn btn-primary file-upload-btn">
                                Choose File
                                <input type="file" id="file-upload" accept=".csv,.xlsx,.xls" class="d-none" <?php echo !$has_enough_tokens ? 'disabled' : ''; ?>>
                            </label>
                        </div>
                        <div id="selected-filename" class="selected-filename d-none"></div>
                        <button type="submit" class="btn btn-success mt-3" disabled <?php echo !$has_enough_tokens ? 'disabled' : ''; ?>>Upload & Analyze</button>
                    </form>
                </div>
            </div>
            
            <!-- AI Data Analysis Section -->
            <div id="ai-data-analysis-section" class="d-none">
                <!-- Data Preview -->
                <div class="data-preview-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Data Preview</h4>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary" id="export-data">Export Data</button>
                                <select class="form-control form-control-sm ml-2" id="export-format">
                                    <option value="csv">CSV</option>
                                    <option value="json">JSON</option>
                                    <option value="xlsx">Excel</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="previewTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="top-rows-tab" data-toggle="tab" href="#top-rows" role="tab" aria-controls="top-rows" aria-selected="true">Top 10 Rows</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="bottom-rows-tab" data-toggle="tab" href="#bottom-rows" role="tab" aria-controls="bottom-rows" aria-selected="false">Bottom 10 Rows</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="previewTabsContent">
                                <div class="tab-pane fade show active" id="top-rows" role="tabpanel" aria-labelledby="top-rows-tab">
                                    <div id="top-rows-preview" class="table-responsive"></div>
                                </div>
                                <div class="tab-pane fade" id="bottom-rows" role="tabpanel" aria-labelledby="bottom-rows-tab">
                                    <div id="bottom-rows-preview" class="table-responsive"></div>
                                </div>
                            </div>
                        </div>
                        <div id="file-info" class="file-info"></div>
                    </div>
                </div>
                
                <!-- AI Summary Section -->
                <div class="ai-summary-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">AI-Generated Summary</h4>
                            <div>
                                <span class="badge badge-info" id="summary-tokens-used">0 tokens</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="ai-summary-loading" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Generating AI summary...</p>
                            </div>
                            <div id="ai-summary-content" class="d-none"></div>
                        </div>
                    </div>
                </div>
                
                <!-- AI Chat Section -->
                <div class="ai-chat-section">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Chat with AI about Your Data</h4>
                            <div class="d-flex align-items-center">
                                <button id="download-chat-btn" class="btn btn-sm btn-outline-primary mr-2" title="Download Chat History">
                                    <i class="fas fa-download"></i> Download Chat
                                </button>
                                <span class="badge badge-info" id="chat-tokens-used">0 tokens</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="chat-messages" class="chat-messages"></div>
                            <div class="chat-input-container">
                                <form id="chat-form">
                                    <div class="input-group">
                                        <input type="text" id="chat-input" class="form-control chat-input" placeholder="Ask a question about your data or request charts and insights..." <?php echo !$has_enough_tokens ? 'disabled' : ''; ?>>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary chat-submit" <?php echo !$has_enough_tokens ? 'disabled' : ''; ?>>
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="chat-suggestions">
                                        <span class="suggestion-chip" data-suggestion="Summarize this dataset">Summarize this dataset</span>
                                        <span class="suggestion-chip" data-suggestion="Show me the key insights">Key insights</span>
                                        <span class="suggestion-chip" data-suggestion="Generate a chart of the main trends">Chart main trends</span>
                                        <span class="suggestion-chip" data-suggestion="Find correlations in the data">Find correlations</span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- AI Visualization Section -->
                <div class="ai-visualization-section">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">AI-Generated Visualizations</h4>
                        </div>
                        <div class="card-body">
                            <div id="ai-visualizations"></div>
                            <div id="no-visualizations" class="text-center d-none">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <p>No visualizations yet. Ask the AI to generate charts or insights in the chat above.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upgrade Modal -->
<div class="modal fade" id="upgrade-modal" tabindex="-1" role="dialog" aria-labelledby="upgradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="upgradeModalLabel">Upgrade Your Plan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="plan-card">
                            <div class="plan-title">Basic</div>
                            <div class="plan-price">$9.99<span class="text-muted">/month</span></div>
                            <ul class="plan-features">
                                <li>5,000 tokens per month</li>
                                <li>Basic data analysis</li>
                                <li>Standard visualizations</li>
                                <li>Email support</li>
                            </ul>
                            <button class="btn btn-primary plan-cta">Upgrade to Basic</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="plan-card" style="border-color: #007bff; transform: scale(1.05);">
                            <div class="plan-title">Pro</div>
                            <div class="plan-price">$19.99<span class="text-muted">/month</span></div>
                            <ul class="plan-features">
                                <li>20,000 tokens per month</li>
                                <li>Advanced data analysis</li>
                                <li>Premium visualizations</li>
                                <li>Priority support</li>
                                <li>Data export in all formats</li>
                            </ul>
                            <button class="btn btn-primary plan-cta">Upgrade to Pro</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="plan-card">
                            <div class="plan-title">Enterprise</div>
                            <div class="plan-price">$49.99<span class="text-muted">/month</span></div>
                            <ul class="plan-features">
                                <li>Unlimited tokens</li>
                                <li>Enterprise-grade analysis</li>
                                <li>Custom visualizations</li>
                                <li>24/7 dedicated support</li>
                                <li>Team collaboration</li>
                            </ul>
                            <button class="btn btn-primary plan-cta">Contact Sales</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Required JavaScript Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.2/papaparse.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/4.2.12/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>

<!-- Custom JavaScript -->
<script src="assets/js/ai-data-analysis.js"></script>

<?php
// Include footer
include 'includes/footer.php';
?>