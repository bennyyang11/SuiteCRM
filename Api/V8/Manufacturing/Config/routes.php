<?php

use Api\V8\Factory\ParamsMiddlewareFactory;
use Api\V8\Manufacturing\Controller\ProductsController;
use Api\V8\Manufacturing\Controller\OrdersController;
use Api\V8\Manufacturing\Controller\QuotesController;
use Api\V8\Manufacturing\Controller\InventoryController;
use Api\V8\Manufacturing\Controller\AnalyticsController;
use Api\V8\Manufacturing\Controller\SuggestionsController;
use Api\V8\Manufacturing\Param;

/**
 * Manufacturing API Routes - Version 8
 * RESTful endpoints for SuiteCRM Manufacturing Distribution Module
 * 
 * API Versioning Strategy:
 * - /api/v8/manufacturing/* - Current stable version
 * - /api/v9/manufacturing/* - Future version (backward compatible)
 * 
 * @author AI Assistant
 * @version 1.0.0
 */

$app->group('/manufacturing', function () use ($app) {
    /** @var ParamsMiddlewareFactory $paramsMiddlewareFactory */
    $paramsMiddlewareFactory = $app->getContainer()->get(ParamsMiddlewareFactory::class);

    // ========================================
    // PRODUCTS API ENDPOINTS
    // ========================================
    
    /**
     * GET /api/v8/manufacturing/products
     * Get all products with optional filtering and pagination
     */
    $app->get('/products', ProductsController::class . ':getProducts')
        ->add($paramsMiddlewareFactory->bind(Param\GetProductsParams::class));

    /**
     * GET /api/v8/manufacturing/products/{id}
     * Get a specific product by ID
     */
    $app->get('/products/{id}', ProductsController::class . ':getProduct')
        ->add($paramsMiddlewareFactory->bind(Param\GetProductParams::class));

    /**
     * POST /api/v8/manufacturing/products
     * Create a new product
     */
    $app->post('/products', ProductsController::class . ':createProduct')
        ->add($paramsMiddlewareFactory->bind(Param\CreateProductParams::class));

    /**
     * PUT /api/v8/manufacturing/products/{id}
     * Update an existing product
     */
    $app->put('/products/{id}', ProductsController::class . ':updateProduct')
        ->add($paramsMiddlewareFactory->bind(Param\UpdateProductParams::class));

    /**
     * DELETE /api/v8/manufacturing/products/{id}
     * Delete a product
     */
    $app->delete('/products/{id}', ProductsController::class . ':deleteProduct')
        ->add($paramsMiddlewareFactory->bind(Param\DeleteProductParams::class));

    /**
     * GET /api/v8/manufacturing/products/search
     * Advanced product search with filtering
     */
    $app->get('/products/search', ProductsController::class . ':searchProducts')
        ->add($paramsMiddlewareFactory->bind(Param\SearchProductsParams::class));

    /**
     * GET /api/v8/manufacturing/products/{id}/pricing/{clientId}
     * Get client-specific pricing for a product
     */
    $app->get('/products/{id}/pricing/{clientId}', ProductsController::class . ':getClientPricing')
        ->add($paramsMiddlewareFactory->bind(Param\GetClientPricingParams::class));

    /**
     * GET /api/v8/manufacturing/products/suggestions
     * Get product suggestions based on criteria
     */
    $app->get('/products/suggestions', SuggestionsController::class . ':getProductSuggestions')
        ->add($paramsMiddlewareFactory->bind(Param\GetSuggestionsParams::class));

    // ========================================
    // ORDERS API ENDPOINTS
    // ========================================

    /**
     * GET /api/v8/manufacturing/orders
     * Get all orders with optional filtering
     */
    $app->get('/orders', OrdersController::class . ':getOrders')
        ->add($paramsMiddlewareFactory->bind(Param\GetOrdersParams::class));

    /**
     * GET /api/v8/manufacturing/orders/{id}
     * Get a specific order by ID
     */
    $app->get('/orders/{id}', OrdersController::class . ':getOrder')
        ->add($paramsMiddlewareFactory->bind(Param\GetOrderParams::class));

    /**
     * POST /api/v8/manufacturing/orders
     * Create a new order
     */
    $app->post('/orders', OrdersController::class . ':createOrder')
        ->add($paramsMiddlewareFactory->bind(Param\CreateOrderParams::class));

    /**
     * PUT /api/v8/manufacturing/orders/{id}
     * Update an existing order
     */
    $app->put('/orders/{id}', OrdersController::class . ':updateOrder')
        ->add($paramsMiddlewareFactory->bind(Param\UpdateOrderParams::class));

    /**
     * PUT /api/v8/manufacturing/orders/{id}/status
     * Update order status in pipeline
     */
    $app->put('/orders/{id}/status', OrdersController::class . ':updateOrderStatus')
        ->add($paramsMiddlewareFactory->bind(Param\UpdateOrderStatusParams::class));

    /**
     * GET /api/v8/manufacturing/orders/pipeline
     * Get orders organized by pipeline stages
     */
    $app->get('/orders/pipeline', OrdersController::class . ':getOrdersPipeline')
        ->add($paramsMiddlewareFactory->bind(Param\GetOrdersPipelineParams::class));

    /**
     * GET /api/v8/manufacturing/orders/{id}/tracking
     * Get order tracking information
     */
    $app->get('/orders/{id}/tracking', OrdersController::class . ':getOrderTracking')
        ->add($paramsMiddlewareFactory->bind(Param\GetOrderTrackingParams::class));

    // ========================================
    // QUOTES API ENDPOINTS
    // ========================================

    /**
     * GET /api/v8/manufacturing/quotes
     * Get all quotes with optional filtering
     */
    $app->get('/quotes', QuotesController::class . ':getQuotes')
        ->add($paramsMiddlewareFactory->bind(Param\GetQuotesParams::class));

    /**
     * GET /api/v8/manufacturing/quotes/{id}
     * Get a specific quote by ID
     */
    $app->get('/quotes/{id}', QuotesController::class . ':getQuote')
        ->add($paramsMiddlewareFactory->bind(Param\GetQuoteParams::class));

    /**
     * POST /api/v8/manufacturing/quotes
     * Create a new quote
     */
    $app->post('/quotes', QuotesController::class . ':createQuote')
        ->add($paramsMiddlewareFactory->bind(Param\CreateQuoteParams::class));

    /**
     * PUT /api/v8/manufacturing/quotes/{id}
     * Update an existing quote
     */
    $app->put('/quotes/{id}', QuotesController::class . ':updateQuote')
        ->add($paramsMiddlewareFactory->bind(Param\UpdateQuoteParams::class));

    /**
     * GET /api/v8/manufacturing/quotes/{id}/pdf
     * Generate and download quote PDF
     */
    $app->get('/quotes/{id}/pdf', QuotesController::class . ':generateQuotePDF')
        ->add($paramsMiddlewareFactory->bind(Param\GenerateQuotePDFParams::class));

    /**
     * POST /api/v8/manufacturing/quotes/{id}/accept
     * Accept a quote and convert to order
     */
    $app->post('/quotes/{id}/accept', QuotesController::class . ':acceptQuote')
        ->add($paramsMiddlewareFactory->bind(Param\AcceptQuoteParams::class));

    /**
     * POST /api/v8/manufacturing/quotes/{id}/email
     * Email quote to client
     */
    $app->post('/quotes/{id}/email', QuotesController::class . ':emailQuote')
        ->add($paramsMiddlewareFactory->bind(Param\EmailQuoteParams::class));

    // ========================================
    // INVENTORY API ENDPOINTS
    // ========================================

    /**
     * GET /api/v8/manufacturing/inventory
     * Get inventory status for all products
     */
    $app->get('/inventory', InventoryController::class . ':getInventory')
        ->add($paramsMiddlewareFactory->bind(Param\GetInventoryParams::class));

    /**
     * GET /api/v8/manufacturing/inventory/{productId}
     * Get inventory status for specific product
     */
    $app->get('/inventory/{productId}', InventoryController::class . ':getProductInventory')
        ->add($paramsMiddlewareFactory->bind(Param\GetProductInventoryParams::class));

    /**
     * PUT /api/v8/manufacturing/inventory/{productId}
     * Update inventory levels
     */
    $app->put('/inventory/{productId}', InventoryController::class . ':updateInventory')
        ->add($paramsMiddlewareFactory->bind(Param\UpdateInventoryParams::class));

    /**
     * POST /api/v8/manufacturing/inventory/sync
     * Sync inventory with external systems
     */
    $app->post('/inventory/sync', InventoryController::class . ':syncInventory')
        ->add($paramsMiddlewareFactory->bind(Param\SyncInventoryParams::class));

    /**
     * GET /api/v8/manufacturing/inventory/alerts
     * Get low stock alerts
     */
    $app->get('/inventory/alerts', InventoryController::class . ':getStockAlerts')
        ->add($paramsMiddlewareFactory->bind(Param\GetStockAlertsParams::class));

    // ========================================
    // ANALYTICS API ENDPOINTS
    // ========================================

    /**
     * GET /api/v8/manufacturing/analytics/sales
     * Get sales analytics and metrics
     */
    $app->get('/analytics/sales', AnalyticsController::class . ':getSalesAnalytics')
        ->add($paramsMiddlewareFactory->bind(Param\GetSalesAnalyticsParams::class));

    /**
     * GET /api/v8/manufacturing/analytics/performance
     * Get performance metrics
     */
    $app->get('/analytics/performance', AnalyticsController::class . ':getPerformanceMetrics')
        ->add($paramsMiddlewareFactory->bind(Param\GetPerformanceMetricsParams::class));

    /**
     * GET /api/v8/manufacturing/analytics/dashboard
     * Get dashboard data for manufacturing
     */
    $app->get('/analytics/dashboard', AnalyticsController::class . ':getDashboardData')
        ->add($paramsMiddlewareFactory->bind(Param\GetDashboardDataParams::class));

    /**
     * GET /api/v8/manufacturing/analytics/forecasting
     * Get sales forecasting data
     */
    $app->get('/analytics/forecasting', AnalyticsController::class . ':getForecastingData')
        ->add($paramsMiddlewareFactory->bind(Param\GetForecastingDataParams::class));

    // ========================================
    // HEALTH CHECK & META ENDPOINTS
    // ========================================

    /**
     * GET /api/v8/manufacturing/health
     * API health check endpoint
     */
    $app->get('/health', function ($request, $response) {
        return $response->withJson([
            'status' => 'healthy',
            'version' => '8.0.0',
            'timestamp' => date('c'),
            'endpoints' => [
                'products' => 'operational',
                'orders' => 'operational',
                'quotes' => 'operational',
                'inventory' => 'operational',
                'analytics' => 'operational'
            ]
        ]);
    });

    /**
     * GET /api/v8/manufacturing/swagger.json
     * Get OpenAPI/Swagger documentation
     */
    $app->get('/swagger.json', function ($request, $response) {
        $swaggerPath = __DIR__ . '/../swagger.yaml';
        if (file_exists($swaggerPath)) {
            $swagger = yaml_parse_file($swaggerPath);
            return $response->withJson($swagger);
        }
        return $response->withStatus(404)->withJson(['error' => 'Swagger documentation not found']);
    });
});
