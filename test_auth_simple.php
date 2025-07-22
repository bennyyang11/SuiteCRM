<?php
/**
 * Simple SuiteCRM Modern Authentication Test
 */

// Initialize SuiteCRM
if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';
require_once 'vendor/autoload.php';

use SuiteCRM\Authentication\ModernAuth\JWTManager;
use SuiteCRM\Authentication\TwoFactor\TwoFactorAuth;
use SuiteCRM\Authentication\Security\PasswordPolicy;

// Simple HTML output
echo "<h1>SuiteCRM Modern Authentication Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .pass{color:green;} .fail{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:4px;}</style>\n";

$tests = [];

// Test 1: Database Tables
echo "<h2>1. Database Tables Test</h2>\n";
try {
    global $db;
    
    $requiredTables = [
        'user_refresh_tokens',
        'user_oauth_tokens', 
        'user_2fa_settings',
        'auth_audit_log',
        'user_lockouts',
        'user_oauth_accounts',
        'security_headers_config'
    ];
    
    $existingTables = [];
    foreach ($requiredTables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($db->getRowCount($result) > 0) {
            $existingTables[] = $table;
        }
    }
    
    $missingTables = array_diff($requiredTables, $existingTables);
    
    if (empty($missingTables)) {
        echo "<p class='pass'>✓ All database tables exist (" . count($existingTables) . "/" . count($requiredTables) . ")</p>\n";
        $tests['database'] = true;
    } else {
        echo "<p class='fail'>✗ Missing tables: " . implode(', ', $missingTables) . "</p>\n";
        $tests['database'] = false;
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ Database test failed: " . $e->getMessage() . "</p>\n";
    $tests['database'] = false;
}

// Test 2: JWT Manager (without database operations)
echo "<h2>2. JWT Token Test</h2>\n";
try {
    $jwtManager = new JWTManager();
    
    // Test token generation and validation
    $payload = [
        'iss' => 'suitecrm-test',
        'sub' => 'test-user',
        'iat' => time(),
        'exp' => time() + 3600,
        'type' => 'access'
    ];
    
    $secretKey = base64_encode(random_bytes(64));
    $token = \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');
    $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secretKey, 'HS256'));
    
    if ($decoded->sub === 'test-user') {
        echo "<p class='pass'>✓ JWT token generation and validation working</p>\n";
        echo "<p class='info'>Sample token: " . substr($token, 0, 50) . "...</p>\n";
        $tests['jwt'] = true;
    } else {
        echo "<p class='fail'>✗ JWT token validation failed</p>\n";
        $tests['jwt'] = false;
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ JWT test failed: " . $e->getMessage() . "</p>\n";
    $tests['jwt'] = false;
}

