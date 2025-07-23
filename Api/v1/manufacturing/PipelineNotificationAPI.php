<?php
/**
 * Pipeline Notification API
 * Handles email/SMS notifications for pipeline stage changes
 */

if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once 'include/api/SugarApi.php';
require_once 'include/utils.php';

class PipelineNotificationAPI extends SugarApi
{
    public function registerApiRest()
    {
        return [
            'sendStageChangeNotification' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'notifications', 'stage-change'],
                'pathVars' => [],
                'method' => 'sendStageChangeNotification',
                'shortHelp' => 'Send notification for pipeline stage change',
                'longHelp' => 'Sends email/SMS notifications when pipeline stages change',
            ],
            'sendOverdueAlert' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'notifications', 'overdue-alert'],
                'pathVars' => [],
                'method' => 'sendOverdueAlert',
                'shortHelp' => 'Send overdue pipeline alert',
                'longHelp' => 'Sends notifications for overdue pipeline items',
            ],
            'getUserNotificationPreferences' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'notifications', 'preferences', '?'],
                'pathVars' => ['userId'],
                'method' => 'getUserNotificationPreferences',
                'shortHelp' => 'Get user notification preferences',
                'longHelp' => 'Retrieves notification preferences for a specific user',
            ],
            'updateNotificationPreferences' => [
                'reqType' => 'PUT',
                'path' => ['manufacturing', 'notifications', 'preferences', '?'],
                'pathVars' => ['userId'],
                'method' => 'updateNotificationPreferences',
                'shortHelp' => 'Update user notification preferences',
                'longHelp' => 'Updates notification preferences for a specific user',
            ],
            'sendDailySummary' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'notifications', 'daily-summary'],
                'pathVars' => [],
                'method' => 'sendDailySummary',
                'shortHelp' => 'Send daily pipeline summary',
                'longHelp' => 'Sends daily summary notifications to managers and sales reps',
            ],
        ];
    }

    /**
     * Send stage change notification
     */
    public function sendStageChangeNotification(ServiceBase $api, array $args)
    {
        global $db, $current_user;

        try {
            // Validate required parameters
            $required = ['pipelineId', 'fromStage', 'toStage', 'userId'];
            foreach ($required as $field) {
                if (empty($args[$field])) {
                    throw new SugarApiExceptionMissingParameter("Missing required parameter: $field");
                }
            }

            $pipelineId = $args['pipelineId'];
            $fromStage = $args['fromStage'];
            $toStage = $args['toStage'];
            $userId = $args['userId'];

            // Get pipeline details
            $pipeline = $this->getPipelineDetails($pipelineId);
            if (!$pipeline) {
                throw new SugarApiExceptionNotFound('Pipeline not found');
            }

            // Get notification preferences for user
            $preferences = $this->getUserNotificationPreferencesData($userId);
            
            // Filter preferences for stage change notifications
            $stageChangePrefs = array_filter($preferences, function($pref) {
                return $pref['notification_type'] === 'stage_change' && $pref['enabled'] == 1;
            });

            $notifications = [];
            
            foreach ($stageChangePrefs as $pref) {
                $notificationId = create_guid();
                
                // Generate notification content
                $content = $this->generateStageChangeContent($pipeline, $fromStage, $toStage, $pref['delivery_method']);
                
                // Queue notification
                $this->queueNotification([
                    'id' => $notificationId,
                    'pipeline_id' => $pipelineId,
                    'recipient_user_id' => $userId,
                    'notification_type' => 'stage_change',
                    'delivery_method' => $pref['delivery_method'],
                    'priority' => $pipeline['priority'] === 'urgent' ? 'urgent' : 'medium',
                    'subject' => $content['subject'],
                    'message' => $content['message'],
                    'message_html' => $content['html'] ?? null,
                    'action_url' => $content['action_url'],
                    'scheduled_time' => date('Y-m-d H:i:s'),
                    'created_by' => $current_user->id
                ]);

                // Immediately send high priority notifications
                if ($pipeline['priority'] === 'urgent' || $pref['delivery_method'] === 'sms') {
                    $this->processNotification($notificationId);
                }

                $notifications[] = [
                    'id' => $notificationId,
                    'delivery_method' => $pref['delivery_method'],
                    'status' => 'queued'
                ];
            }

            // Also notify account owner and assigned user
            if ($pipeline['assigned_user_id'] !== $userId) {
                $this->sendAdditionalNotifications($pipeline, $fromStage, $toStage, ['assigned_user']);
            }

            return [
                'status' => 'success',
                'message' => 'Stage change notifications queued successfully',
                'notifications' => $notifications,
                'pipeline' => [
                    'id' => $pipelineId,
                    'pipeline_number' => $pipeline['pipeline_number'],
                    'from_stage' => $fromStage,
                    'to_stage' => $toStage
                ]
            ];

        } catch (Exception $e) {
            $GLOBALS['log']->error("Pipeline notification error: " . $e->getMessage());
            throw new SugarApiExceptionError('Failed to send notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send overdue alert notifications
     */
    public function sendOverdueAlert(ServiceBase $api, array $args)
    {
        global $db;

        try {
            $thresholdHours = $args['thresholdHours'] ?? 48;
            
            // Find overdue pipelines
            $overdueQuery = "
                SELECT p.*, a.name as account_name, u.user_name, u.first_name, u.last_name, u.email1
                FROM mfg_order_pipeline p
                LEFT JOIN accounts a ON p.account_id = a.id
                LEFT JOIN users u ON p.assigned_user_id = u.id
                WHERE p.deleted = 0 
                AND p.status = 'active'
                AND p.current_stage != 'invoiced_delivered'
                AND TIMESTAMPDIFF(HOUR, p.stage_entered_date, NOW()) > ?
            ";

            $stmt = $db->getConnection()->prepare($overdueQuery);
            $stmt->execute([$thresholdHours]);
            $overduePipelines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $notifications = [];

            foreach ($overduePipelines as $pipeline) {
                // Get user preferences for overdue alerts
                $preferences = $this->getUserNotificationPreferencesData($pipeline['assigned_user_id']);
                $overduePrefs = array_filter($preferences, function($pref) use ($thresholdHours) {
                    return $pref['notification_type'] === 'overdue_alert' 
                        && $pref['enabled'] == 1
                        && ($pref['threshold_hours'] ?? 48) <= $thresholdHours;
                });

                foreach ($overduePrefs as $pref) {
                    $notificationId = create_guid();
                    $content = $this->generateOverdueAlertContent($pipeline, $pref['delivery_method']);
                    
                    $this->queueNotification([
                        'id' => $notificationId,
                        'pipeline_id' => $pipeline['id'],
                        'recipient_user_id' => $pipeline['assigned_user_id'],
                        'notification_type' => 'overdue_alert',
                        'delivery_method' => $pref['delivery_method'],
                        'priority' => 'high',
                        'subject' => $content['subject'],
                        'message' => $content['message'],
                        'message_html' => $content['html'] ?? null,
                        'action_url' => $content['action_url'],
                        'scheduled_time' => date('Y-m-d H:i:s'),
                        'created_by' => $pipeline['assigned_user_id']
                    ]);

                    $notifications[] = [
                        'pipeline_id' => $pipeline['id'],
                        'pipeline_number' => $pipeline['pipeline_number'],
                        'notification_id' => $notificationId,
                        'delivery_method' => $pref['delivery_method']
                    ];
                }
            }

            return [
                'status' => 'success',
                'message' => 'Overdue alert notifications queued',
                'notifications' => $notifications,
                'overdue_count' => count($overduePipelines)
            ];

        } catch (Exception $e) {
            $GLOBALS['log']->error("Overdue alert error: " . $e->getMessage());
            throw new SugarApiExceptionError('Failed to send overdue alerts: ' . $e->getMessage());
        }
    }

    /**
     * Get user notification preferences
     */
    public function getUserNotificationPreferences(ServiceBase $api, array $args)
    {
        $userId = $args['userId'];
        
        if (empty($userId)) {
            throw new SugarApiExceptionMissingParameter('User ID is required');
        }

        try {
            $preferences = $this->getUserNotificationPreferencesData($userId);
            
            // Group by notification type
            $grouped = [];
            foreach ($preferences as $pref) {
                $type = $pref['notification_type'];
                if (!isset($grouped[$type])) {
                    $grouped[$type] = [
                        'type' => $type,
                        'methods' => []
                    ];
                }
                $grouped[$type]['methods'][] = $pref;
            }

            return [
                'status' => 'success',
                'user_id' => $userId,
                'preferences' => array_values($grouped)
            ];

        } catch (Exception $e) {
            throw new SugarApiExceptionError('Failed to get notification preferences: ' . $e->getMessage());
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(ServiceBase $api, array $args)
    {
        global $db, $current_user;
        
        $userId = $args['userId'];
        $preferences = $args['preferences'] ?? [];

        if (empty($userId)) {
            throw new SugarApiExceptionMissingParameter('User ID is required');
        }

        try {
            $db->startTransaction();

            // Delete existing preferences
            $deleteQuery = "DELETE FROM mfg_notification_preferences WHERE user_id = ? AND deleted = 0";
            $db->getConnection()->prepare($deleteQuery)->execute([$userId]);

            // Insert new preferences
            $insertQuery = "
                INSERT INTO mfg_notification_preferences (
                    id, user_id, notification_type, delivery_method, enabled, 
                    threshold_hours, schedule_time, date_entered, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ";

            $stmt = $db->getConnection()->prepare($insertQuery);

            foreach ($preferences as $pref) {
                $stmt->execute([
                    create_guid(),
                    $userId,
                    $pref['notification_type'],
                    $pref['delivery_method'],
                    $pref['enabled'] ? 1 : 0,
                    $pref['threshold_hours'] ?? null,
                    $pref['schedule_time'] ?? null,
                    $current_user->id
                ]);
            }

            $db->commit();

            return [
                'status' => 'success',
                'message' => 'Notification preferences updated successfully',
                'user_id' => $userId,
                'updated_count' => count($preferences)
            ];

        } catch (Exception $e) {
            $db->rollback();
            throw new SugarApiExceptionError('Failed to update notification preferences: ' . $e->getMessage());
        }
    }

    /**
     * Send daily summary notifications
     */
    public function sendDailySummary(ServiceBase $api, array $args)
    {
        global $db;

        try {
            // Get users who want daily summaries
            $summaryQuery = "
                SELECT DISTINCT np.user_id, u.user_name, u.first_name, u.last_name, u.email1
                FROM mfg_notification_preferences np
                JOIN users u ON np.user_id = u.id
                WHERE np.notification_type = 'daily_summary'
                AND np.enabled = 1
                AND np.deleted = 0
                AND u.deleted = 0
                AND u.status = 'Active'
            ";

            $users = $db->getConnection()->query($summaryQuery)->fetchAll(PDO::FETCH_ASSOC);
            $summaries = [];

            foreach ($users as $user) {
                $summaryData = $this->generateDailySummaryData($user['user_id']);
                
                if ($summaryData['has_data']) {
                    $notificationId = create_guid();
                    $content = $this->generateDailySummaryContent($summaryData, 'email');
                    
                    $this->queueNotification([
                        'id' => $notificationId,
                        'recipient_user_id' => $user['user_id'],
                        'notification_type' => 'daily_summary',
                        'delivery_method' => 'email',
                        'priority' => 'medium',
                        'subject' => $content['subject'],
                        'message' => $content['message'],
                        'message_html' => $content['html'],
                        'scheduled_time' => date('Y-m-d H:i:s'),
                        'created_by' => $user['user_id']
                    ]);

                    $summaries[] = [
                        'user_id' => $user['user_id'],
                        'user_name' => $user['user_name'],
                        'notification_id' => $notificationId,
                        'pipeline_count' => $summaryData['pipeline_count']
                    ];
                }
            }

            return [
                'status' => 'success',
                'message' => 'Daily summaries queued successfully',
                'summaries' => $summaries
            ];

        } catch (Exception $e) {
            $GLOBALS['log']->error("Daily summary error: " . $e->getMessage());
            throw new SugarApiExceptionError('Failed to send daily summaries: ' . $e->getMessage());
        }
    }

    // Helper methods

    private function getPipelineDetails($pipelineId)
    {
        global $db;
        
        $query = "
            SELECT p.*, a.name as account_name, u.user_name as assigned_user_name
            FROM mfg_order_pipeline p
            LEFT JOIN accounts a ON p.account_id = a.id
            LEFT JOIN users u ON p.assigned_user_id = u.id
            WHERE p.id = ? AND p.deleted = 0
        ";
        
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$pipelineId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getUserNotificationPreferencesData($userId)
    {
        global $db;
        
        $query = "
            SELECT * FROM mfg_notification_preferences 
            WHERE user_id = ? AND deleted = 0
            ORDER BY notification_type, delivery_method
        ";
        
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function queueNotification($data)
    {
        global $db;
        
        $query = "
            INSERT INTO mfg_notification_queue (
                id, pipeline_id, recipient_user_id, notification_type, delivery_method,
                priority, subject, message, message_html, action_url, status,
                scheduled_time, date_entered, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), ?)
        ";
        
        $stmt = $db->getConnection()->prepare($query);
        return $stmt->execute([
            $data['id'],
            $data['pipeline_id'] ?? null,
            $data['recipient_user_id'],
            $data['notification_type'],
            $data['delivery_method'],
            $data['priority'],
            $data['subject'],
            $data['message'],
            $data['message_html'] ?? null,
            $data['action_url'] ?? null,
            $data['scheduled_time'],
            $data['created_by']
        ]);
    }

    private function generateStageChangeContent($pipeline, $fromStage, $toStage, $deliveryMethod)
    {
        $baseUrl = $GLOBALS['sugar_config']['site_url'];
        $dashboardUrl = $baseUrl . '/index.php?module=Manufacturing&action=PipelineDashboard&pipeline=' . $pipeline['id'];
        
        $fromStageDisplay = ucwords(str_replace('_', ' ', $fromStage));
        $toStageDisplay = ucwords(str_replace('_', ' ', $toStage));
        
        $subject = "Pipeline Update: {$pipeline['pipeline_number']} → {$toStageDisplay}";
        
        if ($deliveryMethod === 'sms') {
            $message = "Pipeline {$pipeline['pipeline_number']} moved from {$fromStageDisplay} to {$toStageDisplay}. View: {$dashboardUrl}";
            return [
                'subject' => $subject,
                'message' => $message,
                'action_url' => $dashboardUrl
            ];
        }
        
        // Email content
        $message = "Order pipeline {$pipeline['pipeline_number']} for {$pipeline['account_name']} has been moved from {$fromStageDisplay} to {$toStageDisplay}.";
        
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #2563eb; color: white; padding: 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>Pipeline Status Update</h1>
            </div>
            
            <div style='padding: 30px; background: #f9fafb;'>
                <h2 style='color: #1f2937; margin-top: 0;'>Order Moved to {$toStageDisplay}</h2>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Pipeline:</td>
                            <td style='padding: 8px 0; color: #6b7280;'>{$pipeline['pipeline_number']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Client:</td>
                            <td style='padding: 8px 0; color: #6b7280;'>{$pipeline['account_name']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Value:</td>
                            <td style='padding: 8px 0; color: #6b7280;'>\$" . number_format($pipeline['total_value'], 2) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Status:</td>
                            <td style='padding: 8px 0;'>
                                <span style='background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>
                                    {$toStageDisplay}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$dashboardUrl}' style='background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                        View Pipeline Dashboard
                    </a>
                </div>
                
                <p style='color: #6b7280; font-size: 14px; margin-top: 30px;'>
                    This notification was sent because you are assigned to this pipeline or have opted to receive stage change updates.
                </p>
            </div>
        </div>";
        
        return [
            'subject' => $subject,
            'message' => $message,
            'html' => $html,
            'action_url' => $dashboardUrl
        ];
    }

    private function generateOverdueAlertContent($pipeline, $deliveryMethod)
    {
        $baseUrl = $GLOBALS['sugar_config']['site_url'];
        $dashboardUrl = $baseUrl . '/index.php?module=Manufacturing&action=PipelineDashboard&pipeline=' . $pipeline['id'];
        
        $stageDisplay = ucwords(str_replace('_', ' ', $pipeline['current_stage']));
        $daysInStage = floor((time() - strtotime($pipeline['stage_entered_date'])) / (60 * 60 * 24));
        
        $subject = "⚠️ Overdue Alert: {$pipeline['pipeline_number']} ({$daysInStage} days)";
        
        if ($deliveryMethod === 'sms') {
            $message = "OVERDUE: Pipeline {$pipeline['pipeline_number']} has been in {$stageDisplay} for {$daysInStage} days. Review needed: {$dashboardUrl}";
            return [
                'subject' => $subject,
                'message' => $message,
                'action_url' => $dashboardUrl
            ];
        }
        
        // Email content with urgency styling
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #dc2626; color: white; padding: 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>⚠️ Overdue Pipeline Alert</h1>
            </div>
            
            <div style='padding: 30px; background: #fef2f2;'>
                <div style='background: #fee2e2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; margin-bottom: 20px;'>
                    <p style='margin: 0; color: #991b1b; font-weight: bold;'>
                        Pipeline {$pipeline['pipeline_number']} requires immediate attention
                    </p>
                </div>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Pipeline:</td>
                            <td style='padding: 8px 0; color: #6b7280;'>{$pipeline['pipeline_number']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Client:</td>
                            <td style='padding: 8px 0; color: #6b7280;'>{$pipeline['account_name']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Current Stage:</td>
                            <td style='padding: 8px 0; color: #6b7280;'>{$stageDisplay}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Days in Stage:</td>
                            <td style='padding: 8px 0; color: #dc2626; font-weight: bold;'>{$daysInStage} days</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #374151;'>Value:</td>
                            <td style='padding: 8px 0; color: #6b7280;'>\$" . number_format($pipeline['total_value'], 2) . "</td>
                        </tr>
                    </table>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$dashboardUrl}' style='background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: bold;'>
                        Review Pipeline Now
                    </a>
                </div>
            </div>
        </div>";
        
        return [
            'subject' => $subject,
            'message' => "Pipeline {$pipeline['pipeline_number']} for {$pipeline['account_name']} has been in {$stageDisplay} stage for {$daysInStage} days and requires attention.",
            'html' => $html,
            'action_url' => $dashboardUrl
        ];
    }

    private function generateDailySummaryData($userId)
    {
        global $db;
        
        // Get user's pipeline data for today
        $query = "
            SELECT 
                COUNT(*) as total_pipelines,
                SUM(CASE WHEN current_stage = 'quote_requested' THEN 1 ELSE 0 END) as new_requests,
                SUM(CASE WHEN DATE(stage_entered_date) = CURDATE() THEN 1 ELSE 0 END) as stage_changes_today,
                SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, stage_entered_date, NOW()) > 48 THEN 1 ELSE 0 END) as overdue_count,
                SUM(total_value) as total_value
            FROM mfg_order_pipeline 
            WHERE assigned_user_id = ? 
            AND deleted = 0 
            AND status = 'active'
        ";
        
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'has_data' => $summary['total_pipelines'] > 0,
            'pipeline_count' => $summary['total_pipelines'],
            'new_requests' => $summary['new_requests'],
            'stage_changes_today' => $summary['stage_changes_today'],
            'urgent_count' => $summary['urgent_count'],
            'overdue_count' => $summary['overdue_count'],
            'total_value' => $summary['total_value']
        ];
    }

    private function generateDailySummaryContent($summaryData, $deliveryMethod)
    {
        $subject = "Daily Pipeline Summary - " . date('F j, Y');
        
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #1f2937; color: white; padding: 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>Daily Pipeline Summary</h1>
                <p style='margin: 5px 0 0 0; opacity: 0.8;'>" . date('l, F j, Y') . "</p>
            </div>
            
            <div style='padding: 30px; background: #f9fafb;'>
                <div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 30px;'>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 28px; font-weight: bold; color: #2563eb;'>{$summaryData['pipeline_count']}</div>
                        <div style='color: #6b7280; font-size: 14px;'>Active Pipelines</div>
                    </div>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 28px; font-weight: bold; color: #059669;'>\$" . number_format($summaryData['total_value'], 0) . "</div>
                        <div style='color: #6b7280; font-size: 14px;'>Total Value</div>
                    </div>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 28px; font-weight: bold; color: #7c3aed;'>{$summaryData['stage_changes_today']}</div>
                        <div style='color: #6b7280; font-size: 14px;'>Stage Changes Today</div>
                    </div>
                    <div style='background: white; padding: 20px; border-radius: 8px; text-align: center;'>
                        <div style='font-size: 28px; font-weight: bold; color: " . ($summaryData['overdue_count'] > 0 ? '#dc2626' : '#6b7280') . ";'>{$summaryData['overdue_count']}</div>
                        <div style='color: #6b7280; font-size: 14px;'>Overdue Items</div>
                    </div>
                </div>";
        
        if ($summaryData['urgent_count'] > 0) {
            $html .= "
                <div style='background: #fee2e2; border: 1px solid #fecaca; padding: 15px; border-radius: 6px; margin-bottom: 20px;'>
                    <p style='margin: 0; color: #991b1b; font-weight: bold;'>
                        ⚠️ You have {$summaryData['urgent_count']} urgent pipeline(s) requiring attention
                    </p>
                </div>";
        }
        
        $html .= "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $GLOBALS['sugar_config']['site_url'] . "/index.php?module=Manufacturing&action=PipelineDashboard' style='background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                        View Pipeline Dashboard
                    </a>
                </div>
            </div>
        </div>";
        
        return [
            'subject' => $subject,
            'message' => "Daily pipeline summary: {$summaryData['pipeline_count']} active pipelines, {$summaryData['stage_changes_today']} stage changes today.",
            'html' => $html
        ];
    }

    private function processNotification($notificationId)
    {
        // Implementation for immediate notification processing
        // This would integrate with email service (SMTP) and SMS service
        // For now, just mark as sent
        global $db;
        
        $query = "UPDATE mfg_notification_queue SET status = 'sent', sent_time = NOW() WHERE id = ?";
        $db->getConnection()->prepare($query)->execute([$notificationId]);
        
        return true;
    }

    private function sendAdditionalNotifications($pipeline, $fromStage, $toStage, $recipients)
    {
        // Implementation for additional notifications to stakeholders
        // This would send notifications to account managers, supervisors, etc.
        return true;
    }
}
