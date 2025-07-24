<?php
namespace Api\V8\Manufacturing\Controller;

use Api\V8\Manufacturing\Service\OrdersService;
use Slim\Http\Request;
use Slim\Http\Response as HttpResponse;

/**
 * Orders Controller
 * 
 * Handles all order-related API endpoints for the manufacturing module
 * including CRUD operations, pipeline management, and status updates.
 * 
 * @author AI Assistant
 * @version 1.0.0
 */
class OrdersController extends BaseManufacturingController
{
    /**
     * @var OrdersService
     */
    private $ordersService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->ordersService = new OrdersService();
    }

    /**
     * GET /api/v8/manufacturing/orders
     * Get all orders with optional filtering
     */
    public function getOrders(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $this->logApiAccess($request, 'get_orders');

            $params = $request->getQueryParams();
            $page = (int)($params['page'] ?? 1);
            $limit = min((int)($params['limit'] ?? 20), 100);
            
            $filters = [];
            if (!empty($params['status'])) $filters['status'] = $params['status'];
            if (!empty($params['client_id'])) $filters['client_id'] = $params['client_id'];
            if (!empty($params['date_from'])) $filters['date_from'] = $params['date_from'];
            if (!empty($params['date_to'])) $filters['date_to'] = $params['date_to'];

            $result = $this->ordersService->getOrders($page, $limit, $filters);
            
            $meta = $this->generatePaginationMeta($result['total'], $page, $limit);
            $links = $this->generatePaginationLinks(
                $request->getUri()->getPath(),
                $page,
                $meta['total_pages'],
                array_filter($params, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)
            );

            return $this->generateSuccessResponse($response, $result['orders'], self::HTTP_STATUS['OK'], $meta, $links);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * GET /api/v8/manufacturing/orders/{id}
     * Get a specific order by ID
     */
    public function getOrder(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $orderId = $args['id'] ?? null;
            
            if (!$orderId) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Order ID is required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $this->logApiAccess($request, 'get_order', ['order_id' => $orderId]);

            $order = $this->ordersService->getOrderById($orderId);
            
            if (!$order) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Not Found',
                    'Order not found',
                    self::HTTP_STATUS['NOT_FOUND']
                );
            }

            return $this->generateSuccessResponse($response, $order);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * POST /api/v8/manufacturing/orders
     * Create a new order
     */
    public function createOrder(Request $request, HttpResponse $response, array $args): HttpResponse
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
            $requiredFields = ['client_id', 'items'];
            $attributes = $data['attributes'] ?? [];
            $validationErrors = $this->validateRequiredFields($attributes, $requiredFields);

            if (!empty($validationErrors)) {
                return $this->generateValidationErrorResponse($response, $validationErrors);
            }

            $attributes = $this->sanitizeInputData($attributes);

            $this->logApiAccess($request, 'create_order', ['client_id' => $attributes['client_id']]);

            $order = $this->ordersService->createOrder($attributes);

            return $this->generateSuccessResponse($response, $order, self::HTTP_STATUS['CREATED']);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * PUT /api/v8/manufacturing/orders/{id}/status
     * Update order status in pipeline
     */
    public function updateOrderStatus(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $orderId = $args['id'] ?? null;
            
            if (!$orderId) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Order ID is required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $requestData = json_decode($request->getBody()->getContents(), true);
            
            if (!$requestData || !isset($requestData['data']['attributes']['status'])) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Status is required in request body',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $newStatus = $requestData['data']['attributes']['status'];
            $notes = $requestData['data']['attributes']['notes'] ?? '';

            $this->logApiAccess($request, 'update_order_status', [
                'order_id' => $orderId,
                'new_status' => $newStatus
            ]);

            $order = $this->ordersService->updateOrderStatus($orderId, $newStatus, $notes);
            
            if (!$order) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Not Found',
                    'Order not found',
                    self::HTTP_STATUS['NOT_FOUND']
                );
            }

            return $this->generateSuccessResponse($response, $order);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * GET /api/v8/manufacturing/orders/pipeline
     * Get orders organized by pipeline stages
     */
    public function getOrdersPipeline(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $this->logApiAccess($request, 'get_orders_pipeline');

            $params = $request->getQueryParams();
            $filters = [];
            if (!empty($params['date_from'])) $filters['date_from'] = $params['date_from'];
            if (!empty($params['date_to'])) $filters['date_to'] = $params['date_to'];

            $pipeline = $this->ordersService->getOrdersPipeline($filters);

            return $this->generateSuccessResponse($response, $pipeline);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    /**
     * GET /api/v8/manufacturing/orders/{id}/tracking
     * Get order tracking information
     */
    public function getOrderTracking(Request $request, HttpResponse $response, array $args): HttpResponse
    {
        try {
            $orderId = $args['id'] ?? null;
            
            if (!$orderId) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Bad Request',
                    'Order ID is required',
                    self::HTTP_STATUS['BAD_REQUEST']
                );
            }

            $this->logApiAccess($request, 'get_order_tracking', ['order_id' => $orderId]);

            $tracking = $this->ordersService->getOrderTracking($orderId);
            
            if (!$tracking) {
                return $this->generateManufacturingErrorResponse(
                    $response,
                    'Not Found',
                    'Order tracking not found',
                    self::HTTP_STATUS['NOT_FOUND']
                );
            }

            return $this->generateSuccessResponse($response, $tracking);

        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }
}
