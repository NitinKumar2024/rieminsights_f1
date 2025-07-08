-- =====================================================
-- DATA ANALYSIS RESULTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS data_analysis_results (
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
);

-- Update the uploaded_files table to include column headers
ALTER TABLE uploaded_files
ADD COLUMN column_headers JSON NULL AFTER total_columns,
ADD COLUMN data_preview JSON NULL AFTER column_headers;