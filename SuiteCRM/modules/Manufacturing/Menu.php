<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

global $mod_strings, $app_strings, $sugar_config;

$module_menu = array(
    array(
        "index.php?module=Manufacturing&action=ProductCatalog", 
        $mod_strings['LNK_PRODUCT_CATALOG'], 
        "ProductCatalog", 
        'Manufacturing'
    ),
    array(
        "index.php?module=Manufacturing&action=OrderDashboard", 
        $mod_strings['LNK_ORDER_DASHBOARD'], 
        "OrderDashboard", 
        'Manufacturing'
    ),
);
