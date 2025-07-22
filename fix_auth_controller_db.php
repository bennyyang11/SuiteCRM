<?php

// Script to fix database calls in AuthenticationController.php

$filePath = 'include/Authentication/ModernAuth/AuthenticationController.php';
$content = file_get_contents($filePath);

// Array of replacements
$replacements = [
    // Fix getConnection() calls with proper SuiteCRM methods
    [
        'old' => '$stmt = $db->getConnection()->prepare($updateQuery);
        $now = date(\'Y-m-d H:i:s\');
        $stmt->execute([$newHash, $now, $now, $userId]);',
        'new' => '$now = date(\'Y-m-d H:i:s\');
        $updateQuery = str_replace([\'?\', \'?\', \'?\'], 
                                  [$db->quoted($newHash), $db->quoted($now), $db->quoted($userId)], 
                                  $updateQuery);
        $db->query($updateQuery);'
    ],
    [
        'old' => '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$username]);
        
        $result = $stmt->fetch();',
        'new' => '$query = str_replace(\'?\', $db->quoted($username), $query);
        $result = $db->query($query);
        $row = $result ? $db->fetchByAssoc($result) : null;'
    ],
    [
        'old' => '$stmt = $db->getConnection()->prepare($updateQuery);
        $now = date(\'Y-m-d H:i:s\');
        $stmt->execute([$now, $userId]);',
        'new' => '$now = date(\'Y-m-d H:i:s\');
        $updateQuery = str_replace([\'?\', \'?\'], 
                                  [$db->quoted($now), $db->quoted($userId)], 
                                  $updateQuery);
        $db->query($updateQuery);'
    ],
    [
        'old' => '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();',
        'new' => '$query = str_replace(\'?\', $db->quoted($userId), $query);
        $result = $db->query($query);
        $row = $result ? $db->fetchByAssoc($result) : null;
        return $row ? $row[\'failed_login_attempts\'] : 0;'
    ],
    [
        'old' => '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$username]);
        $result = $stmt->fetch();',
        'new' => '$query = str_replace(\'?\', $db->quoted($username), $query);
        $result = $db->query($query);
        $row = $result ? $db->fetchByAssoc($result) : null;'
    ],
    [
        'old' => '$checkStmt = $db->getConnection()->prepare($checkQuery);
        $checkStmt->execute([$username]);
        $currentAttempts = $checkStmt->fetchColumn() ?: 0;',
        'new' => '$checkQuery = str_replace(\'?\', $db->quoted($username), $checkQuery);
        $checkResult = $db->query($checkQuery);
        $checkRow = $checkResult ? $db->fetchByAssoc($checkResult) : null;
        $currentAttempts = $checkRow ? $checkRow[\'failed_login_attempts\'] : 0;'
    ],
    [
        'old' => '$lockStmt = $db->getConnection()->prepare($lockQuery);
        $lockStmt->execute([$newAttempts, $lockUntil, $username]);',
        'new' => '$lockQuery = str_replace([\'?\', \'?\', \'?\'], 
                                         [$db->quoted($newAttempts), $db->quoted($lockUntil), $db->quoted($username)], 
                                         $lockQuery);
        $db->query($lockQuery);'
    ],
    [
        'old' => '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$username]);
        
        // Reset failed attempts on successful login
        $resetQuery = "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE user_name = ? OR email_address = ?";
        $resetStmt = $db->getConnection()->prepare($resetQuery);
        $resetStmt->execute([$username, $username]);',
        'new' => '$query = str_replace(\'?\', $db->quoted($username), $query);
        $result = $db->query($query);
        
        // Reset failed attempts on successful login
        $resetQuery = "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL 
                       WHERE user_name = " . $db->quoted($username) . " OR email_address = " . $db->quoted($username);
        $db->query($resetQuery);'
    ],
    [
        'old' => '$stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$action, $username, $ipAddress, $userAgent, $success, $details, $timestamp]);',
        'new' => '$query = str_replace([\'?\', \'?\', \'?\', \'?\', \'?\', \'?\', \'?\'], 
                                     [$db->quoted($action), $db->quoted($username), $db->quoted($ipAddress), 
                                      $db->quoted($userAgent), $db->quoted($success), $db->quoted($details), $db->quoted($timestamp)], 
                                     $query);
        $db->query($query);'
    ]
];

// Apply all replacements
foreach ($replacements as $replacement) {
    $content = str_replace($replacement['old'], $replacement['new'], $content);
}

// Write the updated content back
file_put_contents($filePath, $content);

echo "AuthenticationController.php database calls have been fixed!\n";
