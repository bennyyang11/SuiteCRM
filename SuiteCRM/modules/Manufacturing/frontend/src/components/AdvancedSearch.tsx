/**
 * AdvancedSearch Component
 * Google-like search experience with faceted filtering and instant results
 */

import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import SearchBar from './SearchBar';
import SearchFilters from './SearchFilters';
import SearchResults from './SearchResults';
import SearchSuggestions from './SearchSuggestions';
import { 
    SearchParams, 
    SearchResult, 
    SearchFacets, 
    SearchSuggestion,
    SavedSearch,
    SearchHistory 
} from '../types/Search';
import { advancedSearchAPI } from '../services/AdvancedSearchAPI';
import { useDebounce } from '../hooks/useDebounce';
import { useLocalStorage } from '../hooks/useLocalStorage';

interface AdvancedSearchProps {
    clientId?: string;
    onProductSelect?: (productId: string) => void;
    initialSearch?: string;
    className?: string;
}

const AdvancedSearch: React.FC<AdvancedSearchProps> = ({
    clientId,
    onProductSelect,
    initialSearch = '',
    className = ''
}) => {
    // Core search state
    const [searchQuery, setSearchQuery] = useState(initialSearch);
    const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
    const [facets, setFacets] = useState<SearchFacets | null>(null);
    const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
    const [instantResults, setInstantResults] = useState<SearchResult[]>([]);
    
    // UI state
    const [loading, setLoading] = useState(false);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [showFilters, setShowFilters] = useState(false);
    const [activeFilter, setActiveFilter] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    
    // Pagination and sorting
    const [page, setPage] = useState(1);
    const [totalResults, setTotalResults] = useState(0);
    const [sortBy, setSortBy] = useState<'relevance' | 'name' | 'price' | 'stock'>('relevance');
    const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
    
    // Advanced filters
    const [filters, setFilters] = useState<SearchParams>({
        query: '',
        category: '',
        material: '',
        price_min: 0,
        price_max: 999999,
        stock_status: '',
        manufacturer: '',
        weight_min: 0,
        weight_max: 999999,
        lead_time_max: 365,
        specifications: [],
        tags: [],
        client_id: clientId || ''
    });
    
    // Search history and saved searches
    const [searchHistory, setSearchHistory] = useLocalStorage<SearchHistory[]>('mfg_search_history', []);
    const [savedSearches, setSavedSearches] = useLocalStorage<SavedSearch[]>('mfg_saved_searches', []);
    
    // Refs for managing focus and scroll
    const searchInputRef = useRef<HTMLInputElement>(null);
    const resultsContainerRef = useRef<HTMLDivElement>(null);
    
    // Debounced search query for instant search
    const debouncedQuery = useDebounce(searchQuery, 300);
    
    // Memoized search parameters
    const searchParams = useMemo(() => ({
        ...filters,
        query: searchQuery,
        page,
        limit: 20,
        sort: sortBy,
        order: sortOrder
    }), [filters, searchQuery, page, sortBy, sortOrder]);
    
    // Instant search effect
    useEffect(() => {
        if (debouncedQuery.length >= 2) {
            performInstantSearch(debouncedQuery);
        } else {
            setInstantResults([]);
            setSuggestions([]);
            setShowSuggestions(false);
        }
    }, [debouncedQuery]);
    
    // Advanced search effect
    useEffect(() => {
        if (searchQuery && (page > 1 || Object.values(filters).some(v => 
            Array.isArray(v) ? v.length > 0 : v !== '' && v !== 0 && v !== 999999 && v !== 365
        ))) {
            performAdvancedSearch();
        }
    }, [searchParams]);
    
    // Instant search implementation
    const performInstantSearch = useCallback(async (query: string) => {
        try {
            setLoading(true);
            const response = await advancedSearchAPI.instantSearch({
                q: query,
                limit: 10
            });
            
            if (response.success) {
                setInstantResults(response.data.products);
                setSuggestions(response.data.suggestions);
                setFacets(response.data.facets);
                setShowSuggestions(true);
            }
        } catch (error) {
            console.error('Instant search error:', error);
        } finally {
            setLoading(false);
        }
    }, []);
    
    // Advanced search implementation
    const performAdvancedSearch = useCallback(async () => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await advancedSearchAPI.advancedSearch(searchParams);
            
            if (response.success) {
                const { products, pagination, facets: searchFacets } = response.data;
                
                if (page === 1) {
                    setSearchResults(products);
                } else {
                    setSearchResults(prev => [...prev, ...products]);
                }
                
                setTotalResults(pagination.total);
                setFacets(searchFacets);
                setShowSuggestions(false);
                
                // Add to search history
                addToSearchHistory(searchQuery, filters, products.length);
                
            } else {
                setError('Search failed. Please try again.');
            }
        } catch (error) {
            console.error('Advanced search error:', error);
            setError('Search error occurred. Please check your connection.');
        } finally {
            setLoading(false);
        }
    }, [searchParams, page]);
    
    // Search handlers
    const handleSearchSubmit = useCallback((query: string) => {
        setSearchQuery(query);
        setPage(1);
        setShowSuggestions(false);
        updateFilters({ query });
    }, []);
    
    const handleSuggestionClick = useCallback((suggestion: SearchSuggestion) => {
        setSearchQuery(suggestion.text);
        setShowSuggestions(false);
        handleSearchSubmit(suggestion.text);
        
        // Track suggestion usage
        advancedSearchAPI.trackSuggestionUsage(suggestion.text, suggestion.type);
    }, [handleSearchSubmit]);
    
    const handleFilterChange = useCallback((newFilters: Partial<SearchParams>) => {
        setPage(1);
        updateFilters(newFilters);
    }, []);
    
    const handleClearFilters = useCallback(() => {
        setFilters({
            query: searchQuery,
            category: '',
            material: '',
            price_min: 0,
            price_max: 999999,
            stock_status: '',
            manufacturer: '',
            weight_min: 0,
            weight_max: 999999,
            lead_time_max: 365,
            specifications: [],
            tags: [],
            client_id: clientId || ''
        });
        setPage(1);
    }, [searchQuery, clientId]);
    
    const handleSortChange = useCallback((sort: string, order: 'asc' | 'desc') => {
        setSortBy(sort as any);
        setSortOrder(order);
        setPage(1);
    }, []);
    
    const handleLoadMore = useCallback(() => {
        if (!loading && searchResults.length < totalResults) {
            setPage(prev => prev + 1);
        }
    }, [loading, searchResults.length, totalResults]);
    
    // Saved search management
    const handleSaveSearch = useCallback(async (searchName: string) => {
        const savedSearch: SavedSearch = {
            id: Date.now().toString(),
            name: searchName,
            query: searchQuery,
            filters: { ...filters },
            created_date: new Date().toISOString(),
            usage_count: 0
        };
        
        setSavedSearches(prev => [...prev, savedSearch]);
        
        try {
            await advancedSearchAPI.saveSearch(savedSearch);
        } catch (error) {
            console.error('Error saving search:', error);
        }
    }, [searchQuery, filters, setSavedSearches]);
    
    const handleLoadSavedSearch = useCallback((savedSearch: SavedSearch) => {
        setSearchQuery(savedSearch.query);
        setFilters(savedSearch.filters);
        setPage(1);
        
        // Update usage count
        setSavedSearches(prev => 
            prev.map(s => 
                s.id === savedSearch.id 
                    ? { ...s, usage_count: s.usage_count + 1 }
                    : s
            )
        );
    }, [setSavedSearches]);
    
    // Utility functions
    const updateFilters = useCallback((newFilters: Partial<SearchParams>) => {
        setFilters(prev => ({ ...prev, ...newFilters }));
    }, []);
    
    const addToSearchHistory = useCallback((query: string, searchFilters: SearchParams, resultCount: number) => {
        const historyItem: SearchHistory = {
            id: Date.now().toString(),
            query,
            filters: { ...searchFilters },
            result_count: resultCount,
            timestamp: new Date().toISOString()
        };
        
        setSearchHistory(prev => [historyItem, ...prev.slice(0, 49)]); // Keep last 50 searches
    }, [setSearchHistory]);
    
    // Auto-complete data
    const autocompleteData = useMemo(() => ({
        suggestions,
        recentSearches: searchHistory.slice(0, 5).map(h => h.query),
        popularSearches: facets?.popular_searches || []
    }), [suggestions, searchHistory, facets]);
    
    // Check if search has active filters
    const hasActiveFilters = useMemo(() => {
        return Object.entries(filters).some(([key, value]) => {
            if (key === 'query' || key === 'client_id') return false;
            if (Array.isArray(value)) return value.length > 0;
            if (key === 'price_min' || key === 'weight_min') return value > 0;
            if (key === 'price_max') return value < 999999;
            if (key === 'weight_max') return value < 999999;
            if (key === 'lead_time_max') return value < 365;
            return value !== '';
        });
    }, [filters]);
    
    return (
        <div className={`advanced-search-container ${className}`}>
            {/* Search Header */}
            <div className="search-header bg-white shadow-sm border-b">
                <div className="container mx-auto px-4 py-4">
                    <div className="flex items-center space-x-4">
                        <div className="flex-1 relative">
                            <SearchBar
                                ref={searchInputRef}
                                value={searchQuery}
                                onChange={setSearchQuery}
                                onSubmit={handleSearchSubmit}
                                onFocus={() => setShowSuggestions(true)}
                                placeholder="Search products, SKUs, materials..."
                                loading={loading}
                                className="w-full"
                            />
                            
                            {/* Search Suggestions Dropdown */}
                            {showSuggestions && (suggestions.length > 0 || instantResults.length > 0) && (
                                <SearchSuggestions
                                    suggestions={suggestions}
                                    instantResults={instantResults}
                                    searchHistory={searchHistory.slice(0, 3)}
                                    onSuggestionClick={handleSuggestionClick}
                                    onResultClick={(productId) => {
                                        onProductSelect?.(productId);
                                        setShowSuggestions(false);
                                    }}
                                    loading={loading}
                                />
                            )}
                        </div>
                        
                        {/* Filter Toggle */}
                        <button
                            onClick={() => setShowFilters(!showFilters)}
                            className={`px-4 py-2 rounded-lg border transition-colors ${
                                showFilters || hasActiveFilters
                                    ? 'bg-blue-50 border-blue-300 text-blue-700'
                                    : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
                            }`}
                        >
                            <svg className="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filters
                            {hasActiveFilters && (
                                <span className="ml-2 px-2 py-1 text-xs bg-blue-600 text-white rounded-full">
                                    {Object.values(filters).filter(v => 
                                        Array.isArray(v) ? v.length > 0 : 
                                        typeof v === 'string' ? v !== '' :
                                        typeof v === 'number' ? (v > 0 && v < 999999 && v < 365) : false
                                    ).length}
                                </span>
                            )}
                        </button>
                        
                        {/* Saved Searches */}
                        {savedSearches.length > 0 && (
                            <div className="relative">
                                <button
                                    className="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                                    onClick={() => {/* Show saved searches dropdown */}}
                                >
                                    <svg className="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                              d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    Saved ({savedSearches.length})
                                </button>
                            </div>
                        )}
                    </div>
                    
                    {/* Active Filters Display */}
                    {hasActiveFilters && (
                        <div className="mt-3 flex items-center space-x-2 flex-wrap">
                            <span className="text-sm text-gray-500">Active filters:</span>
                            {Object.entries(filters).map(([key, value]) => {
                                if (!value || value === '' || 
                                    (Array.isArray(value) && value.length === 0) ||
                                    (key === 'price_min' && value === 0) ||
                                    (key === 'weight_min' && value === 0) ||
                                    (key === 'price_max' && value === 999999) ||
                                    (key === 'weight_max' && value === 999999) ||
                                    (key === 'lead_time_max' && value === 365) ||
                                    key === 'query' || key === 'client_id') {
                                    return null;
                                }
                                
                                return (
                                    <span key={key} className="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                        {key.replace('_', ' ')}: {Array.isArray(value) ? value.join(', ') : value}
                                        <button
                                            onClick={() => handleFilterChange({ [key]: Array.isArray(value) ? [] : key.includes('min') ? 0 : key.includes('max') ? (key === 'lead_time_max' ? 365 : 999999) : '' })}
                                            className="ml-2 text-blue-600 hover:text-blue-800"
                                        >
                                            ×
                                        </button>
                                    </span>
                                );
                            })}
                            <button
                                onClick={handleClearFilters}
                                className="text-sm text-gray-500 hover:text-gray-700 underline"
                            >
                                Clear all
                            </button>
                        </div>
                    )}
                </div>
            </div>
            
            {/* Main Content */}
            <div className="flex-1 flex">
                {/* Filters Sidebar */}
                {showFilters && (
                    <div className="w-80 bg-white border-r shadow-sm">
                        <SearchFilters
                            filters={filters}
                            facets={facets}
                            onFilterChange={handleFilterChange}
                            onClearFilters={handleClearFilters}
                            loading={loading}
                        />
                    </div>
                )}
                
                {/* Search Results */}
                <div className="flex-1 bg-gray-50">
                    <SearchResults
                        ref={resultsContainerRef}
                        results={searchResults}
                        loading={loading}
                        error={error}
                        totalResults={totalResults}
                        searchQuery={searchQuery}
                        sortBy={sortBy}
                        sortOrder={sortOrder}
                        onSortChange={handleSortChange}
                        onLoadMore={handleLoadMore}
                        onProductSelect={onProductSelect}
                        onSaveSearch={handleSaveSearch}
                        hasMore={searchResults.length < totalResults}
                        facets={facets}
                    />
                </div>
            </div>
        </div>
    );
};

export default AdvancedSearch;
