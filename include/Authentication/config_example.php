<?php
/**
 * SuiteCRM Modern Authentication Configuration Example
 * 
 * Copy this file to config_override.php and modify the values
 */

// OAuth Provider Configuration
$sugar_config['oauth_providers'] = [
    'google' => [
        'client_id' => 'your-google-client-id.apps.googleusercontent.com',
        'client_secret' => 'your-google-client-secret',
        'enabled' => true,
        'hosted_domain' => 'yourcompany.com' // Optional: restrict to specific domain
    ],
    'microsoft' => [
        'client_id' => 'your-microsoft-client-id',
        'client_secret' => 'your-microsoft-client-secret',
        'enabled' => true,
        'tenant_id' => 'common' // or your specific tenant ID
    ],
    'github' => [
        'client_id' => 'your-github-client-id',
        'client_secret' => 'your-github-client-secret',
        'enabled' => true
    ]
];

// Enhanced Password Policy Configuration
$sugar_config['password_policy'] = [
    'min_length' => 12,
    'max_length' => 128,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special_chars' => true,
    'min_special_chars' => 1,
    'min_numbers' => 1,
    'min_uppercase' => 1,
    'min_lowercase' => 1,
    'prevent_common_passwords' => true,
    'prevent_personal_info' => true,
    'prevent_dictionary_words' => true,
    'password_history_count' => 5,
    'password_expiry_days' => 90, // 0 = never expires
    'force_change_on_first_login' => true,
    'account_lockout_attempts' => 5,
    'account_lockout_duration' => 30, // minutes
    'allowed_special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
];

// Two-Factor Authentication Configuration
$sugar_config['2fa_config'] = [
    'enabled' => true,
    'required_for_admins' => true,
    'backup_codes_count' => 10,
    'issuer_name' => 'SuiteCRM'
];

// Security Headers Configuration
$sugar_config['security_headers'] = [
    'enabled' => true,
    'strict_transport_security' => 'max-age=31536000; includeSubDomains',
    'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google.com https://apis.google.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https: blob:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://api.github.com https://graph.microsoft.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self'",
    'x_frame_options' => 'DENY',
    'x_content_type_options' => 'nosniff',
    'x_xss_protection' => '1; mode=block',
    'referrer_policy' => 'strict-origin-when-cross-origin'
];

// JWT Configuration (auto-generated if not set)
// $sugar_config['jwt_secret_key'] = 'your-secure-jwt-secret-key';

// Session Security Configuration
$sugar_config['session_security'] = [
    'secure_cookies' => true, // Requires HTTPS
    'httponly_cookies' => true,
    'samesite_cookies' => 'Strict',
    'regenerate_id_on_login' => true,
    'session_timeout' => 3600 // seconds
];

// Rate Limiting Configuration
$sugar_config['rate_limiting'] = [
    'login_attempts' => [
        'max_attempts' => 5,
        'time_window' => 900 // 15 minutes
    ],
    'api_requests' => [
        'max_requests' => 1000,
        'time_window' => 3600 // 1 hour
    ]
];

// CORS Configuration for API access
$sugar_config['cors_allowed_origins'] = [
    'https://yourdomain.com',
    'https://app.yourdomain.com'
];

// Audit Logging Configuration
$sugar_config['audit_config'] = [
    'enabled' => true,
    'log_failed_logins' => true,
    'log_password_changes' => true,
    'log_2fa_events' => true,
    'log_oauth_events' => true,
    'retention_days' => 90
];

// Modern Login Interface Configuration
$sugar_config['modern_login'] = [
    'enabled' => true,
    'show_oauth_buttons' => true,
    'show_password_strength' => true,
    'show_security_notice' => true,
    'enable_remember_me' => false // Disabled for security
];

// Email Configuration for 2FA and Password Reset
$sugar_config['email_2fa'] = [
    'from_email' => 'noreply@yourcompany.com',
    'from_name' => 'SuiteCRM Security',
    'subject_2fa_setup' => 'Two-Factor Authentication Setup',
    'subject_password_reset' => 'Password Reset Request'
];

// Advanced Security Options
$sugar_config['advanced_security'] = [
    'enable_honeypot_fields' => true,
    'enable_request_signing' => false,
    'enable_device_fingerprinting' => false,
    'require_password_change_after_reset' => true,
    'max_concurrent_sessions' => 5
];

// Development/Testing Configuration
if (isset($_SERVER['APPLICATION_ENV']) && $_SERVER['APPLICATION_ENV'] === 'development') {
    // Relaxed settings for development
    $sugar_config['password_policy']['min_length'] = 8;
    $sugar_config['password_policy']['password_expiry_days'] = 0;
    $sugar_config['oauth_providers']['google']['enabled'] = false;
    $sugar_config['oauth_providers']['microsoft']['enabled'] = false;
    $sugar_config['oauth_providers']['github']['enabled'] = false;
}
