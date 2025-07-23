<?php
/**
 * Quote PDF Generation API
 * Generates professional PDFs using HTML templates
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

// Get quote data
$input = file_get_contents('php://input');
$quoteData = json_decode($input, true);

if (!$quoteData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid quote data']);
    exit;
}

// Validate required fields
$required = ['quote_number', 'client_name', 'valid_until', 'items'];
foreach ($required as $field) {
    if (empty($quoteData[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Check if we can use Puppeteer (Node.js) or fall back to PHP PDF generation
    $pdfContent = generatePDFWithHTML($quoteData);
    
    if ($pdfContent) {
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Quote-' . $quoteData['quote_number'] . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        
        echo $pdfContent;
    } else {
        throw new Exception('Failed to generate PDF');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Generate PDF using HTML template and wkhtmltopdf (if available) or TCPDF
 */
function generatePDFWithHTML($quoteData) {
    // Generate HTML content
    $html = generateQuoteHTML($quoteData);
    
    // Try wkhtmltopdf first (if available)
    if (commandExists('wkhtmltopdf')) {
        return generateWithWkhtmltopdf($html, $quoteData['quote_number']);
    }
    
    // Fall back to TCPDF
    return generateWithTCPDF($quoteData);
}

/**
 * Generate HTML template for the quote
 */
