<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/MVC/View/SugarView.php');

class ManufacturingViewProductcatalog extends SugarView
{
    public function __construct()
    {
        parent::__construct();
    }

    public function display()
    {
        global $mod_strings, $current_user;
        
        // Get current user context for React app
        $user_context = array(
            'id' => $current_user->id,
            'name' => $current_user->name,
            'email' => $current_user->email1,
            'is_admin' => $current_user->is_admin
        );
        
        // Include the React app
        $frontend_path = 'modules/Manufacturing/frontend/';
        $app_html_path = $frontend_path . 'ProductCatalogApp.html';
        
        // Check if React app exists
        if (file_exists($app_html_path)) {
            // Serve the React app
            echo '<div style="margin: -20px -15px;">';
            
            // Pass SuiteCRM context to React app
            echo '<script>';
            echo 'window.SUITECRM_CONTEXT = ' . json_encode(array(
                'user' => $user_context,
                'base_url' => rtrim($GLOBALS['sugar_config']['site_url'], '/'),
                'api_url' => rtrim($GLOBALS['sugar_config']['site_url'], '/') . '/api_manufacturing.php',
                'module' => 'Manufacturing',
                'csrf_token' => !empty($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : ''
            )) . ';';
            echo '</script>';
            
            // Load the React app HTML
            $html_content = file_get_contents($app_html_path);
            
            // Update paths to be relative to SuiteCRM root
            $html_content = str_replace('src="src/', 'src="' . $frontend_path . 'src/', $html_content);
            $html_content = str_replace('href="src/', 'href="' . $frontend_path . 'src/', $html_content);
            $html_content = str_replace('href="dist/', 'href="' . $frontend_path . 'dist/', $html_content);
            $html_content = str_replace('href="manifest.json"', 'href="' . $frontend_path . 'manifest.json"', $html_content);
            $html_content = str_replace('href="icons/', 'href="' . $frontend_path . 'icons/', $html_content);
            
            echo $html_content;
            echo '</div>';
            
        } else {
            // Fallback: Basic product catalog if React app not available
            $this->displayFallbackCatalog();
        }
    }
    
    /**
     * Fallback product catalog using basic HTML/PHP
     */
    private function displayFallbackCatalog()
    {
        global $current_user;
        
        echo '<div class="container-fluid">';
        echo '<div class="row">';
        echo '<div class="col-12">';
        echo '<h2 class="page-header">Product Catalog</h2>';
        echo '<div class="alert alert-info">';
        echo '<strong>Note:</strong> Loading basic product catalog. For the full mobile experience, ensure the React frontend is properly built.';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Simple product grid
        echo '<div class="row">';
        echo '<div class="col-md-3">';
        echo '<div class="card">';
        echo '<div class="card-header">Quick Filters</div>';
        echo '<div class="card-body">';
        echo '<div class="form-group">';
        echo '<label>Search</label>';
        echo '<input type="text" class="form-control" id="product-search" placeholder="Search products...">';
        echo '</div>';
        echo '<div class="form-group">';
        echo '<label>Category</label>';
        echo '<select class="form-control" id="category-filter">';
        echo '<option value="">All Categories</option>';
        echo '<option value="Steel">Steel</option>';
        echo '<option value="Aluminum">Aluminum</option>';
        echo '<option value="Fasteners">Fasteners</option>';
        echo '<option value="Tools">Tools</option>';
        echo '<option value="Electrical">Electrical</option>';
        echo '</select>';
        echo '</div>';
        echo '<button class="btn btn-primary btn-block" onclick="loadProducts()">Search Products</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="col-md-9">';
        echo '<div id="products-container">';
        echo '<div class="text-center py-5">';
        echo '<p>Click "Search Products" to load products from the API</p>';
        echo '<div class="spinner-border d-none" id="loading-spinner"></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Basic JavaScript for API integration
        echo '<script>';
        echo 'function loadProducts() {';
        echo '  const search = document.getElementById("product-search").value;';
        echo '  const category = document.getElementById("category-filter").value;';
        echo '  const container = document.getElementById("products-container");';
        echo '  const spinner = document.getElementById("loading-spinner");';
        echo '  ';
        echo '  spinner.classList.remove("d-none");';
        echo '  container.innerHTML = "<div class=\'text-center py-5\'><div class=\'spinner-border\'></div><p>Loading products...</p></div>";';
        echo '  ';
        echo '  let url = "/api_manufacturing.php?endpoint=products/search&limit=12";';
        echo '  if (search) url += "&q=" + encodeURIComponent(search);';
        echo '  if (category) url += "&category=" + encodeURIComponent(category);';
        echo '  ';
        echo '  fetch(url)';
        echo '    .then(response => response.json())';
        echo '    .then(data => {';
        echo '      if (data.status === "success") {';
        echo '        displayProducts(data.data.products);';
        echo '      } else {';
        echo '        container.innerHTML = "<div class=\'alert alert-danger\'>Error loading products: " + (data.error?.message || "Unknown error") + "</div>";';
        echo '      }';
        echo '    })';
        echo '    .catch(error => {';
        echo '      container.innerHTML = "<div class=\'alert alert-danger\'>Network error: " + error.message + "</div>";';
        echo '    });';
        echo '}';
        echo '';
        echo 'function displayProducts(products) {';
        echo '  const container = document.getElementById("products-container");';
        echo '  if (products.length === 0) {';
        echo '    container.innerHTML = "<div class=\'alert alert-info\'>No products found matching your criteria.</div>";';
        echo '    return;';
        echo '  }';
        echo '  ';
        echo '  let html = "<div class=\'row\'>";';
        echo '  products.forEach(product => {';
        echo '    html += `';
        echo '      <div class="col-lg-4 col-md-6 mb-4">';
        echo '        <div class="card h-100">';
        echo '          <div class="card-body">';
        echo '            <h5 class="card-title">${product.name}</h5>';
        echo '            <p class="card-text">${product.description || ""}</p>';
        echo '            <p class="text-muted small">SKU: ${product.sku}</p>';
        echo '            <p class="text-muted small">Category: ${product.category}</p>';
        echo '            <div class="d-flex justify-content-between align-items-center">';
        echo '              <span class="h5 mb-0 text-success">$${product.pricing?.base_price?.toFixed(2) || "0.00"}</span>';
        echo '              <span class="badge badge-${product.inventory?.stock_status === "In Stock" ? "success" : product.inventory?.stock_status === "Low Stock" ? "warning" : "danger"}">';
        echo '                ${product.inventory?.stock_status || "Unknown"}';
        echo '              </span>';
        echo '            </div>';
        echo '            <button class="btn btn-primary btn-sm mt-2" onclick="addToQuote(\\\'${product.id}\\\')">Add to Quote</button>';
        echo '          </div>';
        echo '        </div>';
        echo '      </div>`;';
        echo '  });';
        echo '  html += "</div>";';
        echo '  container.innerHTML = html;';
        echo '}';
        echo '';
        echo 'function addToQuote(productId) {';
        echo '  alert("Add to Quote functionality requires the full React app. Product ID: " + productId);';
        echo '}';
        echo '';
        echo '// Auto-load products on page load';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo '  loadProducts();';
        echo '});';
        echo '</script>';
        
        // CSS for better styling
        echo '<style>';
        echo '.page-header { border-bottom: 1px solid #dee2e6; padding-bottom: 10px; margin-bottom: 30px; }';
        echo '.card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }';
        echo '.card:hover { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); transition: box-shadow 0.15s ease-in-out; }';
        echo '</style>';
    }
}
