<?php
require_once 'config.php';

// Function to execute SQL queries
function execute_sql($sql) {
    global $conn;
    try {
        $result = $conn->query($sql);
        if ($result) {
            return true;
        } else {
            echo "Error executing query: " . $conn->error . "<br>";
            return false;
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    echo "<p>You must be logged in as an admin to run database updates.</p>";
    exit;
}

// Start the update process
echo "<h2>Database Update Process</h2>";

// Create data_analysis_results table if it doesn't exist
$create_results_table = "CREATE TABLE IF NOT EXISTS data_analysis_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    file_id INT NOT NULL,
    analysis_type ENUM('visualization', 'query', 'statistics') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    chart_type VARCHAR(50) NULL,
    chart_config JSON NULL,
    query_text TEXT NULL,
    result_data JSON NULL,
    tokens_used INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES uploaded_files(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_file_id (file_id),
    INDEX idx_analysis_type (analysis_type),
    INDEX idx_created_at (created_at)
)";

if (execute_sql($create_results_table)) {
    echo "<p>✅ Created data_analysis_results table successfully.</p>";
} else {
    echo "<p>❌ Failed to create data_analysis_results table.</p>";
}

// Check if uploaded_files table exists
$check_uploaded_files = "SHOW TABLES LIKE 'uploaded_files'";
$result = $conn->query($check_uploaded_files);

if ($result && $result->num_rows > 0) {
    // Check if column_headers column exists
    $check_column = "SHOW COLUMNS FROM uploaded_files LIKE 'column_headers'";
    $column_result = $conn->query($check_column);
    
    if ($column_result && $column_result->num_rows == 0) {
        // Add column_headers and data_preview columns
        $alter_table = "ALTER TABLE uploaded_files
            ADD COLUMN column_headers JSON NULL AFTER total_columns,
            ADD COLUMN data_preview JSON NULL AFTER column_headers";
        
        if (execute_sql($alter_table)) {
            echo "<p>✅ Added column_headers and data_preview columns to uploaded_files table.</p>";
        } else {
            echo "<p>❌ Failed to add columns to uploaded_files table.</p>";
        }
    } else {
        echo "<p>ℹ️ column_headers column already exists in uploaded_files table.</p>";
    }
} else {
    // Create uploaded_files table if it doesn't exist
    $create_uploaded_files = "CREATE TABLE uploaded_files (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_type ENUM('csv', 'xlsx', 'xls') NOT NULL,
        file_size INT NOT NULL,
        total_rows INT DEFAULT 0,
        total_columns INT DEFAULT 0,
        column_headers JSON NULL,
        data_preview JSON NULL,
        is_cleaned BOOLEAN DEFAULT FALSE,
        cleaned_file_path VARCHAR(500) NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_accessed TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_upload_date (upload_date),
        INDEX idx_file_type (file_type)
    )";
    
    if (execute_sql($create_uploaded_files)) {
        echo "<p>✅ Created uploaded_files table successfully.</p>";
    } else {
        echo "<p>❌ Failed to create uploaded_files table.</p>";
    }
}

echo "<p>Database update process completed.</p>";
echo "<p><a href='index.php'>Return to Dashboard</a></p>";
?>