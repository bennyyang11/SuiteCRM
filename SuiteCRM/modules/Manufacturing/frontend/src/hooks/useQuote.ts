/**
 * Quote Management Hook
 * Manufacturing Product Catalog - Quote State Management
 */

import { useState, useCallback, useEffect } from 'react';
import { Product, QuoteItem, Quote } from '../types/Product';
import { productAPI } from '../services/ProductAPI';

interface QuoteState {
  items: QuoteItem[];
  subtotal: number;
  tax: number;
  total: number;
  isLoading: boolean;
  error: string | null;
}

interface UseQuoteReturn {
  quote: QuoteState;
  addToQuote: (product: Product, quantity: number) => Promise<void>;
  removeFromQuote: (productId: string) => void;
  updateQuantity: (productId: string, quantity: number) => Promise<void>;
  clearQuote: () => void;
  getItemCount: () => number;
  hasProduct: (productId: string) => boolean;
  exportQuote: () => Quote;
  loadQuoteFromStorage: () => void;
}

const STORAGE_KEY = 'manufacturing_quote';
const TAX_RATE = 0.0875; // 8.75% tax rate (configurable)

export const useQuote = (clientId?: string): UseQuoteReturn => {
  const [quote, setQuote] = useState<QuoteState>({
    items: [],
    subtotal: 0,
    tax: 0,
    total: 0,
    isLoading: false,
    error: null,
  });

  /**
   * Calculate totals for the quote
   */
  const calculateTotals = useCallback((items: QuoteItem[]) => {
    const subtotal = items.reduce((sum, item) => sum + item.total_price, 0);
    const tax = subtotal * TAX_RATE;
    const total = subtotal + tax;

    return { subtotal, tax, total };
  }, []);

  /**
   * Update quote state with new items and recalculate totals
   */
  const updateQuoteState = useCallback((items: QuoteItem[]) => {
    const { subtotal, tax, total } = calculateTotals(items);
    
    setQuote(prev => ({
      ...prev,
      items,
      subtotal,
      tax,
      total,
      isLoading: false,
      error: null,
    }));

    // Save to localStorage
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      items,
      subtotal,
      tax,
      total,
      clientId,
      lastUpdated: new Date().toISOString(),
    }));
  }, [calculateTotals, clientId]);

  /**
   * Add product to quote with pricing calculation
   */
  const addToQuote = useCallback(async (product: Product, quantity: number) => {
    setQuote(prev => ({ ...prev, isLoading: true, error: null }));

    try {
      // Get pricing for the product
      const pricingResponse = await productAPI.calculatePricing(
        product.id,
        quantity,
        clientId
      );

      if (pricingResponse.status === 'error') {
        throw new Error(pricingResponse.error?.message || 'Failed to calculate pricing');
      }

      const pricing = pricingResponse.data!;
      
      // Check if product already exists in quote
      const existingItemIndex = quote.items.findIndex(item => item.product.id === product.id);
      
      let updatedItems: QuoteItem[];
      
      if (existingItemIndex >= 0) {
        // Update existing item
        updatedItems = [...quote.items];
        updatedItems[existingItemIndex] = {
          ...updatedItems[existingItemIndex],
          quantity: updatedItems[existingItemIndex].quantity + quantity,
          unit_price: pricing.pricing.unit_price,
          total_price: pricing.pricing.total_price,
        };
      } else {
        // Add new item
        const newItem: QuoteItem = {
          product,
          quantity,
          unit_price: pricing.pricing.unit_price,
          total_price: pricing.pricing.total_price,
        };
        updatedItems = [...quote.items, newItem];
      }

      updateQuoteState(updatedItems);
    } catch (error) {
      console.error('Error adding to quote:', error);
      setQuote(prev => ({
        ...prev,
        isLoading: false,
        error: error instanceof Error ? error.message : 'Failed to add product to quote',
      }));
    }
  }, [quote.items, updateQuoteState, clientId]);

  /**
   * Remove product from quote
   */
  const removeFromQuote = useCallback((productId: string) => {
    const updatedItems = quote.items.filter(item => item.product.id !== productId);
    updateQuoteState(updatedItems);
  }, [quote.items, updateQuoteState]);

  /**
   * Update quantity for a product in the quote
   */
  const updateQuantity = useCallback(async (productId: string, quantity: number) => {
    if (quantity <= 0) {
      removeFromQuote(productId);
      return;
    }

    setQuote(prev => ({ ...prev, isLoading: true, error: null }));

    try {
      const itemIndex = quote.items.findIndex(item => item.product.id === productId);
      if (itemIndex < 0) return;

      const item = quote.items[itemIndex];
      
      // Recalculate pricing for new quantity
      const pricingResponse = await productAPI.calculatePricing(
        productId,
        quantity,
        clientId
      );

      if (pricingResponse.status === 'error') {
        throw new Error(pricingResponse.error?.message || 'Failed to calculate pricing');
      }

      const pricing = pricingResponse.data!;
      
      const updatedItems = [...quote.items];
      updatedItems[itemIndex] = {
        ...item,
        quantity,
        unit_price: pricing.pricing.unit_price,
        total_price: pricing.pricing.total_price,
      };

      updateQuoteState(updatedItems);
    } catch (error) {
      console.error('Error updating quantity:', error);
      setQuote(prev => ({
        ...prev,
        isLoading: false,
        error: error instanceof Error ? error.message : 'Failed to update quantity',
      }));
    }
  }, [quote.items, updateQuoteState, removeFromQuote, clientId]);

  /**
   * Clear all items from quote
   */
  const clearQuote = useCallback(() => {
    updateQuoteState([]);
  }, [updateQuoteState]);

  /**
   * Get total number of items in quote
   */
  const getItemCount = useCallback(() => {
    return quote.items.reduce((count, item) => count + item.quantity, 0);
  }, [quote.items]);

  /**
   * Check if product is already in quote
   */
  const hasProduct = useCallback((productId: string) => {
    return quote.items.some(item => item.product.id === productId);
  }, [quote.items]);

  /**
   * Export quote as a complete Quote object
   */
  const exportQuote = useCallback((): Quote => {
    return {
      id: `quote-${Date.now()}`,
      items: quote.items,
      subtotal: quote.subtotal,
      tax: quote.tax,
      total: quote.total,
      client_id: clientId,
      created_date: new Date().toISOString(),
      status: 'draft',
    };
  }, [quote, clientId]);

  /**
   * Load quote from localStorage
   */
  const loadQuoteFromStorage = useCallback(() => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const parsedQuote = JSON.parse(stored);
        if (parsedQuote.items && Array.isArray(parsedQuote.items)) {
          setQuote({
            items: parsedQuote.items,
            subtotal: parsedQuote.subtotal || 0,
            tax: parsedQuote.tax || 0,
            total: parsedQuote.total || 0,
            isLoading: false,
            error: null,
          });
        }
      }
    } catch (error) {
      console.error('Error loading quote from storage:', error);
      // Clear corrupted data
      localStorage.removeItem(STORAGE_KEY);
    }
  }, []);

  /**
   * Load quote on component mount
   */
  useEffect(() => {
    loadQuoteFromStorage();
  }, [loadQuoteFromStorage]);

  return {
    quote,
    addToQuote,
    removeFromQuote,
    updateQuantity,
    clearQuote,
    getItemCount,
    hasProduct,
    exportQuote,
    loadQuoteFromStorage,
  };
};

export default useQuote;
