<?php
echo "Testing manufacturing demo...\n";
echo "Checking for Feature 3 content...\n";

$content = file_get_contents('manufacturing_demo.php');
$feature3_count = substr_count($content, 'Feature 3');

echo "Feature 3 mentions in file: $feature3_count\n";

if ($feature3_count > 0) {
    echo "✅ Feature 3 content exists in the file\n";
} else {
    echo "❌ Feature 3 content not found\n";
}

// Test HTTP response
echo "Testing HTTP response...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/manufacturing_demo.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response length: " . strlen($response) . " characters\n";

$feature3_in_response = substr_count($response, 'Feature 3');
echo "Feature 3 mentions in response: $feature3_in_response\n";

if ($feature3_in_response > 0) {
    echo "✅ Feature 3 content is being served correctly\n";
} else {
    echo "❌ Feature 3 content not found in HTTP response\n";
    echo "Checking for PHP errors...\n";
    if (strpos($response, 'Fatal error') !== false || strpos($response, 'Parse error') !== false) {
        echo "PHP ERROR DETECTED:\n";
        echo substr($response, 0, 500) . "...\n";
    }
}
?>
