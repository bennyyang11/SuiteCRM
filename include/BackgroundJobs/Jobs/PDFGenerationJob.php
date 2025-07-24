<?php
/**
 * PDF Generation Background Job
 * Generates PDFs for quotes, invoices, and reports
 */

require_once('include/BackgroundJobs/JobInterface.php');

class PDFGenerationJob implements JobInterface
{
    /**
     * Execute PDF generation
     */
    public function execute($payload)
    {
        $startTime = microtime(true);
        
        try {
            // Validate payload
            $this->validatePayload($payload);
            
            // Generate PDF based on type
            $result = $this->generatePDF($payload);
            
            // Store PDF file
            $filePath = $this->storePDF($result['content'], $payload);
            
            // Update database record
            $this->updateRecord($payload, $filePath);
            
            // Send notification if requested
            if (!empty($payload['notify_user'])) {
                $this->sendNotification($payload, $filePath);
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => strlen($result['content']),
                'pdf_type' => $payload['type'],
                'record_id' => $payload['record_id'],
                'execution_time_ms' => round($executionTime, 2)
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("PDF generation job failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate PDF generation payload
     */
    protected function validatePayload($payload)
    {
        $required = ['type', 'record_id'];
        
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        $validTypes = ['quote', 'invoice', 'report', 'product_catalog'];
        if (!in_array($payload['type'], $validTypes)) {
            throw new Exception("Invalid PDF type: {$payload['type']}");
        }
    }

    /**
     * Generate PDF content based on type
     */
    protected function generatePDF($payload)
    {
        switch ($payload['type']) {
            case 'quote':
                return $this->generateQuotePDF($payload['record_id'], $payload['template'] ?? 'default');
            
            case 'invoice':
                return $this->generateInvoicePDF($payload['record_id'], $payload['template'] ?? 'default');
            
            case 'report':
                return $this->generateReportPDF($payload['record_id'], $payload);
            
            case 'product_catalog':
                return $this->generateProductCatalogPDF($payload);
            
            default:
                throw new Exception("Unsupported PDF type: {$payload['type']}");
        }
    }

    /**
     * Generate quote PDF
     */
    protected function generateQuotePDF($quoteId, $template)
    {
        // Get quote data
        $quoteData = $this->getQuoteData($quoteId);
        
        // Load HTML template
        $html = $this->renderPDFTemplate('quote', $template, $quoteData);
        
        // Generate PDF using Puppeteer or similar
        return $this->htmlToPDF($html, [
            'format' => 'A4',
            'margin' => ['top' => '1in', 'right' => '0.5in', 'bottom' => '1in', 'left' => '0.5in'],
            'displayHeaderFooter' => true,
            'headerTemplate' => $this->getQuoteHeader($quoteData),
            'footerTemplate' => $this->getQuoteFooter($quoteData)
        ]);
    }

    /**
     * Generate invoice PDF
     */
    protected function generateInvoicePDF($invoiceId, $template)
    {
        // Get invoice data
        $invoiceData = $this->getInvoiceData($invoiceId);
        
        // Load HTML template
        $html = $this->renderPDFTemplate('invoice', $template, $invoiceData);
        
        return $this->htmlToPDF($html, [
            'format' => 'A4',
            'margin' => ['top' => '1in', 'right' => '0.5in', 'bottom' => '1in', 'left' => '0.5in']
        ]);
    }

    /**
     * Generate product catalog PDF
     */
    protected function generateProductCatalogPDF($payload)
    {
        // Get product data
        $products = $this->getProductCatalogData($payload);
        
        // Load HTML template
        $html = $this->renderPDFTemplate('product_catalog', $payload['template'] ?? 'default', [
            'products' => $products,
            'client' => $payload['client_data'] ?? null,
            'filters' => $payload['filters'] ?? []
        ]);
        
        return $this->htmlToPDF($html, [
            'format' => 'A4',
            'margin' => ['top' => '0.5in', 'right' => '0.5in', 'bottom' => '0.5in', 'left' => '0.5in']
        ]);
    }

    /**
     * Convert HTML to PDF using Puppeteer
     */
    protected function htmlToPDF($html, $options = [])
    {
        // Create temporary HTML file
        $tempHtml = tempnam(sys_get_temp_dir(), 'pdf_html_');
        file_put_contents($tempHtml, $html);
        
        // Create temporary PDF file
        $tempPdf = tempnam(sys_get_temp_dir(), 'pdf_output_');
        
        try {
            // Use Puppeteer to generate PDF
            $command = $this->buildPuppeteerCommand($tempHtml, $tempPdf, $options);
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception("PDF generation failed: " . implode("\n", $output));
            }
            
            if (!file_exists($tempPdf) || filesize($tempPdf) === 0) {
                throw new Exception("PDF file was not generated or is empty");
            }
            
            $pdfContent = file_get_contents($tempPdf);
            
            return [
                'content' => $pdfContent,
                'size' => strlen($pdfContent),
                'mime_type' => 'application/pdf'
            ];
            
        } finally {
            // Clean up temporary files
            if (file_exists($tempHtml)) {
                unlink($tempHtml);
            }
            if (file_exists($tempPdf)) {
                unlink($tempPdf);
            }
        }
    }

    /**
     * Build Puppeteer command
     */
    protected function buildPuppeteerCommand($htmlFile, $pdfFile, $options)
    {
        $puppeteerScript = __DIR__ . '/../../scripts/generate_pdf.js';
        
        if (!file_exists($puppeteerScript)) {
            // Create basic Puppeteer script if it doesn't exist
            $this->createPuppeteerScript($puppeteerScript);
        }
        
        $optionsJson = json_encode($options);
        
        return "node {$puppeteerScript} '{$htmlFile}' '{$pdfFile}' '{$optionsJson}'";
    }

    /**
     * Create Puppeteer script for PDF generation
     */
    protected function createPuppeteerScript($scriptPath)
    {
        $scriptDir = dirname($scriptPath);
        if (!is_dir($scriptDir)) {
            mkdir($scriptDir, 0755, true);
        }
        
        $script = <<<'JS'
const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
    const [,, htmlFile, pdfFile, optionsStr] = process.argv;
    const options = JSON.parse(optionsStr || '{}');
    
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    try {
        const page = await browser.newPage();
        const html = fs.readFileSync(htmlFile, 'utf8');
        
        await page.setContent(html, { waitUntil: 'networkidle0' });
        
        const pdfOptions = {
            path: pdfFile,
            format: options.format || 'A4',
            margin: options.margin || {},
            displayHeaderFooter: options.displayHeaderFooter || false,
            headerTemplate: options.headerTemplate || '',
            footerTemplate: options.footerTemplate || '',
            printBackground: true
        };
        
        await page.pdf(pdfOptions);
        
        console.log('PDF generated successfully');
    } catch (error) {
        console.error('PDF generation error:', error);
        process.exit(1);
    } finally {
        await browser.close();
    }
})();
JS;
        
        file_put_contents($scriptPath, $script);
    }

    /**
     * Get quote data
     */
    protected function getQuoteData($quoteId)
    {
        global $db;
        
        $sql = "
        SELECT 
            q.*,
            a.name as account_name,
            a.billing_address_street,
            a.billing_address_city,
            a.billing_address_state,
            a.billing_address_postalcode,
            c.first_name as contact_first_name,
            c.last_name as contact_last_name,
            c.email1 as contact_email
        FROM manufacturing_quotes q
        LEFT JOIN accounts a ON q.account_id = a.id
        LEFT JOIN contacts c ON q.contact_id = c.id
        WHERE q.id = ?
        ";
        
        $result = $db->pQuery($sql, [$quoteId]);
        $quote = $db->fetchByAssoc($result);
        
        if (!$quote) {
            throw new Exception("Quote not found: {$quoteId}");
        }
        
        // Get quote line items
        $quote['line_items'] = $this->getQuoteLineItems($quoteId);
        
        return $quote;
    }

    /**
     * Get quote line items
     */
    protected function getQuoteLineItems($quoteId)
    {
        global $db;
        
        $sql = "
        SELECT 
            qli.*,
            p.name as product_name,
            p.sku,
            p.description
        FROM manufacturing_quote_line_items qli
        LEFT JOIN manufacturing_products p ON qli.product_id = p.id
        WHERE qli.quote_id = ?
        ORDER BY qli.line_number
        ";
        
        $result = $db->pQuery($sql, [$quoteId]);
        $lineItems = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $lineItems[] = $row;
        }
        
        return $lineItems;
    }

