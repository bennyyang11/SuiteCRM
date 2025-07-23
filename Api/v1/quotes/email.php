<?php
/**
 * Quote Email API
 * Handles sending quotes via email with tracking
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get email data
$input = file_get_contents('php://input');
$emailData = json_decode($input, true);

if (!$emailData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email data']);
    exit;
}

// Validate required fields
$required = ['to_email', 'quote_data'];
foreach ($required as $field) {
    if (empty($emailData[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Generate PDF for attachment
    $pdfContent = generateQuotePDF($emailData['quote_data']);
    
    // Send email
    $result = sendQuoteEmail($emailData, $pdfContent);
    
    if ($result) {
        // Log email tracking
        logEmailTracking($emailData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Quote sent successfully',
            'tracking_id' => $result['tracking_id']
        ]);
    } else {
        throw new Exception('Failed to send email');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Generate PDF content for email attachment
 */
function generateQuotePDF($quoteData) {
    // Use the same PDF generation logic from generate_pdf.php
    $url = 'http://localhost:3000/Api/v1/quotes/generate_pdf.php';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($quoteData)
        ]
    ]);
    
    $pdfContent = file_get_contents($url, false, $context);
    
    if ($pdfContent === false) {
        throw new Exception('Failed to generate PDF for email');
    }
    
    return $pdfContent;
}

/**
 * Send quote email
 */
function sendQuoteEmail($emailData, $pdfContent) {
    $quote = $emailData['quote_data'];
    $toEmail = $emailData['to_email'];
    $tracking_id = generateTrackingId();
    
    // Email configuration
    $from = 'quotes@mfgdist.com';
    $fromName = 'Manufacturing Distribution Co.';
    $subject = "Quote #{$quote['quote_number']} - {$quote['client_name']}";
    
    // Create email body
    $emailBody = generateEmailBody($quote, $tracking_id);
    
    // In production, use a proper email library like PHPMailer or SwiftMailer
    // For demo purposes, we'll simulate the email sending
    
    if (function_exists('mail') && !empty(ini_get('sendmail_path'))) {
        // Try to send real email if mail is configured
        $boundary = md5(time());
        
        $headers = [
            "From: {$fromName} <{$from}>",
            "Reply-To: {$from}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\""
        ];
        
        $message = createMultipartMessage($emailBody, $pdfContent, $quote['quote_number'], $boundary);
        
        $sent = mail($toEmail, $subject, $message, implode("\r\n", $headers));
        
        if ($sent) {
            return ['tracking_id' => $tracking_id, 'method' => 'mail'];
        }
    }
    
    // Fallback: Log email instead of sending (demo mode)
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $toEmail,
        'subject' => $subject,
        'tracking_id' => $tracking_id,
        'quote_number' => $quote['quote_number'],
        'status' => 'sent_demo'
    ];
    
    file_put_contents('email_log.json', json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    
    return ['tracking_id' => $tracking_id, 'method' => 'demo'];
}

/**
 * Generate email body HTML
 */
