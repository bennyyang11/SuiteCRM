<?php
/**
 * Quote Preview Page
 * Shows a preview of the quote before PDF generation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Preview</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f5f5; 
            padding: 20px;
        }
        
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .preview-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .preview-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .preview-header p {
            opacity: 0.9;
        }
        
        .quote-content {
            padding: 30px;
        }
        
        .quote-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-section h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-section p {
            margin-bottom: 5px;
            color: #555;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .items-table th {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
        }
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }
        .items-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .item-name {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        .item-sku {
            font-size: 12px;
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
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            max-width: 300px;
            margin-left: auto;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .summary-row:last-child {
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
            font-weight: bold;
            font-size: 18px;
            color: #27ae60;
        }
        
        .terms-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }
        .terms-section h3 {
            color: #2c3e50;
            margin-bottom: 15px;
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
        
        .action-buttons {
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover { background: #5a6268; }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover { background: #0056b3; }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover { background: #1e7e34; }
        
        @media print {
            body { background: white; padding: 0; }
            .preview-container { box-shadow: none; }
            .action-buttons { display: none; }
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <h1>🏭 Manufacturing Distribution Co.</h1>
            <p>Professional Industrial Solutions</p>
            <p>📍 1234 Industrial Blvd, Manufacturing City, ST 12345</p>
            <p>📞 (555) 123-4567 | 📧 quotes@mfgdist.com</p>
        </div>
        
        <div class="quote-content">
            <div class="quote-info">
                <div class="info-section">
                    <h3>📋 Quote Information</h3>
                    <p><strong>Quote #:</strong> <span id="quoteNumber">-</span></p>
                    <p><strong>Date:</strong> <span id="quoteDate">-</span></p>
                    <p><strong>Valid Until:</strong> <span id="validUntil">-</span></p>
                </div>
                <div class="info-section">
                    <h3>👤 Client Information</h3>
                    <p><strong>Client:</strong> <span id="clientName">-</span></p>
                    <p><strong>Pricing Tier:</strong> <span id="clientTier">-</span></p>
                    <p><strong>Prepared By:</strong> Sales Representative</p>
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
                <tbody id="itemsTableBody">
                    <!-- Items will be populated here -->
                </tbody>
            </table>
            
            <div class="summary-section">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Discount:</span>
                    <span id="discountTotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax (8.5%):</span>
                    <span id="taxTotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span id="shippingTotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Total:</span>
                    <span id="grandTotal">$0.00</span>
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
        </div>
        
        <div class="action-buttons">
            <button class="btn btn-secondary" onclick="window.print()">🖨️ Print</button>
            <button class="btn btn-primary" onclick="window.close()">← Back to Builder</button>
            <button class="btn btn-success" onclick="generatePDF()">📄 Download PDF</button>
        </div>
    </div>

    <script>
        let quoteData = null;
        
        // Listen for quote data from parent window
        window.addEventListener('message', function(event) {
            if (event.data && event.data.quote_number) {
                quoteData = event.data;
                populatePreview(quoteData);
            }
        });
        
        // Check if data was passed via URL parameters (fallback)
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const dataParam = urlParams.get('data');
            
            if (dataParam) {
                try {
                    quoteData = JSON.parse(decodeURIComponent(dataParam));
                    populatePreview(quoteData);
                } catch (e) {
                    console.error('Error parsing quote data:', e);
                }
            }
        });
        
        function populatePreview(data) {
            // Populate quote information
            document.getElementById('quoteNumber').textContent = data.quote_number || '-';
            document.getElementById('quoteDate').textContent = new Date().toLocaleDateString();
            document.getElementById('validUntil').textContent = new Date(data.valid_until).toLocaleDateString();
            document.getElementById('clientName').textContent = data.client_name || '-';
            document.getElementById('clientTier').textContent = (data.client_tier || 'retail').toUpperCase();
            
            // Populate items table
            const tbody = document.getElementById('itemsTableBody');
            tbody.innerHTML = '';
            
            let subtotal = 0;
            let discountTotal = 0;
            
            data.items.forEach(item => {
                const itemTotal = item.price * item.quantity;
                const itemDiscount = itemTotal * (item.discount || 0) / 100;
                const finalTotal = itemTotal - itemDiscount;
                
                subtotal += finalTotal;
                discountTotal += itemDiscount;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <div class="item-name">${item.image || '📦'} ${item.name}</div>
                        <div class="item-sku">SKU: ${item.sku}</div>
                    </td>
                    <td class="quantity-cell">${item.quantity}</td>
                    <td class="price-cell">$${item.price.toFixed(2)}</td>
                    <td class="discount-cell">${item.discount ? item.discount + '%' : '-'}</td>
                    <td class="total-cell">$${finalTotal.toFixed(2)}</td>
                `;
                tbody.appendChild(row);
            });
            
            // Calculate and display totals
            const tax = subtotal * 0.085; // 8.5% tax
            const shipping = subtotal > 500 ? 0 : 75;
            const total = subtotal + tax + shipping;
            
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('discountTotal').textContent = `-$${discountTotal.toFixed(2)}`;
            document.getElementById('taxTotal').textContent = `$${tax.toFixed(2)}`;
            document.getElementById('shippingTotal').textContent = `$${shipping.toFixed(2)}`;
            document.getElementById('grandTotal').textContent = `$${total.toFixed(2)}`;
        }
        
        async function generatePDF() {
            if (!quoteData) {
                alert('No quote data available');
                return;
            }
            
            try {
                const response = await fetch('../Api/v1/quotes/generate_pdf.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(quoteData)
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `Quote-${quoteData.quote_number}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    const error = await response.json();
                    alert('Error generating PDF: ' + error.message);
                }
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
            }
        }
    </script>
</body>
</html>
