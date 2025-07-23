<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('data/SugarBean.php');

class Product extends SugarBean
{
    public $table_name = 'manufacturing_products';
    public $object_name = 'Product';
    public $module_dir = 'Manufacturing';
    public $module_name = 'Manufacturing';
    public $new_schema = true;
    
    public $id;
    public $name;
    public $sku;
    public $category;
    public $base_price;
    public $description;
    public $specifications;
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
    
    public function getProductsByCategory($category = '')
    {
        global $db;
        
        $query = "SELECT * FROM {$this->table_name} WHERE deleted = 0";
        if (!empty($category)) {
            $query .= " AND category = '" . $db->quote($category) . "'";
        }
        $query .= " ORDER BY name";
        
        return $db->query($query);
    }
    
    public function getClientPricing($client_id, $product_id)
    {
        global $db;
        
        $query = "SELECT cp.price, cp.discount_percentage 
                 FROM manufacturing_client_pricing cp 
                 WHERE cp.client_id = '" . $db->quote($client_id) . "' 
                 AND cp.product_id = '" . $db->quote($product_id) . "' 
                 AND cp.deleted = 0";
        
        $result = $db->query($query);
        if ($row = $db->fetchByAssoc($result)) {
            return $row;
        }
        
        return array('price' => $this->base_price, 'discount_percentage' => 0);
    }
}