// Test 3: Two-Factor Authentication
echo "<h2>3. Two-Factor Authentication Test</h2>\n";
try {
    $twoFactorAuth = new TwoFactorAuth();
    
    // Generate secret key
    $secretKey = $twoFactorAuth->generateSecretKey();
    
    // Generate current code
    $currentCode = $twoFactorAuth->getCurrentCode($secretKey);
    
    // Verify the code
    $isValid = $twoFactorAuth->verifyCode($secretKey, $currentCode);
    
    if ($isValid && !empty($secretKey) && !empty($currentCode)) {
        echo "<p class='pass'>✓ 2FA generation and verification working</p>\n";
        echo "<p class='info'>Secret key: " . chunk_split($secretKey, 4, ' ') . "</p>\n";
        echo "<p class='info'>Current code: " . $currentCode . "</p>\n";
        $tests['2fa'] = true;
    } else {
        echo "<p class='fail'>✗ 2FA verification failed</p>\n";
        $tests['2fa'] = false;
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ 2FA test failed: " . $e->getMessage() . "</p>\n";
    $tests['2fa'] = false;
}

// Test 4: Password Policy
echo "<h2>4. Password Policy Test</h2>\n";
try {
    $passwordPolicy = new PasswordPolicy();
    
    // Test weak password
    $weakResult = $passwordPolicy->validatePassword('123456');
    
    // Test strong password
    $strongPassword = 'MySecure@Password123!';
    $strongResult = $passwordPolicy->validatePassword($strongPassword);
    
    // Generate secure password
    $generatedPassword = $passwordPolicy->generateSecurePassword(16);
    $generatedStrength = $passwordPolicy->calculatePasswordStrength($generatedPassword);
    
    if (!$weakResult['valid'] && $strongResult['valid'] && $generatedStrength >= 70) {
        echo "<p class='pass'>✓ Password policy validation working correctly</p>\n";
        echo "<p class='info'>Generated password: " . $generatedPassword . " (strength: " . $generatedStrength . "/100)</p>\n";
        if (!$weakResult['valid']) {
            echo "<p class='info'>Weak password errors: " . implode(', ', $weakResult['errors']) . "</p>\n";
        }
        $tests['password'] = true;
    } else {
        echo "<p class='fail'>✗ Password policy validation issues</p>\n";
        echo "<p>Weak password valid: " . ($weakResult['valid'] ? 'Yes (should be No)' : 'No') . "</p>\n";
        echo "<p>Strong password valid: " . ($strongResult['valid'] ? 'Yes' : 'No (should be Yes)') . "</p>\n";
        $tests['password'] = false;
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ Password policy test failed: " . $e->getMessage() . "</p>\n";
    $tests['password'] = false;
}

// Test 5: Required PHP Extensions
echo "<h2>5. PHP Extensions Test</h2>\n";
$requiredExtensions = ['openssl', 'json', 'mbstring', 'gd'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (empty($missingExtensions)) {
    echo "<p class='pass'>✓ All required PHP extensions are loaded</p>\n";
    $tests['extensions'] = true;
} else {
    echo "<p class='fail'>✗ Missing PHP extensions: " . implode(', ', $missingExtensions) . "</p>\n";
    $tests['extensions'] = false;
}

// Test 6: File Permissions
echo "<h2>6. File Permissions Test</h2>\n";
$testFile = 'config_override.php';
$configWritable = is_writable($testFile) || is_writable(dirname($testFile));

if ($configWritable) {
    echo "<p class='pass'>✓ Configuration file is writable</p>\n";
    $tests['permissions'] = true;
} else {
    echo "<p class='fail'>✗ Configuration file is not writable</p>\n";
    $tests['permissions'] = false;
}

// Summary
echo "<h2>Test Summary</h2>\n";
$passedTests = array_sum($tests);
$totalTests = count($tests);
$percentage = round(($passedTests / $totalTests) * 100);

echo "<p><strong>Tests Passed:</strong> $passedTests / $totalTests ($percentage%)</p>\n";

if ($percentage >= 80) {
    echo "<p class='pass' style='font-weight:bold;'>✓ Modern Authentication System is ready for use!</p>\n";
} elseif ($percentage >= 60) {
    echo "<p class='info' style='font-weight:bold;'>⚠ System is mostly working but needs some attention</p>\n";
} else {
    echo "<p class='fail' style='font-weight:bold;'>✗ System needs configuration before use</p>\n";
}

// Configuration Status
echo "<h2>Configuration Status</h2>\n";
global $sugar_config;

echo "<pre>\n";
echo "JWT Secret Key: " . (isset($sugar_config['jwt_secret_key']) ? 'Configured' : 'Not configured') . "\n";
echo "OAuth Providers: " . (isset($sugar_config['oauth_providers']) ? 'Configured' : 'Not configured') . "\n";
echo "Password Policy: " . (isset($sugar_config['password_policy']) ? 'Configured' : 'Using defaults') . "\n";
echo "2FA Config: " . (isset($sugar_config['2fa_config']) ? 'Configured' : 'Using defaults') . "\n";
echo "Security Headers: " . (isset($sugar_config['security_headers']) ? 'Configured' : 'Using defaults') . "\n";
echo "</pre>\n";

echo "<h2>Next Steps</h2>\n";
echo "<ol>\n";
echo "<li>Review the configuration example in <code>include/Authentication/config_example.php</code></li>\n";
echo "<li>Copy desired settings to <code>config_override.php</code></li>\n";
echo "<li>Configure OAuth providers (Google, Microsoft, GitHub) if needed</li>\n";
echo "<li>Enable the modern login interface</li>\n";
echo "<li>Test the authentication system with real users</li>\n";
echo "</ol>\n";

echo "<p style='margin-top:30px;color:#666;'>Test completed at: " . date('Y-m-d H:i:s') . "</p>\n";
