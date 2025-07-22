<?php
/**
 * SuiteCRM Modern Authentication Test Script
 * 
 * Test the new authentication system components
 */

// Initialize SuiteCRM
if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';

// Include modern auth classes
require_once 'vendor/autoload.php';

use SuiteCRM\Authentication\ModernAuth\JWTManager;
use SuiteCRM\Authentication\OAuth\OAuthProviders;
use SuiteCRM\Authentication\TwoFactor\TwoFactorAuth;
use SuiteCRM\Authentication\Security\PasswordPolicy;
use SuiteCRM\Authentication\Security\SecurityHeaders;

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>SuiteCRM Modern Authentication Test</title>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .test{margin:20px 0;padding:15px;border:1px solid #ddd;} .pass{background:#d4edda;border-color:#c3e6cb;} .fail{background:#f8d7da;border-color:#f5c6cb;} h2{color:#333;} pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow:auto;}</style>\n";
echo "</head>\n<body>\n";

echo "<h1>SuiteCRM Modern Authentication System Test</h1>\n";

$testResults = [];

// Test 1: Database Tables
echo "<div class='test'>\n";
echo "<h2>Test 1: Database Tables</h2>\n";
try {
    global $db;
    
    $tables = [
        'user_refresh_tokens',
        'user_oauth_tokens', 
        'user_2fa_settings',
        'auth_audit_log',
        'user_lockouts',
        'user_oauth_accounts',
        'security_headers_config'
    ];
    
    $existingTables = [];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($db->getRowCount($result) > 0) {
            $existingTables[] = $table;
        }
    }
    
    echo "<p><strong>Required tables:</strong> " . implode(', ', $tables) . "</p>\n";
    echo "<p><strong>Existing tables:</strong> " . implode(', ', $existingTables) . "</p>\n";
    
    if (count($existingTables) === count($tables)) {
        echo "<p style='color:green;'>✓ All database tables exist</p>\n";
        $testResults['database'] = true;
    } else {
        echo "<p style='color:red;'>✗ Missing tables: " . implode(', ', array_diff($tables, $existingTables)) . "</p>\n";
        $testResults['database'] = false;
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Database test failed: " . $e->getMessage() . "</p>\n";
    $testResults['database'] = false;
}
echo "</div>\n";

// Test 2: JWT Manager
echo "<div class='test'>\n";
echo "<h2>Test 2: JWT Token Manager</h2>\n";
try {
    $jwtManager = new JWTManager();
    
    // Generate test tokens
    $tokens = $jwtManager->generateTokens('test-user-id', ['test' => 'data']);
    
    // Validate access token
    $payload = $jwtManager->validateToken($tokens['access_token']);
    
    if ($payload && $payload['sub'] === 'test-user-id') {
        echo "<p style='color:green;'>✓ JWT token generation and validation working</p>\n";
        echo "<pre>Access Token: " . substr($tokens['access_token'], 0, 50) . "...</pre>\n";
        $testResults['jwt'] = true;
    } else {
        echo "<p style='color:red;'>✗ JWT token validation failed</p>\n";
        $testResults['jwt'] = false;
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ JWT test failed: " . $e->getMessage() . "</p>\n";
    $testResults['jwt'] = false;
}
echo "</div>\n";

// Test 3: OAuth Providers
echo "<div class='test'>\n";
echo "<h2>Test 3: OAuth Providers</h2>\n";
try {
    $oauthProviders = new OAuthProviders();
    $configuredProviders = $oauthProviders->getConfiguredProviders();
    
    echo "<p><strong>Configured OAuth providers:</strong> " . (empty($configuredProviders) ? 'None' : implode(', ', $configuredProviders)) . "</p>\n";
    
    // Test provider initialization
    $providers = ['google', 'microsoft', 'github'];
    $workingProviders = [];
    
    foreach ($providers as $provider) {
        try {
            $providerInstance = $oauthProviders->getProvider($provider);
            if ($providerInstance) {
                $workingProviders[] = $provider;
            }
        } catch (Exception $e) {
            // Provider not configured
        }
    }
    
    echo "<p><strong>Working providers:</strong> " . (empty($workingProviders) ? 'None (not configured)' : implode(', ', $workingProviders)) . "</p>\n";
    echo "<p style='color:blue;'>ℹ Configure OAuth providers in config_override.php to enable social login</p>\n";
    $testResults['oauth'] = true;
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ OAuth test failed: " . $e->getMessage() . "</p>\n";
    $testResults['oauth'] = false;
}
echo "</div>\n";

// Test 4: Two-Factor Authentication
echo "<div class='test'>\n";
echo "<h2>Test 4: Two-Factor Authentication</h2>\n";
try {
    $twoFactorAuth = new TwoFactorAuth();
    
    // Generate secret key
    $secretKey = $twoFactorAuth->generateSecretKey();
    
    // Generate QR code
    $qrCode = $twoFactorAuth->generateQRCode('test@example.com', $secretKey);
    
    // Get current code
    $currentCode = $twoFactorAuth->getCurrentCode($secretKey);
    
    // Verify the code
    $isValid = $twoFactorAuth->verifyCode($secretKey, $currentCode);
    
    if ($isValid) {
        echo "<p style='color:green;'>✓ 2FA code generation and verification working</p>\n";
        echo "<p><strong>Secret Key:</strong> " . chunk_split($secretKey, 4, ' ') . "</p>\n";
        echo "<p><strong>Current Code:</strong> " . $currentCode . "</p>\n";
        echo "<p><strong>QR Code Generated:</strong> " . (strlen($qrCode) > 100 ? 'Yes' : 'No') . "</p>\n";
        $testResults['2fa'] = true;
    } else {
        echo "<p style='color:red;'>✗ 2FA code verification failed</p>\n";
        $testResults['2fa'] = false;
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ 2FA test failed: " . $e->getMessage() . "</p>\n";
    $testResults['2fa'] = false;
}
echo "</div>\n";

// Test 5: Password Policy
echo "<div class='test'>\n";
echo "<h2>Test 5: Password Policy</h2>\n";
try {
    $passwordPolicy = new PasswordPolicy();
    
    // Test weak password
    $weakResult = $passwordPolicy->validatePassword('password123');
    
    // Test strong password
    $strongResult = $passwordPolicy->validatePassword('MyStr0ng@P@ssw0rd!2024');
    
    // Generate secure password
    $generatedPassword = $passwordPolicy->generateSecurePassword();
    
    echo "<p><strong>Weak password test:</strong> " . ($weakResult['valid'] ? 'Passed (should fail)' : 'Failed as expected') . "</p>\n";
    if (!$weakResult['valid']) {
        echo "<p>Errors: " . implode(', ', $weakResult['errors']) . "</p>\n";
    }
    
    echo "<p><strong>Strong password test:</strong> " . ($strongResult['valid'] ? 'Passed' : 'Failed') . "</p>\n";
    echo "<p><strong>Generated password:</strong> " . $generatedPassword . "</p>\n";
    echo "<p><strong>Generated password strength:</strong> " . $passwordPolicy->calculatePasswordStrength($generatedPassword) . "/100</p>\n";
    
    if (!$weakResult['valid'] && $strongResult['valid']) {
        echo "<p style='color:green;'>✓ Password policy validation working correctly</p>\n";
        $testResults['password_policy'] = true;
    } else {
        echo "<p style='color:red;'>✗ Password policy validation not working correctly</p>\n";
        $testResults['password_policy'] = false;
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Password policy test failed: " . $e->getMessage() . "</p>\n";
    $testResults['password_policy'] = false;
}
echo "</div>\n";

// Test 6: Security Headers
echo "<div class='test'>\n";
echo "<h2>Test 6: Security Headers</h2>\n";
try {
    $security = new SecurityHeaders();
    
    // Generate CSRF token
    $csrfToken = $security->generateCSRFToken();
    
    // Test CSRF validation
    $_SESSION['csrf_token'] = $csrfToken;
    $isValidCSRF = $security->validateCSRFToken($csrfToken);
    
    // Test malicious input detection
    $maliciousInput = "<script>alert('xss')</script>";
    $threats = $security->detectMaliciousInput($maliciousInput);
    
    // Test rate limiting
    $rateLimitOk = $security->checkRateLimit('test_action', 10, 3600);
    
    echo "<p><strong>CSRF Token:</strong> " . substr($csrfToken, 0, 20) . "...</p>\n";
    echo "<p><strong>CSRF Validation:</strong> " . ($isValidCSRF ? 'Working' : 'Failed') . "</p>\n";
    echo "<p><strong>Malicious Input Detection:</strong> " . (empty($threats) ? 'None detected' : implode(', ', $threats)) . "</p>\n";
    echo "<p><strong>Rate Limiting:</strong> " . ($rateLimitOk ? 'Working' : 'Blocked') . "</p>\n";
    
    if ($isValidCSRF && !empty($threats)) {
        echo "<p style='color:green;'>✓ Security features working correctly</p>\n";
        $testResults['security'] = true;
    } else {
        echo "<p style='color:red;'>✗ Some security features not working</p>\n";
        $testResults['security'] = false;
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Security test failed: " . $e->getMessage() . "</p>\n";
    $testResults['security'] = false;
}
echo "</div>\n";

// Test Summary
echo "<div class='test'>\n";
echo "<h2>Test Summary</h2>\n";
$passedTests = array_sum($testResults);
$totalTests = count($testResults);
$percentage = round(($passedTests / $totalTests) * 100);

echo "<p><strong>Tests Passed:</strong> $passedTests / $totalTests ($percentage%)</p>\n";

if ($percentage >= 80) {
    echo "<p style='color:green;font-weight:bold;'>✓ Modern Authentication System is working well!</p>\n";
} else {
    echo "<p style='color:orange;font-weight:bold;'>⚠ Some components need attention</p>\n";
}

echo "<h3>Next Steps:</h3>\n";
echo "<ul>\n";
echo "<li>Configure OAuth providers in config_override.php for social login</li>\n";
echo "<li>Set up HTTPS for production (required for secure cookies)</li>\n";
echo "<li>Configure email settings for 2FA and password reset</li>\n";
echo "<li>Review and adjust password policy settings</li>\n";
echo "<li>Enable the modern login interface by updating login template</li>\n";
echo "</ul>\n";

echo "</div>\n";

// Configuration Check
echo "<div class='test'>\n";
echo "<h2>Configuration Status</h2>\n";
global $sugar_config;

echo "<h3>Current Configuration:</h3>\n";
echo "<pre>\n";
echo "JWT Secret Key: " . (isset($sugar_config['jwt_secret_key']) ? 'Configured' : 'Not configured') . "\n";
echo "OAuth Providers: " . (isset($sugar_config['oauth_providers']) ? 'Configured' : 'Not configured') . "\n";
echo "Password Policy: " . (isset($sugar_config['password_policy']) ? 'Configured' : 'Using defaults') . "\n";
echo "2FA Encryption Key: " . (isset($sugar_config['2fa_encryption_key']) ? 'Configured' : 'Not configured') . "\n";
echo "OAuth Encryption Key: " . (isset($sugar_config['oauth_encryption_key']) ? 'Configured' : 'Not configured') . "\n";
echo "</pre>\n";
echo "</div>\n";

echo "</body>\n</html>";

// Cleanup test session data
unset($_SESSION['csrf_token']);
