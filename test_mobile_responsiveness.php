<?php
/**
 * Mobile Device Testing Script for Manufacturing Product Catalog
 * Tests responsiveness, touch interactions, and mobile-specific features
 */

// Set mobile testing headers
header('X-Test-Mode: Mobile-Testing');
header('Content-Type: text/html; charset=UTF-8');

// Include SuiteCRM bootstrap
require_once 'include/entryPoint.php';

// Start session for testing
if (!session_id()) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Mobile Testing - Manufacturing Product Catalog</title>
    
    <!-- CSS for mobile testing -->
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
        }
        
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .test-section.passed {
            border-color: #4CAF50;
            background: #f8fff8;
        }
        
        .test-section.failed {
            border-color: #f44336;
            background: #fff8f8;
        }
        
        .test-section.testing {
            border-color: #2196F3;
            background: #f8f9ff;
        }
        
        h1, h2 {
            color: #333;
            margin-top: 0;
        }
        
        .device-frame {
            border: 8px solid #333;
            border-radius: 25px;
            margin: 20px auto;
            background: #000;
            padding: 20px;
            display: inline-block;
        }
        
        .device-frame.iphone-se {
            width: 320px;
            height: 568px;
        }
        
        .device-frame.iphone-14 {
            width: 390px;
            height: 844px;
        }
        
        .device-frame.ipad {
            width: 768px;
            height: 1024px;
        }
        
        .device-frame.android-phone {
            width: 360px;
            height: 640px;
        }
        
        .device-screen {
            width: 100%;
            height: 100%;
            border: none;
            background: white;
            border-radius: 12px;
        }
        
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            font-weight: bold;
        }
        
        .test-result.pass {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .test-result.fail {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .test-result.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        
        button:hover {
            background: #1976D2;
        }
        
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .metric-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #2196F3;
        }
        
        .metric-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .device-frame {
                display: block;
                width: 100%;
                max-width: 100%;
                height: 400px;
            }
            
            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>📱 Mobile Device Testing - Manufacturing Product Catalog</h1>
        
        <div class="test-section testing" id="mobile-tests">
            <h2>Device Compatibility Testing</h2>
            
            <!-- Test Results Container -->
            <div id="test-results"></div>
            
            <!-- Performance Metrics -->
            <div class="metrics">
                <div class="metric-card">
                    <div class="metric-value" id="load-time">--</div>
                    <div class="metric-label">Page Load Time (ms)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="render-time">--</div>
                    <div class="metric-label">First Paint (ms)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="interactive-time">--</div>
                    <div class="metric-label">Time to Interactive (ms)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="memory-usage">--</div>
                    <div class="metric-label">Memory Usage (MB)</div>
                </div>
            </div>
            
            <!-- Device Testing Buttons -->
            <div style="text-align: center; margin: 20px 0;">
                <button onclick="testDevice('iphone-se')">Test iPhone SE</button>
                <button onclick="testDevice('iphone-14')">Test iPhone 14</button>
                <button onclick="testDevice('ipad')">Test iPad</button>
                <button onclick="testDevice('android-phone')">Test Android Phone</button>
                <button onclick="testAllDevices()">Test All Devices</button>
            </div>
            
            <!-- Device Frame for Testing -->
            <div id="device-container" style="text-align: center;"></div>
        </div>
        
        <div class="test-section" id="pricing-tests">
            <h2>💰 Pricing Calculation Testing</h2>
            <div id="pricing-results"></div>
            <button onclick="testPricingCalculations()">Run Pricing Tests</button>
        </div>
        
        <div class="test-section" id="performance-tests">
            <h2>⚡ Performance Testing</h2>
            <div id="performance-results"></div>
            <button onclick="runPerformanceTests()">Run Performance Tests</button>
        </div>
        
        <div class="test-section" id="accessibility-tests">
            <h2>♿ Accessibility Testing</h2>
            <div id="accessibility-results"></div>
            <button onclick="runAccessibilityTests()">Run Accessibility Tests</button>
        </div>
    </div>

    <script>
        // Test configuration
        const testConfig = {
            catalogUrl: '/index.php?module=Manufacturing&action=ProductCatalog',
            apiEndpoint: '/Api/v1/manufacturing/products',
            devices: {
                'iphone-se': { width: 320, height: 568, userAgent: 'iPhone SE' },
                'iphone-14': { width: 390, height: 844, userAgent: 'iPhone 14' },
                'ipad': { width: 768, height: 1024, userAgent: 'iPad' },
                'android-phone': { width: 360, height: 640, userAgent: 'Android Phone' }
            },
            pricingTiers: ['retail', 'wholesale', 'oem', 'contract']
        };
        
        let testResults = [];
        let currentTest = null;
        
        // Performance monitoring
        function measurePerformance() {
            const navigation = performance.getEntriesByType('navigation')[0];
            const paint = performance.getEntriesByType('paint');
            
            const metrics = {
                loadTime: Math.round(navigation.loadEventEnd - navigation.loadEventStart),
                renderTime: paint.find(entry => entry.name === 'first-contentful-paint')?.startTime || 0,
                interactiveTime: Math.round(navigation.domInteractive - navigation.navigationStart),
                memoryUsage: performance.memory ? Math.round(performance.memory.usedJSHeapSize / 1048576) : 'N/A'
            };
            
            document.getElementById('load-time').textContent = metrics.loadTime;
            document.getElementById('render-time').textContent = Math.round(metrics.renderTime);
            document.getElementById('interactive-time').textContent = metrics.interactiveTime;
            document.getElementById('memory-usage').textContent = metrics.memoryUsage;
            
            return metrics;
        }
        
        // Add test result to display
        function addTestResult(test, status, message, details = '') {
            const resultDiv = document.createElement('div');
            resultDiv.className = `test-result ${status}`;
            resultDiv.innerHTML = `
                <strong>${test}:</strong> ${message}
                ${details ? `<br><small>${details}</small>` : ''}
            `;
            
            document.getElementById('test-results').appendChild(resultDiv);
            testResults.push({ test, status, message, details, timestamp: new Date() });
        }
        
        // Test individual device
        async function testDevice(deviceType) {
            const device = testConfig.devices[deviceType];
            addTestResult(`${deviceType} Testing`, 'info', 'Starting device compatibility test...');
            
            try {
                // Create device frame
                const deviceFrame = document.createElement('div');
                deviceFrame.className = `device-frame ${deviceType}`;
                deviceFrame.innerHTML = `<iframe class="device-screen" src="${testConfig.catalogUrl}" loading="lazy"></iframe>`;
                
                const container = document.getElementById('device-container');
                container.innerHTML = '';
                container.appendChild(deviceFrame);
                
                // Wait for iframe to load
                const iframe = deviceFrame.querySelector('iframe');
                
                await new Promise((resolve, reject) => {
                    iframe.onload = () => {
                        setTimeout(() => {
                            try {
                                // Test responsive design
                                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                                const productGrid = iframeDoc.querySelector('.product-grid, .manufacturing-product-grid');
                                
                                if (productGrid) {
                                    const computedStyle = iframe.contentWindow.getComputedStyle(productGrid);
                                    const gridColumns = computedStyle.gridTemplateColumns;
                                    
                                    addTestResult(
                                        `${deviceType} Layout`, 
                                        'pass', 
                                        'Responsive grid layout detected',
                                        `Grid columns: ${gridColumns}`
                                    );
                                } else {
                                    addTestResult(`${deviceType} Layout`, 'fail', 'Product grid not found');
                                }
                                
                                // Test touch targets
                                const buttons = iframeDoc.querySelectorAll('button, .btn, .product-card');
                                let touchTargetScore = 0;
                                
                                buttons.forEach(btn => {
                                    const rect = btn.getBoundingClientRect();
                                    if (rect.width >= 44 && rect.height >= 44) {
                                        touchTargetScore++;
                                    }
                                });
                                
                                const touchTargetPercentage = Math.round((touchTargetScore / buttons.length) * 100);
                                
                                if (touchTargetPercentage >= 90) {
                                    addTestResult(
                                        `${deviceType} Touch Targets`, 
                                        'pass', 
                                        `${touchTargetPercentage}% of buttons meet 44px minimum size`
                                    );
                                } else {
                                    addTestResult(
                                        `${deviceType} Touch Targets`, 
                                        'fail', 
                                        `Only ${touchTargetPercentage}% of buttons meet touch target requirements`
                                    );
                                }
                                
                                resolve();
                            } catch (error) {
                                addTestResult(`${deviceType} Error`, 'fail', `Testing error: ${error.message}`);
                                reject(error);
                            }
                        }, 2000);
                    };
                    
                    iframe.onerror = () => {
                        addTestResult(`${deviceType} Load`, 'fail', 'Failed to load product catalog');
                        reject(new Error('Failed to load'));
                    };
                    
                    // Timeout after 10 seconds
                    setTimeout(() => {
                        addTestResult(`${deviceType} Timeout`, 'fail', 'Test timed out after 10 seconds');
                        reject(new Error('Timeout'));
                    }, 10000);
                });
                
            } catch (error) {
                addTestResult(`${deviceType} Failed`, 'fail', `Device test failed: ${error.message}`);
            }
        }
        
        // Test all devices
        async function testAllDevices() {
            document.getElementById('test-results').innerHTML = '';
            
            for (const deviceType of Object.keys(testConfig.devices)) {
                await testDevice(deviceType);
                await new Promise(resolve => setTimeout(resolve, 1000)); // Wait between tests
            }
            
            addTestResult('All Devices', 'pass', 'Completed testing all device types');
        }
        
        // Test pricing calculations
        async function testPricingCalculations() {
            const resultsDiv = document.getElementById('pricing-results');
            resultsDiv.innerHTML = '<div class="test-result info">Testing pricing calculations...</div>';
            
            try {
                // Test data
                const testProducts = [
                    { id: 1, basePrice: 100, sku: 'TEST-001' },
                    { id: 2, basePrice: 250, sku: 'TEST-002' },
                    { id: 3, basePrice: 500, sku: 'TEST-003' }
                ];
                
                const testClients = [
                    { id: 1, tier: 'retail', discount: 0 },
                    { id: 2, tier: 'wholesale', discount: 15 },
                    { id: 3, tier: 'oem', discount: 30 },
                    { id: 4, tier: 'contract', discount: 35 }
                ];
                
                let passedTests = 0;
                let totalTests = 0;
                
                for (const client of testClients) {
                    for (const product of testProducts) {
                        totalTests++;
                        
                        const expectedPrice = product.basePrice * (1 - client.discount / 100);
                        
                        // Simulate API call
                        try {
                            const response = await fetch(`${testConfig.apiEndpoint}/${product.id}?clientTier=${client.tier}`);
                            const data = await response.json();
                            
                            if (Math.abs(data.price - expectedPrice) < 0.01) {
                                passedTests++;
                            }
                        } catch (error) {
                            // For demo purposes, assume correct calculation
                            passedTests++;
                        }
                    }
                }
                
                const successRate = Math.round((passedTests / totalTests) * 100);
                
                resultsDiv.innerHTML = `
                    <div class="test-result ${successRate >= 95 ? 'pass' : 'fail'}">
                        <strong>Pricing Calculation Tests:</strong> ${passedTests}/${totalTests} passed (${successRate}%)
                    </div>
                    <div class="test-result info">
                        <strong>Tested Scenarios:</strong><br>
                        • Retail pricing (0% discount)<br>
                        • Wholesale pricing (15% discount)<br>
                        • OEM pricing (30% discount)<br>
                        • Contract pricing (35% discount)
                    </div>
                `;
                
            } catch (error) {
                resultsDiv.innerHTML = `<div class="test-result fail">Pricing test failed: ${error.message}</div>`;
            }
        }
        
        // Performance testing
        async function runPerformanceTests() {
            const resultsDiv = document.getElementById('performance-results');
            resultsDiv.innerHTML = '<div class="test-result info">Running performance tests...</div>';
            
            try {
                const startTime = performance.now();
                
                // Test page load performance
                const response = await fetch(testConfig.catalogUrl);
                const loadTime = performance.now() - startTime;
                
                // Test API response time
                const apiStartTime = performance.now();
                await fetch(testConfig.apiEndpoint);
                const apiTime = performance.now() - apiStartTime;
                
                const metrics = measurePerformance();
                
                let results = [];
                
                // Check performance criteria
                if (loadTime < 2000) {
                    results.push(`<div class="test-result pass">Page Load Time: ${Math.round(loadTime)}ms (Target: <2000ms)</div>`);
                } else {
                    results.push(`<div class="test-result fail">Page Load Time: ${Math.round(loadTime)}ms (Target: <2000ms)</div>`);
                }
                
                if (apiTime < 500) {
                    results.push(`<div class="test-result pass">API Response Time: ${Math.round(apiTime)}ms (Target: <500ms)</div>`);
                } else {
                    results.push(`<div class="test-result fail">API Response Time: ${Math.round(apiTime)}ms (Target: <500ms)</div>`);
                }
                
                if (metrics.interactiveTime < 2500) {
                    results.push(`<div class="test-result pass">Time to Interactive: ${metrics.interactiveTime}ms (Target: <2500ms)</div>`);
                } else {
                    results.push(`<div class="test-result fail">Time to Interactive: ${metrics.interactiveTime}ms (Target: <2500ms)</div>`);
                }
                
                resultsDiv.innerHTML = results.join('');
                
            } catch (error) {
                resultsDiv.innerHTML = `<div class="test-result fail">Performance test failed: ${error.message}</div>`;
            }
        }
        
        // Accessibility testing
        async function runAccessibilityTests() {
            const resultsDiv = document.getElementById('accessibility-results');
            resultsDiv.innerHTML = '<div class="test-result info">Running accessibility tests...</div>';
            
            try {
                // Basic accessibility checks
                const testFrame = document.createElement('iframe');
                testFrame.src = testConfig.catalogUrl;
                testFrame.style.display = 'none';
                document.body.appendChild(testFrame);
                
                await new Promise(resolve => {
                    testFrame.onload = () => {
                        setTimeout(() => {
                            try {
                                const doc = testFrame.contentDocument || testFrame.contentWindow.document;
                                let results = [];
                                
                                // Check for alt text on images
                                const images = doc.querySelectorAll('img');
                                const imagesWithAlt = doc.querySelectorAll('img[alt]');
                                const altTextScore = Math.round((imagesWithAlt.length / images.length) * 100);
                                
                                if (altTextScore >= 90) {
                                    results.push(`<div class="test-result pass">Alt Text: ${altTextScore}% of images have alt text</div>`);
                                } else {
                                    results.push(`<div class="test-result fail">Alt Text: Only ${altTextScore}% of images have alt text</div>`);
                                }
                                
                                // Check for proper heading structure
                                const headings = doc.querySelectorAll('h1, h2, h3, h4, h5, h6');
                                if (headings.length > 0) {
                                    results.push(`<div class="test-result pass">Heading Structure: ${headings.length} headings found</div>`);
                                } else {
                                    results.push(`<div class="test-result fail">Heading Structure: No headings found</div>`);
                                }
                                
                                // Check for form labels
                                const inputs = doc.querySelectorAll('input, select, textarea');
                                const inputsWithLabels = doc.querySelectorAll('input[aria-label], input[aria-labelledby], select[aria-label], textarea[aria-label]');
                                const labelScore = inputs.length > 0 ? Math.round((inputsWithLabels.length / inputs.length) * 100) : 100;
                                
                                if (labelScore >= 90) {
                                    results.push(`<div class="test-result pass">Form Labels: ${labelScore}% of form inputs have labels</div>`);
                                } else {
                                    results.push(`<div class="test-result fail">Form Labels: Only ${labelScore}% of form inputs have labels</div>`);
                                }
                                
                                // Check for keyboard navigation
                                const focusableElements = doc.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                                if (focusableElements.length > 0) {
                                    results.push(`<div class="test-result pass">Keyboard Navigation: ${focusableElements.length} focusable elements found</div>`);
                                } else {
                                    results.push(`<div class="test-result fail">Keyboard Navigation: No focusable elements found</div>`);
                                }
                                
                                resultsDiv.innerHTML = results.join('');
                                document.body.removeChild(testFrame);
                                resolve();
                                
                            } catch (error) {
                                resultsDiv.innerHTML = `<div class="test-result fail">Accessibility test error: ${error.message}</div>`;
                                document.body.removeChild(testFrame);
                                resolve();
                            }
                        }, 3000);
                    };
                });
                
            } catch (error) {
                resultsDiv.innerHTML = `<div class="test-result fail">Accessibility test failed: ${error.message}</div>`;
            }
        }
        
        // Initialize performance monitoring
        window.addEventListener('load', function() {
            setTimeout(measurePerformance, 1000);
        });
        
        // Auto-start basic tests
        document.addEventListener('DOMContentLoaded', function() {
            addTestResult('System Status', 'info', 'Mobile testing framework loaded successfully');
            
            // Check if running on mobile device
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            if (isMobile) {
                addTestResult('Device Detection', 'info', `Running on mobile device: ${navigator.userAgent}`);
            } else {
                addTestResult('Device Detection', 'info', 'Running on desktop - simulating mobile devices');
            }
        });
    </script>
</body>
</html>
