/**
 * ProductCatalog Component
 * Manufacturing Product Catalog - Main Container Component
 */

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Product, FilterState, ProductCategory, ProductSearchParams } from '../types/Product';
import { productAPI } from '../services/ProductAPI';
import { useQuote } from '../hooks/useQuote';
import ProductCard from './ProductCard';
import FilterSidebar from './FilterSidebar';

interface ProductCatalogProps {
  clientId?: string;
  className?: string;
}

const ProductCatalog: React.FC<ProductCatalogProps> = ({
  clientId,
  className = '',
}) => {
  // State management
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<ProductCategory[]>([]);
  const [materials, setMaterials] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [totalCount, setTotalCount] = useState(0);
  const [isFilterOpen, setIsFilterOpen] = useState(false);

  // Quote management
  const {
    quote,
    addToQuote,
    hasProduct,
    getItemCount,
  } = useQuote(clientId);

  // Filter state
  const [filters, setFilters] = useState<FilterState>({
    searchQuery: '',
    selectedCategories: [],
    selectedMaterials: [],
    priceRange: { min: 0, max: 10000 },
    stockFilter: 'all',
    sortBy: 'name',
    sortOrder: 'ASC',
  });

  // Price range from all products
  const priceRange = useMemo(() => {
    if (products.length === 0) return { min: 0, max: 10000 };
    
    const prices = products.map(p => p.pricing.base_price);
    return {
      min: Math.min(...prices),
      max: Math.max(...prices),
    };
  }, [products]);

  // Debounced search
  useEffect(() => {
    const timer = setTimeout(() => {
      setFilters(prev => ({ ...prev, searchQuery }));
      setPage(1);
    }, 300);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  // Load categories and materials on mount
  useEffect(() => {
    loadCategories();
  }, []);

  // Load products when filters change
  useEffect(() => {
    loadProducts(true);
  }, [filters, page]);

  // Load categories
  const loadCategories = async () => {
    try {
      const response = await productAPI.getCategories();
      if (response.status === 'success' && response.data) {
        setCategories(response.data.categories);
        
        // Extract unique materials from categories
        // This would ideally come from a separate API endpoint
        const uniqueMaterials = ['Carbon Steel', 'Aluminum', 'Stainless Steel', 'Copper', 'Brass', 'Plastic'];
        setMaterials(uniqueMaterials);
      }
    } catch (error) {
      console.error('Error loading categories:', error);
    }
  };

  // Load products
  const loadProducts = async (reset = false) => {
    if (loading) return;
    
    setLoading(true);
    setError(null);

    try {
      const searchParams: ProductSearchParams = {
        q: filters.searchQuery || undefined,
        category: filters.selectedCategories.length > 0 ? filters.selectedCategories[0] : undefined,
        material: filters.selectedMaterials.length > 0 ? filters.selectedMaterials[0] : undefined,
        price_min: filters.priceRange.min > 0 ? filters.priceRange.min : undefined,
        price_max: filters.priceRange.max < 10000 ? filters.priceRange.max : undefined,
        in_stock: filters.stockFilter === 'in_stock' ? true : undefined,
        page: reset ? 1 : page,
        limit: 20,
        sort_by: filters.sortBy as any,
        sort_order: filters.sortOrder,
      };

      const response = await productAPI.searchProducts(searchParams);
      
      if (response.status === 'success') {
        const newProducts = response.data.products;
        
        if (reset) {
          setProducts(newProducts);
        } else {
          setProducts(prev => [...prev, ...newProducts]);
        }
        
        setTotalCount(response.data.pagination.total_count);
        setHasMore(response.data.pagination.has_next);
        
        if (reset) {
          setPage(1);
        }
      } else {
        throw new Error('Failed to load products');
      }
    } catch (error) {
      console.error('Error loading products:', error);
      setError(error instanceof Error ? error.message : 'Failed to load products');
    } finally {
      setLoading(false);
    }
  };

  // Handle filter changes
  const handleFiltersChange = useCallback((newFilters: FilterState) => {
    setFilters(newFilters);
    setPage(1);
  }, []);

  // Handle filter reset
  const handleFilterReset = useCallback(() => {
    setFilters({
      searchQuery: '',
      selectedCategories: [],
      selectedMaterials: [],
      priceRange: { min: 0, max: 10000 },
      stockFilter: 'all',
      sortBy: 'name',
      sortOrder: 'ASC',
    });
    setSearchQuery('');
    setPage(1);
  }, []);

  // Handle load more
  const handleLoadMore = useCallback(() => {
    if (!loading && hasMore) {
      setPage(prev => prev + 1);
    }
  }, [loading, hasMore]);

  // Handle add to quote
  const handleAddToQuote = useCallback(async (product: Product, quantity: number) => {
    try {
      await addToQuote(product, quantity);
    } catch (error) {
      console.error('Error adding to quote:', error);
      // Show error notification
    }
  }, [addToQuote]);

  // Handle product details view
  const handleViewDetails = useCallback((product: Product) => {
    // This would typically open a modal or navigate to a detail page
    console.log('View details for:', product);
    // TODO: Implement product detail modal
  }, []);

  // Responsive grid classes
  const gridClasses = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6';

  return (
    <div className={`product-catalog ${className} min-h-screen bg-gray-50`}>
      {/* Header */}
      <div className="bg-white shadow-sm sticky top-0 z-30">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            {/* Title and Filter Toggle */}
            <div className="flex items-center">
              <button
                type="button"
                onClick={() => setIsFilterOpen(!isFilterOpen)}
                className="lg:hidden p-2 text-gray-400 hover:text-gray-600 transition-colors"
                aria-label="Toggle filters"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z" />
                </svg>
              </button>
              
              <h1 className="ml-2 lg:ml-0 text-xl font-semibold text-gray-900">
                Product Catalog
              </h1>
              
              <span className="ml-3 text-sm text-gray-500">
                {totalCount} products
              </span>
            </div>

            {/* Search Bar */}
            <div className="flex-1 max-w-lg mx-4">
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search products, SKUs, descriptions..."
                  className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                />
                {searchQuery && (
                  <button
                    type="button"
                    onClick={() => setSearchQuery('')}
                    className="absolute inset-y-0 right-0 pr-3 flex items-center"
                  >
                    <svg className="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                )}
              </div>
            </div>

            {/* Quote Summary */}
            <div className="flex items-center">
              <button
                type="button"
                className="relative p-2 text-gray-400 hover:text-gray-600 transition-colors"
                aria-label="View quote"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                
                {getItemCount() > 0 && (
                  <span className="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                    {getItemCount()}
                  </span>
                )}
              </button>
              
              {quote.total > 0 && (
                <span className="ml-2 text-sm font-medium text-gray-900">
                  ${quote.total.toFixed(2)}
                </span>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="flex">
          {/* Filter Sidebar */}
          <FilterSidebar
            filters={filters}
            categories={categories}
            materials={materials}
            priceRange={priceRange}
            isOpen={isFilterOpen}
            onFiltersChange={handleFiltersChange}
            onToggle={() => setIsFilterOpen(!isFilterOpen)}
            onReset={handleFilterReset}
            className="flex-shrink-0"
          />

          {/* Product Grid */}
          <main className="flex-1 lg:ml-6">
            {/* Loading State */}
            {loading && products.length === 0 && (
              <div className="flex justify-center items-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
              </div>
            )}

            {/* Error State */}
            {error && (
              <div className="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                <div className="flex">
                  <div className="ml-3">
                    <h3 className="text-sm font-medium text-red-800">Error loading products</h3>
                    <div className="mt-2 text-sm text-red-700">
                      <p>{error}</p>
                    </div>
                    <div className="mt-4">
                      <button
                        type="button"
                        onClick={() => loadProducts(true)}
                        className="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200 transition-colors"
                      >
                        Try again
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Empty State */}
            {!loading && !error && products.length === 0 && (
              <div className="text-center py-12">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7" />
                </svg>
                <h3 className="mt-2 text-sm font-medium text-gray-900">No products found</h3>
                <p className="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
                <div className="mt-6">
                  <button
                    type="button"
                    onClick={handleFilterReset}
                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    Clear all filters
                  </button>
                </div>
              </div>
            )}

            {/* Product Grid */}
            {products.length > 0 && (
              <>
                <div className={gridClasses}>
                  {products.map((product) => (
                    <ProductCard
                      key={product.id}
                      product={product}
                      onAddToQuote={handleAddToQuote}
                      onViewDetails={handleViewDetails}
                      isInQuote={hasProduct(product.id)}
                      isLoading={quote.isLoading}
                    />
                  ))}
                </div>

                {/* Load More Button */}
                {hasMore && (
                  <div className="mt-8 text-center">
                    <button
                      type="button"
                      onClick={handleLoadMore}
                      disabled={loading}
                      className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {loading ? (
                        <>
                          <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          Loading...
                        </>
                      ) : (
                        'Load More Products'
                      )}
                    </button>
                  </div>
                )}

                {/* Results Summary */}
                <div className="mt-6 text-center text-sm text-gray-500">
                  Showing {products.length} of {totalCount} products
                </div>
              </>
            )}
          </main>
        </div>
      </div>

      {/* Quote Error Display */}
      {quote.error && (
        <div className="fixed bottom-4 right-4 bg-red-500 text-white px-4 py-2 rounded-md shadow-lg z-50">
          {quote.error}
        </div>
      )}
    </div>
  );
};

export default ProductCatalog;
