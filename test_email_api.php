<?php
/**
 * Test Email API
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Email API</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #218838; }
        input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 300px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email API Test</h1>
        <p>Testing the email sending functionality with sample quote data.</p>
        
        <div style="margin: 20px 0;">
            <label>Email Address:</label><br>
            <input type="email" id="emailAddress" value="test@example.com" placeholder="Enter email address">
        </div>
        
        <button onclick="testEmailSending()">Send Test Quote Email</button>
        
        <div id="result"></div>
    </div>

    <script>
        async function testEmailSending() {
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Sending...';
            button.disabled = true;
            
            const email = document.getElementById('emailAddress').value;
            if (!email) {
                document.getElementById('result').innerHTML = 
                    '<div class="result error">❌ Please enter an email address</div>';
                button.textContent = originalText;
                button.disabled = false;
                return;
            }
            
            const testQuote = {
                quote_number: 'EMAIL-TEST-' + Date.now(),
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
                    }
                ]
            };
            
            const emailData = {
                to_email: email,
                quote_data: testQuote
            };
            
            try {
                const response = await fetch('Api/v1/quotes/email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(emailData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('result').innerHTML = 
                        '<div class="result success">✅ Email sent successfully!<br>' +
                        'Tracking ID: ' + result.tracking_id + '<br>' +
                        'Method: ' + (result.method || 'demo') + '</div>';
                } else {
                    document.getElementById('result').innerHTML = 
                        '<div class="result error">❌ Email failed: ' + result.message + '</div>';
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