function generateEmailBody($quote, $tracking_id) {
    $subtotal = 0;
    foreach ($quote['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemDiscount = $itemTotal * ($item['discount'] ?? 0) / 100;
        $subtotal += $itemTotal - $itemDiscount;
    }
    
    $tax = $subtotal * 0.085;
    $shipping = $subtotal > 500 ? 0 : 75;
    $total = $subtotal + $tax + $shipping;
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .quote-summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .total { font-size: 1.2em; font-weight: bold; color: #27ae60; }
            .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 0.9em; color: #666; }
            .btn { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>🏭 Manufacturing Distribution Co.</h1>
            <h2>Quote #' . htmlspecialchars($quote['quote_number']) . '</h2>
        </div>
        
        <div class="content">
            <p>Dear ' . htmlspecialchars($quote['client_name']) . ',</p>
            
            <p>Thank you for your interest in our products. Please find attached your detailed quote for the items you requested.</p>
            
            <div class="quote-summary">
                <h3>Quote Summary</h3>
                <p><strong>Quote Number:</strong> ' . htmlspecialchars($quote['quote_number']) . '</p>
                <p><strong>Valid Until:</strong> ' . date('M j, Y', strtotime($quote['valid_until'])) . '</p>
                <p><strong>Items:</strong> ' . count($quote['items']) . ' products</p>
                <p class="total"><strong>Total Amount: $' . number_format($total, 2) . '</strong></p>
            </div>
            
            <p>This quote includes:</p>
            <ul>';
    
    foreach ($quote['items'] as $item) {
        $html .= '<li>' . htmlspecialchars($item['name']) . ' (Qty: ' . $item['quantity'] . ')</li>';
    }
    
    $html .= '
            </ul>
            
            <p>To accept this quote and place your order, simply reply to this email or contact us at:</p>
            <ul>
                <li><strong>Phone:</strong> (555) 123-4567</li>
                <li><strong>Email:</strong> orders@mfgdist.com</li>
                <li><strong>Online:</strong> <a href="http://localhost:3000/quote_builder.php">Quote Builder</a></li>
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="http://localhost:3000/quote_acceptance.php?id=' . urlencode($tracking_id) . '" class="btn">
                    ✅ Accept Quote Online
                </a>
                <a href="http://localhost:3000/quote_questions.php?id=' . urlencode($tracking_id) . '" class="btn" style="background: #007bff;">
                    ❓ Ask Questions
                </a>
            </div>
            
            <p>We appreciate your business and look forward to working with you!</p>
            
            <p>Best regards,<br>
            <strong>Sales Team</strong><br>
            Manufacturing Distribution Co.</p>
        </div>
        
        <div class="footer">
            <p>This email was sent regarding Quote #' . htmlspecialchars($quote['quote_number']) . '</p>
            <p>Manufacturing Distribution Co. | 1234 Industrial Blvd | Manufacturing City, ST 12345</p>
            <img src="http://localhost:3000/track_email.php?id=' . urlencode($tracking_id) . '" width="1" height="1" alt="" />
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Create multipart email message with PDF attachment
 */
function createMultipartMessage($htmlBody, $pdfContent, $quoteNumber, $boundary) {
    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $htmlBody . "\r\n\r\n";
    
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: application/pdf; name=\"Quote-{$quoteNumber}.pdf\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"Quote-{$quoteNumber}.pdf\"\r\n\r\n";
    $message .= chunk_split(base64_encode($pdfContent)) . "\r\n";
    
    $message .= "--{$boundary}--\r\n";
    
    return $message;
}

/**
 * Log email for tracking
 */
function logEmailTracking($emailData) {
    try {
        define('sugarEntry', true);
        require_once('../../../config.php');
        require_once('../../../include/database/DBManagerFactory.php');
        
        $db = DBManagerFactory::getInstance();
        
        // Create email tracking table if it doesn't exist
        $trackingTable = "CREATE TABLE IF NOT EXISTS mfg_email_tracking (
            id VARCHAR(36) PRIMARY KEY,
            quote_number VARCHAR(50),
            recipient_email VARCHAR(255),
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            opened_at DATETIME NULL,
            clicked_at DATETIME NULL,
            tracking_id VARCHAR(100) UNIQUE,
            status ENUM('sent', 'delivered', 'opened', 'clicked', 'bounced') DEFAULT 'sent',
            
            INDEX idx_tracking_id (tracking_id),
            INDEX idx_quote_number (quote_number),
            INDEX idx_recipient (recipient_email)
        )";
        
        $db->query($trackingTable);
        
        // Insert tracking record
        $insertQuery = "INSERT INTO mfg_email_tracking (
            id, quote_number, recipient_email, tracking_id, status
        ) VALUES (?, ?, ?, ?, 'sent')";
        
        $db->pquery($insertQuery, [
            generateUUID(),
            $emailData['quote_data']['quote_number'],
            $emailData['to_email'],
            $emailData['tracking_id'] ?? generateTrackingId()
        ]);
        
    } catch (Exception $e) {
        // Log error but don't fail the email send
        error_log('Email tracking failed: ' . $e->getMessage());
    }
}

/**
 * Generate unique tracking ID
 */
function generateTrackingId() {
    return 'track_' . bin2hex(random_bytes(16));
}

/**
 * Generate UUID
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>
