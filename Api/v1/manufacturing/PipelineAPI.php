<?php
/**
 * Manufacturing Pipeline API
 * RESTful endpoints for order tracking dashboard
 */

require_once('include/MVC/Controller/SugarController.php');
require_once('include/utils.php');

class PipelineAPI extends SugarController
{
    private $db;
    
    public function __construct()
    {
        global $db;
        $this->db = $db;
        
        // Set JSON headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * GET /api/v1/pipeline
     * Get all pipeline orders with Kanban data
     */
    public function action_pipeline()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($method) {
                case 'GET':
                    return $this->getPipelineOrders();
                case 'POST':
                    return $this->createPipelineOrder();
                default:
                    return $this->errorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * PUT /api/v1/pipeline/{id}/stage
     * Update pipeline stage
     */
    public function action_update_stage()
    {
        try {
            $pipeline_id = $this->getPathParameter(2); // /api/v1/pipeline/{id}/stage
            
            if (!$pipeline_id) {
                return $this->errorResponse('Pipeline ID is required', 400);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $new_stage = $input['stage'] ?? '';
            $notes = $input['notes'] ?? '';
            
            if (!$new_stage) {
                return $this->errorResponse('New stage is required', 400);
            }
            
            return $this->updatePipelineStage($pipeline_id, $new_stage, $notes);
            
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/pipeline/summary
     * Get pipeline summary for dashboard
     */
    public function action_summary()
    {
        try {
            return $this->getPipelineSummary();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/pipeline/{id}/history
     * Get stage history for specific pipeline
     */
    public function action_history()
    {
        try {
            $pipeline_id = $this->getPathParameter(2); // /api/v1/pipeline/{id}/history
            
            if (!$pipeline_id) {
                return $this->errorResponse('Pipeline ID is required', 400);
            }
            
            return $this->getPipelineHistory($pipeline_id);
            
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    private function getPipelineOrders()
    {
        $rep_id = $_GET['rep_id'] ?? '';
        $stage = $_GET['stage'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $days_filter = intval($_GET['days'] ?? 30);
        
        $where_conditions = ["p.deleted = 0"];
        $params = [];
        
        if ($rep_id) {
            $where_conditions[] = "p.assigned_rep_id = ?";
            $params[] = $rep_id;
        }
        
        if ($stage) {
            $where_conditions[] = "p.current_stage = ?";
            $params[] = $stage;
        }
        
        if ($priority) {
            $where_conditions[] = "p.priority = ?";
            $params[] = $priority;
        }
        
        // Filter by recent activity
        $where_conditions[] = "p.stage_updated_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
        $params[] = $days_filter;
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT 
                p.id,
                p.pipeline_number,
                p.account_id,
                p.account_name,
                p.assigned_rep_id,
                p.assigned_rep_name,
                p.current_stage,
                p.stage_updated_date,
                p.total_amount,
                p.final_amount,
                p.probability_percent,
                p.quote_date,
                p.expected_close_date,
                p.actual_close_date,
                p.client_po_number,
                p.priority,
                p.is_rush_order,
                p.special_instructions,
                p.next_action,
                DATEDIFF(p.expected_close_date, CURDATE()) as days_to_close,
                CASE 
                    WHEN p.expected_close_date < CURDATE() AND p.current_stage NOT IN ('payment_received') THEN 'overdue'
                    WHEN DATEDIFF(p.expected_close_date, CURDATE()) <= 3 THEN 'due_soon'
                    ELSE 'on_track'
                END as timeline_status,
                -- Count of line items
                (SELECT COUNT(*) FROM mfg_pipeline_items pi WHERE pi.pipeline_id = p.id AND pi.deleted = 0) as item_count,
                -- Last activity
                (SELECT MAX(stage_date) FROM mfg_pipeline_stage_history ph WHERE ph.pipeline_id = p.id AND ph.deleted = 0) as last_activity
            FROM mfg_order_pipeline p
            WHERE {$where_clause}
            ORDER BY 
                CASE p.priority 
                    WHEN 'urgent' THEN 1 
                    WHEN 'high' THEN 2 
                    WHEN 'medium' THEN 3 
                    ELSE 4 
                END,
                p.expected_close_date ASC
        ";
        
        $result = $this->db->pQuery($query, $params);
        $orders = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $orders[] = $this->formatPipelineOrder($row);
        }
        
        // Group by stage for Kanban display
        $kanban_data = $this->groupOrdersByStage($orders);
        
        return $this->successResponse([
            'orders' => $orders,
            'kanban' => $kanban_data,
            'summary' => $this->calculateSummaryStats($orders)
        ]);
    }
    
    private function updatePipelineStage($pipeline_id, $new_stage, $notes = '')
    {
        global $current_user;
        
        // Get current stage
        $current_query = "SELECT current_stage, pipeline_number, account_name FROM mfg_order_pipeline WHERE id = ? AND deleted = 0";
        $current_result = $this->db->pQuery($current_query, [$pipeline_id]);
        $current_data = $this->db->fetchByAssoc($current_result);
        
        if (!$current_data) {
            return $this->errorResponse('Pipeline order not found', 404);
        }
        
        $old_stage = $current_data['current_stage'];
        $pipeline_number = $current_data['pipeline_number'];
        
        // Update main pipeline record
        $update_query = "
            UPDATE mfg_order_pipeline 
            SET current_stage = ?, 
                stage_updated_date = NOW(), 
                stage_updated_by = ?,
                date_modified = NOW()
            WHERE id = ? AND deleted = 0
        ";
        
        $user_id = $current_user->id ?? 'system';
        $update_result = $this->db->pQuery($update_query, [$new_stage, $user_id, $pipeline_id]);
        
        if (!$update_result) {
            return $this->errorResponse('Failed to update pipeline stage', 500);
        }
        
        // Record stage history
        $this->recordStageHistory($pipeline_id, $old_stage, $new_stage, $notes, $user_id);
        
        // Trigger notifications
        $this->triggerStageNotifications($pipeline_id, $new_stage, $pipeline_number);
        
        return $this->successResponse([
            'pipeline_id' => $pipeline_id,
            'old_stage' => $old_stage,
            'new_stage' => $new_stage,
            'updated_by' => $current_user->full_name ?? 'System',
            'updated_date' => date('Y-m-d H:i:s'),
            'message' => "Pipeline {$pipeline_number} moved from {$old_stage} to {$new_stage}"
        ]);
    }
    
    private function recordStageHistory($pipeline_id, $from_stage, $to_stage, $notes, $user_id)
    {
        global $current_user;
        
        $history_id = create_guid();
        $user_name = $current_user->full_name ?? 'System User';
        
        // Calculate duration from previous stage
        $duration_query = "
            SELECT TIMESTAMPDIFF(HOUR, MAX(stage_date), NOW()) as duration_hours
            FROM mfg_pipeline_stage_history 
            WHERE pipeline_id = ? AND deleted = 0
        ";
        $duration_result = $this->db->pQuery($duration_query, [$pipeline_id]);
        $duration_data = $this->db->fetchByAssoc($duration_result);
        $duration_hours = $duration_data['duration_hours'] ?? 0;
        
        $history_query = "
            INSERT INTO mfg_pipeline_stage_history 
            (id, pipeline_id, from_stage, to_stage, stage_date, changed_by, changed_by_name, 
             notes, duration_hours, created_by, date_entered) 
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, NOW())
        ";
        
        $this->db->pQuery($history_query, [
            $history_id, $pipeline_id, $from_stage, $to_stage, $user_id, $user_name, 
            $notes, $duration_hours, $user_id
        ]);
    }
    
    private function triggerStageNotifications($pipeline_id, $stage, $pipeline_number)
    {
        // Create notification queue entries based on stage
        $notification_templates = [
            'quote_sent' => [
                'subject' => "Quote {$pipeline_number} has been sent",
                'recipient_type' => 'client'
            ],
            'quote_approved' => [
                'subject' => "Quote {$pipeline_number} approved - processing order",
                'recipient_type' => 'rep'
            ],
            'order_shipped' => [
                'subject' => "Your order {$pipeline_number} has shipped",
                'recipient_type' => 'client'
            ],
            'payment_received' => [
                'subject' => "Payment received for order {$pipeline_number}",
                'recipient_type' => 'rep'
            ]
        ];
        
        if (isset($notification_templates[$stage])) {
            $template = $notification_templates[$stage];
            $notification_id = create_guid();
            
            $notification_query = "
                INSERT INTO mfg_pipeline_notifications 
                (id, pipeline_id, notification_type, recipient_type, subject, 
                 trigger_stage, send_date, status, created_by, date_entered) 
                VALUES (?, ?, 'email', ?, ?, ?, NOW(), 'pending', 'system', NOW())
            ";
            
            $this->db->pQuery($notification_query, [
                $notification_id, $pipeline_id, $template['recipient_type'], 
                $template['subject'], $stage
            ]);
        }
    }
    
    private function getPipelineSummary()
    {
        // Get summary by stage
        $summary_query = "
            SELECT 
                current_stage,
                COUNT(*) as order_count,
                SUM(total_amount) as total_value,
                AVG(probability_percent) as avg_probability
            FROM mfg_order_pipeline 
            WHERE deleted = 0 
            GROUP BY current_stage
        ";
        
        $result = $this->db->query($summary_query);
        $stage_summary = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $stage_summary[$row['current_stage']] = [
                'count' => intval($row['order_count']),
                'value' => floatval($row['total_value']),
                'avg_probability' => floatval($row['avg_probability'])
            ];
        }
        
        // Get overall stats
        $stats_query = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_pipeline_value,
                SUM(CASE WHEN current_stage = 'payment_received' THEN total_amount ELSE 0 END) as closed_revenue,
                AVG(probability_percent) as avg_probability,
                COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_orders,
                COUNT(CASE WHEN expected_close_date < CURDATE() AND current_stage NOT IN ('payment_received') THEN 1 END) as overdue_orders
            FROM mfg_order_pipeline 
            WHERE deleted = 0
        ";
        
        $stats_result = $this->db->query($stats_query);
        $stats = $this->db->fetchByAssoc($stats_result);
        
        return $this->successResponse([
            'stages' => $stage_summary,
            'overall' => [
                'total_orders' => intval($stats['total_orders']),
                'total_pipeline_value' => floatval($stats['total_pipeline_value']),
                'closed_revenue' => floatval($stats['closed_revenue']),
                'avg_probability' => floatval($stats['avg_probability']),
                'urgent_orders' => intval($stats['urgent_orders']),
                'overdue_orders' => intval($stats['overdue_orders'])
            ]
        ]);
    }
    
    private function getPipelineHistory($pipeline_id)
    {
        $history_query = "
            SELECT 
                h.id,
                h.from_stage,
                h.to_stage,
                h.stage_date,
                h.changed_by_name,
                h.notes,
                h.duration_hours,
                h.email_sent,
                h.client_notified
            FROM mfg_pipeline_stage_history h
            WHERE h.pipeline_id = ? AND h.deleted = 0
            ORDER BY h.stage_date DESC
        ";
        
        $result = $this->db->pQuery($history_query, [$pipeline_id]);
        $history = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $history[] = [
                'id' => $row['id'],
                'from_stage' => $row['from_stage'],
                'to_stage' => $row['to_stage'],
                'date' => $row['stage_date'],
                'changed_by' => $row['changed_by_name'],
                'notes' => $row['notes'],
                'duration_hours' => intval($row['duration_hours']),
                'email_sent' => (bool)$row['email_sent'],
                'client_notified' => (bool)$row['client_notified']
            ];
        }
        
        return $this->successResponse([
            'pipeline_id' => $pipeline_id,
            'history' => $history
        ]);
    }
    
    private function formatPipelineOrder($row)
    {
        return [
            'id' => $row['id'],
            'pipeline_number' => $row['pipeline_number'],
            'account' => [
                'id' => $row['account_id'],
                'name' => $row['account_name']
            ],
            'assigned_rep' => [
                'id' => $row['assigned_rep_id'],
                'name' => $row['assigned_rep_name']
            ],
            'stage' => [
                'current' => $row['current_stage'],
                'updated_date' => $row['stage_updated_date'],
                'timeline_status' => $row['timeline_status']
            ],
            'financial' => [
                'total_amount' => floatval($row['total_amount']),
                'final_amount' => floatval($row['final_amount']),
                'probability_percent' => intval($row['probability_percent'])
            ],
            'timeline' => [
                'quote_date' => $row['quote_date'],
                'expected_close_date' => $row['expected_close_date'],
                'actual_close_date' => $row['actual_close_date'],
                'days_to_close' => intval($row['days_to_close'])
            ],
            'details' => [
                'client_po_number' => $row['client_po_number'],
                'priority' => $row['priority'],
                'is_rush_order' => (bool)$row['is_rush_order'],
                'special_instructions' => $row['special_instructions'],
                'next_action' => $row['next_action'],
                'item_count' => intval($row['item_count'])
            ],
            'last_activity' => $row['last_activity']
        ];
    }
    
    private function groupOrdersByStage($orders)
    {
        $stages = [
            'quote_created' => ['label' => 'Quote Created', 'orders' => []],
            'quote_sent' => ['label' => 'Quote Sent', 'orders' => []],
            'quote_approved' => ['label' => 'Quote Approved', 'orders' => []],
            'order_placed' => ['label' => 'Order Placed', 'orders' => []],
            'order_shipped' => ['label' => 'Order Shipped', 'orders' => []],
            'invoice_sent' => ['label' => 'Invoice Sent', 'orders' => []],
            'payment_received' => ['label' => 'Payment Received', 'orders' => []]
        ];
        
        foreach ($orders as $order) {
            $stage = $order['stage']['current'];
            if (isset($stages[$stage])) {
                $stages[$stage]['orders'][] = $order;
            }
        }
        
        return $stages;
    }
    
    private function calculateSummaryStats($orders)
    {
        $total_value = 0;
        $stage_counts = [];
        $priority_counts = ['urgent' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        
        foreach ($orders as $order) {
            $total_value += $order['financial']['total_amount'];
            
            $stage = $order['stage']['current'];
            $stage_counts[$stage] = ($stage_counts[$stage] ?? 0) + 1;
            
            $priority = $order['details']['priority'];
            $priority_counts[$priority]++;
        }
        
        return [
            'total_orders' => count($orders),
            'total_value' => $total_value,
            'stage_counts' => $stage_counts,
            'priority_counts' => $priority_counts
        ];
    }
    
    private function getPathParameter($index)
    {
        $path = trim($_SERVER['REQUEST_URI'], '/');
        $segments = explode('/', $path);
        return $segments[$index] ?? null;
    }
    
    private function successResponse($data, $status_code = 200)
    {
        http_response_code($status_code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    private function errorResponse($message, $status_code = 400)
    {
        http_response_code($status_code);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status_code
            ],
            'timestamp' => date('c')
        ]);
        exit;
    }
}
