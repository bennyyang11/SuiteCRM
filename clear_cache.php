<?php
// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully\n";
} else {
    echo "OPcache not available\n";
}

// Clear file stat cache
clearstatcache();
echo "File stat cache cleared\n";

echo "Cache clearing complete\n";
?>
