/**
 * FilterSidebar Component
 * Manufacturing Product Catalog - Advanced Filtering Interface
 */

import React, { useState, useCallback, useEffect } from 'react';
import { FilterState, ProductCategory } from '../types/Product';

interface FilterSidebarProps {
  filters: FilterState;
  categories: ProductCategory[];
  materials: string[];
  priceRange: { min: number; max: number };
  isOpen: boolean;
  onFiltersChange: (filters: FilterState) => void;
  onToggle: () => void;
  onReset: () => void;
  className?: string;
}

const FilterSidebar: React.FC<FilterSidebarProps> = ({
  filters,
  categories,
  materials,
  priceRange,
  isOpen,
  onFiltersChange,
  onToggle,
  onReset,
  className = '',
}) => {
  const [localPriceRange, setLocalPriceRange] = useState({
    min: filters.priceRange.min,
    max: filters.priceRange.max,
  });

  // Update local price range when filters change
  useEffect(() => {
    setLocalPriceRange({
      min: filters.priceRange.min,
      max: filters.priceRange.max,
    });
  }, [filters.priceRange]);

  const handleCategoryToggle = useCallback((category: string) => {
    const newCategories = filters.selectedCategories.includes(category)
      ? filters.selectedCategories.filter(c => c !== category)
      : [...filters.selectedCategories, category];
    
    onFiltersChange({
      ...filters,
      selectedCategories: newCategories,
    });
  }, [filters, onFiltersChange]);

  const handleMaterialToggle = useCallback((material: string) => {
    const newMaterials = filters.selectedMaterials.includes(material)
      ? filters.selectedMaterials.filter(m => m !== material)
      : [...filters.selectedMaterials, material];
    
    onFiltersChange({
      ...filters,
      selectedMaterials: newMaterials,
    });
  }, [filters, onFiltersChange]);

  const handleStockFilterChange = useCallback((stockFilter: FilterState['stockFilter']) => {
    onFiltersChange({
      ...filters,
      stockFilter,
    });
  }, [filters, onFiltersChange]);

  const handlePriceRangeChange = useCallback((min: number, max: number) => {
    setLocalPriceRange({ min, max });
    
    // Debounce the filter update
    const timer = setTimeout(() => {
      onFiltersChange({
        ...filters,
        priceRange: { min, max },
      });
    }, 300);

    return () => clearTimeout(timer);
  }, [filters, onFiltersChange]);

  const handleSortChange = useCallback((sortBy: string, sortOrder: 'ASC' | 'DESC') => {
    onFiltersChange({
      ...filters,
      sortBy,
      sortOrder,
    });
  }, [filters, onFiltersChange]);

  const getActiveFilterCount = () => {
    let count = 0;
    if (filters.selectedCategories.length > 0) count++;
    if (filters.selectedMaterials.length > 0) count++;
    if (filters.priceRange.min > priceRange.min || filters.priceRange.max < priceRange.max) count++;
    if (filters.stockFilter !== 'all') count++;
    return count;
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price);
  };

  return (
    <>
      {/* Mobile Overlay */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
          onClick={onToggle}
          aria-hidden="true"
        />
      )}

      {/* Sidebar */}
      <div
        className={`
          ${className}
          fixed inset-y-0 left-0 z-50 w-80 bg-white shadow-lg transform transition-transform duration-300 ease-in-out
          lg:relative lg:translate-x-0 lg:shadow-none lg:w-64
          ${isOpen ? 'translate-x-0' : '-translate-x-full'}
        `}
      >
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-gray-200">
          <div className="flex items-center">
            <h2 className="text-lg font-semibold text-gray-900">Filters</h2>
            {getActiveFilterCount() > 0 && (
              <span className="ml-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                {getActiveFilterCount()}
              </span>
            )}
          </div>
          
          <div className="flex items-center space-x-2">
            <button
              type="button"
              onClick={onReset}
              className="text-sm text-blue-600 hover:text-blue-800 transition-colors"
              disabled={getActiveFilterCount() === 0}
            >
              Reset
            </button>
            
            <button
              type="button"
              onClick={onToggle}
              className="lg:hidden p-1 text-gray-400 hover:text-gray-600 transition-colors"
              aria-label="Close filters"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        {/* Filter Content */}
        <div className="flex-1 overflow-y-auto p-4 space-y-6">
          
          {/* Categories */}
          <div>
            <h3 className="text-sm font-medium text-gray-900 mb-3">Categories</h3>
            <div className="space-y-2">
              {categories.map((category) => (
                <label key={category.name} className="flex items-center">
                  <input
                    type="checkbox"
                    checked={filters.selectedCategories.includes(category.name)}
                    onChange={() => handleCategoryToggle(category.name)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <span className="ml-2 text-sm text-gray-700 flex-1">
                    {category.name}
                  </span>
                  <span className="text-xs text-gray-500">
                    ({category.product_count})
                  </span>
                </label>
              ))}
            </div>
          </div>

          {/* Materials */}
          {materials.length > 0 && (
            <div>
              <h3 className="text-sm font-medium text-gray-900 mb-3">Materials</h3>
              <div className="space-y-2 max-h-40 overflow-y-auto">
                {materials.map((material) => (
                  <label key={material} className="flex items-center">
                    <input
                      type="checkbox"
                      checked={filters.selectedMaterials.includes(material)}
                      onChange={() => handleMaterialToggle(material)}
                      className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <span className="ml-2 text-sm text-gray-700">
                      {material}
                    </span>
                  </label>
                ))}
              </div>
            </div>
          )}

          {/* Price Range */}
          <div>
            <h3 className="text-sm font-medium text-gray-900 mb-3">Price Range</h3>
            <div className="space-y-3">
              <div className="flex items-center justify-between text-sm text-gray-600">
                <span>{formatPrice(localPriceRange.min)}</span>
                <span>{formatPrice(localPriceRange.max)}</span>
              </div>
              
              <div className="relative">
                <input
                  type="range"
                  min={priceRange.min}
                  max={priceRange.max}
                  value={localPriceRange.min}
                  onChange={(e) => handlePriceRangeChange(Number(e.target.value), localPriceRange.max)}
                  className="absolute w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider-thumb"
                />
                <input
                  type="range"
                  min={priceRange.min}
                  max={priceRange.max}
                  value={localPriceRange.max}
                  onChange={(e) => handlePriceRangeChange(localPriceRange.min, Number(e.target.value))}
                  className="absolute w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider-thumb"
                />
              </div>
              
              <div className="flex space-x-2">
                <input
                  type="number"
                  value={localPriceRange.min}
                  onChange={(e) => handlePriceRangeChange(Number(e.target.value), localPriceRange.max)}
                  className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Min"
                />
                <input
                  type="number"
                  value={localPriceRange.max}
                  onChange={(e) => handlePriceRangeChange(localPriceRange.min, Number(e.target.value))}
                  className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Max"
                />
              </div>
            </div>
          </div>

          {/* Stock Status */}
          <div>
            <h3 className="text-sm font-medium text-gray-900 mb-3">Availability</h3>
            <div className="space-y-2">
              {[
                { value: 'all', label: 'All Products' },
                { value: 'in_stock', label: 'In Stock Only' },
                { value: 'low_stock', label: 'Low Stock' },
                { value: 'out_of_stock', label: 'Out of Stock' },
              ].map((option) => (
                <label key={option.value} className="flex items-center">
                  <input
                    type="radio"
                    name="stockFilter"
                    value={option.value}
                    checked={filters.stockFilter === option.value}
                    onChange={(e) => handleStockFilterChange(e.target.value as FilterState['stockFilter'])}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <span className="ml-2 text-sm text-gray-700">
                    {option.label}
                  </span>
                </label>
              ))}
            </div>
          </div>

          {/* Sort Options */}
          <div>
            <h3 className="text-sm font-medium text-gray-900 mb-3">Sort By</h3>
            <select
              value={`${filters.sortBy}_${filters.sortOrder}`}
              onChange={(e) => {
                const [sortBy, sortOrder] = e.target.value.split('_');
                handleSortChange(sortBy, sortOrder as 'ASC' | 'DESC');
              }}
              className="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="name_ASC">Name (A-Z)</option>
              <option value="name_DESC">Name (Z-A)</option>
              <option value="base_price_ASC">Price (Low to High)</option>
              <option value="base_price_DESC">Price (High to Low)</option>
              <option value="category_ASC">Category (A-Z)</option>
              <option value="stock_quantity_DESC">Stock (High to Low)</option>
              <option value="date_modified_DESC">Recently Updated</option>
            </select>
          </div>
        </div>
      </div>
    </>
  );
};

export default FilterSidebar;
