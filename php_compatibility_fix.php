<?php
/**
 * PHP 8.4 Compatibility Fix for SuiteCRM
 * Include this at the top of any file that loads SuiteCRM to suppress warnings
 */

// Suppress PHP 8.4 deprecation warnings from vendor libraries
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set memory and time limits for SuiteCRM
ini_set('memory_limit', '512M');
ini_set('max_execution_time', '120');

// Disable problematic extensions that cause issues
if (extension_loaded('xdebug')) {
    ini_set('xdebug.max_nesting_level', '500');
}

// Override cache class to avoid Redis issues temporarily
if (!defined('SUGAR_CACHE_OVERRIDE')) {
    define('SUGAR_CACHE_OVERRIDE', 'SugarCacheFile');
}

// Register error handler to suppress vendor deprecation warnings
set_error_handler(function($severity, $message, $file, $line) {
    // Only suppress deprecation warnings from vendor directories
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        if (strpos($file, '/vendor/') !== false || 
            strpos($file, '/include/SugarCache/RedisCache.php') !== false) {
            return true; // Suppress the error
        }
    }
    
    // Let other errors pass through normally
    return false;
}, E_ALL);

// Compatibility function for older SuiteCRM code
if (!function_exists('sugar_cache_clear')) {
    function sugar_cache_clear($key = null) {
        if (class_exists('SugarCache')) {
            return SugarCache::instance()->flush();
        }
        return true;
    }
}

// Set up session handling to avoid warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

return true;
