<?php
namespace Api\V8\Manufacturing\Param;

/**
 * Base Manufacturing Parameter Class
 * 
 * Provides basic parameter validation functionality for manufacturing API endpoints.
 */
class BaseManufacturingParam
{
    public function getRules(): array
    {
        return [];
    }
}

// Create all required parameter classes as stubs

class GetProductParams extends BaseManufacturingParam {}
class CreateProductParams extends BaseManufacturingParam {}
class UpdateProductParams extends BaseManufacturingParam {}
class DeleteProductParams extends BaseManufacturingParam {}
class SearchProductsParams extends BaseManufacturingParam {}
class GetClientPricingParams extends BaseManufacturingParam {}
class GetSuggestionsParams extends BaseManufacturingParam {}

class GetOrdersParams extends BaseManufacturingParam {}
class GetOrderParams extends BaseManufacturingParam {}
class CreateOrderParams extends BaseManufacturingParam {}
class UpdateOrderParams extends BaseManufacturingParam {}
class UpdateOrderStatusParams extends BaseManufacturingParam {}
class GetOrdersPipelineParams extends BaseManufacturingParam {}
class GetOrderTrackingParams extends BaseManufacturingParam {}

class GetQuotesParams extends BaseManufacturingParam {}
class GetQuoteParams extends BaseManufacturingParam {}
class CreateQuoteParams extends BaseManufacturingParam {}
class UpdateQuoteParams extends BaseManufacturingParam {}
class GenerateQuotePDFParams extends BaseManufacturingParam {}
class AcceptQuoteParams extends BaseManufacturingParam {}
class EmailQuoteParams extends BaseManufacturingParam {}

class GetInventoryParams extends BaseManufacturingParam {}
class GetProductInventoryParams extends BaseManufacturingParam {}
class UpdateInventoryParams extends BaseManufacturingParam {}
class SyncInventoryParams extends BaseManufacturingParam {}
class GetStockAlertsParams extends BaseManufacturingParam {}

class GetSalesAnalyticsParams extends BaseManufacturingParam {}
class GetPerformanceMetricsParams extends BaseManufacturingParam {}
class GetDashboardDataParams extends BaseManufacturingParam {}
class GetForecastingDataParams extends BaseManufacturingParam {}
