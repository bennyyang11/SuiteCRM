/**
 * Product Types and Interfaces
 * Manufacturing Product Catalog - TypeScript Definitions
 */

export interface Product {
  id: string;
  name: string;
  sku: string;
  description: string;
  category: string;
  subcategory: string;
  material: string;
  grade: string;
  finish: string;
  pricing: {
    base_price: number;
    list_price: number;
    currency: string;
  };
  specifications: {
    weight: number;
    weight_unit: string;
    dimensions: {
      length: number;
      width: number;
      height: number;
      unit: string;
    };
  };
  inventory: {
    stock_quantity: number;
    available_quantity: number;
    stock_status: 'In Stock' | 'Low Stock' | 'Out of Stock' | 'Limited Stock';
    reorder_point: number;
  };
  media: {
    primary_image: string | null;
    datasheet: string | null;
    cad_file: string | null;
  };
  compliance: {
    certifications: string;
    compliance_notes: string;
  };
  status: string;
  last_updated: string;
}

export interface ProductSearchParams {
  q?: string;
  category?: string;
  material?: string;
  price_min?: number;
  price_max?: number;
  sku?: string;
  in_stock?: boolean;
  page?: number;
  limit?: number;
  sort_by?: 'name' | 'sku' | 'category' | 'base_price' | 'stock_quantity' | 'date_modified';
  sort_order?: 'ASC' | 'DESC';
}

export interface ProductSearchResponse {
  status: 'success' | 'error';
  data: {
    products: Product[];
    pagination: {
      page: number;
      limit: number;
      total_count: number;
      total_pages: number;
      has_next: boolean;
      has_prev: boolean;
    };
  };
  meta: {
    response_time: string;
    cache_hit: boolean;
  };
}

export interface ProductCategory {
  name: string;
  product_count: number;
  price_range: {
    min: number;
    max: number;
    avg: number;
  };
}

export interface FilterState {
  searchQuery: string;
  selectedCategories: string[];
  selectedMaterials: string[];
  priceRange: {
    min: number;
    max: number;
  };
  stockFilter: 'all' | 'in_stock' | 'low_stock' | 'out_of_stock';
  sortBy: string;
  sortOrder: 'ASC' | 'DESC';
}

export interface QuoteItem {
  product: Product;
  quantity: number;
  unit_price: number;
  total_price: number;
  notes?: string;
}

export interface Quote {
  id: string;
  items: QuoteItem[];
  subtotal: number;
  tax: number;
  total: number;
  client_id?: string;
  created_date: string;
  status: 'draft' | 'sent' | 'accepted' | 'rejected';
}

export interface PricingCalculation {
  product_id: string;
  client_id?: string;
  quantity: number;
  pricing: {
    base_price: number;
    unit_price: number;
    total_price: number;
    total_savings: number;
    savings_percentage: number;
  };
  discounts_applied: Array<{
    type: string;
    description: string;
    percentage?: number;
    amount: number;
    notes?: string;
  }>;
  currency: string;
}

export interface InventoryCheck {
  product_id: string;
  sku: string;
  name: string;
  stock_status: 'In Stock' | 'Low Stock' | 'Out of Stock' | 'Limited Stock';
  quantities: {
    in_stock: number;
    reserved: number;
    available: number;
    reorder_point: number;
  };
  availability: {
    is_available: boolean;
    max_quantity: number;
    estimated_restock_date: string | null;
    lead_time_days: number;
  };
}

export interface ApiError {
  code: number;
  message: string;
  details?: string;
}

export interface ApiResponse<T> {
  status: 'success' | 'error';
  data?: T;
  error?: ApiError;
  meta?: {
    timestamp: string;
    response_time?: string;
    cache_hit?: boolean;
  };
}
