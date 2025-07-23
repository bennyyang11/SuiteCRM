/**
 * Advanced Search API Service
 * TypeScript service layer for advanced search functionality
 */

import { 
    SearchParams,
    SearchResponse,
    InstantSearchResponse,
    AdvancedSearchResponse,
    AutocompleteResponse,
    SearchHistoryResponse,
    SavedSearchResponse,
    SearchFacets,
    SavedSearch,
    SearchAPIInterface
} from '../types/Search';

class AdvancedSearchAPI implements SearchAPIInterface {
    private baseUrl: string;
    private cache: Map<string, { data: any; timestamp: number; ttl: number }>;
    private defaultCacheTtl: number = 5 * 60 * 1000; // 5 minutes

    constructor(baseUrl: string = '/Api/v1/manufacturing') {
        this.baseUrl = baseUrl;
        this.cache = new Map();
    }

    /**
     * Instant search with autocomplete and suggestions
     */
    async instantSearch(params: { q: string; limit?: number }): Promise<SearchResponse<InstantSearchResponse>> {
        const cacheKey = `instant_search_${JSON.stringify(params)}`;
        
        // Check cache first
        const cached = this.getFromCache(cacheKey);
        if (cached) {
            return { success: true, data: cached, timestamp: new Date().toISOString() };
        }

        try {
            const queryParams = new URLSearchParams({
                q: params.q,
                limit: (params.limit || 10).toString()
            });

            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/instant?${queryParams}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                // Cache the result
                this.setCache(cacheKey, result.data, this.defaultCacheTtl);
            }

            return result;
        } catch (error) {
            console.error('Instant search error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Search failed',
                    code: 500
                },
                data: { suggestions: [], products: [], facets: { categories: [], materials: [], price_ranges: [], stock_status: [] } },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Advanced search with comprehensive filtering
     */
    async advancedSearch(params: SearchParams & { 
        page?: number; 
        limit?: number; 
        sort?: string; 
        order?: 'asc' | 'desc' 
    }): Promise<SearchResponse<AdvancedSearchResponse>> {
        try {
            const searchParams = new URLSearchParams();
            
            // Add all search parameters
            Object.entries(params).forEach(([key, value]) => {
                if (value !== undefined && value !== null && value !== '' && 
                    (Array.isArray(value) ? value.length > 0 : true)) {
                    if (Array.isArray(value)) {
                        value.forEach(v => searchParams.append(`${key}[]`, v));
                    } else {
                        searchParams.append(key, String(value));
                    }
                }
            });

            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/advanced`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json'
                },
                body: searchParams
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            // Track search for analytics
            if (result.success && params.query) {
                this.trackSearch(params.query, result.data.products.length);
            }

            return result;
        } catch (error) {
            console.error('Advanced search error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Advanced search failed',
                    code: 500
                },
                data: {
                    products: [],
                    pagination: { page: 1, limit: 20, total: 0, total_pages: 0 },
                    filters: params,
                    facets: { categories: [], materials: [], price_ranges: [], stock_status: [] }
                },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Get autocomplete suggestions
     */
    async autocomplete(params: { 
        q: string; 
        type?: string; 
        limit?: number 
    }): Promise<SearchResponse<AutocompleteResponse>> {
        const cacheKey = `autocomplete_${JSON.stringify(params)}`;
        
        // Check cache first
        const cached = this.getFromCache(cacheKey);
        if (cached) {
            return { success: true, data: cached, timestamp: new Date().toISOString() };
        }

        try {
            const queryParams = new URLSearchParams({
                q: params.q,
                type: params.type || 'all',
                limit: (params.limit || 10).toString()
            });

            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/autocomplete?${queryParams}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                // Cache with shorter TTL for autocomplete
                this.setCache(cacheKey, result.data, 2 * 60 * 1000); // 2 minutes
            }

            return result;
        } catch (error) {
            console.error('Autocomplete error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Autocomplete failed',
                    code: 500
                },
                data: { suggestions: [] },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Get search facets
     */
    async getFacets(query?: string): Promise<SearchResponse<{ facets: SearchFacets }>> {
        const cacheKey = `facets_${query || 'all'}`;
        
        // Check cache first
        const cached = this.getFromCache(cacheKey);
        if (cached) {
            return { success: true, data: cached, timestamp: new Date().toISOString() };
        }

        try {
            const queryParams = new URLSearchParams();
            if (query) {
                queryParams.append('q', query);
            }

            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/facets?${queryParams}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                // Cache facets for longer duration
                this.setCache(cacheKey, result.data, 10 * 60 * 1000); // 10 minutes
            }

            return result;
        } catch (error) {
            console.error('Get facets error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Failed to get facets',
                    code: 500
                },
                data: { facets: { categories: [], materials: [], price_ranges: [], stock_status: [] } },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Save a search query
     */
    async saveSearch(search: SavedSearch): Promise<SearchResponse<{ success: boolean }>> {
        try {
            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/saved`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(search)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Save search error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Failed to save search',
                    code: 500
                },
                data: { success: false },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Get saved searches
     */
    async getSavedSearches(): Promise<SearchResponse<SavedSearchResponse>> {
        try {
            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/saved`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Get saved searches error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Failed to get saved searches',
                    code: 500
                },
                data: { saved_searches: [] },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Delete a saved search
     */
    async deleteSavedSearch(searchId: string): Promise<SearchResponse<{ success: boolean }>> {
        try {
            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/saved/${searchId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Delete saved search error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Failed to delete saved search',
                    code: 500
                },
                data: { success: false },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Get search history
     */
    async getSearchHistory(limit?: number): Promise<SearchResponse<SearchHistoryResponse>> {
        try {
            const queryParams = new URLSearchParams();
            if (limit) {
                queryParams.append('limit', limit.toString());
            }

            const response = await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/history?${queryParams}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Get search history error:', error);
            return {
                success: false,
                error: {
                    message: error instanceof Error ? error.message : 'Failed to get search history',
                    code: 500
                },
                data: { history: [] },
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Track suggestion usage for analytics
     */
    async trackSuggestionUsage(suggestion: string, type: string): Promise<void> {
        try {
            await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/track-suggestion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ suggestion, type })
            });
        } catch (error) {
            // Silent fail for analytics tracking
            console.warn('Failed to track suggestion usage:', error);
        }
    }

    /**
     * Track search for analytics
     */
    private async trackSearch(query: string, resultCount: number): Promise<void> {
        try {
            await fetch(`${this.baseUrl}/AdvancedSearchAPI.php/track-search`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    query, 
                    result_count: resultCount,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            // Silent fail for analytics tracking
            console.warn('Failed to track search:', error);
        }
    }

    /**
     * Cache management methods
     */
    private getFromCache(key: string): any | null {
        const cached = this.cache.get(key);
        if (cached && Date.now() - cached.timestamp < cached.ttl) {
            return cached.data;
        }
        if (cached) {
            this.cache.delete(key);
        }
        return null;
    }

    private setCache(key: string, data: any, ttl: number): void {
        this.cache.set(key, {
            data,
            timestamp: Date.now(),
            ttl
        });

        // Clean up old cache entries
        if (this.cache.size > 100) {
            const oldestKey = this.cache.keys().next().value;
            this.cache.delete(oldestKey);
        }
    }

    /**
     * Clear all cache
     */
    public clearCache(): void {
        this.cache.clear();
    }

    /**
     * Get cache statistics
     */
    public getCacheStats(): { size: number; keys: string[] } {
        return {
            size: this.cache.size,
            keys: Array.from(this.cache.keys())
        };
    }
}

// Create singleton instance
export const advancedSearchAPI = new AdvancedSearchAPI();

// Also export the class for testing or custom instances
export { AdvancedSearchAPI };

export default advancedSearchAPI;
