# AMP Code Prompt: Backend API Development for Manufacturing Product Catalog

## TASK OVERVIEW
You are developing the backend API infrastructure for a SuiteCRM Enterprise Legacy Modernization project. Your focus is building robust, performant APIs for the mobile-responsive manufacturing product catalog with client-specific pricing capabilities.

## PRIMARY OBJECTIVES
1. **API Architecture**: Create RESTful endpoints following SuiteCRM conventions
2. **Performance Optimization**: Implement caching and efficient data retrieval
3. **Business Logic**: Build pricing calculation and inventory integration engines
4. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### 1. **ProductCatalogAPI.php Creation**
- **Location**: `/Api/v1/manufacturing/ProductCatalogAPI.php`
- **Structure**: Follow SuiteCRM API v8 patterns and conventions
- **Authentication**: Integrate with existing SuiteCRM JWT/OAuth system
- **Error Handling**: Implement proper HTTP status codes and error responses
- **Documentation**: Include inline OpenAPI/Swagger documentation

### 2. **Product Search Endpoint with Filtering**
- **Endpoint**: `GET /Api/v1/manufacturing/products/search`
- **Parameters**: 
  - `q` (search query), `category`, `material`, `price_min`, `price_max`
  - `sku`, `in_stock`, `page`, `limit`, `sort_by`, `sort_order`
- **Features**: 
  - Full-text search across product names, descriptions, SKUs
  - Multi-criteria filtering with AND/OR logic
  - Pagination with metadata (total_count, page_info)
  - Sorting by relevance, price, availability, name

### 3. **Client-Specific Pricing Calculation Engine**
- **Core Logic**: Dynamic pricing based on client tier and contracts
- **Pricing Hierarchy**: Contract Price → Tier Price → Base Price
- **Volume Discounts**: Calculate quantity-based pricing automatically
- **Currency Support**: Handle multiple currencies and conversions
- **Caching**: Cache pricing calculations for performance
- **Audit Trail**: Log pricing calculations for compliance

### 4. **Product Availability/Stock Integration**
- **Real-time Checks**: Query current inventory levels
- **Stock Status**: Available, Low Stock, Out of Stock, Backordered
- **Reservation System**: Temporary stock holds for quote building
- **Alternative Products**: Suggest similar products when out of stock
- **Restock Estimates**: Calculate and display expected availability dates
- **Warehouse Support**: Multi-location inventory tracking

### 5. **Caching Layer Implementation (Redis)**
- **Product Cache**: Cache frequently accessed product data (TTL: 1 hour)
- **Pricing Cache**: Cache complex pricing calculations (TTL: 30 minutes)
- **Search Cache**: Cache popular search results (TTL: 15 minutes)
- **Inventory Cache**: Cache stock levels with short TTL (TTL: 5 minutes)
- **Cache Invalidation**: Smart cache clearing on data updates
- **Fallback Strategy**: Graceful degradation when Redis unavailable

## TECHNICAL REQUIREMENTS

### API Standards:
- **RESTful Design**: Proper HTTP verbs, resource naming, status codes
- **JSON Response Format**: Consistent structure with metadata
- **Rate Limiting**: Implement API throttling for mobile clients
- **CORS Headers**: Enable secure cross-origin requests
- **API Versioning**: Support future API evolution

### Performance Requirements:
- **Response Times**: <500ms for product searches, <200ms for cached responses
- **Pagination**: Efficient large dataset handling (cursor-based preferred)
- **Database Optimization**: Use proper indexes and query optimization
- **Connection Pooling**: Efficient database connection management
- **Background Jobs**: Async processing for heavy operations

### Security Implementation:
- **Input Validation**: Sanitize all search parameters and filters
- **SQL Injection Prevention**: Use parameterized queries exclusively
- **Rate Limiting**: Prevent API abuse and DoS attacks
- **Access Control**: Enforce user permissions and data visibility
- **Audit Logging**: Log all API calls and data access

### Integration Points:
- **SuiteCRM Modules**: Accounts, Contacts, Opportunities integration
- **User Management**: Leverage existing user/role system
- **Database Layer**: Use SuiteCRM's existing database abstraction
- **Notification System**: Integrate with email/SMS systems

## CODE STRUCTURE EXAMPLE

```php
<?php
namespace Api\V1\Manufacturing;

class ProductCatalogAPI extends BaseAPI {
    
    // GET /Api/v1/manufacturing/products/search
    public function searchProducts($request) {
        // Implement search with filtering
    }
    
    // GET /Api/v1/manufacturing/products/{id}/pricing
    public function getProductPricing($productId, $clientId) {
        // Implement client-specific pricing
    }
    
    // GET /Api/v1/manufacturing/products/{id}/availability
    public function checkAvailability($productId) {
        // Real-time inventory check
    }
}
```

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   - [ ] **Backend API Development**
     - [ ] Create `/Api/v1/manufacturing/ProductCatalogAPI.php`
     - [ ] Implement product search endpoint with filtering
     - [ ] Build client-specific pricing calculation engine
     - [ ] Add product availability/stock integration
     - [ ] Implement caching layer (Redis) for performance
   ```

2. **Test your APIs** using:
   - Postman/curl commands for endpoint validation
   - Performance testing with sample data loads
   - Error condition testing (invalid inputs, network failures)
   - Security testing (injection attempts, unauthorized access)

3. **Document API endpoints** with:
   - Request/response examples
   - Parameter descriptions
   - Error code explanations
   - Rate limiting information

## SUCCESS CRITERIA
- ✅ All 5 backend API tasks marked complete with ❌ in checklist
- ✅ ProductCatalogAPI.php created with all required endpoints
- ✅ Search functionality working with complex filtering
- ✅ Client-specific pricing calculations accurate
- ✅ Real-time inventory integration functional
- ✅ Redis caching layer improving response times by 60%+
- ✅ API response times meet performance requirements
- ✅ All endpoints properly secured and validated

## CONTEXT AWARENESS
- **Mobile-First**: APIs optimized for mobile bandwidth and performance
- **Enterprise Scale**: Handle 1000+ products and 100+ concurrent users
- **SuiteCRM Integration**: Seamless integration with existing CRM data
- **Future-Proof**: Extensible architecture for additional features
- **Demo-Ready**: Working endpoints for sales demonstration scenarios

Begin with the ProductCatalogAPI.php structure, implement core search functionality, add pricing engine, integrate inventory checks, and finally implement Redis caching for optimal performance. 