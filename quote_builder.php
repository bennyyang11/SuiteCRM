<?php
/**
 * Quote Builder with PDF Export
 * Feature 4: Manufacturing Quote Builder System
 */

// Initialize session properly
require_once 'session_init.php';
$demo_mode = true;

// Get client information for pricing (demo data)
$client_id = $_GET['client_id'] ?? 'demo-client-001';
$quote_id = $_GET['quote_id'] ?? null;
$client_tier = $_GET['tier'] ?? 'retail';
$existing_quote = null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Builder - Manufacturing Distribution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }

        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
        }
        .header h1 { font-size: 2em; margin-bottom: 5px; }
        .header p { opacity: 0.9; }

        .back-link {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .back-link:hover { background: rgba(255,255,255,0.2); text-decoration: none; }

        .header-actions {
            display: flex;
            gap: 10px;
        }
        .header-btn {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            background: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .header-btn:hover { background: rgba(255,255,255,0.2); text-decoration: none; }
        .header-btn.primary { background: #e74c3c; border-color: #e74c3c; }
        .header-btn.primary:hover { background: #c0392b; }

        .quote-builder-container {
            max-width: 1400px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            padding: 0 20px;
        }

        .product-sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            height: fit-content;
            max-height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .sidebar-header h3 { font-size: 1.3em; margin-bottom: 10px; }

        .search-container {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .search-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .product-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .product-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: grab;
            transition: all 0.3s ease;
            user-select: none;
        }
        .product-card:hover {
            border-color: #3498db;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
            transform: translateY(-2px);
        }
        .product-card.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
            z-index: 1000;
            cursor: grabbing;
        }

        .product-info h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .product-sku {
            color: #7f8c8d;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .product-price {
            color: #27ae60;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .product-description {
            color: #7f8c8d;
            font-size: 13px;
            line-height: 1.4;
        }

        .quote-canvas {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .canvas-header {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .canvas-header h3 { font-size: 1.4em; }

        .quote-info {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .info-field label {
            display: block;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .info-field input, .info-field select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .info-field input:focus, .info-field select:focus {
            outline: none;
            border-color: #27ae60;
        }

        .drop-zone {
            flex: 1;
            padding: 20px;
            min-height: 300px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            margin: 20px;
            position: relative;
            transition: all 0.3s ease;
        }
        .drop-zone.drag-over {
            border-color: #27ae60;
            background: #d4edda;
        }
        .drop-zone.empty::before {
            content: "🛒 Drag products here to build your quote";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #6c757d;
            font-size: 18px;
            text-align: center;
            pointer-events: none;
        }

        .quote-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 60px 1fr 120px 120px 120px 40px;
            gap: 15px;
            align-items: center;
            transition: all 0.3s ease;
        }
        .quote-item:hover {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .item-image {
            width: 50px;
            height: 50px;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .item-details h5 {
            color: #2c3e50;
            margin-bottom: 3px;
        }
        .item-sku {
            color: #7f8c8d;
            font-size: 12px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 6px;
            overflow: hidden;
        }
        .qty-btn {
            background: #e9ecef;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .qty-btn:hover { background: #dee2e6; }
        .qty-input {
            border: none;
            text-align: center;
            width: 60px;
            padding: 8px;
            background: white;
        }

        .discount-control {
            position: relative;
        }
        .discount-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: right;
        }

        .item-total {
            font-weight: bold;
            color: #27ae60;
            text-align: right;
        }

        .remove-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }
        .remove-btn:hover { background: #c0392b; }

        .quote-summary {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .summary-row.total {
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
            font-weight: bold;
            font-size: 18px;
            color: #27ae60;
        }

        .action-buttons {
            padding: 20px;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .quote-builder-container {
                grid-template-columns: 1fr;
                gap: 10px;
                padding: 0 10px;
            }
            .product-sidebar {
                max-height: 400px;
            }
            .quote-info {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .quote-item {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 8px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="complete_manufacturing_demo.php" class="back-link">
                <span>←</span> Back to Demo
            </a>
            <div class="header-center">
                <h1>🛒 Quote Builder</h1>
                <p>Drag & Drop Quote Creation System</p>
            </div>
            <div class="header-actions">
                <button class="header-btn" onclick="saveQuote()">💾 Save Draft</button>
                <button class="header-btn primary" onclick="generatePDF()">📄 Generate PDF</button>
            </div>
        </div>
    </div>

    <div class="quote-builder-container">
        <!-- Product Sidebar -->
        <div class="product-sidebar">
            <div class="sidebar-header">
                <h3>📦 Product Catalog</h3>
                <p>Drag products to your quote</p>
            </div>
            
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search products..." 
                       onkeyup="searchProducts(this.value)">
            </div>
            
            <div class="product-list" id="productList">
                <!-- Products will be loaded here -->
            </div>
        </div>

        <!-- Quote Canvas -->
        <div class="quote-canvas">
            <div class="canvas-header">
                <h3>📋 Quote Builder</h3>
                <div>
                    <span>Client Tier: <strong id="clientTier"><?= strtoupper($client_tier) ?></strong></span>
                </div>
            </div>

            <div class="quote-info">
                <div class="info-field">
                    <label>Quote Number</label>
                    <input type="text" id="quoteNumber" value="<?= $existing_quote['quote_number'] ?? 'Q-' . date('Ymd') . '-' . rand(1000, 9999) ?>" readonly>
                </div>
                <div class="info-field">
                    <label>Client Name</label>
                    <input type="text" id="clientName" value="<?= $existing_quote['client_name'] ?? 'Demo Manufacturing Co.' ?>" required>
                </div>
                <div class="info-field">
                    <label>Valid Until</label>
                    <input type="date" id="validUntil" value="<?= $existing_quote['valid_until'] ?? date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
            </div>

            <div class="drop-zone empty" id="dropZone" ondrop="dropProduct(event)" ondragover="allowDrop(event)">
                <div id="quoteItems">
                    <!-- Quote items will appear here -->
                </div>
            </div>

            <div class="quote-summary" id="quoteSummary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Discount:</span>
                    <span id="discount">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax (8.5%):</span>
                    <span id="tax">$0.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span id="shipping">$0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="total">$0.00</span>
                </div>
            </div>

            <div class="action-buttons">
                <button class="btn btn-secondary" onclick="clearQuote()">🗑️ Clear All</button>
                <button class="btn btn-secondary" onclick="saveTemplate()">📝 Save Template</button>
                <button class="btn btn-primary" onclick="previewQuote()">👁️ Preview</button>
                <button class="btn btn-success" onclick="generatePDF()">📄 Generate PDF</button>
                <button class="btn btn-success" onclick="emailQuote()">📧 Send Quote</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let quoteItems = [];
        let clientTier = '<?= $client_tier ?>';
        let draggedProduct = null;
        let quoteCounter = 0;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            updateSummary();
            
            // Load existing quote if editing
            <?php if ($existing_quote): ?>
            loadExistingQuote(<?= json_encode($existing_quote) ?>);
            <?php endif; ?>
        });

        // Load products from API
        async function loadProducts() {
            try {
                const response = await fetch('Api/v1/manufacturing/ProductCatalogAPI.php?action=list&client_tier=' + clientTier);
                const data = await response.json();
                
                if (data.success) {
                    displayProducts(data.products);
                } else {
                    console.error('Failed to load products:', data.message);
                    // Load demo products if API fails
                    loadDemoProducts();
                }
            } catch (error) {
                console.error('Error loading products:', error);
                loadDemoProducts();
            }
        }

        // Load demo products for demonstration
        function loadDemoProducts() {
            const demoProducts = [
                {
                    id: 'prod-001',
                    name: 'Industrial Bearing Assembly',
                    sku: 'IBA-2024-STD',
                    description: 'Heavy-duty bearing assembly for industrial machinery',
                    price: 245.99,
                    category: 'Bearings',
                    image: '🔩'
                },
                {
                    id: 'prod-002', 
                    name: 'Hydraulic Pump Unit',
                    sku: 'HPU-500-X',
                    description: '500 GPM hydraulic pump with integrated controls',
                    price: 1299.00,
                    category: 'Hydraulics',
                    image: '⚙️'
                },
                {
                    id: 'prod-003',
                    name: 'Steel Mounting Bracket',
                    sku: 'SMB-L-42',
                    description: 'Large steel mounting bracket, powder coated',
                    price: 89.50,
                    category: 'Hardware',
                    image: '🔧'
                },
                {
                    id: 'prod-004',
                    name: 'Precision Drive Belt',
                    sku: 'PDB-1200-V',
                    description: 'V-belt for precision drive applications',
                    price: 156.75,
                    category: 'Belts',
                    image: '🔗'
                },
                {
                    id: 'prod-005',
                    name: 'Control Panel Assembly',
                    sku: 'CPA-24V-IP65',
                    description: '24V control panel with IP65 rating',
                    price: 899.99,
                    category: 'Electronics',
                    image: '📋'
                }
            ];
            
            displayProducts(demoProducts);
        }

        // Display products in sidebar
        function displayProducts(products) {
            const productList = document.getElementById('productList');
            productList.innerHTML = '';
            
            products.forEach(product => {
                const productCard = document.createElement('div');
                productCard.className = 'product-card';
                productCard.draggable = true;
                productCard.dataset.productId = product.id;
                
                productCard.innerHTML = `
                    <div class="product-info">
                        <h4>${product.image || '📦'} ${product.name}</h4>
                        <div class="product-sku">SKU: ${product.sku}</div>
                        <div class="product-price">$${parseFloat(product.price).toFixed(2)}</div>
                        <div class="product-description">${product.description}</div>
                    </div>
                `;
                
                // Add drag event listeners
                productCard.addEventListener('dragstart', function(e) {
                    draggedProduct = product;
                    this.classList.add('dragging');
                });
                
                productCard.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    draggedProduct = null;
                });
                
                productList.appendChild(productCard);
            });
        }

        // Search products
        function searchProducts(query) {
            const productCards = document.querySelectorAll('.product-card');
            const searchTerm = query.toLowerCase();
            
            productCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (searchTerm === '' || text.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Drag and drop functions
        function allowDrop(e) {
            e.preventDefault();
            document.getElementById('dropZone').classList.add('drag-over');
        }

        function dropProduct(e) {
            e.preventDefault();
            document.getElementById('dropZone').classList.remove('drag-over');
            
            if (draggedProduct) {
                addProductToQuote(draggedProduct);
            }
        }

        // Add product to quote
        function addProductToQuote(product) {
            // Check if product already exists in quote
            const existingItem = quoteItems.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity += 1;
                updateQuoteItem(existingItem);
            } else {
                const quoteItem = {
                    id: product.id,
                    name: product.name,
                    sku: product.sku,
                    price: parseFloat(product.price),
                    quantity: 1,
                    discount: 0,
                    image: product.image || '📦'
                };
                
                quoteItems.push(quoteItem);
                addQuoteItemElement(quoteItem);
            }
            
            updateDropZoneVisibility();
            updateSummary();
        }

        // Add quote item element to DOM
        function addQuoteItemElement(item) {
            const quoteItemsContainer = document.getElementById('quoteItems');
            const itemElement = document.createElement('div');
            itemElement.className = 'quote-item';
            itemElement.dataset.itemId = item.id;
            
            itemElement.innerHTML = `
                <div class="item-image">${item.image}</div>
                <div class="item-details">
                    <h5>${item.name}</h5>
                    <div class="item-sku">SKU: ${item.sku}</div>
                </div>
                <div class="quantity-control">
                    <button class="qty-btn" onclick="updateQuantity('${item.id}', -1)">−</button>
                    <input type="number" class="qty-input" value="${item.quantity}" 
                           onchange="setQuantity('${item.id}', this.value)" min="1">
                    <button class="qty-btn" onclick="updateQuantity('${item.id}', 1)">+</button>
                </div>
                <div class="discount-control">
                    <input type="number" class="discount-input" placeholder="0%" 
                           onchange="setDiscount('${item.id}', this.value)" step="0.1" min="0" max="100">
                </div>
                <div class="item-total">$${(item.price * item.quantity * (1 - item.discount / 100)).toFixed(2)}</div>
                <button class="remove-btn" onclick="removeItem('${item.id}')">×</button>
            `;
            
            quoteItemsContainer.appendChild(itemElement);
        }

        // Update quantity
        function updateQuantity(itemId, change) {
            const item = quoteItems.find(i => i.id === itemId);
            if (item) {
                item.quantity = Math.max(1, item.quantity + change);
                updateQuoteItem(item);
                updateSummary();
            }
        }

        // Set quantity directly
        function setQuantity(itemId, quantity) {
            const item = quoteItems.find(i => i.id === itemId);
            if (item) {
                item.quantity = Math.max(1, parseInt(quantity) || 1);
                updateQuoteItem(item);
                updateSummary();
            }
        }

        // Set discount
        function setDiscount(itemId, discount) {
            const item = quoteItems.find(i => i.id === itemId);
            if (item) {
                item.discount = Math.max(0, Math.min(100, parseFloat(discount) || 0));
                updateQuoteItem(item);
                updateSummary();
            }
        }

        // Update quote item display
        function updateQuoteItem(item) {
            const itemElement = document.querySelector(`[data-item-id="${item.id}"]`);
            if (itemElement) {
                const qtyInput = itemElement.querySelector('.qty-input');
                const discountInput = itemElement.querySelector('.discount-input');
                const totalElement = itemElement.querySelector('.item-total');
                
                qtyInput.value = item.quantity;
                discountInput.value = item.discount || '';
                totalElement.textContent = `$${(item.price * item.quantity * (1 - item.discount / 100)).toFixed(2)}`;
            }
        }

        // Remove item from quote
        function removeItem(itemId) {
            quoteItems = quoteItems.filter(item => item.id !== itemId);
            const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
            if (itemElement) {
                itemElement.remove();
            }
            updateDropZoneVisibility();
            updateSummary();
        }

        // Update drop zone visibility
        function updateDropZoneVisibility() {
            const dropZone = document.getElementById('dropZone');
            if (quoteItems.length === 0) {
                dropZone.classList.add('empty');
            } else {
                dropZone.classList.remove('empty');
            }
        }

        // Update quote summary
        function updateSummary() {
            const subtotal = quoteItems.reduce((sum, item) => {
                return sum + (item.price * item.quantity * (1 - item.discount / 100));
            }, 0);
            
            const discountAmount = quoteItems.reduce((sum, item) => {
                return sum + (item.price * item.quantity * item.discount / 100);
            }, 0);
            
            const taxRate = 0.085; // 8.5% tax
            const tax = subtotal * taxRate;
            
            const shippingCost = subtotal > 500 ? 0 : 75; // Free shipping over $500
            const total = subtotal + tax + shippingCost;
            
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('discount').textContent = `-$${discountAmount.toFixed(2)}`;
            document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
            document.getElementById('shipping').textContent = `$${shippingCost.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;
        }

        // Save quote draft
        async function saveQuote() {
            const quoteData = {
                quote_number: document.getElementById('quoteNumber').value,
                client_name: document.getElementById('clientName').value,
                valid_until: document.getElementById('validUntil').value,
                items: quoteItems,
                client_tier: clientTier
            };
            
            try {
                const response = await fetch('Api/v1/quotes/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(quoteData)
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Quote saved successfully!');
                } else {
                    alert('Error saving quote: ' + result.message);
                }
            } catch (error) {
                console.error('Error saving quote:', error);
                alert('Quote saved locally (demo mode)');
            }
        }

        // Generate PDF
        async function generatePDF() {
            if (quoteItems.length === 0) {
                alert('Please add some products to your quote first.');
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner"></span>Generating...';
            button.disabled = true;
            
            const quoteData = {
                quote_number: document.getElementById('quoteNumber').value,
                client_name: document.getElementById('clientName').value,
                valid_until: document.getElementById('validUntil').value,
                items: quoteItems,
                client_tier: clientTier
            };
            
            try {
                const response = await fetch('Api/v1/quotes/generate_pdf.php', {
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
                alert('PDF generation is not available in demo mode');
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Clear quote
        function clearQuote() {
            if (confirm('Are you sure you want to clear all items from this quote?')) {
                quoteItems = [];
                document.getElementById('quoteItems').innerHTML = '';
                updateDropZoneVisibility();
                updateSummary();
            }
        }

        // Preview quote
        function previewQuote() {
            if (quoteItems.length === 0) {
                alert('Please add some products to your quote first.');
                return;
            }
            
            const quoteData = {
                quote_number: document.getElementById('quoteNumber').value,
                client_name: document.getElementById('clientName').value,
                valid_until: document.getElementById('validUntil').value,
                items: quoteItems,
                client_tier: clientTier
            };
            
            // Open preview in new window
            const previewWindow = window.open('quote_preview.php', '_blank', 'width=800,height=1000');
            previewWindow.onload = function() {
                previewWindow.postMessage(quoteData, '*');
            };
        }

        // Email quote
        async function emailQuote() {
            if (quoteItems.length === 0) {
                alert('Please add some products to your quote first.');
                return;
            }
            
            const email = prompt('Enter client email address:');
            if (!email) return;
            
            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner"></span>Sending...';
            button.disabled = true;
            
            const quoteData = {
                quote_number: document.getElementById('quoteNumber').value,
                client_name: document.getElementById('clientName').value,
                valid_until: document.getElementById('validUntil').value,
                items: quoteItems,
                client_tier: clientTier
            };
            
            const emailData = {
                to_email: email,
                quote_data: quoteData
            };
            
            try {
                const response = await fetch('Api/v1/quotes/email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(emailData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`Quote successfully sent to ${email}!\n\nTracking ID: ${result.tracking_id}`);
                } else {
                    alert('Error sending email: ' + result.message);
                }
            } catch (error) {
                console.error('Error sending email:', error);
                alert(`Quote email logged for ${email} (Demo mode - email system not fully configured)`);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }

        // Save template
        function saveTemplate() {
            if (quoteItems.length === 0) {
                alert('Please add some products to create a template.');
                return;
            }
            
            const templateName = prompt('Enter template name:');
            if (templateName) {
                alert(`Template "${templateName}" saved successfully! (Demo mode)`);
            }
        }
    </script>
</body>
</html>
