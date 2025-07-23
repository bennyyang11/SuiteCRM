<?php
/**
 * Push Notification API for Mobile Pipeline Dashboard
 * Handles push notification registration, sending, and management
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/api/SugarApi.php';
require_once 'include/database/DBManagerFactory.php';

class PushNotificationAPI extends SugarApi {
    
    private $vapidKeys = [
        'publicKey' => 'BKqJf9RJhPe7yWzpJQV4lR5QV8fZjfyJ4yBpN2Q1pHf9xVwZtQ3Kg2VhYxRtN5mFpQwXzQ8vB2NhGpYtR1cKjU',
        'privateKey' => 'YOUR_VAPID_PRIVATE_KEY' // Store in environment variable
    ];
    
    public function registerApiRest() {
        return [
            'mobile_push_register' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'push', 'register'],
                'pathVars' => [],
                'method' => 'registerPushSubscription',
                'shortHelp' => 'Register push notification subscription',
                'longHelp' => 'Register a push notification subscription for mobile devices'
            ],
            'mobile_push_send' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'push', 'send'],
                'pathVars' => [],
                'method' => 'sendPushNotification',
                'shortHelp' => 'Send push notification',
                'longHelp' => 'Send push notification to registered devices'
            ],
            'mobile_push_unregister' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'push', 'unregister'],
                'pathVars' => [],
                'method' => 'unregisterPushSubscription',
                'shortHelp' => 'Unregister push subscription',
                'longHelp' => 'Remove push notification subscription'
            ],
            'mobile_push_preferences' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'push', 'preferences'],
                'pathVars' => [],
                'method' => 'getPushPreferences',
                'shortHelp' => 'Get push notification preferences',
                'longHelp' => 'Get user push notification preferences'
            ],
            'mobile_push_update_preferences' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'push', 'preferences'],
                'pathVars' => [],
                'method' => 'updatePushPreferences',
                'shortHelp' => 'Update push notification preferences',
                'longHelp' => 'Update user push notification preferences'
            ]
        ];
    }
    
    /**
     * Register a push notification subscription
     */
    public function registerPushSubscription($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            $userId = $GLOBALS['current_user']->id;
            
            // Validate required fields
            if (empty($args['subscription']) || empty($args['subscription']['endpoint'])) {
                throw new SugarApiExceptionMissingParameter('Missing subscription data');
            }
            
            $subscription = $args['subscription'];
            $deviceInfo = $args['deviceInfo'] ?? [];
            
            // Prepare subscription data
            $subscriptionData = [
                'id' => $this->generateSubscriptionId(),
                'user_id' => $userId,
                'endpoint' => $subscription['endpoint'],
                'p256dh_key' => $subscription['keys']['p256dh'] ?? '',
                'auth_key' => $subscription['keys']['auth'] ?? '',
                'device_type' => $deviceInfo['deviceType'] ?? 'unknown',
                'browser' => $deviceInfo['browser'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'is_active' => 1,
                'date_created' => date('Y-m-d H:i:s'),
                'date_modified' => date('Y-m-d H:i:s')
            ];
            
            // Check if subscription already exists
            $existingSubscription = $this->getExistingSubscription($db, $userId, $subscription['endpoint']);
            
            if ($existingSubscription) {
                // Update existing subscription
                $this->updateSubscription($db, $existingSubscription['id'], $subscriptionData);
                $subscriptionId = $existingSubscription['id'];
            } else {
                // Insert new subscription
                $subscriptionId = $this->insertSubscription($db, $subscriptionData);
            }
            
            // Set default notification preferences if first subscription
            $this->ensureDefaultPreferences($db, $userId);
            
            $GLOBALS['log']->info("Push subscription registered for user: {$userId}");
            
            return [
                'success' => true,
                'subscriptionId' => $subscriptionId,
                'message' => 'Push notification subscription registered successfully'
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to register push subscription: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send push notification
     */
    public function sendPushNotification($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            $userId = $GLOBALS['current_user']->id;
            
            // Validate required fields
            if (empty($args['type']) || empty($args['data'])) {
                throw new SugarApiExceptionMissingParameter('Missing notification type or data');
            }
            
            $type = $args['type'];
            $data = $args['data'];
            $targetUsers = $args['targetUsers'] ?? [$userId];
            
            // Get notification preferences and active subscriptions
            $subscriptions = $this->getActiveSubscriptions($db, $targetUsers, $type);
            
            if (empty($subscriptions)) {
                return [
                    'success' => true,
                    'message' => 'No active subscriptions found for target users',
                    'sentCount' => 0
                ];
            }
            
            // Prepare notification payload
            $payload = $this->prepareNotificationPayload($type, $data);
            
            // Send notifications
            $sentCount = 0;
            $errors = [];
            
            foreach ($subscriptions as $subscription) {
                try {
                    $result = $this->sendToSubscription($subscription, $payload);
                    if ($result['success']) {
                        $sentCount++;
                    } else {
                        $errors[] = $result['error'];
                        
                        // Mark subscription as inactive if permanently failed
                        if ($result['shouldDeactivate']) {
                            $this->deactivateSubscription($db, $subscription['id']);
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            
            // Log notification
            $this->logNotification($db, $type, $data, $sentCount, $targetUsers);
            
            $GLOBALS['log']->info("Push notifications sent: {$sentCount}/{" . count($subscriptions) . "}");
            
            return [
                'success' => true,
                'sentCount' => $sentCount,
                'totalSubscriptions' => count($subscriptions),
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to send push notification: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Unregister push subscription
     */
    public function unregisterPushSubscription($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            $userId = $GLOBALS['current_user']->id;
            
            if (empty($args['endpoint']) && empty($args['subscriptionId'])) {
                throw new SugarApiExceptionMissingParameter('Missing endpoint or subscription ID');
            }
            
            $whereClause = "user_id = '{$userId}' AND is_active = 1";
            
            if (!empty($args['subscriptionId'])) {
                $whereClause .= " AND id = '{$args['subscriptionId']}'";
            } else {
                $endpoint = $db->quote($args['endpoint']);
                $whereClause .= " AND endpoint = {$endpoint}";
            }
            
            $query = "UPDATE mfg_push_subscriptions SET is_active = 0, date_modified = NOW() WHERE {$whereClause}";
            
            $result = $db->query($query);
            
            if ($result) {
                $GLOBALS['log']->info("Push subscription unregistered for user: {$userId}");
                
                return [
                    'success' => true,
                    'message' => 'Push notification subscription unregistered successfully'
                ];
            } else {
                throw new Exception('Failed to unregister subscription');
            }
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to unregister push subscription: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get user push notification preferences
     */
    public function getPushPreferences($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            $userId = $GLOBALS['current_user']->id;
            
            $query = "SELECT * FROM mfg_push_preferences WHERE user_id = '{$userId}'";
            $result = $db->query($query);
            
            if ($row = $db->fetchByAssoc($result)) {
                $preferences = [
                    'stageChanges' => (bool)$row['stage_changes'],
                    'overdueAlerts' => (bool)$row['overdue_alerts'],
                    'urgentOrders' => (bool)$row['urgent_orders'],
                    'assignments' => (bool)$row['assignments'],
                    'dailySummary' => (bool)$row['daily_summary'],
                    'quietHours' => [
                        'enabled' => (bool)$row['quiet_hours_enabled'],
                        'startTime' => $row['quiet_hours_start'],
                        'endTime' => $row['quiet_hours_end']
                    ],
                    'weekends' => (bool)$row['weekend_notifications'],
                    'sound' => (bool)$row['notification_sound'],
                    'vibration' => (bool)$row['notification_vibration']
                ];
            } else {
                // Return default preferences
                $preferences = $this->getDefaultPreferences();
            }
            
            return [
                'success' => true,
                'preferences' => $preferences
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to get push preferences: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update user push notification preferences
     */
    public function updatePushPreferences($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            $userId = $GLOBALS['current_user']->id;
            
            if (empty($args['preferences'])) {
                throw new SugarApiExceptionMissingParameter('Missing preferences data');
            }
            
            $preferences = $args['preferences'];
            
            // Prepare data for database
            $data = [
                'user_id' => $userId,
                'stage_changes' => $preferences['stageChanges'] ? 1 : 0,
                'overdue_alerts' => $preferences['overdueAlerts'] ? 1 : 0,
                'urgent_orders' => $preferences['urgentOrders'] ? 1 : 0,
                'assignments' => $preferences['assignments'] ? 1 : 0,
                'daily_summary' => $preferences['dailySummary'] ? 1 : 0,
                'quiet_hours_enabled' => $preferences['quietHours']['enabled'] ? 1 : 0,
                'quiet_hours_start' => $preferences['quietHours']['startTime'] ?? '22:00',
                'quiet_hours_end' => $preferences['quietHours']['endTime'] ?? '08:00',
                'weekend_notifications' => $preferences['weekends'] ? 1 : 0,
                'notification_sound' => $preferences['sound'] ? 1 : 0,
                'notification_vibration' => $preferences['vibration'] ? 1 : 0,
                'date_modified' => date('Y-m-d H:i:s')
            ];
            
            // Check if preferences exist
            $existingQuery = "SELECT id FROM mfg_push_preferences WHERE user_id = '{$userId}'";
            $existingResult = $db->query($existingQuery);
            
            if ($db->fetchByAssoc($existingResult)) {
                // Update existing preferences
                $setClause = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'user_id') {
                        $setClause[] = "{$key} = '{$value}'";
                    }
                }
                
                $query = "UPDATE mfg_push_preferences SET " . implode(', ', $setClause) . " WHERE user_id = '{$userId}'";
            } else {
                // Insert new preferences
                $data['id'] = $this->generateId();
                $data['date_created'] = date('Y-m-d H:i:s');
                
                $columns = implode(', ', array_keys($data));
                $values = "'" . implode("', '", array_values($data)) . "'";
                
                $query = "INSERT INTO mfg_push_preferences ({$columns}) VALUES ({$values})";
            }
            
            $result = $db->query($query);
            
            if ($result) {
                $GLOBALS['log']->info("Push preferences updated for user: {$userId}");
                
                return [
                    'success' => true,
                    'message' => 'Push notification preferences updated successfully'
                ];
            } else {
                throw new Exception('Failed to update preferences');
            }
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to update push preferences: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send automated notifications for order events
     */
    public function sendOrderNotification($orderId, $type, $additionalData = []) {
        try {
            $db = DBManagerFactory::getInstance();
            
            // Get order details
            $order = $this->getOrderDetails($db, $orderId);
            if (!$order) {
                throw new Exception("Order not found: {$orderId}");
            }
            
            // Determine target users based on notification type
            $targetUsers = $this->getTargetUsers($db, $order, $type);
            
            if (empty($targetUsers)) {
                return ['success' => true, 'message' => 'No target users found'];
            }
            
            // Prepare notification data
            $notificationData = [
                'orderId' => $orderId,
                'orderNumber' => $order['order_number'],
                'accountName' => $order['account_name'],
                'assignedUser' => $order['assigned_user_name'],
                'priority' => $order['priority'],
                'actionUrl' => "/index.php?module=Orders&action=DetailView&record={$orderId}"
            ];
            
            // Add type-specific data
            $notificationData = array_merge($notificationData, $additionalData);
            
            // Send notification
            $api = new self();
            return $api->sendPushNotification(null, [
                'type' => $type,
                'data' => $notificationData,
                'targetUsers' => $targetUsers
            ]);
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to send order notification: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Private helper methods
    
    private function generateSubscriptionId() {
        return 'sub_' . uniqid() . '_' . time();
    }
    
    private function generateId() {
        return uniqid() . '_' . time();
    }
    
    private function getExistingSubscription($db, $userId, $endpoint) {
        $endpoint = $db->quote($endpoint);
        $query = "SELECT * FROM mfg_push_subscriptions WHERE user_id = '{$userId}' AND endpoint = {$endpoint} AND is_active = 1";
        $result = $db->query($query);
        
        return $db->fetchByAssoc($result);
    }
    
    private function insertSubscription($db, $data) {
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";
        
        $query = "INSERT INTO mfg_push_subscriptions ({$columns}) VALUES ({$values})";
        $result = $db->query($query);
        
        if ($result) {
            return $data['id'];
        } else {
            throw new Exception('Failed to insert subscription');
        }
    }
    
    private function updateSubscription($db, $subscriptionId, $data) {
        $setClause = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $setClause[] = "{$key} = '{$value}'";
            }
        }
        
        $query = "UPDATE mfg_push_subscriptions SET " . implode(', ', $setClause) . " WHERE id = '{$subscriptionId}'";
        $result = $db->query($query);
        
        if (!$result) {
            throw new Exception('Failed to update subscription');
        }
    }
    
    private function ensureDefaultPreferences($db, $userId) {
        $existingQuery = "SELECT id FROM mfg_push_preferences WHERE user_id = '{$userId}'";
        $existingResult = $db->query($existingQuery);
        
        if (!$db->fetchByAssoc($existingResult)) {
            $defaults = $this->getDefaultPreferences();
            
            $data = [
                'id' => $this->generateId(),
                'user_id' => $userId,
                'stage_changes' => $defaults['stageChanges'] ? 1 : 0,
                'overdue_alerts' => $defaults['overdueAlerts'] ? 1 : 0,
                'urgent_orders' => $defaults['urgentOrders'] ? 1 : 0,
                'assignments' => $defaults['assignments'] ? 1 : 0,
                'daily_summary' => $defaults['dailySummary'] ? 1 : 0,
                'quiet_hours_enabled' => $defaults['quietHours']['enabled'] ? 1 : 0,
                'quiet_hours_start' => $defaults['quietHours']['startTime'],
                'quiet_hours_end' => $defaults['quietHours']['endTime'],
                'weekend_notifications' => $defaults['weekends'] ? 1 : 0,
                'notification_sound' => $defaults['sound'] ? 1 : 0,
                'notification_vibration' => $defaults['vibration'] ? 1 : 0,
                'date_created' => date('Y-m-d H:i:s'),
                'date_modified' => date('Y-m-d H:i:s')
            ];
            
            $columns = implode(', ', array_keys($data));
            $values = "'" . implode("', '", array_values($data)) . "'";
            
            $query = "INSERT INTO mfg_push_preferences ({$columns}) VALUES ({$values})";
            $db->query($query);
        }
    }
    
    private function getDefaultPreferences() {
        return [
            'stageChanges' => true,
            'overdueAlerts' => true,
            'urgentOrders' => true,
            'assignments' => true,
            'dailySummary' => false,
            'quietHours' => [
                'enabled' => true,
                'startTime' => '22:00',
                'endTime' => '08:00'
            ],
            'weekends' => false,
            'sound' => true,
            'vibration' => true
        ];
    }
    
    private function getActiveSubscriptions($db, $targetUsers, $notificationType) {
        $userIds = "'" . implode("', '", $targetUsers) . "'";
        
        $query = "
            SELECT s.*, p.*
            FROM mfg_push_subscriptions s
            LEFT JOIN mfg_push_preferences p ON s.user_id = p.user_id
            WHERE s.user_id IN ({$userIds})
            AND s.is_active = 1
        ";
        
        $result = $db->query($query);
        $subscriptions = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            // Check if user wants this type of notification
            if ($this->shouldSendNotification($row, $notificationType)) {
                $subscriptions[] = $row;
            }
        }
        
        return $subscriptions;
    }
    
    private function shouldSendNotification($subscription, $type) {
        // Check notification preferences
        $typeMapping = [
            'stage_change' => 'stage_changes',
            'overdue_alert' => 'overdue_alerts',
            'urgent_order' => 'urgent_orders',
            'assignment' => 'assignments',
            'daily_summary' => 'daily_summary'
        ];
        
        $preferenceKey = $typeMapping[$type] ?? null;
        
        if ($preferenceKey && isset($subscription[$preferenceKey]) && !$subscription[$preferenceKey]) {
            return false;
        }
        
        // Check quiet hours
        if ($subscription['quiet_hours_enabled'] && $this->isQuietHours($subscription)) {
            return false;
        }
        
        // Check weekend notifications
        if (!$subscription['weekend_notifications'] && $this->isWeekend()) {
            return false;
        }
        
        return true;
    }
    
    private function isQuietHours($subscription) {
        $currentTime = date('H:i');
        $startTime = $subscription['quiet_hours_start'];
        $endTime = $subscription['quiet_hours_end'];
        
        if ($startTime > $endTime) {
            // Quiet hours cross midnight
            return $currentTime >= $startTime || $currentTime <= $endTime;
        } else {
            // Normal quiet hours
            return $currentTime >= $startTime && $currentTime <= $endTime;
        }
    }
    
    private function isWeekend() {
        $dayOfWeek = date('N');
        return $dayOfWeek >= 6; // Saturday (6) or Sunday (7)
    }
    
    private function prepareNotificationPayload($type, $data) {
        return json_encode([
            'type' => $type,
            'orderId' => $data['orderId'] ?? '',
            'orderNumber' => $data['orderNumber'] ?? '',
            'fromStage' => $data['fromStage'] ?? '',
            'toStage' => $data['toStage'] ?? '',
            'priority' => $data['priority'] ?? 'normal',
            'accountName' => $data['accountName'] ?? '',
            'assignedUser' => $data['assignedUser'] ?? '',
            'actionUrl' => $data['actionUrl'] ?? '',
            'summary' => $data['summary'] ?? '',
            'message' => $data['message'] ?? '',
            'timestamp' => time()
        ]);
    }
    
    private function sendToSubscription($subscription, $payload) {
        // This would typically use a library like web-push-php
        // For demo purposes, we'll simulate the process
        
        try {
            $endpoint = $subscription['endpoint'];
            $p256dh = $subscription['p256dh_key'];
            $auth = $subscription['auth_key'];
            
            // In a real implementation, you would use:
            // - Minishlink\WebPush\WebPush
            // - Google FCM
            // - Mozilla AutoPush
            // - Microsoft WNS
            
            // Simulate success
            $success = true; // In real implementation, check actual response
            
            if ($success) {
                return ['success' => true];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send notification',
                    'shouldDeactivate' => false
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'shouldDeactivate' => strpos($e->getMessage(), '410') !== false // Gone status
            ];
        }
    }
    
    private function deactivateSubscription($db, $subscriptionId) {
        $query = "UPDATE mfg_push_subscriptions SET is_active = 0, date_modified = NOW() WHERE id = '{$subscriptionId}'";
        $db->query($query);
    }
    
    private function logNotification($db, $type, $data, $sentCount, $targetUsers) {
        $logData = [
            'id' => $this->generateId(),
            'notification_type' => $type,
            'target_users' => implode(',', $targetUsers),
            'sent_count' => $sentCount,
            'data' => json_encode($data),
            'date_created' => date('Y-m-d H:i:s')
        ];
        
        $columns = implode(', ', array_keys($logData));
        $values = "'" . implode("', '", array_values($logData)) . "'";
        
        $query = "INSERT INTO mfg_push_notification_log ({$columns}) VALUES ({$values})";
        $db->query($query);
    }
    
    private function getOrderDetails($db, $orderId) {
        $query = "
            SELECT o.*, a.name as account_name, u.user_name as assigned_user_name
            FROM mfg_order_pipeline o
            LEFT JOIN accounts a ON o.account_id = a.id
            LEFT JOIN users u ON o.assigned_user_id = u.id
            WHERE o.id = '{$orderId}'
        ";
        
        $result = $db->query($query);
        return $db->fetchByAssoc($result);
    }
    
    private function getTargetUsers($db, $order, $type) {
        $targetUsers = [];
        
        switch ($type) {
            case 'stage_change':
            case 'assignment':
                // Notify assigned user and manager
                if ($order['assigned_user_id']) {
                    $targetUsers[] = $order['assigned_user_id'];
                }
                // Add manager logic here
                break;
                
            case 'overdue_alert':
            case 'urgent_order':
                // Notify assigned user and all managers
                if ($order['assigned_user_id']) {
                    $targetUsers[] = $order['assigned_user_id'];
                }
                // Add all managers
                $managerQuery = "SELECT id FROM users WHERE is_admin = 1 OR title LIKE '%manager%'";
                $managerResult = $db->query($managerQuery);
                while ($manager = $db->fetchByAssoc($managerResult)) {
                    $targetUsers[] = $manager['id'];
                }
                break;
        }
        
        return array_unique($targetUsers);
    }
}