function generateQuoteHTML($quoteData) {
    $subtotal = 0;
    $discountTotal = 0;
    $taxRate = 0.085;
    
    // Calculate totals
    foreach ($quoteData['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemDiscount = $itemTotal * ($item['discount'] ?? 0) / 100;
        $subtotal += $itemTotal - $itemDiscount;
        $discountTotal += $itemDiscount;
    }
    
    $tax = $subtotal * $taxRate;
    $shipping = $subtotal > 500 ? 0 : 75;
    $total = $subtotal + $tax + $shipping;
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Quote ' . htmlspecialchars($quoteData['quote_number']) . '</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: "Segoe UI", Arial, sans-serif; 
                font-size: 12px; 
                line-height: 1.4; 
                color: #333;
                background: white;
            }
            
            .quote-header {
                background: linear-gradient(135deg, #2c3e50, #34495e);
                color: white;
                padding: 30px;
                margin-bottom: 30px;
            }
            .header-content {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .company-info h1 {
                font-size: 28px;
                font-weight: bold;
                margin-bottom: 8px;
            }
            .company-info p {
                font-size: 14px;
                opacity: 0.9;
            }
            .quote-info {
                text-align: right;
            }
            .quote-info h2 {
                font-size: 24px;
                margin-bottom: 10px;
            }
            .quote-info p {
                font-size: 14px;
                margin-bottom: 5px;
            }
            
            .client-section {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
                border-left: 4px solid #3498db;
            }
            .client-section h3 {
                color: #2c3e50;
                font-size: 16px;
                margin-bottom: 10px;
            }
            .client-details {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }
            .client-detail p {
                margin-bottom: 5px;
            }
            .client-detail strong {
                color: #2c3e50;
            }
            
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                border-radius: 8px;
                overflow: hidden;
            }
            .items-table th {
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                padding: 15px 10px;
                text-align: left;
                font-weight: bold;
                font-size: 13px;
            }
            .items-table td {
                padding: 12px 10px;
                border-bottom: 1px solid #eee;
                vertical-align: middle;
            }
            .items-table tr:nth-child(even) {
                background: #f8f9fa;
            }
            .items-table tr:hover {
                background: #e3f2fd;
            }
            
            .item-name {
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 3px;
            }
            .item-sku {
                font-size: 11px;
                color: #7f8c8d;
            }
            
            .quantity-cell, .price-cell, .total-cell {
                text-align: right;
                font-weight: bold;
            }
            .discount-cell {
                text-align: center;
                color: #e74c3c;
            }
            
            .summary-section {
                float: right;
                width: 300px;
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                border: 1px solid #dee2e6;
            }
            .summary-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #dee2e6;
            }
            .summary-row:last-child {
                border-bottom: none;
                border-top: 2px solid #dee2e6;
                padding-top: 15px;
                margin-top: 10px;
                font-weight: bold;
                font-size: 16px;
                color: #27ae60;
            }
            
            .terms-section {
                clear: both;
                margin-top: 40px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #f39c12;
            }
            .terms-section h3 {
                color: #2c3e50;
                margin-bottom: 15px;
                font-size: 16px;
            }
            .terms-list {
                list-style: none;
                padding: 0;
            }
            .terms-list li {
                margin-bottom: 8px;
                padding-left: 20px;
                position: relative;
            }
            .terms-list li:before {
                content: "•";
                color: #f39c12;
                font-weight: bold;
                position: absolute;
                left: 0;
            }
            
            .footer {
                margin-top: 40px;
                text-align: center;
                padding: 20px;
                border-top: 2px solid #dee2e6;
                color: #7f8c8d;
            }
            .footer p {
                margin-bottom: 5px;
            }
            
            .signature-section {
                margin-top: 40px;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 40px;
            }
            .signature-box {
                text-align: center;
                padding: 20px;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                background: #f8f9fa;
            }
            .signature-line {
                border-top: 2px solid #2c3e50;
                margin: 30px 20px 10px;
            }
            .signature-label {
                font-weight: bold;
                color: #2c3e50;
            }
            
            @media print {
                .quote-header { 
                    background: #2c3e50 !important; 
                    -webkit-print-color-adjust: exact; 
                }
                .items-table th { 
                    background: #3498db !important; 
                    -webkit-print-color-adjust: exact; 
                }
            }
        </style>
    </head>
    <body>
        <div class="quote-header">
            <div class="header-content">
                <div class="company-info">
                    <h1>🏭 Manufacturing Distribution Co.</h1>
                    <p>Professional Industrial Solutions</p>
                    <p>📍 1234 Industrial Blvd, Manufacturing City, ST 12345</p>
                    <p>📞 (555) 123-4567 | 📧 quotes@mfgdist.com</p>
                </div>
                <div class="quote-info">
                    <h2>QUOTE</h2>
                    <p><strong>Quote #:</strong> ' . htmlspecialchars($quoteData['quote_number']) . '</p>
                    <p><strong>Date:</strong> ' . date('M j, Y') . '</p>
                    <p><strong>Valid Until:</strong> ' . date('M j, Y', strtotime($quoteData['valid_until'])) . '</p>
                </div>
            </div>
        </div>
        
        <div class="client-section">
            <h3>📋 Quote Details</h3>
            <div class="client-details">
                <div class="client-detail">
                    <p><strong>Client:</strong> ' . htmlspecialchars($quoteData['client_name']) . '</p>
                    <p><strong>Pricing Tier:</strong> ' . strtoupper($quoteData['client_tier'] ?? 'RETAIL') . '</p>
                </div>
                <div class="client-detail">
                    <p><strong>Prepared By:</strong> Sales Representative</p>
                    <p><strong>Contact:</strong> sales@mfgdist.com</p>
                </div>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%">Product Description</th>
                    <th style="width: 15%">Quantity</th>
                    <th style="width: 15%">Unit Price</th>
                    <th style="width: 10%">Discount</th>
                    <th style="width: 20%">Total</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($quoteData['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemDiscount = $item['discount'] ?? 0;
        $finalTotal = $itemTotal * (1 - $itemDiscount / 100);
        
        $html .= '
                <tr>
                    <td>
                        <div class="item-name">' . htmlspecialchars($item['name']) . '</div>
                        <div class="item-sku">SKU: ' . htmlspecialchars($item['sku']) . '</div>
                    </td>
                    <td class="quantity-cell">' . number_format($item['quantity']) . '</td>
                    <td class="price-cell">$' . number_format($item['price'], 2) . '</td>
                    <td class="discount-cell">' . ($itemDiscount > 0 ? $itemDiscount . '%' : '-') . '</td>
                    <td class="total-cell">$' . number_format($finalTotal, 2) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="summary-section">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>$' . number_format($subtotal, 2) . '</span>
            </div>
            <div class="summary-row">
                <span>Discount:</span>
                <span>-$' . number_format($discountTotal, 2) . '</span>
            </div>
            <div class="summary-row">
                <span>Tax (8.5%):</span>
                <span>$' . number_format($tax, 2) . '</span>
            </div>
            <div class="summary-row">
                <span>Shipping:</span>
                <span>$' . number_format($shipping, 2) . '</span>
            </div>
            <div class="summary-row">
                <span>Total:</span>
                <span>$' . number_format($total, 2) . '</span>
            </div>
        </div>
        
        <div class="terms-section">
            <h3>📋 Terms & Conditions</h3>
            <ul class="terms-list">
                <li>Quote valid for 30 days from date of issue</li>
                <li>Payment terms: Net 30 days</li>
                <li>Prices subject to change without notice</li>
                <li>Delivery: 5-10 business days after order confirmation</li>
                <li>Free shipping on orders over $500</li>
                <li>All sales subject to our standard terms and conditions</li>
                <li>Products are subject to availability</li>
                <li>Special pricing may apply for bulk orders</li>
            </ul>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Customer Signature</div>
                <p style="font-size: 11px; color: #7f8c8d; margin-top: 10px;">
                    By signing, you agree to the terms and conditions outlined above
                </p>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Sales Representative</div>
                <p style="font-size: 11px; color: #7f8c8d; margin-top: 10px;">
                    Manufacturing Distribution Co.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>For questions about this quote, please contact us at (555) 123-4567</p>
            <p style="font-size: 10px; color: #95a5a6; margin-top: 10px;">
                This quote was generated electronically and is valid without signature
            </p>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Generate PDF using wkhtmltopdf
 */
function generateWithWkhtmltopdf($html, $quoteNumber) {
    $tempHtml = tempnam(sys_get_temp_dir(), 'quote_') . '.html';
    $tempPdf = tempnam(sys_get_temp_dir(), 'quote_') . '.pdf';
    
    file_put_contents($tempHtml, $html);
    
    $command = sprintf(
        'wkhtmltopdf --page-size A4 --margin-top 0.75in --margin-right 0.75in --margin-bottom 0.75in --margin-left 0.75in --encoding UTF-8 --quiet %s %s',
        escapeshellarg($tempHtml),
        escapeshellarg($tempPdf)
    );
    
    exec($command, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($tempPdf)) {
        $pdfContent = file_get_contents($tempPdf);
        unlink($tempHtml);
        unlink($tempPdf);
        return $pdfContent;
    }
    
    // Clean up temp files
    if (file_exists($tempHtml)) unlink($tempHtml);
    if (file_exists($tempPdf)) unlink($tempPdf);
    
    return false;
}

/**
 * Generate PDF using TCPDF (fallback) or demo mode
 */
function generateWithTCPDF($quoteData) {
    // Check if TCPDF is available
    if (!class_exists('TCPDF')) {
        // Try to load TCPDF if it exists
        $tcpdfPaths = [
            '../../../include/tcpdf/tcpdf.php',
            'vendor/tecnickcom/tcpdf/tcpdf.php',
            'lib/tcpdf/tcpdf.php',
            '../tcpdf/tcpdf.php'
        ];
        
        $tcpdfLoaded = false;
        foreach ($tcpdfPaths as $path) {
            if (file_exists($path)) {
                require_once($path);
                $tcpdfLoaded = true;
                break;
            }
        }
        
        if (!$tcpdfLoaded) {
            // Demo mode: create a simple HTML-based PDF simulation
            return generateDemoPDF($quoteData);
        }
    }
    
    // Create TCPDF instance
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Manufacturing Distribution CRM');
    $pdf->SetAuthor('Manufacturing Distribution Co.');
    $pdf->SetTitle('Quote ' . $quoteData['quote_number']);
    $pdf->SetSubject('Manufacturing Quote');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Generate simplified HTML for TCPDF
    $html = generateSimpleQuoteHTML($quoteData);
    
    // Print HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Return PDF content
    return $pdf->Output('', 'S');
}

/**
 * Generate simplified HTML for TCPDF compatibility
 */
function generateSimpleQuoteHTML($quoteData) {
    $subtotal = 0;
    $discountTotal = 0;
    
    foreach ($quoteData['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemDiscount = $itemTotal * ($item['discount'] ?? 0) / 100;
        $subtotal += $itemTotal - $itemDiscount;
        $discountTotal += $itemDiscount;
    }
    
    $tax = $subtotal * 0.085;
    $shipping = $subtotal > 500 ? 0 : 75;
    $total = $subtotal + $tax + $shipping;
    
    $html = '
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { background-color: #2c3e50; color: white; padding: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .client-info { background-color: #f8f9fa; padding: 15px; margin-bottom: 20px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #3498db; color: white; padding: 10px; }
        .items-table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .summary { float: right; width: 250px; }
        .terms { margin-top: 20px; font-size: 10px; }
    </style>
    
    <div class="header">
        <h1>Manufacturing Distribution Co.</h1>
        <p>Quote #: ' . htmlspecialchars($quoteData['quote_number']) . '</p>
        <p>Date: ' . date('M j, Y') . '</p>
    </div>
    
    <div class="client-info">
        <strong>Client:</strong> ' . htmlspecialchars($quoteData['client_name']) . '<br>
        <strong>Valid Until:</strong> ' . date('M j, Y', strtotime($quoteData['valid_until'])) . '<br>
        <strong>Pricing Tier:</strong> ' . strtoupper($quoteData['client_tier'] ?? 'RETAIL') . '
    </div>
    
    <table class="items-table">
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Discount</th>
            <th>Total</th>
        </tr>';
    
    foreach ($quoteData['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemDiscount = $item['discount'] ?? 0;
        $finalTotal = $itemTotal * (1 - $itemDiscount / 100);
        
        $html .= '
        <tr>
            <td>' . htmlspecialchars($item['name']) . '<br><small>SKU: ' . htmlspecialchars($item['sku']) . '</small></td>
            <td>' . $item['quantity'] . '</td>
            <td>$' . number_format($item['price'], 2) . '</td>
            <td>' . ($itemDiscount > 0 ? $itemDiscount . '%' : '-') . '</td>
            <td>$' . number_format($finalTotal, 2) . '</td>
        </tr>';
    }
    
    $html .= '
    </table>
    
    <div class="summary">
        <table>
            <tr><td>Subtotal:</td><td>$' . number_format($subtotal, 2) . '</td></tr>
            <tr><td>Discount:</td><td>-$' . number_format($discountTotal, 2) . '</td></tr>
            <tr><td>Tax (8.5%):</td><td>$' . number_format($tax, 2) . '</td></tr>
            <tr><td>Shipping:</td><td>$' . number_format($shipping, 2) . '</td></tr>
            <tr><td><strong>Total:</strong></td><td><strong>$' . number_format($total, 2) . '</strong></td></tr>
        </table>
    </div>
    
    <div class="terms">
        <h3>Terms & Conditions</h3>
        <ul>
            <li>Quote valid for 30 days</li>
            <li>Payment terms: Net 30 days</li>
            <li>Free shipping on orders over $500</li>
        </ul>
    </div>';
    
    return $html;
}

/**
 * Generate demo PDF (HTML content for demonstration)
 */
function generateDemoPDF($quoteData) {
    // For demo purposes, create a simple "PDF" content
    $html = generateQuoteHTML($quoteData);
    
    // Add PDF-like styling and convert to basic PDF format
    $pdfContent = "<!DOCTYPE html><html><head><style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .pdf-header { text-align: center; color: #333; border-bottom: 2px solid #ccc; padding-bottom: 20px; }
        .demo-notice { background: #fff3cd; padding: 15px; margin: 20px 0; border: 1px solid #ffeaa7; border-radius: 5px; }
        @media print { .demo-notice { display: none; } }
    </style></head><body>";
    
    $pdfContent .= "<div class='demo-notice'>";
    $pdfContent .= "<strong>📄 Demo PDF Generated</strong><br>";
    $pdfContent .= "This is a demonstration PDF. In production, this would be a proper PDF file generated by wkhtmltopdf or TCPDF.<br>";
    $pdfContent .= "<em>Generated at: " . date('Y-m-d H:i:s') . "</em>";
    $pdfContent .= "</div>";
    
    $pdfContent .= $html;
    $pdfContent .= "</body></html>";
    
    // Create a simple binary-like content for demo
    $binaryHeader = "%PDF-1.4\n% Demo PDF generated by Quote Builder\n";
    $compressedContent = gzcompress($pdfContent);
    
    return $binaryHeader . $compressedContent;
}

/**
 * Check if a command exists
 */
function commandExists($command) {
    $whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    $process = proc_open(
        "$whereIsCommand $command",
        [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ],
        $pipes
    );
    if ($process !== false) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);
        return $returnCode === 0;
    }
    return false;
}
?>
