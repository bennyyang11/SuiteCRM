<?php
/**
 * SuiteCRM OAuth Login Entry Point
 */

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';

use SuiteCRM\Authentication\ModernAuth\AuthenticationController;
use SuiteCRM\Authentication\Security\SecurityHeaders;

// Apply security headers
$security = new SecurityHeaders();
$security->applyHeaders();

// Validate CSRF if not GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !$security->validateRequest()) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token validation failed']);
    exit();
}

try {
    $provider = $_GET['provider'] ?? '';
    
    if (empty($provider)) {
        throw new Exception('OAuth provider not specified');
    }
    
    $authController = new AuthenticationController();
    $authUrl = $authController->initiateOAuth($provider);
    
    // Redirect to OAuth provider
    header('Location: ' . $authUrl);
    exit();
    
} catch (Exception $e) {
    error_log('OAuth Login Error: ' . $e->getMessage());
    
    $errorMessage = urlencode($e->getMessage());
    header('Location: index.php?action=Login&module=Users&loginErrorMessage=' . $errorMessage);
    exit();
}
