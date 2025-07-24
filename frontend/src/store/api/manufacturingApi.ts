import { createApi, fetchBaseQuery } from '@reduxjs/toolkit/query/react'
import type { RootState } from '../index'

// Base query with authentication
const baseQuery = fetchBaseQuery({
  baseUrl: '/api/v8/manufacturing/',
  prepareHeaders: (headers, { getState }) => {
    const token = (getState() as RootState).auth.token
    if (token) {
      headers.set('authorization', `Bearer ${token}`)
    }
    headers.set('Content-Type', 'application/json')
    return headers
  },
})

// Enhanced base query with error handling
const baseQueryWithReauth = async (args: any, api: any, extraOptions: any) => {
  const result = await baseQuery(args, api, extraOptions)
  
  if (result.error && result.error.status === 401) {
    // Auto logout on 401
    api.dispatch({ type: 'auth/logout' })
  }
  
  return result
}

export interface Product {
  id: string
  name: string
  sku: string
  category: string
  description: string
  basePrice: number
  clientPrice?: number
  stockLevel: number
  stockStatus: 'in-stock' | 'low-stock' | 'out-of-stock'
  image?: string
  specifications: Record<string, any>
  alternatives: string[]
  warehouseLocation?: string
  updatedAt: string
}

export interface Order {
  id: string
  clientId: string
  clientName: string
  status: 'quote' | 'pending' | 'confirmed' | 'production' | 'shipped' | 'delivered' | 'cancelled'
  items: OrderItem[]
  totalAmount: number
  currency: string
  createdAt: string
  updatedAt: string
  estimatedDelivery?: string
  notes?: string
}

export interface OrderItem {
  productId: string
  productName: string
  quantity: number
  unitPrice: number
  totalPrice: number
}

export interface Quote {
  id: string
  clientId: string
  clientName: string
  status: 'draft' | 'sent' | 'accepted' | 'rejected' | 'expired'
  items: OrderItem[]
  totalAmount: number
  validUntil: string
  createdAt: string
  notes?: string
  pdfUrl?: string
}

export interface SearchFilters {
  category?: string
  minPrice?: number
  maxPrice?: number
  stockStatus?: string
  specifications?: Record<string, any>
  query?: string
}

// Manufacturing API endpoints
export const manufacturingApi = createApi({
  reducerPath: 'manufacturingApi',
  baseQuery: baseQueryWithReauth,
  tagTypes: ['Product', 'Order', 'Quote', 'Inventory', 'Client'],
  endpoints: (builder) => ({
    // Product endpoints
    getProducts: builder.query<Product[], SearchFilters>({
      query: (filters) => ({
        url: 'products',
        params: filters,
      }),
      providesTags: ['Product'],
      // Cache for 5 minutes
      keepUnusedDataFor: 300,
    }),
    
    getProduct: builder.query<Product, string>({
      query: (id) => `products/${id}`,
      providesTags: (result, error, id) => [{ type: 'Product', id }],
    }),
    
    searchProducts: builder.query<Product[], { query: string; filters?: SearchFilters }>({
      query: ({ query, filters }) => ({
        url: 'products/search',
        params: { q: query, ...filters },
      }),
      providesTags: ['Product'],
    }),
    
    getProductSuggestions: builder.query<Product[], string>({
      query: (productId) => `products/${productId}/suggestions`,
      providesTags: ['Product'],
    }),
    
    // Order endpoints
    getOrders: builder.query<Order[], { status?: string; clientId?: string }>({
      query: (filters) => ({
        url: 'orders',
        params: filters,
      }),
      providesTags: ['Order'],
    }),
    
    getOrder: builder.query<Order, string>({
      query: (id) => `orders/${id}`,
      providesTags: (result, error, id) => [{ type: 'Order', id }],
    }),
    
    createOrder: builder.mutation<Order, Omit<Order, 'id' | 'createdAt' | 'updatedAt'>>({
      query: (order) => ({
        url: 'orders',
        method: 'POST',
        body: order,
      }),
      invalidatesTags: ['Order'],
    }),
    
    updateOrderStatus: builder.mutation<Order, { id: string; status: Order['status'] }>({
      query: ({ id, status }) => ({
        url: `orders/${id}/status`,
        method: 'PATCH',
        body: { status },
      }),
      invalidatesTags: (result, error, { id }) => [{ type: 'Order', id }],
    }),
    
    // Quote endpoints
    getQuotes: builder.query<Quote[], { clientId?: string; status?: string }>({
      query: (filters) => ({
        url: 'quotes',
        params: filters,
      }),
      providesTags: ['Quote'],
    }),
    
    createQuote: builder.mutation<Quote, Omit<Quote, 'id' | 'createdAt'>>({
      query: (quote) => ({
        url: 'quotes',
        method: 'POST',
        body: quote,
      }),
      invalidatesTags: ['Quote'],
    }),
    
    generateQuotePDF: builder.mutation<{ pdfUrl: string }, string>({
      query: (quoteId) => ({
        url: `quotes/${quoteId}/pdf`,
        method: 'POST',
      }),
    }),
    
    acceptQuote: builder.mutation<Order, string>({
      query: (quoteId) => ({
        url: `quotes/${quoteId}/accept`,
        method: 'POST',
      }),
      invalidatesTags: ['Quote', 'Order'],
    }),
    
    // Inventory endpoints
    getInventoryStatus: builder.query<Record<string, number>, void>({
      query: () => 'inventory/status',
      providesTags: ['Inventory'],
    }),
    
    updateInventory: builder.mutation<void, { productId: string; quantity: number }>({
      query: ({ productId, quantity }) => ({
        url: `inventory/${productId}`,
        method: 'PATCH',
        body: { quantity },
      }),
      invalidatesTags: ['Inventory', 'Product'],
    }),
    
    // Client pricing endpoints
    getClientPricing: builder.query<Record<string, number>, string>({
      query: (clientId) => `pricing/client/${clientId}`,
      providesTags: ['Client'],
    }),
    
    // Analytics endpoints
    getSalesMetrics: builder.query<any, { period: string }>({
      query: ({ period }) => ({
        url: 'analytics/sales',
        params: { period },
      }),
    }),
    
    getPerformanceMetrics: builder.query<any, void>({
      query: () => 'analytics/performance',
    }),
  }),
})

// Export hooks for use in components
export const {
  useGetProductsQuery,
  useGetProductQuery,
  useSearchProductsQuery,
  useGetProductSuggestionsQuery,
  useGetOrdersQuery,
  useGetOrderQuery,
  useCreateOrderMutation,
  useUpdateOrderStatusMutation,
  useGetQuotesQuery,
  useCreateQuoteMutation,
  useGenerateQuotePDFMutation,
  useAcceptQuoteMutation,
  useGetInventoryStatusQuery,
  useUpdateInventoryMutation,
  useGetClientPricingQuery,
  useGetSalesMetricsQuery,
  useGetPerformanceMetricsQuery,
} = manufacturingApi
