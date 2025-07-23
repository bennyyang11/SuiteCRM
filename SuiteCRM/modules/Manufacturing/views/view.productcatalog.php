<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/MVC/View/SugarView.php');
require_once('modules/Manufacturing/Product.php');

class ManufacturingViewProductcatalog extends SugarView
{
    public function __construct()
    {
        parent::__construct();
    }

    public function display()
    {
        global $mod_strings, $current_user, $sugar_smarty;
        
        $product = new Product();
        $products_result = $product->getProductsByCategory();
        $products = array();
        
        while ($row = $GLOBALS['db']->fetchByAssoc($products_result)) {
            $products[] = $row;
        }
        
        $sugar_smarty->assign('products', $products);
        $sugar_smarty->assign('current_user', $current_user);
        $sugar_smarty->assign('module_name', 'Manufacturing');
        
        echo '<div id="manufacturing-product-catalog" class="container-fluid">';
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<h2 class="page-header">Product Catalog</h2>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="row">';
        echo '<div class="col-md-3">';
        echo '<div class="card">';
        echo '<div class="card-header">Filters</div>';
        echo '<div class="card-body">';
        echo '<div class="form-group">';
        echo '<label>Category</label>';
        echo '<select class="form-control" id="category-filter">';
        echo '<option value="">All Categories</option>';
        echo '<option value="electrical">Electrical</option>';
        echo '<option value="mechanical">Mechanical</option>';
        echo '<option value="tools">Tools</option>';
        echo '</select>';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label>Search</label>';
        echo '<input type="text" class="form-control" id="search-filter" placeholder="Search products...">';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="col-md-9">';
        echo '<div id="product-grid" class="row">';
        
        foreach ($products as $product_data) {
            echo '<div class="col-lg-4 col-md-6 mb-4">';
            echo '<div class="card product-card">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . htmlspecialchars($product_data['name']) . '</h5>';
            echo '<p class="card-text">' . htmlspecialchars($product_data['description']) . '</p>';
            echo '<p class="product-sku">SKU: ' . htmlspecialchars($product_data['sku']) . '</p>';
            echo '<p class="product-price">$' . number_format($product_data['base_price'], 2) . '</p>';
            echo '<button class="btn btn-primary btn-sm">View Details</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Include CSS and JS
        echo '<style>
        .product-card { height: 100%; }
        .product-price { font-size: 1.2em; font-weight: bold; color: #28a745; }
        .product-sku { font-size: 0.9em; color: #6c757d; }
        .page-header { margin-bottom: 30px; }
        </style>';
        
        echo '<script>
        $(document).ready(function() {
            $("#category-filter").change(function() {
                var category = $(this).val();
                filterProducts(category, $("#search-filter").val());
            });
            
            $("#search-filter").on("input", function() {
                var search = $(this).val();
                filterProducts($("#category-filter").val(), search);
            });
            
            function filterProducts(category, search) {
                $(".product-card").parent().hide();
                $(".product-card").each(function() {
                    var card = $(this);
                    var title = card.find(".card-title").text().toLowerCase();
                    var description = card.find(".card-text").text().toLowerCase();
                    
                    var matchesSearch = search === "" || title.includes(search.toLowerCase()) || description.includes(search.toLowerCase());
                    var matchesCategory = category === "" || card.data("category") === category;
                    
                    if (matchesSearch && matchesCategory) {
                        card.parent().show();
                    }
                });
            }
        });
        </script>';
    }
}
