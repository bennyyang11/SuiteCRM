/**
 * SearchResults Component
 * Display search results with sorting, pagination, and product cards
 */

import React, { forwardRef, useState, useCallback, useMemo } from 'react';
import { SearchResult, SearchFacets } from '../types/Search';
import ProductCard from './ProductCard';

interface SearchResultsProps {
    results: SearchResult[];
    loading?: boolean;
    error?: string | null;
    totalResults: number;
    searchQuery: string;
    sortBy: string;
    sortOrder: 'asc' | 'desc';
    onSortChange: (sort: string, order: 'asc' | 'desc') => void;
    onLoadMore: () => void;
    onProductSelect?: (productId: string) => void;
    onSaveSearch?: (searchName: string) => void;
    hasMore: boolean;
    facets?: SearchFacets | null;
    className?: string;
}

const SearchResults = forwardRef<HTMLDivElement, SearchResultsProps>(({
    results,
    loading = false,
    error = null,
    totalResults,
    searchQuery,
    sortBy,
    sortOrder,
    onSortChange,
    onLoadMore,
    onProductSelect,
    onSaveSearch,
    hasMore,
    facets,
    className = ''
}, ref) => {
    const [showSaveDialog, setShowSaveDialog] = useState(false);
    const [saveSearchName, setSaveSearchName] = useState('');
    const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

    const sortOptions = [
        { value: 'relevance', label: 'Relevance', icon: '🎯' },
        { value: 'name', label: 'Name', icon: '🔤' },
        { value: 'price', label: 'Price', icon: '💰' },
        { value: 'stock', label: 'Stock Level', icon: '📦' },
        { value: 'category', label: 'Category', icon: '📁' },
        { value: 'material', label: 'Material', icon: '🏗️' }
    ];

    const formatNumber = useCallback((num: number) => {
        return new Intl.NumberFormat().format(num);
    }, []);

    const handleSortChange = useCallback((newSort: string) => {
        const newOrder = sortBy === newSort && sortOrder === 'asc' ? 'desc' : 'asc';
        onSortChange(newSort, newOrder);
    }, [sortBy, sortOrder, onSortChange]);

    const handleSaveSearch = useCallback(() => {
        if (saveSearchName.trim() && onSaveSearch) {
            onSaveSearch(saveSearchName.trim());
            setShowSaveDialog(false);
            setSaveSearchName('');
        }
    }, [saveSearchName, onSaveSearch]);

    const getSortIcon = useCallback((sort: string) => {
        if (sortBy !== sort) return null;
        return sortOrder === 'asc' ? '↑' : '↓';
    }, [sortBy, sortOrder]);

    const resultStats = useMemo(() => {
        if (totalResults === 0) return 'No results found';
        if (totalResults === 1) return '1 result';
        const displayedCount = results.length;
        if (displayedCount === totalResults) {
            return `${formatNumber(totalResults)} results`;
        }
        return `${formatNumber(displayedCount)} of ${formatNumber(totalResults)} results`;
    }, [results.length, totalResults, formatNumber]);

    // Error State
    if (error) {
        return (
            <div ref={ref} className={`p-8 text-center ${className}`}>
                <div className="max-w-md mx-auto">
                    <svg className="w-16 h-16 mx-auto mb-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">Search Error</h3>
                    <p className="text-gray-600 mb-4">{error}</p>
                    <button
                        onClick={() => window.location.reload()}
                        className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        Try Again
                    </button>
                </div>
            </div>
        );
    }

    // Empty State
    if (!loading && results.length === 0 && searchQuery) {
        return (
            <div ref={ref} className={`p-8 text-center ${className}`}>
                <div className="max-w-md mx-auto">
                    <svg className="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <h3 className="text-lg font-semibold text-gray-900 mb-2">No Results Found</h3>
                    <p className="text-gray-600 mb-4">
                        We couldn't find any products matching "<span className="font-medium">{searchQuery}</span>"
                    </p>
                    <div className="space-y-2 text-sm text-gray-500">
                        <p>Try:</p>
                        <ul className="space-y-1">
                            <li>• Checking your spelling</li>
                            <li>• Using more general terms</li>
                            <li>• Searching by SKU or category</li>
                            <li>• Removing some filters</li>
                        </ul>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div ref={ref} className={`h-full flex flex-col ${className}`}>
            {/* Results Header */}
            {(searchQuery || results.length > 0) && (
                <div className="bg-white border-b border-gray-200 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-4">
                            <h2 className="text-lg font-semibold text-gray-900">
                                {searchQuery ? `Search: "${searchQuery}"` : 'Products'}
                            </h2>
                            <span className="text-sm text-gray-500">{resultStats}</span>
                        </div>

                        <div className="flex items-center space-x-4">
                            {/* Save Search Button */}
                            {searchQuery && onSaveSearch && (
                                <button
                                    onClick={() => setShowSaveDialog(true)}
                                    className="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <svg className="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                              d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    Save Search
                                </button>
                            )}

                            {/* View Mode Toggle */}
                            <div className="flex border border-gray-300 rounded-lg overflow-hidden">
                                <button
                                    onClick={() => setViewMode('grid')}
                                    className={`px-3 py-1 text-sm transition-colors ${
                                        viewMode === 'grid' 
                                            ? 'bg-blue-500 text-white' 
                                            : 'bg-white text-gray-700 hover:bg-gray-50'
                                    }`}
                                >
                                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M10 4H4a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2zM10 14H4a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2zM20 4h-6a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2zM20 14h-6a2 2 0 00-2 2v4a2 2 0 002 2h6a2 2 0 002-2v-4a2 2 0 00-2-2z"/>
                                    </svg>
                                </button>
                                <button
                                    onClick={() => setViewMode('list')}
                                    className={`px-3 py-1 text-sm transition-colors ${
                                        viewMode === 'list' 
                                            ? 'bg-blue-500 text-white' 
                                            : 'bg-white text-gray-700 hover:bg-gray-50'
                                    }`}
                                >
                                    <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/>
                                    </svg>
                                </button>
                            </div>

                            {/* Sort Dropdown */}
                            <div className="relative">
                                <select
                                    value={sortBy}
                                    onChange={(e) => handleSortChange(e.target.value)}
                                    className="pl-3 pr-8 py-2 text-sm border border-gray-300 rounded-lg bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    {sortOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.icon} {option.label} {getSortIcon(option.value)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Results Content */}
            <div className="flex-1 overflow-y-auto bg-gray-50">
                {loading && results.length === 0 ? (
                    <div className="p-8 text-center">
                        <div className="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                        <p className="text-gray-500">Searching products...</p>
                    </div>
                ) : (
                    <div className="p-6">
                        {/* Results Grid/List */}
                        <div className={`grid gap-4 ${
                            viewMode === 'grid' 
                                ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' 
                                : 'grid-cols-1'
                        }`}>
                            {results.map((product) => (
                                <ProductCard
                                    key={product.id}
                                    product={product as any} // Type conversion for compatibility
                                    onSelect={() => onProductSelect?.(product.id)}
                                    onAddToQuote={() => {/* Add to quote logic */}}
                                    variant={viewMode === 'list' ? 'list' : 'card'}
                                    showRelevance={sortBy === 'relevance'}
                                />
                            ))}
                        </div>

                        {/* Load More Button */}
                        {hasMore && (
                            <div className="mt-8 text-center">
                                <button
                                    onClick={onLoadMore}
                                    disabled={loading}
                                    className="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    {loading ? (
                                        <div className="flex items-center space-x-2">
                                            <div className="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                                            <span>Loading more...</span>
                                        </div>
                                    ) : (
                                        `Load More (${totalResults - results.length} remaining)`
                                    )}
                                </button>
                            </div>
                        )}

                        {/* End of Results */}
                        {!hasMore && results.length > 0 && (
                            <div className="mt-8 text-center py-4 border-t border-gray-200">
                                <p className="text-gray-500">You've reached the end of the results</p>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Save Search Dialog */}
            {showSaveDialog && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Save Search</h3>
                        <p className="text-sm text-gray-600 mb-4">
                            Save this search to easily access it later.
                        </p>
                        <input
                            type="text"
                            value={saveSearchName}
                            onChange={(e) => setSaveSearchName(e.target.value)}
                            placeholder="Enter search name..."
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 mb-4"
                            autoFocus
                        />
                        <div className="flex space-x-3">
                            <button
                                onClick={() => setShowSaveDialog(false)}
                                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                onClick={handleSaveSearch}
                                disabled={!saveSearchName.trim()}
                                className="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
});

SearchResults.displayName = 'SearchResults';

export default SearchResults;
