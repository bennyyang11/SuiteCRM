-- SuiteCRM Modern Authentication Database Schema
-- Tables for JWT refresh tokens, OAuth tokens, 2FA settings, and audit logging

-- JWT Refresh Tokens Table
CREATE TABLE IF NOT EXISTS user_refresh_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_token (user_id, token),
    KEY idx_user_id (user_id),
    KEY idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- OAuth Tokens Table
CREATE TABLE IF NOT EXISTS user_oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    provider ENUM('google', 'microsoft', 'github') NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    expires_at DATETIME,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_provider (user_id, provider),
    KEY idx_user_id (user_id),
    KEY idx_provider (provider),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Two-Factor Authentication Settings Table
CREATE TABLE IF NOT EXISTS user_2fa_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    secret_key TEXT NOT NULL,
    backup_codes JSON,
    enabled BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_2fa (user_id),
    KEY idx_user_id (user_id),
    KEY idx_enabled (enabled),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Authentication Audit Log Table
CREATE TABLE IF NOT EXISTS auth_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36),
    username VARCHAR(255),
    event_type ENUM('login_success', 'login_failed', 'logout', '2fa_enabled', '2fa_disabled', 'password_changed', 'account_locked', 'account_unlocked', 'oauth_login') NOT NULL,
    auth_method ENUM('password', 'oauth_google', 'oauth_microsoft', 'oauth_github', '2fa', 'backup_code') DEFAULT 'password',
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(255),
    details JSON,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_event_type (event_type),
    KEY idx_auth_method (auth_method),
    KEY idx_ip_address (ip_address),
    KEY idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Account Lockout Table
CREATE TABLE IF NOT EXISTS user_lockouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36),
    username VARCHAR(255),
    ip_address VARCHAR(45),
    failed_attempts INT DEFAULT 0,
    locked_until DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_lockout (user_id),
    KEY idx_username (username),
    KEY idx_ip_address (ip_address),
    KEY idx_locked_until (locked_until),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password Policy Tracking Table
CREATE TABLE IF NOT EXISTS user_password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- OAuth Provider Accounts Linking Table
CREATE TABLE IF NOT EXISTS user_oauth_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    provider ENUM('google', 'microsoft', 'github') NOT NULL,
    provider_user_id VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    name VARCHAR(255),
    picture VARCHAR(500),
    linked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_provider_account (provider, provider_user_id),
    KEY idx_user_id (user_id),
    KEY idx_provider (provider),
    KEY idx_email (email),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Security Headers Configuration Table
CREATE TABLE IF NOT EXISTS security_headers_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    header_name VARCHAR(100) NOT NULL,
    header_value TEXT NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_header (header_name)
);

-- Insert default security headers
INSERT IGNORE INTO security_headers_config (header_name, header_value, description) VALUES
('X-Content-Type-Options', 'nosniff', 'Prevents MIME type sniffing'),
('X-Frame-Options', 'DENY', 'Prevents clickjacking attacks'),
('X-XSS-Protection', '1; mode=block', 'Enables XSS filtering'),
('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', 'Forces HTTPS connections'),
('Referrer-Policy', 'strict-origin-when-cross-origin', 'Controls referrer information'),
('Content-Security-Policy', 'default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\' https:; connect-src \'self\'; frame-ancestors \'none\';', 'Prevents XSS and data injection attacks');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_user_refresh_tokens_cleanup ON user_refresh_tokens(expires_at);
CREATE INDEX IF NOT EXISTS idx_auth_audit_log_date_range ON auth_audit_log(created_at, event_type);
CREATE INDEX IF NOT EXISTS idx_user_lockouts_active ON user_lockouts(locked_until, failed_attempts);

-- Add custom fields to users table for enhanced security
SET @sql = '';
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'password_expires_at';

SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN password_expires_at DATETIME NULL', 'SELECT "Column password_expires_at already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'force_password_change';

SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN force_password_change BOOLEAN DEFAULT FALSE', 'SELECT "Column force_password_change already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'last_login_at';

SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL', 'SELECT "Column last_login_at already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'failed_login_attempts';

SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0', 'SELECT "Column failed_login_attempts already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'account_locked_until';

SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN account_locked_until DATETIME NULL', 'SELECT "Column account_locked_until already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'two_factor_enabled';

SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE', 'SELECT "Column two_factor_enabled already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'password_changed_at';

SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL', 'SELECT "Column password_changed_at already exists" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create view for security dashboard
CREATE OR REPLACE VIEW security_dashboard AS
SELECT 
    'total_users' as metric,
    COUNT(*) as value,
    'Total registered users' as description
FROM users WHERE deleted = 0

UNION ALL

SELECT 
    'active_2fa_users' as metric,
    COUNT(*) as value,
    'Users with 2FA enabled' as description
FROM users u 
JOIN user_2fa_settings tfa ON u.id = tfa.user_id 
WHERE u.deleted = 0 AND tfa.enabled = 1

UNION ALL

SELECT 
    'oauth_linked_users' as metric,
    COUNT(DISTINCT user_id) as value,
    'Users with OAuth accounts linked' as description
FROM user_oauth_accounts

UNION ALL

SELECT 
    'locked_accounts' as metric,
    COUNT(*) as value,
    'Currently locked user accounts' as description
FROM users 
WHERE deleted = 0 AND account_locked_until > NOW()

UNION ALL

SELECT 
    'recent_logins' as metric,
    COUNT(*) as value,
    'Successful logins in last 24 hours' as description
FROM auth_audit_log 
WHERE event_type = 'login_success' 
AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)

UNION ALL

SELECT 
    'failed_logins' as metric,
    COUNT(*) as value,
    'Failed login attempts in last 24 hours' as description
FROM auth_audit_log 
WHERE event_type = 'login_failed' 
AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR);
