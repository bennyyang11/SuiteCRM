/**
 * Search-related TypeScript type definitions
 * For advanced search functionality
 */

export interface SearchParams {
    query: string;
    category: string;
    material: string;
    price_min: number;
    price_max: number;
    stock_status: string;
    manufacturer: string;
    weight_min: number;
    weight_max: number;
    lead_time_max: number;
    specifications: string[];
    tags: string[];
    client_id: string;
}

export interface SearchResult {
    id: string;
    name: string;
    sku: string;
    description: string;
    category: string;
    material?: string;
    price: number;
    thumbnail?: string;
    stock_status: 'in_stock' | 'low_stock' | 'out_of_stock';
    relevance?: number;
    weight_lbs?: number;
    dimensions?: string;
    manufacturer?: string;
    lead_time_days?: number;
    specifications?: Record<string, any>;
    tags?: string[];
    inventory?: {
        quantity_available: number;
        stock_status: string;
    };
    pricing?: {
        list_price: number;
        client_price: number;
        discount_percentage: number;
        tier_name: string;
    };
}

export interface SearchSuggestion {
    text: string;
    type: 'product' | 'category' | 'material' | 'sku' | 'manufacturer' | 'history';
    weight: number;
    count?: number;
}

export interface SearchFacet {
    value: string;
    count: number;
    label: string;
}

export interface SearchFacets {
    categories: SearchFacet[];
    materials: SearchFacet[];
    price_ranges: Array<{
        min: number;
        max: number;
        count: number;
        label: string;
    }>;
    stock_status: SearchFacet[];
    manufacturers?: SearchFacet[];
    popular_searches?: string[];
    popular_filters?: Array<{
        name: string;
        filters: Partial<SearchParams>;
    }>;
}

export interface SearchHistory {
    id: string;
    query: string;
    filters: SearchParams;
    result_count: number;
    timestamp: string;
    clicked_products?: string[];
}

export interface SavedSearch {
    id: string;
    name: string;
    query: string;
    filters: SearchParams;
    is_alert_enabled?: boolean;
    alert_frequency?: 'daily' | 'weekly' | 'monthly';
    created_date: string;
    last_used?: string;
    usage_count: number;
}

export interface SearchAnalytics {
    search_term: string;
    search_count: number;
    no_results_count: number;
    avg_results_count: number;
    click_through_rate: number;
    most_clicked_products: string[];
    trending_score: number;
    search_category?: string;
}

export interface PopularSearch {
    search_term: string;
    search_type: 'product' | 'category' | 'material' | 'sku' | 'manufacturer';
    search_count: number;
    success_rate: number;
    is_trending: boolean;
    popularity_rank: number;
}

export interface SearchResponse<T = any> {
    success: boolean;
    data: T;
    error?: {
        message: string;
        code: number;
    };
    timestamp: string;
}

export interface InstantSearchResponse {
    suggestions: SearchSuggestion[];
    products: SearchResult[];
    facets: SearchFacets;
}

export interface AdvancedSearchResponse {
    products: SearchResult[];
    pagination: {
        page: number;
        limit: number;
        total: number;
        total_pages: number;
    };
    filters: SearchParams;
    facets: SearchFacets;
}

export interface AutocompleteResponse {
    suggestions: SearchSuggestion[];
}

export interface SearchHistoryResponse {
    history: SearchHistory[];
}

export interface SavedSearchResponse {
    saved_searches: SavedSearch[];
}

// Search API Interface
export interface SearchAPIInterface {
    instantSearch(params: { q: string; limit?: number }): Promise<SearchResponse<InstantSearchResponse>>;
    advancedSearch(params: SearchParams & { 
        page?: number; 
        limit?: number; 
        sort?: string; 
        order?: 'asc' | 'desc' 
    }): Promise<SearchResponse<AdvancedSearchResponse>>;
    autocomplete(params: { 
        q: string; 
        type?: string; 
        limit?: number 
    }): Promise<SearchResponse<AutocompleteResponse>>;
    getFacets(query?: string): Promise<SearchResponse<{ facets: SearchFacets }>>;
    saveSearch(search: SavedSearch): Promise<SearchResponse<{ success: boolean }>>;
    getSavedSearches(): Promise<SearchResponse<SavedSearchResponse>>;
    deleteSavedSearch(searchId: string): Promise<SearchResponse<{ success: boolean }>>;
    getSearchHistory(limit?: number): Promise<SearchResponse<SearchHistoryResponse>>;
    trackSuggestionUsage(suggestion: string, type: string): Promise<void>;
}

// Search Component Props
export interface SearchComponentProps {
    clientId?: string;
    onProductSelect?: (productId: string) => void;
    initialSearch?: string;
    className?: string;
}

// Hook return types
export interface UseSearchReturn {
    searchQuery: string;
    setSearchQuery: (query: string) => void;
    searchResults: SearchResult[];
    facets: SearchFacets | null;
    suggestions: SearchSuggestion[];
    loading: boolean;
    error: string | null;
    totalResults: number;
    hasMore: boolean;
    performSearch: (params: SearchParams) => Promise<void>;
    loadMore: () => Promise<void>;
    clearResults: () => void;
}

export interface UseSearchHistoryReturn {
    searchHistory: SearchHistory[];
    addToHistory: (query: string, filters: SearchParams, resultCount: number) => void;
    clearHistory: () => void;
    removeFromHistory: (historyId: string) => void;
}

export interface UseSavedSearchesReturn {
    savedSearches: SavedSearch[];
    saveSearch: (name: string, query: string, filters: SearchParams) => Promise<void>;
    loadSearch: (searchId: string) => Promise<SavedSearch | null>;
    deleteSearch: (searchId: string) => Promise<void>;
    updateSearchUsage: (searchId: string) => void;
}

// Filter and UI state types
export interface FilterState {
    [key: string]: any;
}

export interface SearchUIState {
    showSuggestions: boolean;
    showFilters: boolean;
    activeFilter: string | null;
    viewMode: 'grid' | 'list';
    sortBy: string;
    sortOrder: 'asc' | 'desc';
}

// Events and callbacks
export type SearchEventHandler = (query: string) => void;
export type FilterChangeHandler = (filters: Partial<SearchParams>) => void;
export type SortChangeHandler = (sort: string, order: 'asc' | 'desc') => void;
export type ProductSelectHandler = (productId: string) => void;

export default SearchParams;
