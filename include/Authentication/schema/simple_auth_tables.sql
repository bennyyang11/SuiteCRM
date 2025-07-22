-- SuiteCRM Modern Authentication Database Schema - Simplified
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
    KEY idx_expires_at (expires_at)
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
    KEY idx_provider (provider)
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
    KEY idx_enabled (enabled)
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
    KEY idx_created_at (created_at)
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
    KEY idx_locked_until (locked_until)
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
    KEY idx_email (email)
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
('Content-Security-Policy', 'default-src ''self''; script-src ''self'' ''unsafe-inline'' ''unsafe-eval''; style-src ''self'' ''unsafe-inline''; img-src ''self'' data: https:; font-src ''self'' https:; connect-src ''self''; frame-ancestors ''none'';', 'Prevents XSS and data injection attacks');
