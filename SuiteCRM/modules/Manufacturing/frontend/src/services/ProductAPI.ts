/**
 * Product API Service
 * Manufacturing Product Catalog - API Integration Layer
 */

import {
  Product,
  ProductSearchParams,
  ProductSearchResponse,
  ProductCategory,
  PricingCalculation,
  InventoryCheck,
  ApiResponse
} from '../types/Product';

class ProductAPIService {
  private baseUrl: string;
  private cache: Map<string, { data: any; timestamp: number; ttl: number }>;
  private cacheTTL = {
    search: 15 * 60 * 1000, // 15 minutes
    categories: 60 * 60 * 1000, // 1 hour
    pricing: 30 * 60 * 1000, // 30 minutes
    inventory: 5 * 60 * 1000, // 5 minutes
  };

  constructor(baseUrl: string = '/api_manufacturing.php') {
    this.baseUrl = baseUrl;
    this.cache = new Map();
  }

  /**
   * Get cached data if available and not expired
   */
  private getCached<T>(key: string): T | null {
    const cached = this.cache.get(key);
    if (cached && Date.now() - cached.timestamp < cached.ttl) {
      return cached.data;
    }
    this.cache.delete(key);
    return null;
  }

  /**
   * Cache data with TTL
   */
  private setCached(key: string, data: any, ttl: number): void {
    this.cache.set(key, {
      data,
      timestamp: Date.now(),
      ttl
    });
  }

  /**
   * Make HTTP request with error handling
   */
  private async makeRequest<T>(
    endpoint: string,
    method: 'GET' | 'POST' = 'GET',
    params?: Record<string, any>,
    data?: Record<string, any>
  ): Promise<T> {
    try {
      let url = `${this.baseUrl}?endpoint=${endpoint}`;
      
      // Add query parameters for GET requests
      if (method === 'GET' && params) {
        const queryParams = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
          if (value !== undefined && value !== null && value !== '') {
            queryParams.append(key, value.toString());
          }
        });
        const queryString = queryParams.toString();
        if (queryString) {
          url += `&${queryString}`;
        }
      }

      const requestOptions: RequestInit = {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      };

      // Add body data for POST requests
      if (method === 'POST' && data) {
        requestOptions.body = JSON.stringify(data);
      }

      const response = await fetch(url, requestOptions);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const result = await response.json();
      
      if (result.status === 'error') {
        throw new Error(result.error?.message || 'API request failed');
      }

      return result;
    } catch (error) {
      console.error(`API Error for ${endpoint}:`, error);
      throw error;
    }
  }

  /**
   * Search products with caching
   */
  async searchProducts(params: ProductSearchParams): Promise<ProductSearchResponse> {
    const cacheKey = `search:${JSON.stringify(params)}`;
    const cached = this.getCached<ProductSearchResponse>(cacheKey);
    
    if (cached) {
      return cached;
    }

    const response = await this.makeRequest<ProductSearchResponse>('products/search', 'GET', params);
    this.setCached(cacheKey, response, this.cacheTTL.search);
    
    return response;
  }

  /**
   * Get product categories
   */
  async getCategories(): Promise<ApiResponse<{ categories: ProductCategory[] }>> {
    const cacheKey = 'categories:all';
    const cached = this.getCached<ApiResponse<{ categories: ProductCategory[] }>>(cacheKey);
    
    if (cached) {
      return cached;
    }

    const response = await this.makeRequest<ApiResponse<{ categories: ProductCategory[] }>>('products/categories');
    this.setCached(cacheKey, response, this.cacheTTL.categories);
    
    return response;
  }

  /**
   * Calculate pricing for a product
   */
  async calculatePricing(
    productId: string,
    quantity: number,
    clientId?: string
  ): Promise<ApiResponse<PricingCalculation>> {
    const data = {
      product_id: productId,
      quantity,
      client_id: clientId
    };

    return this.makeRequest<ApiResponse<PricingCalculation>>(
      'pricing/calculate',
      'POST',
      undefined,
      data
    );
  }

  /**
   * Check inventory for products
   */
  async checkInventory(productIds: string[]): Promise<ApiResponse<{ inventory: InventoryCheck[] }>> {
    const data = { product_ids: productIds };
    
    return this.makeRequest<ApiResponse<{ inventory: InventoryCheck[] }>>(
      'inventory/check',
      'POST',
      undefined,
      data
    );
  }

  /**
   * Get product recommendations
   */
  async getRecommendations(
    productId?: string,
    category?: string,
    limit: number = 5
  ): Promise<ApiResponse<{ recommendations: any[] }>> {
    const params = {
      product_id: productId,
      category,
      limit
    };

    return this.makeRequest<ApiResponse<{ recommendations: any[] }>>(
      'products/recommendations',
      'GET',
      params
    );
  }

  /**
   * Clear cache (useful for testing or after updates)
   */
  clearCache(): void {
    this.cache.clear();
  }

  /**
   * Get cache stats (for debugging)
   */
  getCacheStats(): { size: number; keys: string[] } {
    return {
      size: this.cache.size,
      keys: Array.from(this.cache.keys())
    };
  }
}

// Export singleton instance
export const productAPI = new ProductAPIService();
export default ProductAPIService;
