/**
 * ProductCard Component
 * Manufacturing Product Catalog - Touch-optimized Product Display
 */

import React, { useState, useCallback } from 'react';
import { Product } from '../types/Product';

interface ProductCardProps {
  product: Product;
  onAddToQuote: (product: Product, quantity: number) => void;
  onViewDetails: (product: Product) => void;
  isInQuote: boolean;
  isLoading: boolean;
  className?: string;
}

const ProductCard: React.FC<ProductCardProps> = ({
  product,
  onAddToQuote,
  onViewDetails,
  isInQuote,
  isLoading,
  className = '',
}) => {
  const [quantity, setQuantity] = useState(1);
  const [imageLoaded, setImageLoaded] = useState(false);
  const [imageError, setImageError] = useState(false);
  const [isPressed, setIsPressed] = useState(false);

  const handleAddToQuote = useCallback((e: React.MouseEvent) => {
    e.stopPropagation();
    onAddToQuote(product, quantity);
  }, [product, quantity, onAddToQuote]);

  const handleCardClick = useCallback(() => {
    onViewDetails(product);
  }, [product, onViewDetails]);

  const handleQuantityChange = useCallback((delta: number) => {
    setQuantity(prev => Math.max(1, prev + delta));
  }, []);

  const getStockStatusColor = (status: string) => {
    switch (status) {
      case 'In Stock':
        return 'bg-green-100 text-green-800';
      case 'Low Stock':
        return 'bg-yellow-100 text-yellow-800';
      case 'Out of Stock':
        return 'bg-red-100 text-red-800';
      case 'Limited Stock':
        return 'bg-orange-100 text-orange-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: product.pricing.currency || 'USD',
    }).format(price);
  };

  const placeholderImage = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjIwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmMGYwZjAiLz4KPHN2ZyB4PSI3NSIgeT0iNzUiIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCI+CjxwYXRoIGQ9Ik0yNSAyNWE1IDUgMCAwIDEgNSA1djE1YTUgNSAwIDAgMS01IDVINWE1IDUgMCAwIDEtNS01VjMwYTUgNSAwIDAgMSA1LTVIM25WMTBhNSA1IDAgMCAxIDUtNWgxNWE1IDUgMCAwIDEgNSA1djE1eiIgZmlsbD0iI2NjYyIvPgo8L3N2Zz4KPC9zdmc+';

  return (
    <div
      className={`
        product-card 
        ${className}
        ${isPressed ? 'transform scale-95' : ''}
        bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 
        cursor-pointer overflow-hidden border border-gray-200
        ${isInQuote ? 'ring-2 ring-blue-500' : ''}
      `}
      onClick={handleCardClick}
      onMouseDown={() => setIsPressed(true)}
      onMouseUp={() => setIsPressed(false)}
      onMouseLeave={() => setIsPressed(false)}
      onTouchStart={() => setIsPressed(true)}
      onTouchEnd={() => setIsPressed(false)}
      role="button"
      tabIndex={0}
      aria-label={`Product: ${product.name}`}
    >
      {/* Product Image */}
      <div className="relative h-48 bg-gray-100 overflow-hidden">
        {!imageLoaded && !imageError && (
          <div className="absolute inset-0 bg-gray-200 animate-pulse flex items-center justify-center">
            <div className="w-12 h-12 text-gray-400">
              <svg fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
              </svg>
            </div>
          </div>
        )}
        
        <img
          src={imageError ? placeholderImage : (product.media.primary_image || placeholderImage)}
          alt={product.name}
          className={`w-full h-full object-cover transition-opacity duration-200 ${
            imageLoaded ? 'opacity-100' : 'opacity-0'
          }`}
          onLoad={() => setImageLoaded(true)}
          onError={() => {
            setImageError(true);
            setImageLoaded(true);
          }}
          loading="lazy"
        />

        {/* Stock Status Badge */}
        <div className="absolute top-2 right-2">
          <span className={`
            px-2 py-1 text-xs font-medium rounded-full
            ${getStockStatusColor(product.inventory.stock_status)}
          `}>
            {product.inventory.stock_status}
          </span>
        </div>

        {/* Quote Badge */}
        {isInQuote && (
          <div className="absolute top-2 left-2">
            <span className="bg-blue-500 text-white px-2 py-1 text-xs font-medium rounded-full">
              In Quote
            </span>
          </div>
        )}
      </div>

      {/* Product Information */}
      <div className="p-4">
        {/* Name and SKU */}
        <div className="mb-2">
          <h3 className="font-semibold text-gray-900 text-sm line-clamp-2 leading-tight">
            {product.name}
          </h3>
          <p className="text-xs text-gray-500 mt-1">SKU: {product.sku}</p>
        </div>

        {/* Category and Material */}
        <div className="mb-3">
          <div className="flex flex-wrap gap-1">
            <span className="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">
              {product.category}
            </span>
            {product.material && (
              <span className="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded">
                {product.material}
              </span>
            )}
          </div>
        </div>

        {/* Description */}
        <p className="text-xs text-gray-600 mb-3 line-clamp-2">
          {product.description}
        </p>

        {/* Specifications */}
        {product.specifications.weight > 0 && (
          <div className="text-xs text-gray-500 mb-3">
            <span>Weight: {product.specifications.weight} {product.specifications.weight_unit}</span>
            {product.specifications.dimensions.length > 0 && (
              <span className="ml-2">
                • {product.specifications.dimensions.length}" × {product.specifications.dimensions.width}" × {product.specifications.dimensions.height}"
              </span>
            )}
          </div>
        )}

        {/* Pricing */}
        <div className="mb-4">
          <div className="flex items-baseline justify-between">
            <span className="text-lg font-bold text-gray-900">
              {formatPrice(product.pricing.base_price)}
            </span>
            {product.pricing.list_price > product.pricing.base_price && (
              <span className="text-sm text-gray-500 line-through">
                {formatPrice(product.pricing.list_price)}
              </span>
            )}
          </div>
        </div>

        {/* Quantity Selector and Add to Quote */}
        <div className="flex items-center justify-between">
          {/* Quantity Selector */}
          <div className="flex items-center bg-gray-100 rounded-md">
            <button
              type="button"
              className="p-2 text-gray-600 hover:text-gray-800 transition-colors touch-manipulation"
              onClick={(e) => {
                e.stopPropagation();
                handleQuantityChange(-1);
              }}
              disabled={quantity <= 1}
              aria-label="Decrease quantity"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
              </svg>
            </button>
            
            <span className="px-3 py-2 text-sm font-medium text-gray-900 min-w-[2rem] text-center">
              {quantity}
            </span>
            
            <button
              type="button"
              className="p-2 text-gray-600 hover:text-gray-800 transition-colors touch-manipulation"
              onClick={(e) => {
                e.stopPropagation();
                handleQuantityChange(1);
              }}
              disabled={quantity >= product.inventory.available_quantity}
              aria-label="Increase quantity"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </button>
          </div>

          {/* Add to Quote Button */}
          <button
            type="button"
            className={`
              px-4 py-2 text-sm font-medium rounded-md transition-all duration-200 touch-manipulation
              ${product.inventory.available_quantity > 0
                ? 'bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
              }
              ${isLoading ? 'opacity-50 cursor-not-allowed' : ''}
              ${isInQuote ? 'bg-green-600 hover:bg-green-700' : ''}
            `}
            onClick={handleAddToQuote}
            disabled={product.inventory.available_quantity === 0 || isLoading}
            aria-label={`Add ${quantity} ${product.name} to quote`}
          >
            {isLoading ? (
              <div className="flex items-center">
                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Adding...
              </div>
            ) : isInQuote ? (
              '+ Add More'
            ) : (
              'Add to Quote'
            )}
          </button>
        </div>

        {/* Stock Warning */}
        {product.inventory.available_quantity < 10 && product.inventory.available_quantity > 0 && (
          <div className="mt-2 text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded">
            Only {product.inventory.available_quantity} left in stock
          </div>
        )}
      </div>
    </div>
  );
};

// CSS classes for line clamping (add to your global CSS)
const styles = `
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
`;

export default ProductCard;
