<?php
/**
 * RiemInsights - Data Analysis Page
 * Allows users to upload CSV/Excel files, visualize data, and ask questions
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
    redirect('login.php', 'Please log in to access the data analysis tools');
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
    redirect('login.php', 'User not found. Please log in again.');
    exit;
}

// Get user data
$user = $result->fetch_assoc();
$stmt->close();

// Set active page for navigation
$active_page = 'data_analysis';

// Include header
include 'includes/header.php';
?>

<!-- Include required libraries -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
<link rel="stylesheet" href="assets/css/data-analysis.css">

<!-- Main Content -->
<div class="container data-analysis-container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Data Analysis & Visualization</h1>
            
            <!-- Alerts Container -->
            <div id="alerts-container"></div>
            
            <!-- Loading Container -->
            <div id="loading-container" class="d-none"></div>
            
            <!-- File Upload Section -->
            <div class="file-upload-section">
                <h3>Upload Your Data</h3>
                <p class="text-muted">Upload a CSV or Excel file to analyze and visualize your data.</p>
                
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
                                <input type="file" id="file-upload" accept=".csv,.xlsx,.xls" class="d-none">
                            </label>
                        </div>
                        <div id="selected-filename" class="selected-filename d-none"></div>
                        <button type="submit" class="btn btn-success mt-3" disabled>Upload & Analyze</button>
                    </form>
                </div>
            </div>
            
            <!-- Data Analysis Section -->
            <div id="data-analysis-section" class="d-none">
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
                        <div id="data-preview" class="card-body"></div>
                        <div id="file-info" class="file-info"></div>
                    </div>
                </div>
                
                <!-- Data Statistics -->
                <div class="data-stats-section">
                    <h4>Data Statistics</h4>
                    <div id="data-stats"></div>
                </div>
                
                <!-- Visualization Section -->
                <div class="visualization-section">
                    <div id="visualizations"></div>
                    <div id="ai-visualization" class="d-none"></div>
                </div>
                
                <!-- Chat Section -->
                <div class="chat-section">
                    <div class="chat-container">
                        <div class="chat-header">
                            <h4 class="mb-0">Ask Questions About Your Data</h4>
                        </div>
                        <div id="chat-messages" class="chat-messages"></div>
                        <div class="chat-input-container">
                            <form id="chat-form">
                                <div class="input-group">
                                    <input type="text" id="chat-input" class="form-control chat-input" placeholder="Ask a question about your data...">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary chat-submit">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
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
<script src="assets/js/fontawesome.js"></script>
<script src="assets/js/main.js"></script>

<?php
// No footer included as per requirements
?>