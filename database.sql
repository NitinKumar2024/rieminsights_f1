CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_name VARCHAR(100),
    plan_type ENUM('free', 'starter', 'pro', 'teams') DEFAULT 'free',
    tokens_remaining INT DEFAULT 20000,
    total_tokens_purchased INT DEFAULT 20000,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_plan_type (plan_type),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- 2. PLANS TABLE
-- =====================================================
CREATE TABLE plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(50) NOT NULL,
    plan_type ENUM('free', 'starter', 'pro', 'teams') UNIQUE NOT NULL,
    monthly_tokens INT NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plan_type (plan_type)
);

-- =====================================================
-- 3. TOKEN PACKS TABLE (Add-on purchases)
-- =====================================================
CREATE TABLE token_packs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pack_name VARCHAR(100) NOT NULL,
    tokens INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    pack_type ENUM('regular', 'r1_advanced') DEFAULT 'regular',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pack_type (pack_type)
);

-- =====================================================
-- 4. UPLOADED FILES TABLE
-- =====================================================
CREATE TABLE uploaded_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type ENUM('csv', 'xlsx', 'xls') NOT NULL,
    file_size INT NOT NULL,
    total_rows INT DEFAULT 0,
    total_columns INT DEFAULT 0,
    is_cleaned BOOLEAN DEFAULT FALSE,
    cleaned_file_path VARCHAR(500) NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_upload_date (upload_date),
    INDEX idx_file_type (file_type)
);

-- =====================================================
-- 5. TOKEN USAGE TRACKING TABLE
-- =====================================================
CREATE TABLE token_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    file_id INT NULL,
    tokens_used INT NOT NULL,
    action_type ENUM('ai_query', 'data_analysis', 'file_processing') NOT NULL,
    query_text TEXT NULL,
    response_text TEXT NULL,
    api_response_time DECIMAL(5,3) NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES uploaded_files(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action_type (action_type)
);

-- =====================================================
-- 6. TOKEN PURCHASES TABLE
-- =====================================================
CREATE TABLE token_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    pack_id INT NOT NULL,
    tokens_purchased INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_method ENUM('paynow', 'manual') DEFAULT 'manual',
    payment_reference VARCHAR(255) NULL,
    payment_status ENUM('pending', 'confirmed', 'failed') DEFAULT 'pending',
    confirmed_by INT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pack_id) REFERENCES token_packs(id),
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_purchase_date (purchase_date)
);

-- =====================================================
-- 7. ADMIN USERS TABLE
-- =====================================================
CREATE TABLE admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(200),
    role ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- =====================================================
-- 8. SYSTEM LOGS TABLE
-- =====================================================
CREATE TABLE system_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    admin_id INT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
);

-- =====================================================
-- 9. SESSIONS TABLE (Optional - for database sessions)
-- =====================================================
CREATE TABLE user_sessions (
    session_id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 24 HOUR),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default plans
INSERT INTO plans (plan_name, plan_type, monthly_tokens, price, description) VALUES
('Free Plan', 'free', 20000, 0.00, 'Perfect for getting started with basic data analysis'),
('Starter Plan', 'starter', 350000, 19.99, 'Ideal for small businesses and individual professionals'),
('Pro Plan', 'pro', 700000, 39.99, 'Advanced features for growing businesses'),
('Teams Plan', 'teams', 1000000, 59.99, 'Collaborative features for teams and enterprises');

-- Insert default token packs
INSERT INTO token_packs (pack_name, tokens, price, pack_type, description) VALUES
('Basic Token Pack', 50000, 3.00, 'regular', '50K additional tokens for regular AI queries'),
('Standard Token Pack', 100000, 5.00, 'regular', '100K additional tokens for extended analysis'),
('Premium R1 Pack', 100000, 9.00, 'r1_advanced', '100K R1 tokens for advanced AI models');

-- Insert default admin user (password: admin123 - CHANGE THIS!)
INSERT INTO admin_users (username, email, password, full_name, role) VALUES
('admin', 'admin@rieminsights.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');
