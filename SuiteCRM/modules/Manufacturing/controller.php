<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/MVC/Controller/SugarController.php');

class ManufacturingController extends SugarController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function action_ProductCatalog()
    {
        $this->view = 'productcatalog';
    }
    
    public function action_productcatalog()
    {
        $this->view = 'productcatalog';
    }
    
    public function action_OrderDashboard()
    {
        $this->view = 'orderdashboard';
    }
    
    public function action_orderdashboard()
    {
        $this->view = 'orderdashboard';
    }
    
    public function action_ListView()
    {
        $this->view = 'list';
    }
    
    public function action_DetailView()
    {
        $this->view = 'detail';
    }
    
    public function action_EditView()
    {
        $this->view = 'edit';
    }
    
    public function action_index()
    {
        $this->action_ProductCatalog();
    }
    
    public function action_updateStage()
    {
        require_once('modules/Manufacturing/Pipeline.php');
        
        $order_id = $_POST['order_id'];
        $new_stage = $_POST['new_stage'];
        
        $pipeline = new Pipeline();
        $result = $pipeline->updateStage($order_id, $new_stage, $GLOBALS['current_user']->id);
        
        header('Content-Type: application/json');
        echo json_encode(array('success' => (bool)$result));
        exit;
    }
    
    protected function action_save()
    {
        parent::action_save();
    }
}
