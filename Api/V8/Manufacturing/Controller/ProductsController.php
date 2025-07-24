<?php
namespace Api\V8\Manufacturing\Controller;

use Api\V8\Manufacturing\Service\ProductsService;
use Api\V8\Manufacturing\Service\PricingService;
use Slim\Http\Request;
use Slim\Http\Response as HttpResponse;

/**
 * Products Controller
 * 
 * Handles all product-related API endpoints for the manufacturing module
 * including CRUD operations, search, client-specific pricing, and suggestions.
 * 
 * @author AI Assistant
 * @version 1.0.0
 */
class ProductsController extends BaseManufacturingController
{
    /**
     * @var ProductsService
     */
    private $productsService;

    /**
     * @var PricingService
     */
    private $pricingService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->productsService = new ProductsService();
        $this->pricingService = new PricingService();
    }

    /**
     * GET /api/v8/manufacturing/products
     * Get all products with optional filtering and pagination
     *
     * @param Request $request
     * @param HttpResponse $response
     * @param array $args
     * @return HttpResponse
     */
    public function getProducts(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $this->logApiAccess($request, 'get_products');

            // Get query parameters
            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = min((int)($params['limit'] ?? 20), 100); // Cap at 100
            $search = $params['search'] ?? null;
            $category = $params['category'] ?? null;
            $inStock = isset($params['in_stock']) ? filter_var($params['in_stock'], FILTER_VALIDATE_BOOLEAN) : null;

            // Build filters
            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($category) $filters['category'] = $category;
            if ($inStock !== null) $filters['in_stock'] = $inStock;

            // Get products from service
            $result = $this->productsService->getProducts($page, $limit, $filters);
            
            // Generate pagination metadata
            $meta = $this->generatePaginationMeta(
                $result['total'],
                $page,
                $limit
            );

            // Generate pagination links
            $baseUrl = $request->getUri()->getPath();
            $queryParams = array_filter($params, function($key) {
                return $key !== 'page';
            }, ARRAY_FILTER_USE_KEY);
            
            $links = $this->generatePaginationLinks(
                $baseUrl,
                $page,
                $meta['total_pages'],
                $queryParams
            );

            return $this->generateSuccessResponse(
                $response,
                $result['products'],
                self::HTTP_STATUS['OK'],
                $meta,
                $links
            );

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * GET /api/v8/manufacturing/products/{id}
     * Get a specific product by ID
     *
     * @param Request $request
     * @param HttpResponse $response
     * @param array $args
     * @return HttpResponse
     */
    public function getProduct(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $productId = $args['id'] ?? null;
            
            if (!$productId) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Product ID is required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $this->logApiAccess($request, 'get_product', ['product_id' => $productId]);

            $product = $this->productsService->getProductById($productId);
            
            if (!$product) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Not Found',
                    'Product not found',
                    self::HTTP_STATUS['NOT_FOUND']
                );
            }

            return $this->generateSuccessResponse($response, $product);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * POST /api/v8/manufacturing/products
     * Create a new product
     *
     * @param Request $request
     * @param HttpResponse $response
     * @param array $args
     * @return HttpResponse
     */
    public function createProduct(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $requestData = json_decode($request->getBody()->getContents(), true);
            
            if (!$requestData || !isset($requestData['data'])) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Invalid request format. Expected JSON with data object.',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $data = $requestData['data'];
            
            // Validate required fields
            $requiredFields = ['name', 'sku', 'price'];
            $attributes = $data['attributes'] ?? [];
            $validationErrors = $this->validateRequiredFields($attributes, $requiredFields);

            if (!empty($validationErrors)) {
                return $this->generateValidationErrorResponse($response, $validationErrors);
            }

            // Sanitize input data
            $attributes = $this->sanitizeInputData($attributes);

            $this->logApiAccess($request, 'create_product', ['sku' => $attributes['sku']]);

            // Create product
            $product = $this->productsService->createProduct($attributes);

            return $this->generateSuccessResponse(
                $response,
                $product,
                self::HTTP_STATUS['CREATED']
            );

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * PUT /api/v8/manufacturing/products/{id}
     * Update an existing product
     *
     * @param Request $request
     * @param HttpResponse $response
     * @param array $args
     * @return HttpResponse
     */
    public function updateProduct(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $productId = $args['id'] ?? null;
            
            if (!$productId) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Product ID is required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $requestData = json_decode($request->getBody()->getContents(), true);
            
            if (!$requestData || !isset($requestData['data'])) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Invalid request format. Expected JSON with data object.',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $attributes = $requestData['data']['attributes'] ?? [];
            
            // Sanitize input data
            $attributes = $this->sanitizeInputData($attributes);

            $this->logApiAccess($request, 'update_product', ['product_id' => $productId]);

            // Update product
            $product = $this->productsService->updateProduct($productId, $attributes);
            
            if (!$product) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Not Found',
                    'Product not found',
                    self::HTTP_STATUS['NOT_FOUND']
                );
            }

            return $this->generateSuccessResponse($response, $product);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * DELETE /api/v8/manufacturing/products/{id}
     * Delete a product
     *
     * @param Request $request
     * @param HttpResponse $response
     * @param array $args
     * @return HttpResponse
     */
    public function deleteProduct(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $productId = $args['id'] ?? null;
            
            if (!$productId) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Product ID is required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $this->logApiAccess($request, 'delete_product', ['product_id' => $productId]);

            $success = $this->productsService->deleteProduct($productId);
            
            if (!$success) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Not Found',
                    'Product not found',
                    self::HTTP_STATUS['NOT_FOUND']
                );
            }

            return $this->generateSuccessResponse(
                $response,
                null,
                self::HTTP_STATUS['NO_CONTENT']
            );

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * GET /api/v8/manufacturing/products/search
     * Advanced product search with filtering
     *
     * @param Request $request
     * @param HttpResponse $response
     * @param array $args
     * @return HttpResponse
     */
    public function searchProducts(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $params = $request->getQueryParams();
            $query = $params['q'] ?? '';
            
            if (empty($query)) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Search query (q) parameter is required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $filters = [];
            if (isset($params['filters'])) {
                $filters = json_decode($params['filters'], true) ?? [];
            }

            $sort = $params['sort'] ?? 'name_asc';
            $page = (int)($params['page'] ?? 1);
            $limit = min((int)($params['limit'] ?? 20), 100);

            $this->logApiAccess($request, 'search_products', ['query' => $query]);

            $result = $this->productsService->searchProducts($query, $filters, $sort, $page, $limit);

            // Generate pagination metadata
            $meta = $this->generatePaginationMeta(
                $result['total'],
                $page,
                $limit
            );

            return $this->generateSuccessResponse(
                $response,
                $result['products'],
                self::HTTP_STATUS['OK'],
                array_merge($meta, [
                    'search_query' => $query,
                    'filters_applied' => $filters,
                    'sort_order' => $sort
                ])
            );

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * GET /api/v8/manufacturing/products/{id}/pricing/{clientId}
     * Get client-specific pricing for a product
     *
     * @param Request $request
     * @param HttpResponse $response
     * @param array $args
     * @return HttpResponse
     */
    public function getClientPricing(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $productId = $args['id'] ?? null;
            $clientId = $args['clientId'] ?? null;
            
            if (!$productId || !$clientId) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Both product ID and client ID are required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $params = $request->getQueryParams();
            $quantity = (int)($params['quantity'] ?? 1);

            $this->logApiAccess($request, 'get_client_pricing', [
                'product_id' => $productId,
                'client_id' => $clientId,
                'quantity' => $quantity
            ]);

            $pricing = $this->pricingService->getClientPricing($productId, $clientId, $quantity);
            
            if (!$pricing) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Not Found',
                    'Product or client not found, or pricing not available',
                    self::HTTP_STATUS['NOT_FOUND']
                );
            }

            return $this->generateSuccessResponse($response, $pricing);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }
}
