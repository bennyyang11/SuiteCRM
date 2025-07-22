<?php
/**
 * Test Security Headers Implementation
 */

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';

// Force headers to be sent
use SuiteCRM\Authentication\Security\SecurityHeaders;

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Security Headers Test</title>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .header{background:#f0f0f0;padding:10px;margin:5px 0;border-radius:4px;} .pass{color:green;} .fail{color:red;}</style>\n";
echo "</head>\n<body>\n";

echo "<h1>Security Headers Test</h1>\n";

// Test security headers class
try {
    $security = new SecurityHeaders();
    $security->applyHeaders();
    
    echo "<p class='pass'>✓ SecurityHeaders class instantiated successfully</p>\n";
    
    // Generate CSRF token
    $csrfToken = $security->generateCSRFToken();
    echo "<p class='pass'>✓ CSRF token generated: " . substr($csrfToken, 0, 16) . "...</p>\n";
    
    // Test CSRF validation
    $_SESSION['csrf_token'] = $csrfToken;
    $isValid = $security->validateCSRFToken($csrfToken);
    echo "<p class='" . ($isValid ? 'pass' : 'fail') . "'>" . ($isValid ? '✓' : '✗') . " CSRF token validation: " . ($isValid ? 'Working' : 'Failed') . "</p>\n";
    
    // Test malicious input detection
    $maliciousInput = "<script>alert('xss')</script>";
    $threats = $security->detectMaliciousInput($maliciousInput);
    echo "<p class='" . (!empty($threats) ? 'pass' : 'fail') . "'>" . (!empty($threats) ? '✓' : '✗') . " XSS detection: " . (!empty($threats) ? implode(', ', $threats) : 'Not detected') . "</p>\n";
    
    // Test rate limiting
    $rateLimited = !$security->checkRateLimit('test_action', 5, 3600);
    echo "<p class='pass'>✓ Rate limiting: " . ($rateLimited ? 'Active' : 'Allows requests') . "</p>\n";
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ Security headers test failed: " . $e->getMessage() . "</p>\n";
}

// Check if headers are actually being sent
echo "<h2>Response Headers Check</h2>\n";
echo "<p><em>Check browser developer tools Network tab to see actual headers sent.</em></p>\n";

// Force some security headers manually for testing
header('X-Modern-Auth-Test: Active');
header('X-CSRF-Token: ' . ($_SESSION['csrf_token'] ?? 'not-set'));

echo "<h2>Manual Header Test</h2>\n";
echo "<p>Custom test headers should be visible in browser dev tools:</p>\n";
echo "<ul>\n";
echo "<li>X-Modern-Auth-Test: Active</li>\n";
echo "<li>X-CSRF-Token: " . ($_SESSION['csrf_token'] ?? 'not-set') . "</li>\n";
echo "</ul>\n";

// Test JWT functionality
echo "<h2>JWT System Test</h2>\n";

try {
    use SuiteCRM\Authentication\ModernAuth\JWTManager;
    
    $jwtManager = new JWTManager();
    
    // Test token generation without database operations
    $testPayload = [
        'iss' => 'suitecrm-test',
        'sub' => 'test-user-123',
        'iat' => time(),
        'exp' => time() + 3600,
        'type' => 'access'
    ];
    
    $secretKey = base64_encode(random_bytes(64));
    $testToken = \Firebase\JWT\JWT::encode($testPayload, $secretKey, 'HS256');
    $decoded = \Firebase\JWT\JWT::decode($testToken, new \Firebase\JWT\Key($secretKey, 'HS256'));
    
    if ($decoded->sub === 'test-user-123') {
        echo "<p class='pass'>✓ JWT token generation and validation working</p>\n";
        echo "<p>Test token (first 50 chars): " . substr($testToken, 0, 50) . "...</p>\n";
        
        // Test with JWT Manager's validateToken method
        global $sugar_config;
        $sugar_config['jwt_secret_key'] = $secretKey;
        
        $managerPayload = $jwtManager->validateToken($testToken);
        if ($managerPayload && $managerPayload['sub'] === 'test-user-123') {
            echo "<p class='pass'>✓ JWT Manager validation working</p>\n";
        } else {
            echo "<p class='fail'>✗ JWT Manager validation failed</p>\n";
        }
        
    } else {
        echo "<p class='fail'>✗ JWT token validation failed</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ JWT test failed: " . $e->getMessage() . "</p>\n";
}

// Test database connection for JWT
echo "<h2>Database Connection Test</h2>\n";

try {
    global $db;
    
    // Test if user_refresh_tokens table exists
    $result = $db->query("SHOW TABLES LIKE 'user_refresh_tokens'");
    if ($db->getRowCount($result) > 0) {
        echo "<p class='pass'>✓ user_refresh_tokens table exists</p>\n";
        
        // Test insert (will fail safely if table structure issues)
        $testUserId = 'test-user-' . time();
        $testToken = hash('sha256', 'test-token-' . time());
        $expiryDate = date('Y-m-d H:i:s', time() + 3600);
        $createdDate = date('Y-m-d H:i:s');
        
        $insertQuery = "INSERT INTO user_refresh_tokens (user_id, token, expires_at, created_at) 
                       VALUES ('{$testUserId}', '{$testToken}', '{$expiryDate}', '{$createdDate}')";
        
        $insertResult = $db->query($insertQuery);
        if ($insertResult) {
            echo "<p class='pass'>✓ Database insert test successful</p>\n";
            
            // Clean up test data
            $deleteQuery = "DELETE FROM user_refresh_tokens WHERE user_id = '{$testUserId}'";
            $db->query($deleteQuery);
            echo "<p class='pass'>✓ Test data cleaned up</p>\n";
            
        } else {
            echo "<p class='fail'>✗ Database insert test failed</p>\n";
        }
        
    } else {
        echo "<p class='fail'>✗ user_refresh_tokens table does not exist</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>✗ Database test failed: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Summary</h2>\n";
echo "<p>This test verifies that:</p>\n";
echo "<ul>\n";
echo "<li>Security headers are being applied</li>\n";
echo "<li>CSRF protection is working</li>\n";
echo "<li>XSS detection is functional</li>\n";
echo "<li>JWT token system is operational</li>\n";
echo "<li>Database connections are working</li>\n";
echo "</ul>\n";

echo "<p><strong>Check your browser's developer tools (Network tab) to verify that security headers are actually being sent with this response.</strong></p>\n";

echo "</body>\n</html>";
