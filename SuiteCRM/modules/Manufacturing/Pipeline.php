<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('data/SugarBean.php');

class Pipeline extends SugarBean
{
    public $table_name = 'manufacturing_pipeline';
    public $object_name = 'Pipeline';
    public $module_dir = 'Manufacturing';
    public $module_name = 'Manufacturing';
    public $new_schema = true;
    
    public $id;
    public $order_id;
    public $client_id;
    public $stage;
    public $priority;
    public $value;
    public $expected_close_date;
    public $probability;
    public $description;
    public $date_entered;
    public $date_modified;
    public $modified_user_id;
    public $created_by;
    public $deleted;
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function bean_implements($interface)
    {
        switch($interface) {
            case 'ACL':
                return true;
        }
        return false;
    }
    
    public function getOrdersByStage($stage = '')
    {
        global $db;
        
        $query = "SELECT p.*, c.name as client_name 
                 FROM {$this->table_name} p 
                 LEFT JOIN accounts c ON p.client_id = c.id 
                 WHERE p.deleted = 0";
        
        if (!empty($stage)) {
            $query .= " AND p.stage = '" . $db->quote($stage) . "'";
        }
        
        $query .= " ORDER BY p.priority DESC, p.date_modified DESC";
        
        return $db->query($query);
    }
    
    public function updateStage($order_id, $new_stage, $user_id)
    {
        global $db;
        
        // Update pipeline stage
        $query = "UPDATE {$this->table_name} 
                 SET stage = '" . $db->quote($new_stage) . "',
                     date_modified = NOW(),
                     modified_user_id = '" . $db->quote($user_id) . "'
                 WHERE order_id = '" . $db->quote($order_id) . "'";
        
        $result = $db->query($query);
        
        // Log stage change in history
        if ($result) {
            $history_query = "INSERT INTO manufacturing_pipeline_history 
                            (id, pipeline_id, old_stage, new_stage, changed_by, date_changed)
                            VALUES (
                                '" . create_guid() . "',
                                '" . $db->quote($order_id) . "',
                                (SELECT stage FROM {$this->table_name} WHERE order_id = '" . $db->quote($order_id) . "'),
                                '" . $db->quote($new_stage) . "',
                                '" . $db->quote($user_id) . "',
                                NOW()
                            )";
            $db->query($history_query);
        }
        
        return $result;
    }
    
    public function getStageSummary()
    {
        global $db;
        
        $query = "SELECT stage, COUNT(*) as count, SUM(value) as total_value 
                 FROM {$this->table_name} 
                 WHERE deleted = 0 
                 GROUP BY stage";
        
        $result = $db->query($query);
        $summary = array();
        
        while ($row = $db->fetchByAssoc($result)) {
            $summary[$row['stage']] = array(
                'count' => $row['count'],
                'total_value' => $row['total_value']
            );
        }
        
        return $summary;
    }
}
