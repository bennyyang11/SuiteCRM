/**
 * SearchFilters Component
 * Advanced filtering sidebar with faceted search capabilities
 */

import React, { useState, useCallback, useMemo } from 'react';
import { SearchParams, SearchFacets } from '../types/Search';

interface SearchFiltersProps {
    filters: SearchParams;
    facets: SearchFacets | null;
    onFilterChange: (filters: Partial<SearchParams>) => void;
    onClearFilters: () => void;
    loading?: boolean;
    className?: string;
}

const SearchFilters: React.FC<SearchFiltersProps> = ({
    filters,
    facets,
    onFilterChange,
    onClearFilters,
    loading = false,
    className = ''
}) => {
    const [expandedSections, setExpandedSections] = useState<Set<string>>(
        new Set(['category', 'material', 'price', 'stock'])
    );

    const toggleSection = useCallback((sectionId: string) => {
        setExpandedSections(prev => {
            const newSet = new Set(prev);
            if (newSet.has(sectionId)) {
                newSet.delete(sectionId);
            } else {
                newSet.add(sectionId);
            }
            return newSet;
        });
    }, []);

    const handlePriceRangeChange = useCallback((type: 'min' | 'max', value: string) => {
        const numericValue = parseFloat(value) || 0;
        onFilterChange({
            [`price_${type}`]: numericValue
        });
    }, [onFilterChange]);

    const handleWeightRangeChange = useCallback((type: 'min' | 'max', value: string) => {
        const numericValue = parseFloat(value) || 0;
        onFilterChange({
            [`weight_${type}`]: numericValue
        });
    }, [onFilterChange]);

    const handleLeadTimeChange = useCallback((value: string) => {
        const numericValue = parseInt(value) || 365;
        onFilterChange({
            lead_time_max: numericValue
        });
    }, [onFilterChange]);

    const handleSpecificationToggle = useCallback((spec: string) => {
        const currentSpecs = filters.specifications || [];
        const newSpecs = currentSpecs.includes(spec)
            ? currentSpecs.filter(s => s !== spec)
            : [...currentSpecs, spec];
        
        onFilterChange({
            specifications: newSpecs
        });
    }, [filters.specifications, onFilterChange]);

    const handleTagToggle = useCallback((tag: string) => {
        const currentTags = filters.tags || [];
        const newTags = currentTags.includes(tag)
            ? currentTags.filter(t => t !== tag)
            : [...currentTags, tag];
        
        onFilterChange({
            tags: newTags
        });
    }, [filters.tags, onFilterChange]);

    const activeFiltersCount = useMemo(() => {
        return Object.entries(filters).filter(([key, value]) => {
            if (key === 'query' || key === 'client_id') return false;
            if (Array.isArray(value)) return value.length > 0;
            if (key === 'price_min' || key === 'weight_min') return value > 0;
            if (key === 'price_max') return value < 999999;
            if (key === 'weight_max') return value < 999999;
            if (key === 'lead_time_max') return value < 365;
            return value !== '';
        }).length;
    }, [filters]);

    const FilterSection: React.FC<{
        id: string;
        title: string;
        children: React.ReactNode;
        count?: number;
    }> = ({ id, title, children, count }) => {
        const isExpanded = expandedSections.has(id);

        return (
            <div className="border-b border-gray-200">
                <button
                    onClick={() => toggleSection(id)}
                    className="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 transition-colors"
                >
                    <div className="flex items-center space-x-2">
                        <span className="font-medium text-gray-900">{title}</span>
                        {count !== undefined && (
                            <span className="px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
                                {count}
                            </span>
                        )}
                    </div>
                    <svg
                        className={`w-5 h-5 text-gray-400 transition-transform ${
                            isExpanded ? 'rotate-180' : ''
                        }`}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                {isExpanded && (
                    <div className="px-4 pb-4">
                        {children}
                    </div>
                )}
            </div>
        );
    };

    const CheckboxFilter: React.FC<{
        items: Array<{ value: string; count?: number; label?: string }>;
        selectedValues: string[];
        onChange: (value: string) => void;
        showCounts?: boolean;
    }> = ({ items, selectedValues, onChange, showCounts = true }) => (
        <div className="space-y-2 max-h-48 overflow-y-auto">
            {items.map((item) => (
                <label key={item.value} className="flex items-center space-x-2 cursor-pointer">
                    <input
                        type="checkbox"
                        checked={selectedValues.includes(item.value)}
                        onChange={() => onChange(item.value)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span className="flex-1 text-sm text-gray-700">
                        {item.label || item.value}
                    </span>
                    {showCounts && item.count !== undefined && (
                        <span className="text-xs text-gray-500">({item.count})</span>
                    )}
                </label>
            ))}
        </div>
    );

    const RangeSlider: React.FC<{
        min: number;
        max: number;
        currentMin: number;
        currentMax: number;
        onMinChange: (value: string) => void;
        onMaxChange: (value: string) => void;
        step?: number;
        prefix?: string;
        suffix?: string;
    }> = ({ min, max, currentMin, currentMax, onMinChange, onMaxChange, step = 1, prefix = '', suffix = '' }) => (
        <div className="space-y-3">
            <div className="flex items-center space-x-2">
                <div className="flex-1">
                    <label className="block text-xs text-gray-500 mb-1">Min</label>
                    <input
                        type="number"
                        value={currentMin || ''}
                        onChange={(e) => onMinChange(e.target.value)}
                        min={min}
                        max={max}
                        step={step}
                        className="w-full px-3 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder={`${prefix}${min}${suffix}`}
                    />
                </div>
                <div className="px-2 pt-5 text-gray-400">to</div>
                <div className="flex-1">
                    <label className="block text-xs text-gray-500 mb-1">Max</label>
                    <input
                        type="number"
                        value={currentMax === 999999 || currentMax === 365 ? '' : currentMax || ''}
                        onChange={(e) => onMaxChange(e.target.value)}
                        min={min}
                        max={max}
                        step={step}
                        className="w-full px-3 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder={`${prefix}${max}${suffix}`}
                    />
                </div>
            </div>
            <div className="text-xs text-gray-500">
                {prefix}{Math.min(currentMin || min, currentMax || max)}{suffix} - {prefix}{Math.max(currentMin || min, currentMax || max)}{suffix}
            </div>
        </div>
    );

    return (
        <div className={`bg-white h-full ${className}`}>
            {/* Filter Header */}
            <div className="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold text-gray-900">Filters</h3>
                    {activeFiltersCount > 0 && (
                        <button
                            onClick={onClearFilters}
                            className="text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            Clear All ({activeFiltersCount})
                        </button>
                    )}
                </div>
            </div>

            {loading && (
                <div className="p-4 text-center">
                    <div className="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                    <p className="text-sm text-gray-500 mt-2">Loading filters...</p>
                </div>
            )}

            {!loading && (
                <div className="overflow-y-auto h-full pb-4">
                    {/* Category Filter */}
                    {facets?.categories && facets.categories.length > 0 && (
                        <FilterSection
                            id="category"
                            title="Category"
                            count={filters.category ? 1 : 0}
                        >
                            <div className="space-y-2">
                                {facets.categories.map((category) => (
                                    <label key={category.value} className="flex items-center space-x-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="category"
                                            checked={filters.category === category.value}
                                            onChange={() => onFilterChange({
                                                category: filters.category === category.value ? '' : category.value
                                            })}
                                            className="text-blue-600 focus:ring-blue-500"
                                        />
                                        <span className="flex-1 text-sm text-gray-700">
                                            {category.label}
                                        </span>
                                        <span className="text-xs text-gray-500">({category.count})</span>
                                    </label>
                                ))}
                            </div>
                        </FilterSection>
                    )}

                    {/* Material Filter */}
                    {facets?.materials && facets.materials.length > 0 && (
                        <FilterSection
                            id="material"
                            title="Material"
                            count={filters.material ? 1 : 0}
                        >
                            <div className="space-y-2">
                                {facets.materials.map((material) => (
                                    <label key={material.value} className="flex items-center space-x-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="material"
                                            checked={filters.material === material.value}
                                            onChange={() => onFilterChange({
                                                material: filters.material === material.value ? '' : material.value
                                            })}
                                            className="text-blue-600 focus:ring-blue-500"
                                        />
                                        <span className="flex-1 text-sm text-gray-700">
                                            {material.label}
                                        </span>
                                        <span className="text-xs text-gray-500">({material.count})</span>
                                    </label>
                                ))}
                            </div>
                        </FilterSection>
                    )}

                    {/* Price Range Filter */}
                    <FilterSection
                        id="price"
                        title="Price Range"
                        count={(filters.price_min > 0 || filters.price_max < 999999) ? 1 : 0}
                    >
                        <RangeSlider
                            min={0}
                            max={10000}
                            currentMin={filters.price_min}
                            currentMax={filters.price_max}
                            onMinChange={(value) => handlePriceRangeChange('min', value)}
                            onMaxChange={(value) => handlePriceRangeChange('max', value)}
                            step={10}
                            prefix="$"
                        />
                    </FilterSection>

                    {/* Stock Status Filter */}
                    {facets?.stock_status && facets.stock_status.length > 0 && (
                        <FilterSection
                            id="stock"
                            title="Stock Status"
                            count={filters.stock_status ? 1 : 0}
                        >
                            <div className="space-y-2">
                                {facets.stock_status.map((status) => (
                                    <label key={status.value} className="flex items-center space-x-2 cursor-pointer">
                                        <input
                                            type="radio"
                                            name="stock_status"
                                            checked={filters.stock_status === status.value}
                                            onChange={() => onFilterChange({
                                                stock_status: filters.stock_status === status.value ? '' : status.value
                                            })}
                                            className="text-blue-600 focus:ring-blue-500"
                                        />
                                        <span className="flex-1 text-sm text-gray-700 capitalize">
                                            {status.label.replace('_', ' ')}
                                        </span>
                                        <span className="text-xs text-gray-500">({status.count})</span>
                                    </label>
                                ))}
                            </div>
                        </FilterSection>
                    )}

                    {/* Weight Range Filter */}
                    <FilterSection
                        id="weight"
                        title="Weight Range"
                        count={(filters.weight_min > 0 || filters.weight_max < 999999) ? 1 : 0}
                    >
                        <RangeSlider
                            min={0}
                            max={1000}
                            currentMin={filters.weight_min}
                            currentMax={filters.weight_max}
                            onMinChange={(value) => handleWeightRangeChange('min', value)}
                            onMaxChange={(value) => handleWeightRangeChange('max', value)}
                            step={0.1}
                            suffix=" lbs"
                        />
                    </FilterSection>

                    {/* Lead Time Filter */}
                    <FilterSection
                        id="lead_time"
                        title="Lead Time"
                        count={filters.lead_time_max < 365 ? 1 : 0}
                    >
                        <div className="space-y-2">
                            <label className="block text-sm text-gray-700">Maximum days</label>
                            <input
                                type="range"
                                min="1"
                                max="365"
                                value={filters.lead_time_max}
                                onChange={(e) => handleLeadTimeChange(e.target.value)}
                                className="w-full"
                            />
                            <div className="flex justify-between text-xs text-gray-500">
                                <span>1 day</span>
                                <span className="font-medium">{filters.lead_time_max} days</span>
                                <span>365 days</span>
                            </div>
                        </div>
                    </FilterSection>

                    {/* Manufacturer Filter */}
                    <FilterSection
                        id="manufacturer"
                        title="Manufacturer"
                        count={filters.manufacturer ? 1 : 0}
                    >
                        <input
                            type="text"
                            value={filters.manufacturer}
                            onChange={(e) => onFilterChange({ manufacturer: e.target.value })}
                            placeholder="Enter manufacturer name..."
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </FilterSection>

                    {/* Popular Filters */}
                    {facets?.popular_filters && facets.popular_filters.length > 0 && (
                        <FilterSection
                            id="popular"
                            title="Popular Filters"
                        >
                            <div className="flex flex-wrap gap-2">
                                {facets.popular_filters.map((filter) => (
                                    <button
                                        key={filter.name}
                                        onClick={() => onFilterChange(filter.filters)}
                                        className="px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full hover:bg-blue-100 transition-colors"
                                    >
                                        {filter.name}
                                    </button>
                                ))}
                            </div>
                        </FilterSection>
                    )}
                </div>
            )}
        </div>
    );
};

export default SearchFilters;
