<?php
/**
 * Test script to verify the specific URL access
 */

echo "<h2>Testing URL: localhost:3000/index.php?module=Home&action=index</h2>";

// Simulate the request
$_GET['module'] = 'Home';
$_GET['action'] = 'index';

echo "<p><strong>Request parameters detected:</strong></p>";
echo "<ul>";
echo "<li>module: " . ($_GET['module'] ?? 'not set') . "</li>";
echo "<li>action: " . ($_GET['action'] ?? 'not set') . "</li>";
echo "</ul>";

// Check if conditions match what's in index.php
$hasModule = isset($_GET['module']) || isset($_POST['module']);
$hasAction = isset($_GET['action']) || isset($_POST['action']);

echo "<p><strong>Condition check:</strong></p>";
echo "<ul>";
echo "<li>Has module parameter: " . ($hasModule ? 'YES' : 'NO') . "</li>";
echo "<li>Has action parameter: " . ($hasAction ? 'YES' : 'NO') . "</li>";
echo "<li>Should route to SuiteCRM: " . (($hasModule || $hasAction) ? 'YES' : 'NO') . "</li>";
echo "</ul>";

echo "<p><strong>Result:</strong> With these parameters, the system should show the SuiteCRM interface (login or dashboard).</p>";

echo "<hr>";
echo "<h3>Quick Links to Test:</h3>";
echo "<ul>";
echo "<li><a href='/'>Home (no parameters) - Should show manufacturing interface</a></li>";
echo "<li><a href='/?module=Home&action=index'>SuiteCRM Access - Should show login/dashboard</a></li>";
echo "<li><a href='/manufacturing_demo.php'>Manufacturing Demo - Direct access</a></li>";
echo "</ul>";
?>
