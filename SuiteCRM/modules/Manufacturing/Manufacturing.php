<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('data/SugarBean.php');

class Manufacturing extends SugarBean
{
    public $table_name = 'manufacturing';
    public $object_name = 'Manufacturing';
    public $module_dir = 'Manufacturing';
    public $module_name = 'Manufacturing';
    public $new_schema = true;
    
    public $id;
    public $name;
    public $date_entered;
    public $date_modified;
    public $modified_user_id;
    public $created_by;
    public $description;
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
}
