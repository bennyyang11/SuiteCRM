/**
 * SearchSuggestions Component
 * Intelligent autocomplete dropdown with suggestions and instant results
 */

import React, { useState, useCallback } from 'react';
import { SearchSuggestion, SearchResult, SearchHistory } from '../types/Search';

interface SearchSuggestionsProps {
    suggestions: SearchSuggestion[];
    instantResults: SearchResult[];
    searchHistory: SearchHistory[];
    onSuggestionClick: (suggestion: SearchSuggestion) => void;
    onResultClick: (productId: string) => void;
    loading?: boolean;
    className?: string;
}

const SearchSuggestions: React.FC<SearchSuggestionsProps> = ({
    suggestions,
    instantResults,
    searchHistory,
    onSuggestionClick,
    onResultClick,
    loading = false,
    className = ''
}) => {
    const [activeSection, setActiveSection] = useState<'suggestions' | 'results' | 'history'>('suggestions');

    const getSuggestionIcon = (type: string) => {
        switch (type) {
            case 'product':
                return (
                    <svg className="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                );
            case 'category':
                return (
                    <svg className="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                );
            case 'material':
                return (
                    <svg className="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                );
            case 'sku':
                return (
                    <svg className="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                              d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                    </svg>
                );
            default:
                return (
                    <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                );
        }
    };

    const getStockStatusColor = (status: string) => {
        switch (status) {
            case 'in_stock':
                return 'text-green-600 bg-green-100';
            case 'low_stock':
                return 'text-yellow-600 bg-yellow-100';
            case 'out_of_stock':
                return 'text-red-600 bg-red-100';
            default:
                return 'text-gray-600 bg-gray-100';
        }
    };

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(price);
    };

    const handleSuggestionClick = useCallback((suggestion: SearchSuggestion) => {
        onSuggestionClick(suggestion);
    }, [onSuggestionClick]);

    const handleResultClick = useCallback((productId: string) => {
        onResultClick(productId);
    }, [onResultClick]);

    return (
        <div className={`absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 max-h-96 overflow-hidden ${className}`}>
            {loading && (
                <div className="p-4 text-center">
                    <div className="inline-flex items-center space-x-2 text-gray-500">
                        <div className="animate-spin w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full"></div>
                        <span>Searching...</span>
                    </div>
                </div>
            )}

            {!loading && (
                <div className="divide-y divide-gray-100">
                    {/* Section Tabs */}
                    <div className="flex border-b border-gray-200">
                        {suggestions.length > 0 && (
                            <button
                                onClick={() => setActiveSection('suggestions')}
                                className={`flex-1 px-4 py-2 text-sm font-medium transition-colors ${
                                    activeSection === 'suggestions'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                Suggestions ({suggestions.length})
                            </button>
                        )}
                        {instantResults.length > 0 && (
                            <button
                                onClick={() => setActiveSection('results')}
                                className={`flex-1 px-4 py-2 text-sm font-medium transition-colors ${
                                    activeSection === 'results'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                Products ({instantResults.length})
                            </button>
                        )}
                        {searchHistory.length > 0 && (
                            <button
                                onClick={() => setActiveSection('history')}
                                className={`flex-1 px-4 py-2 text-sm font-medium transition-colors ${
                                    activeSection === 'history'
                                        ? 'text-blue-600 border-b-2 border-blue-600'
                                        : 'text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                Recent
                            </button>
                        )}
                    </div>

                    {/* Suggestions Section */}
                    {activeSection === 'suggestions' && suggestions.length > 0 && (
                        <div className="max-h-64 overflow-y-auto">
                            {suggestions.map((suggestion, index) => (
                                <button
                                    key={`${suggestion.text}-${index}`}
                                    onClick={() => handleSuggestionClick(suggestion)}
                                    className="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center space-x-3 transition-colors"
                                >
                                    {getSuggestionIcon(suggestion.type)}
                                    <div className="flex-1">
                                        <div className="font-medium text-gray-900">
                                            {suggestion.text}
                                        </div>
                                        <div className="text-sm text-gray-500 capitalize">
                                            {suggestion.type} • Weight: {suggestion.weight}
                                        </div>
                                    </div>
                                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                              d="M7 7l3-3 3 3m0 8l-3 3-3-3" />
                                    </svg>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Instant Results Section */}
                    {activeSection === 'results' && instantResults.length > 0 && (
                        <div className="max-h-80 overflow-y-auto">
                            {instantResults.map((result) => (
                                <button
                                    key={result.id}
                                    onClick={() => handleResultClick(result.id)}
                                    className="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors"
                                >
                                    <div className="flex items-start space-x-3">
                                        {/* Product Image */}
                                        <div className="flex-shrink-0">
                                            {result.thumbnail ? (
                                                <img
                                                    src={result.thumbnail}
                                                    alt={result.name}
                                                    className="w-12 h-12 object-cover rounded-lg bg-gray-100"
                                                />
                                            ) : (
                                                <div className="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <svg className="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>

                                        {/* Product Info */}
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <h4 className="font-medium text-gray-900 truncate">
                                                        {result.name}
                                                    </h4>
                                                    <p className="text-sm text-gray-500 truncate">
                                                        SKU: {result.sku} • {result.category}
                                                    </p>
                                                    {result.description && (
                                                        <p className="text-sm text-gray-400 mt-1 line-clamp-2">
                                                            {result.description}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="text-right ml-4">
                                                    <div className="font-semibold text-gray-900">
                                                        {formatPrice(result.price)}
                                                    </div>
                                                    <span className={`inline-block px-2 py-1 text-xs font-medium rounded-full ${getStockStatusColor(result.stock_status)}`}>
                                                        {result.stock_status.replace('_', ' ')}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Search History Section */}
                    {activeSection === 'history' && searchHistory.length > 0 && (
                        <div className="max-h-48 overflow-y-auto">
                            <div className="px-4 py-2 text-xs font-medium text-gray-500 bg-gray-50">
                                Recent Searches
                            </div>
                            {searchHistory.map((historyItem) => (
                                <button
                                    key={historyItem.id}
                                    onClick={() => handleSuggestionClick({ text: historyItem.query, type: 'history', weight: 0 })}
                                    className="w-full px-4 py-2 text-left hover:bg-gray-50 flex items-center space-x-3 transition-colors"
                                >
                                    <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div className="flex-1">
                                        <div className="text-sm font-medium text-gray-900">
                                            {historyItem.query}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {historyItem.result_count} results • {new Date(historyItem.timestamp).toLocaleDateString()}
                                        </div>
                                    </div>
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Empty State */}
                    {!loading && suggestions.length === 0 && instantResults.length === 0 && searchHistory.length === 0 && (
                        <div className="p-6 text-center text-gray-500">
                            <svg className="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p className="text-sm">No suggestions available</p>
                            <p className="text-xs text-gray-400 mt-1">Start typing to see search suggestions</p>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default SearchSuggestions;
