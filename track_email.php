<?php
/**
 * Email Tracking Pixel
 * Tracks when emails are opened
 */

// Get tracking ID
$tracking_id = $_GET['id'] ?? null;

if ($tracking_id) {
    try {
        define('sugarEntry', true);
        require_once('config.php');
        require_once('include/database/DBManagerFactory.php');
        
        $db = DBManagerFactory::getInstance();
        
        // Update tracking record
        $updateQuery = "UPDATE mfg_email_tracking 
                       SET opened_at = NOW(), status = 'opened' 
                       WHERE tracking_id = ? AND opened_at IS NULL";
        
        $db->pquery($updateQuery, [$tracking_id]);
        
    } catch (Exception $e) {
        // Silently fail - don't break the tracking pixel
        error_log('Email tracking update failed: ' . $e->getMessage());
    }
}

// Return 1x1 transparent pixel
header('Content-Type: image/gif');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
?>
