<?php

// Complete fix for all remaining database calls

$filePath = 'include/Authentication/ModernAuth/AuthenticationController.php';
$content = file_get_contents($filePath);

// Replace all remaining getConnection() calls with SuiteCRM compatible methods
$patterns = [
    // Pattern 1: Simple query/execute without fetch
    '/\$stmt = \$db->getConnection\(\)->prepare\(\$([^)]+)\);\s*\$stmt->execute\(\[([^\]]*)\]\);/' => 
    'if (!empty($1)) { $query_tmp = str_replace(array_fill(0, substr_count($1, \'?\'), \'?\'), [$2], $1); $db->query($query_tmp); }',
    
    // Pattern 2: Query with fetch
    '/\$stmt = \$db->getConnection\(\)->prepare\(\$([^)]+)\);\s*\$stmt->execute\(\[([^\]]*)\]\);\s*\$result = \$stmt->fetch\(\);/' =>
    '$query_tmp = str_replace(array_fill(0, substr_count($1, \'?\'), \'?\'), [$2], $1); $db_result = $db->query($query_tmp); $result = $db_result ? $db->fetchByAssoc($db_result) : null;',
    
    // Pattern 3: Query with fetchColumn
    '/\$stmt = \$db->getConnection\(\)->prepare\(\$([^)]+)\);\s*\$stmt->execute\(\[([^\]]*)\]\);\s*return \$stmt->fetchColumn\(\);/' =>
    '$query_tmp = str_replace(array_fill(0, substr_count($1, \'?\'), \'?\'), [$2], $1); $db_result = $db->query($query_tmp); $row = $db_result ? $db->fetchByAssoc($db_result) : null; return $row ? array_values($row)[0] : 0;'
];

foreach ($patterns as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

// Manual fixes for specific complex cases
$manualFixes = [
    // Fix the lockAccount method issues
    '$checkStmt = $db->getConnection()->prepare($checkQuery);
        $checkStmt->execute([$username]);
        $currentAttempts = $checkStmt->fetchColumn() ?: 0;' =>
    '$checkQuery = str_replace(\'?\', $db->quoted($username), $checkQuery);
        $checkResult = $db->query($checkQuery);
        $checkRow = $checkResult ? $db->fetchByAssoc($checkResult) : null;
        $currentAttempts = $checkRow ? (int)$checkRow[\'failed_login_attempts\'] : 0;',
    
    '$lockStmt = $db->getConnection()->prepare($lockQuery);
        $lockStmt->execute([$newAttempts, $lockUntil, $username]);' =>
    '$lockQuery = str_replace([\'?\', \'?\', \'?\'], [$db->quoted($newAttempts), $db->quoted($lockUntil), $db->quoted($username)], $lockQuery);
        $db->query($lockQuery);',
    
    // Fix remaining individual calls
    '$stmt = $db->getConnection()->prepare($updateQuery);
        $now = date(\'Y-m-d H:i:s\');
        $stmt->execute([$now, $userId]);' =>
    '$now = date(\'Y-m-d H:i:s\');
        $updateQuery = str_replace([\'?\', \'?\'], [$db->quoted($now), $db->quoted($userId)], $updateQuery);
        $db->query($updateQuery);',
    
    '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();' =>
    '$query = str_replace(\'?\', $db->quoted($userId), $query);
        $result = $db->query($query);
        $row = $result ? $db->fetchByAssoc($result) : null;
        return $row ? (int)array_values($row)[0] : 0;',
    
    '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$username]);
        $result = $stmt->fetch();' =>
    '$query = str_replace(\'?\', $db->quoted($username), $query);
        $db_result = $db->query($query);
        $result = $db_result ? $db->fetchByAssoc($db_result) : null;',
    
    '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$action, $username, $ipAddress, $userAgent, $success, $details, $timestamp]);' =>
    '$query = str_replace([\'?\', \'?\', \'?\', \'?\', \'?\', \'?\', \'?\'], 
                        [$db->quoted($action), $db->quoted($username), $db->quoted($ipAddress), 
                         $db->quoted($userAgent), $db->quoted($success), $db->quoted($details), $db->quoted($timestamp)], 
                        $query);
        $db->query($query);'
];

foreach ($manualFixes as $old => $new) {
    $content = str_replace($old, $new, $content);
}

// Write back the fixed content
file_put_contents($filePath, $content);

echo "All remaining database calls have been fixed!\n";
