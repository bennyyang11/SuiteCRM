<?php
/**
 * Pipeline-Opportunity Integration API
 * Handles synchronization between SuiteCRM opportunities and manufacturing pipeline
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/api/SugarApi.php';
require_once 'include/database/DBManagerFactory.php';
require_once 'modules/Opportunities/Opportunity.php';

class PipelineOpportunityIntegration extends SugarApi {
    
    public function registerApiRest() {
        return [
            'pipeline_sync_opportunity' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'pipeline', 'sync-opportunity'],
                'pathVars' => [],
                'method' => 'syncOpportunityToPipeline',
                'shortHelp' => 'Sync opportunity to pipeline',
                'longHelp' => 'Create or update pipeline record from opportunity data'
            ],
            'opportunity_sync_pipeline' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'opportunity', 'sync-pipeline'],
                'pathVars' => [],
                'method' => 'syncPipelineToOpportunity',
                'shortHelp' => 'Sync pipeline to opportunity',
                'longHelp' => 'Update opportunity data from pipeline progression'
            ],
            'integration_status' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'integration', 'status'],
                'pathVars' => [],
                'method' => 'getIntegrationStatus',
                'shortHelp' => 'Get integration status',
                'longHelp' => 'Get status of opportunity-pipeline integration'
            ],
            'auto_create_pipeline' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'pipeline', 'auto-create'],
                'pathVars' => [],
                'method' => 'autoCreatePipelineFromOpportunity',
                'shortHelp' => 'Auto-create pipeline from opportunity',
                'longHelp' => 'Automatically create pipeline when opportunity reaches quote stage'
            ],
            'validate_integration' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'integration', 'validate'],
                'pathVars' => [],
                'method' => 'validateIntegration',
                'shortHelp' => 'Validate integration data',
                'longHelp' => 'Validate data consistency between opportunities and pipeline'
            ],
            'bulk_sync' => [
                'reqType' => 'POST',
                'path' => ['manufacturing', 'integration', 'bulk-sync'],
                'pathVars' => [],
                'method' => 'bulkSyncOpportunities',
                'shortHelp' => 'Bulk sync opportunities',
                'longHelp' => 'Sync multiple opportunities to pipeline in batch'
            ]
        ];
    }
    
    /**
     * Sync opportunity data to pipeline
     */
    public function syncOpportunityToPipeline($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            
            if (empty($args['opportunityId'])) {
                throw new SugarApiExceptionMissingParameter('Missing opportunity ID');
            }
            
            $opportunityId = $args['opportunityId'];
            $forceCreate = $args['forceCreate'] ?? false;
            
            // Get opportunity data
            $opportunity = $this->getOpportunityData($db, $opportunityId);
            if (!$opportunity) {
                throw new SugarApiExceptionNotFound('Opportunity not found');
            }
            
            // Check if pipeline already exists
            $pipelineId = $this->getPipelineIdForOpportunity($db, $opportunityId);
            
            if ($pipelineId) {
                // Update existing pipeline
                $result = $this->updatePipelineFromOpportunity($db, $pipelineId, $opportunity);
                $action = 'updated';
            } else if ($this->shouldCreatePipeline($opportunity) || $forceCreate) {
                // Create new pipeline
                $pipelineId = $this->createPipelineFromOpportunity($db, $opportunity);
                $result = ['pipelineId' => $pipelineId];
                $action = 'created';
            } else {
                return [
                    'success' => false,
                    'message' => 'Opportunity does not meet criteria for pipeline creation'
                ];
            }
            
            // Log sync operation
            $this->logSyncOperation($db, $opportunityId, $pipelineId, 'sync_to_pipeline', $action);
            
            $GLOBALS['log']->info("Pipeline {$action} for opportunity: {$opportunityId}");
            
            return [
                'success' => true,
                'action' => $action,
                'pipelineId' => $pipelineId,
                'opportunityId' => $opportunityId,
                'data' => $result
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to sync opportunity to pipeline: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Sync pipeline data back to opportunity
     */
    public function syncPipelineToOpportunity($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            
            if (empty($args['pipelineId'])) {
                throw new SugarApiExceptionMissingParameter('Missing pipeline ID');
            }
            
            $pipelineId = $args['pipelineId'];
            $syncFields = $args['syncFields'] ?? ['stage', 'value', 'dates', 'assignment'];
            
            // Get pipeline data
            $pipeline = $this->getPipelineData($db, $pipelineId);
            if (!$pipeline) {
                throw new SugarApiExceptionNotFound('Pipeline not found');
            }
            
            if (empty($pipeline['opportunity_id'])) {
                throw new SugarApiExceptionInvalidParameter('Pipeline is not linked to an opportunity');
            }
            
            // Update opportunity based on pipeline data
            $updateData = $this->prepareSyncData($pipeline, $syncFields);
            $result = $this->updateOpportunityFromPipeline($db, $pipeline['opportunity_id'], $updateData);
            
            // Log sync operation
            $this->logSyncOperation($db, $pipeline['opportunity_id'], $pipelineId, 'sync_to_opportunity', 'updated');
            
            $GLOBALS['log']->info("Opportunity updated from pipeline: {$pipelineId}");
            
            return [
                'success' => true,
                'opportunityId' => $pipeline['opportunity_id'],
                'pipelineId' => $pipelineId,
                'updatedFields' => array_keys($updateData),
                'data' => $result
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to sync pipeline to opportunity: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get integration status and statistics
     */
    public function getIntegrationStatus($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            
            // Get overall statistics
            $stats = $this->getIntegrationStatistics($db);
            
            // Get recent sync activity
            $recentActivity = $this->getRecentSyncActivity($db);
            
            // Get configuration status
            $configStatus = $this->getConfigurationStatus($db);
            
            // Check system health
            $healthCheck = $this->performHealthCheck($db);
            
            return [
                'success' => true,
                'statistics' => $stats,
                'recentActivity' => $recentActivity,
                'configuration' => $configStatus,
                'healthCheck' => $healthCheck
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to get integration status: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Auto-create pipeline when opportunity reaches quote stage
     */
    public function autoCreatePipelineFromOpportunity($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            
            if (empty($args['opportunityId'])) {
                throw new SugarApiExceptionMissingParameter('Missing opportunity ID');
            }
            
            $opportunityId = $args['opportunityId'];
            
            // Check if auto-creation is enabled
            if (!$this->isAutoCreateEnabled($db)) {
                return [
                    'success' => false,
                    'message' => 'Auto-creation is disabled in configuration'
                ];
            }
            
            // Get opportunity data
            $opportunity = $this->getOpportunityData($db, $opportunityId);
            if (!$opportunity) {
                throw new SugarApiExceptionNotFound('Opportunity not found');
            }
            
            // Check if opportunity meets criteria
            if (!$this->shouldCreatePipeline($opportunity)) {
                return [
                    'success' => false,
                    'message' => 'Opportunity does not meet auto-creation criteria',
                    'criteria' => $this->getAutoCreateCriteria($db)
                ];
            }
            
            // Check if pipeline already exists
            $existingPipelineId = $this->getPipelineIdForOpportunity($db, $opportunityId);
            if ($existingPipelineId) {
                return [
                    'success' => false,
                    'message' => 'Pipeline already exists for this opportunity',
                    'pipelineId' => $existingPipelineId
                ];
            }
            
            // Create pipeline
            $pipelineId = $this->createPipelineFromOpportunity($db, $opportunity);
            
            // Send notification
            $this->sendAutoCreationNotification($opportunity, $pipelineId);
            
            // Log operation
            $this->logSyncOperation($db, $opportunityId, $pipelineId, 'auto_create', 'created');
            
            $GLOBALS['log']->info("Auto-created pipeline for opportunity: {$opportunityId}");
            
            return [
                'success' => true,
                'action' => 'auto-created',
                'opportunityId' => $opportunityId,
                'pipelineId' => $pipelineId,
                'message' => 'Pipeline created automatically'
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to auto-create pipeline: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate integration data consistency
     */
    public function validateIntegration($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            
            $validationType = $args['type'] ?? 'full';
            $fixIssues = $args['fixIssues'] ?? false;
            
            $validation = [
                'success' => true,
                'validationType' => $validationType,
                'timestamp' => date('Y-m-d H:i:s'),
                'issues' => [],
                'statistics' => []
            ];
            
            // Validate data consistency
            switch ($validationType) {
                case 'values':
                    $validation['issues'] = $this->validateValueConsistency($db, $fixIssues);
                    break;
                case 'stages':
                    $validation['issues'] = $this->validateStageConsistency($db, $fixIssues);
                    break;
                case 'assignments':
                    $validation['issues'] = $this->validateAssignmentConsistency($db, $fixIssues);
                    break;
                case 'full':
                default:
                    $validation['issues'] = array_merge(
                        $this->validateValueConsistency($db, $fixIssues),
                        $this->validateStageConsistency($db, $fixIssues),
                        $this->validateAssignmentConsistency($db, $fixIssues),
                        $this->validateOrphanedRecords($db, $fixIssues)
                    );
                    break;
            }
            
            // Get validation statistics
            $validation['statistics'] = [
                'totalIssues' => count($validation['issues']),
                'criticalIssues' => count(array_filter($validation['issues'], function($issue) {
                    return $issue['severity'] === 'critical';
                })),
                'fixedIssues' => count(array_filter($validation['issues'], function($issue) {
                    return isset($issue['fixed']) && $issue['fixed'];
                }))
            ];
            
            // Overall success status
            $validation['success'] = $validation['statistics']['criticalIssues'] === 0;
            
            return $validation;
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to validate integration: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Bulk sync multiple opportunities
     */
    public function bulkSyncOpportunities($api, $args) {
        try {
            $db = DBManagerFactory::getInstance();
            
            $opportunityIds = $args['opportunityIds'] ?? [];
            $syncDirection = $args['direction'] ?? 'to_pipeline'; // to_pipeline or to_opportunity
            $batchSize = $args['batchSize'] ?? 50;
            
            if (empty($opportunityIds)) {
                throw new SugarApiExceptionMissingParameter('Missing opportunity IDs');
            }
            
            $results = [
                'success' => true,
                'totalRequested' => count($opportunityIds),
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            // Process in batches
            $batches = array_chunk($opportunityIds, $batchSize);
            
            foreach ($batches as $batch) {
                $batchResults = $this->processBatchSync($db, $batch, $syncDirection);
                
                $results['processed'] += $batchResults['processed'];
                $results['created'] += $batchResults['created'];
                $results['updated'] += $batchResults['updated'];
                $results['failed'] += $batchResults['failed'];
                $results['errors'] = array_merge($results['errors'], $batchResults['errors']);
            }
            
            // Log bulk operation
            $GLOBALS['log']->info("Bulk sync completed: {$results['processed']} opportunities processed");
            
            return $results;
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to bulk sync opportunities: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Private helper methods
    
    private function getOpportunityData($db, $opportunityId) {
        $query = "
            SELECT o.*, oc.pipeline_id_c, oc.pipeline_stage_c, oc.expected_ship_date_c,
                   oc.manufacturing_priority_c, oc.delivery_method_c,
                   a.name as account_name, u.user_name as assigned_user_name
            FROM opportunities o
            LEFT JOIN opportunities_cstm oc ON o.id = oc.id_c
            LEFT JOIN accounts a ON o.account_id = a.id
            LEFT JOIN users u ON o.assigned_user_id = u.id
            WHERE o.id = '{$opportunityId}' AND o.deleted = 0
        ";
        
        $result = $db->query($query);
        return $db->fetchByAssoc($result);
    }
    
    private function getPipelineData($db, $pipelineId) {
        $query = "
            SELECT p.*, ps.name as stage_name, ps.stage_key,
                   a.name as account_name, u.user_name as assigned_user_name
            FROM mfg_order_pipeline p
            LEFT JOIN mfg_pipeline_stages ps ON p.stage_id = ps.id
            LEFT JOIN accounts a ON p.account_id = a.id
            LEFT JOIN users u ON p.assigned_user_id = u.id
            WHERE p.id = '{$pipelineId}' AND p.deleted = 0
        ";
        
        $result = $db->query($query);
        return $db->fetchByAssoc($result);
    }
    
    private function getPipelineIdForOpportunity($db, $opportunityId) {
        $query = "SELECT pipeline_id_c FROM opportunities_cstm WHERE id_c = '{$opportunityId}'";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        
        return $row['pipeline_id_c'] ?? null;
    }
    
    private function shouldCreatePipeline($opportunity) {
        $eligibleStages = ['Proposal/Price Quote', 'Negotiation/Review', 'Closed Won'];
        return in_array($opportunity['sales_stage'], $eligibleStages) && 
               !empty($opportunity['account_id']) && 
               !empty($opportunity['amount']);
    }
    
    private function createPipelineFromOpportunity($db, $opportunity) {
        $pipelineId = $this->generateId();
        $orderNumber = $this->generateOrderNumber($db);
        
        // Get initial stage
        $stageQuery = "SELECT id FROM mfg_pipeline_stages WHERE stage_key = 'quote_prepared' LIMIT 1";
        $stageResult = $db->query($stageQuery);
        $stageRow = $db->fetchByAssoc($stageResult);
        $stageId = $stageRow['id'];
        
        // Create pipeline record
        $pipelineData = [
            'id' => $pipelineId,
            'order_number' => $orderNumber,
            'stage_id' => $stageId,
            'opportunity_id' => $opportunity['id'],
            'account_id' => $opportunity['account_id'],
            'assigned_user_id' => $opportunity['assigned_user_id'],
            'total_value' => $opportunity['amount'] ?? 0,
            'expected_close_date' => $opportunity['date_closed'],
            'priority' => 'normal',
            'date_created' => date('Y-m-d H:i:s'),
            'created_by' => $GLOBALS['current_user']->id,
            'deleted' => 0
        ];
        
        $this->insertPipelineRecord($db, $pipelineData);
        
        // Update opportunity custom fields
        $this->updateOpportunityCustomFields($db, $opportunity['id'], [
            'pipeline_id_c' => $pipelineId,
            'pipeline_stage_c' => 'quote_prepared',
            'manufacturing_priority_c' => 'normal'
        ]);
        
        return $pipelineId;
    }
    
    private function updatePipelineFromOpportunity($db, $pipelineId, $opportunity) {
        $updateData = [
            'total_value' => $opportunity['amount'],
            'expected_close_date' => $opportunity['date_closed'],
            'assigned_user_id' => $opportunity['assigned_user_id'],
            'account_id' => $opportunity['account_id'],
            'date_modified' => date('Y-m-d H:i:s'),
            'modified_user_id' => $GLOBALS['current_user']->id
        ];
        
        $setClause = [];
        foreach ($updateData as $field => $value) {
            $setClause[] = "{$field} = " . ($value === null ? 'NULL' : "'{$value}'");
        }
        
        $query = "UPDATE mfg_order_pipeline SET " . implode(', ', $setClause) . " WHERE id = '{$pipelineId}'";
        $result = $db->query($query);
        
        return ['updated' => $result];
    }
    
    private function updateOpportunityFromPipeline($db, $opportunityId, $updateData) {
        // Update opportunity main record
        if (!empty($updateData['opportunity'])) {
            $oppData = $updateData['opportunity'];
            $setClause = [];
            
            foreach ($oppData as $field => $value) {
                $setClause[] = "{$field} = " . ($value === null ? 'NULL' : "'{$value}'");
            }
            
            if (!empty($setClause)) {
                $query = "UPDATE opportunities SET " . implode(', ', $setClause) . 
                        ", date_modified = NOW(), modified_user_id = '{$GLOBALS['current_user']->id}' " .
                        "WHERE id = '{$opportunityId}'";
                $db->query($query);
            }
        }
        
        // Update custom fields
        if (!empty($updateData['custom'])) {
            $this->updateOpportunityCustomFields($db, $opportunityId, $updateData['custom']);
        }
        
        return ['updated' => true];
    }
    
    private function prepareSyncData($pipeline, $syncFields) {
        $updateData = ['opportunity' => [], 'custom' => []];
        
        foreach ($syncFields as $field) {
            switch ($field) {
                case 'value':
                    if (!empty($pipeline['total_value'])) {
                        $updateData['opportunity']['amount'] = $pipeline['total_value'];
                    }
                    break;
                    
                case 'dates':
                    if (!empty($pipeline['expected_close_date'])) {
                        $updateData['opportunity']['date_closed'] = $pipeline['expected_close_date'];
                    }
                    break;
                    
                case 'assignment':
                    if (!empty($pipeline['assigned_user_id'])) {
                        $updateData['opportunity']['assigned_user_id'] = $pipeline['assigned_user_id'];
                    }
                    break;
                    
                case 'stage':
                    // Map pipeline stage to opportunity stage
                    $oppStage = $this->mapPipelineStageToOpportunity($pipeline['stage_key']);
                    if ($oppStage) {
                        $updateData['opportunity']['sales_stage'] = $oppStage;
                        $updateData['custom']['pipeline_stage_c'] = $pipeline['stage_key'];
                    }
                    break;
            }
        }
        
        return $updateData;
    }
    
    private function mapPipelineStageToOpportunity($pipelineStage) {
        $mapping = [
            'quote_requested' => 'Prospecting',
            'quote_prepared' => 'Proposal/Price Quote',
            'quote_sent' => 'Proposal/Price Quote',
            'quote_approved' => 'Negotiation/Review',
            'order_processing' => 'Closed Won',
            'shipped' => 'Closed Won',
            'delivered' => 'Closed Won'
        ];
        
        return $mapping[$pipelineStage] ?? null;
    }
    
    private function insertPipelineRecord($db, $data) {
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";
        
        $query = "INSERT INTO mfg_order_pipeline ({$columns}) VALUES ({$values})";
        return $db->query($query);
    }
    
    private function updateOpportunityCustomFields($db, $opportunityId, $data) {
        if (empty($data)) return;
        
        $setClause = [];
        foreach ($data as $field => $value) {
            $setClause[] = "{$field} = " . ($value === null ? 'NULL' : "'{$value}'");
        }
        
        $query = "INSERT INTO opportunities_cstm (id_c, " . implode(', ', array_keys($data)) . ") " .
                "VALUES ('{$opportunityId}', '" . implode("', '", array_values($data)) . "') " .
                "ON DUPLICATE KEY UPDATE " . implode(', ', $setClause);
        
        return $db->query($query);
    }
    
    private function logSyncOperation($db, $opportunityId, $pipelineId, $syncType, $result) {
        $logData = [
            'id' => $this->generateId(),
            'opportunity_id' => $opportunityId,
            'pipeline_id' => $pipelineId,
            'sync_type' => $syncType,
            'sync_status' => 'success',
            'new_values' => json_encode(['result' => $result]),
            'date_created' => date('Y-m-d H:i:s'),
            'created_by' => $GLOBALS['current_user']->id,
            'processed_at' => date('Y-m-d H:i:s')
        ];
        
        $columns = implode(', ', array_keys($logData));
        $values = "'" . implode("', '", array_values($logData)) . "'";
        
        $query = "INSERT INTO mfg_opportunity_pipeline_sync_log ({$columns}) VALUES ({$values})";
        $db->query($query);
    }
    
    private function generateOrderNumber($db) {
        $prefix = 'ORD-';
        $date = date('Ymd');
        
        // Get next sequence number for today
        $query = "SELECT COUNT(*) as count FROM mfg_order_pipeline WHERE order_number LIKE '{$prefix}{$date}%'";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        $sequence = str_pad($row['count'] + 1, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}{$date}-{$sequence}";
    }
    
    private function generateId() {
        return uniqid() . '_' . time();
    }
    
    private function getIntegrationStatistics($db) {
        $stats = [];
        
        // Total linked opportunities
        $query = "SELECT COUNT(*) as count FROM opportunities_cstm WHERE pipeline_id_c IS NOT NULL AND pipeline_id_c != ''";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        $stats['linkedOpportunities'] = (int)$row['count'];
        
        // Total pipeline records
        $query = "SELECT COUNT(*) as count FROM mfg_order_pipeline WHERE deleted = 0";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        $stats['totalPipelines'] = (int)$row['count'];
        
        // Sync operations in last 24 hours
        $query = "SELECT COUNT(*) as count FROM mfg_opportunity_pipeline_sync_log WHERE date_created >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        $stats['recentSyncs'] = (int)$row['count'];
        
        return $stats;
    }
    
    private function getRecentSyncActivity($db, $limit = 10) {
        $query = "
            SELECT sl.*, o.name as opportunity_name, p.order_number
            FROM mfg_opportunity_pipeline_sync_log sl
            LEFT JOIN opportunities o ON sl.opportunity_id = o.id
            LEFT JOIN mfg_order_pipeline p ON sl.pipeline_id = p.id
            ORDER BY sl.date_created DESC
            LIMIT {$limit}
        ";
        
        $result = $db->query($query);
        $activities = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $activities[] = $row;
        }
        
        return $activities;
    }
    
    private function getConfigurationStatus($db) {
        $query = "SELECT config_key, config_value, is_active FROM mfg_pipeline_integration_config WHERE is_active = 1";
        $result = $db->query($query);
        $config = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $config[$row['config_key']] = $row['config_value'];
        }
        
        return $config;
    }
    
    private function performHealthCheck($db) {
        $health = ['status' => 'healthy', 'issues' => []];
        
        // Check for orphaned pipeline records
        $query = "SELECT COUNT(*) as count FROM mfg_order_pipeline p 
                 LEFT JOIN opportunities o ON p.opportunity_id = o.id 
                 WHERE p.opportunity_id IS NOT NULL AND o.id IS NULL AND p.deleted = 0";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        
        if ($row['count'] > 0) {
            $health['issues'][] = "Found {$row['count']} orphaned pipeline records";
            $health['status'] = 'warning';
        }
        
        // Check for value discrepancies
        $query = "SELECT COUNT(*) as count FROM v_opportunity_pipeline_integrated 
                 WHERE ABS(value_variance) > 100";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        
        if ($row['count'] > 0) {
            $health['issues'][] = "Found {$row['count']} records with significant value discrepancies";
            $health['status'] = 'warning';
        }
        
        return $health;
    }
    
    private function isAutoCreateEnabled($db) {
        $query = "SELECT config_value FROM mfg_pipeline_integration_config WHERE config_key = 'auto_create_pipeline' AND is_active = 1";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        
        return $row['config_value'] === 'true';
    }
    
    private function getAutoCreateCriteria($db) {
        $query = "SELECT config_value FROM mfg_pipeline_integration_config WHERE config_key = 'required_opportunity_fields' AND is_active = 1";
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result);
        
        return json_decode($row['config_value'], true);
    }
    
    private function sendAutoCreationNotification($opportunity, $pipelineId) {
        // This would integrate with the notification system
        // For now, just log it
        $GLOBALS['log']->info("Auto-creation notification: Pipeline {$pipelineId} created for opportunity {$opportunity['name']}");
    }
    
    private function validateValueConsistency($db, $fixIssues = false) {
        $issues = [];
        
        $query = "SELECT * FROM v_opportunity_pipeline_integrated WHERE ABS(value_variance) > 100";
        $result = $db->query($query);
        
        while ($row = $db->fetchByAssoc($result)) {
            $issue = [
                'type' => 'value_inconsistency',
                'severity' => 'medium',
                'opportunityId' => $row['opportunity_id'],
                'pipelineId' => $row['pipeline_id'],
                'description' => "Value variance of {$row['value_variance']} between opportunity and pipeline",
                'fixed' => false
            ];
            
            if ($fixIssues) {
                // Sync pipeline value to opportunity
                $updateQuery = "UPDATE opportunities SET amount = '{$row['pipeline_value']}' WHERE id = '{$row['opportunity_id']}'";
                $db->query($updateQuery);
                $issue['fixed'] = true;
            }
            
            $issues[] = $issue;
        }
        
        return $issues;
    }
    
    private function validateStageConsistency($db, $fixIssues = false) {
        // Implementation for stage validation
        return [];
    }
    
    private function validateAssignmentConsistency($db, $fixIssues = false) {
        // Implementation for assignment validation
        return [];
    }
    
    private function validateOrphanedRecords($db, $fixIssues = false) {
        // Implementation for orphaned record validation
        return [];
    }
    
    private function processBatchSync($db, $opportunityIds, $direction) {
        $results = ['processed' => 0, 'created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($opportunityIds as $opportunityId) {
            try {
                if ($direction === 'to_pipeline') {
                    $syncResult = $this->syncOpportunityToPipeline(null, ['opportunityId' => $opportunityId]);
                } else {
                    // Get pipeline ID for opportunity and sync back
                    $pipelineId = $this->getPipelineIdForOpportunity($db, $opportunityId);
                    if ($pipelineId) {
                        $syncResult = $this->syncPipelineToOpportunity(null, ['pipelineId' => $pipelineId]);
                    } else {
                        throw new Exception('No pipeline found for opportunity');
                    }
                }
                
                if ($syncResult['success']) {
                    $results['processed']++;
                    if ($syncResult['action'] === 'created') {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                } else {
                    $results['failed']++;
                    $results['errors'][] = ['opportunityId' => $opportunityId, 'error' => $syncResult['error']];
                }
                
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = ['opportunityId' => $opportunityId, 'error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
}
