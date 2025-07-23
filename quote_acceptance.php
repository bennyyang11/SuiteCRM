<?php
/**
 * Quote Acceptance Page
 * Allows clients to accept quotes online
 */

$tracking_id = $_GET['id'] ?? null;
$quote_accepted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tracking_id) {
    // Process quote acceptance
    try {
        define('sugarEntry', true);
        require_once('config.php');
        require_once('include/database/DBManagerFactory.php');
        
        $db = DBManagerFactory::getInstance();
        
        // Update email tracking
        $updateQuery = "UPDATE mfg_email_tracking 
                       SET clicked_at = NOW(), status = 'clicked' 
                       WHERE tracking_id = ?";
        $db->pquery($updateQuery, [$tracking_id]);
        
        // Get quote information
        $quoteQuery = "SELECT q.* FROM mfg_quotes q 
                      JOIN mfg_email_tracking et ON q.quote_number = et.quote_number 
                      WHERE et.tracking_id = ?";
        $result = $db->pquery($quoteQuery, [$tracking_id]);
        
        if ($db->num_rows($result) > 0) {
            $quote = $db->fetchByAssoc($result);
            
            // Update quote status to approved
            $approveQuery = "UPDATE mfg_quotes SET status = 'approved', date_modified = NOW() WHERE id = ?";
            $db->pquery($approveQuery, [$quote['id']]);
            
            $quote_accepted = true;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Acceptance - Manufacturing Distribution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; }
        .btn { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn:hover { background: #218838; }
        .quote-summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 Manufacturing Distribution Co.</h1>
            <h2>Quote Acceptance</h2>
        </div>
        
        <div class="content">
            <?php if ($quote_accepted): ?>
                <div class="success">
                    <h3>✅ Quote Accepted Successfully!</h3>
                    <p>Thank you for accepting our quote. We will process your order and contact you within 24 hours with the next steps.</p>
                    <p><strong>Order Reference:</strong> ORD-<?= date('Ymd') ?>-<?= rand(1000, 9999) ?></p>
                </div>
            <?php elseif ($tracking_id): ?>
                <h3>Accept Quote</h3>
                <p>Please review the quote details and confirm your acceptance:</p>
                
                <div class="quote-summary">
                    <h4>Quote Summary</h4>
                    <p>Your quote details will be loaded here based on the tracking ID.</p>
                    <p><strong>Tracking ID:</strong> <?= htmlspecialchars($tracking_id) ?></p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Company Name</label>
                        <input type="text" name="company_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Name</label>
                        <input type="text" name="contact_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Additional Notes (Optional)</label>
                        <textarea name="notes" rows="4" placeholder="Any special requirements or questions..."></textarea>
                    </div>
                    
                    <div style="text-align: center;">
                        <button type="submit" class="btn">✅ Accept Quote & Place Order</button>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <h3>❌ Invalid Tracking Link</h3>
                    <p>This quote acceptance link is not valid or has expired.</p>
                    <p>Please contact us directly at (555) 123-4567 for assistance.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
