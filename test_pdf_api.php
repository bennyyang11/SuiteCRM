<?php
/**
 * Test PDF API
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test PDF Generation</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 PDF Generation Test</h1>
        <p>Testing the PDF generation API with sample quote data.</p>
        
        <button onclick="testPDFGeneration()">Generate Test PDF</button>
        
        <div id="result"></div>
    </div>

    <script>
        async function testPDFGeneration() {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Generating...';
            button.disabled = true;
            
            const testQuote = {
                quote_number: 'TEST-' + Date.now(),
                client_name: 'Demo Manufacturing Co.',
                valid_until: '2024-12-31',
                client_tier: 'retail',
                items: [
                    {
                        id: 'prod-001',
                        name: 'Industrial Bearing Assembly',
                        sku: 'IBA-2024-STD',
                        price: 245.99,
                        quantity: 2,
                        discount: 0
                    },
                    {
                        id: 'prod-002',
                        name: 'Hydraulic Pump Unit',
                        sku: 'HPU-500-X',
                        price: 1299.00,
                        quantity: 1,
                        discount: 5
                    }
                ]
            };
            
            try {
                const response = await fetch('Api/v1/quotes/generate_pdf.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testQuote)
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    
                    if (blob.size > 0) {
                        // Create download link
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = testQuote.quote_number + '.pdf';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        document.getElementById('result').innerHTML = 
                            '<div class="result success">✅ PDF generated successfully! File size: ' + 
                            (blob.size / 1024).toFixed(2) + ' KB</div>';
                    } else {
                        document.getElementById('result').innerHTML = 
                            '<div class="result error">❌ PDF generation returned empty file</div>';
                    }
                } else {
                    const errorText = await response.text();
                    document.getElementById('result').innerHTML = 
                        '<div class="result error">❌ HTTP Error ' + response.status + ': ' + errorText + '</div>';
                }
            } catch (error) {
                document.getElementById('result').innerHTML = 
                    '<div class="result error">❌ Network Error: ' + error.message + '</div>';
            } finally {
                button.textContent = originalText;
                button.disabled = false;
            }
        }
    </script>
</body>
</html>
