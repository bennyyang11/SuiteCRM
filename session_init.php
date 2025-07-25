<?php
/**
 * Session Initialization Helper
 * Ensures proper session configuration
 */

// Create session directory if it doesn't exist
$session_dir = '/tmp/php_sessions';
if (!is_dir($session_dir)) {
    mkdir($session_dir, 0755, true);
}

// Set session configuration before starting session
ini_set('session.save_path', $session_dir);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 1440);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting to reduce noise
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
?>