    /**
     * Render PDF template
     */
    protected function renderPDFTemplate($type, $template, $data)
    {
        $templatePath = $this->getPDFTemplatePath($type, $template);
        
        if (!file_exists($templatePath)) {
            throw new Exception("PDF template not found: {$type}/{$template}");
        }
        
        // Start output buffering
        ob_start();
        
        // Extract variables for template
        extract($data);
        
        // Include template
        include $templatePath;
        
        // Get rendered content
        $content = ob_get_clean();
        
        return $content;
    }

    /**
     * Get PDF template path
     */
    protected function getPDFTemplatePath($type, $template)
    {
        $paths = [
            "custom/include/PDFTemplates/{$type}/{$template}.php",
            "include/BackgroundJobs/PDFTemplates/{$type}/{$template}.php",
            "modules/Manufacturing/PDFTemplates/{$type}/{$template}.php"
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        throw new Exception("PDF template file not found: {$type}/{$template}");
    }

    /**
     * Store generated PDF
     */
    protected function storePDF($content, $payload)
    {
        $uploadDir = 'upload/manufacturing/pdfs/' . date('Y/m');
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = $this->generatePDFFilename($payload);
        $filePath = $uploadDir . '/' . $filename;
        
        if (file_put_contents($filePath, $content) === false) {
            throw new Exception("Failed to save PDF file: {$filePath}");
        }
        
        return $filePath;
    }

    /**
     * Generate PDF filename
     */
    protected function generatePDFFilename($payload)
    {
        $timestamp = date('Y-m-d_H-i-s');
        $type = $payload['type'];
        $recordId = substr($payload['record_id'], 0, 8);
        
        return "{$type}_{$recordId}_{$timestamp}.pdf";
    }

    /**
     * Update database record with PDF path
     */
    protected function updateRecord($payload, $filePath)
    {
        global $db;
        
        $table = $this->getTableForType($payload['type']);
        
        $sql = "UPDATE {$table} SET pdf_file_path = ?, pdf_generated_at = NOW() WHERE id = ?";
        $db->pQuery($sql, [$filePath, $payload['record_id']]);
    }

    /**
     * Get database table for PDF type
     */
    protected function getTableForType($type)
    {
        $tables = [
            'quote' => 'manufacturing_quotes',
            'invoice' => 'manufacturing_invoices',
            'report' => 'manufacturing_reports'
        ];
        
        return $tables[$type] ?? 'manufacturing_documents';
    }

    /**
     * Send notification about PDF generation
     */
    protected function sendNotification($payload, $filePath)
    {
        require_once('include/BackgroundJobs/JobQueue.php');
        
        JobQueue::enqueue('email', 'EmailNotificationJob', [
            'to' => $payload['notify_email'] ?? $GLOBALS['current_user']->email1,
            'subject' => "PDF Generated: {$payload['type']}",
            'template' => 'pdf_generation_complete',
            'data' => [
                'pdf_type' => $payload['type'],
                'record_id' => $payload['record_id'],
                'file_path' => $filePath,
                'download_url' => $this->generateDownloadURL($filePath)
            ]
        ]);
    }

    /**
     * Generate download URL for PDF
     */
    protected function generateDownloadURL($filePath)
    {
        global $sugar_config;
        
        $baseUrl = $sugar_config['site_url'] ?? 'http://localhost:3000';
        return $baseUrl . '/' . $filePath;
    }

    /**
     * Get job metadata
     */
    public function getMetadata()
    {
        return [
            'name' => 'PDF Generation',
            'description' => 'Generates PDF documents for quotes, invoices, and reports',
            'estimated_duration' => '10-30 seconds',
            'memory_requirements' => '128MB',
            'dependencies' => ['puppeteer', 'pdf_templates']
        ];
    }
}
