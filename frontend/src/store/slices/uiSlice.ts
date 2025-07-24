import { createSlice, PayloadAction } from '@reduxjs/toolkit'
import type { RootState } from '../index'

interface Toast {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number
}

interface Modal {
  id: string
  type: string
  props?: Record<string, any>
  onClose?: () => void
}

interface UIState {
  // Theme and appearance
  theme: 'light' | 'dark' | 'auto'
  sidebarCollapsed: boolean
  mobileMenuOpen: boolean
  
  // Loading states
  globalLoading: boolean
  pageLoading: boolean
  
  // Notifications
  toasts: Toast[]
  
  // Modals and dialogs
  modals: Modal[]
  
  // Search and filters
  searchQuery: string
  activeFilters: Record<string, any>
  
  // Offline status
  isOnline: boolean
  
  // Performance monitoring
  performanceMetrics: {
    bundleSize: number
    loadTime: number
    apiResponseTimes: Record<string, number>
  }
}

const initialState: UIState = {
  theme: 'light',
  sidebarCollapsed: false,
  mobileMenuOpen: false,
  globalLoading: false,
  pageLoading: false,
  toasts: [],
  modals: [],
  searchQuery: '',
  activeFilters: {},
  isOnline: navigator.onLine,
  performanceMetrics: {
    bundleSize: 0,
    loadTime: 0,
    apiResponseTimes: {},
  },
}

export const uiSlice = createSlice({
  name: 'ui',
  initialState,
  reducers: {
    // Theme management
    setTheme: (state, action: PayloadAction<'light' | 'dark' | 'auto'>) => {
      state.theme = action.payload
    },
    toggleSidebar: (state) => {
      state.sidebarCollapsed = !state.sidebarCollapsed
    },
    setSidebarCollapsed: (state, action: PayloadAction<boolean>) => {
      state.sidebarCollapsed = action.payload
    },
    toggleMobileMenu: (state) => {
      state.mobileMenuOpen = !state.mobileMenuOpen
    },
    setMobileMenuOpen: (state, action: PayloadAction<boolean>) => {
      state.mobileMenuOpen = action.payload
    },
    
    // Loading states
    setGlobalLoading: (state, action: PayloadAction<boolean>) => {
      state.globalLoading = action.payload
    },
    setPageLoading: (state, action: PayloadAction<boolean>) => {
      state.pageLoading = action.payload
    },
    
    // Toast notifications
    addToast: (state, action: PayloadAction<Omit<Toast, 'id'>>) => {
      const toast: Toast = {
        id: `toast-${Date.now()}-${Math.random()}`,
        duration: 5000,
        ...action.payload,
      }
      state.toasts.push(toast)
    },
    removeToast: (state, action: PayloadAction<string>) => {
      state.toasts = state.toasts.filter(toast => toast.id !== action.payload)
    },
    clearToasts: (state) => {
      state.toasts = []
    },
    
    # Modal management
    openModal: (state, action: PayloadAction<Omit<Modal, 'id'>>) => {
      const modal: Modal = {
        id: `modal-${Date.now()}-${Math.random()}`,
        ...action.payload,
      }
      state.modals.push(modal)
    },
    closeModal: (state, action: PayloadAction<string>) => {
      const modal = state.modals.find(m => m.id === action.payload)
      if (modal?.onClose) {
        modal.onClose()
      }
      state.modals = state.modals.filter(m => m.id !== action.payload)
    },
    closeAllModals: (state) => {
      state.modals.forEach(modal => {
        if (modal.onClose) {
          modal.onClose()
        }
      })
      state.modals = []
    },
    
    // Search and filters
    setSearchQuery: (state, action: PayloadAction<string>) => {
      state.searchQuery = action.payload
    },
    setFilter: (state, action: PayloadAction<{ key: string; value: any }>) => {
      state.activeFilters[action.payload.key] = action.payload.value
    },
    removeFilter: (state, action: PayloadAction<string>) => {
      delete state.activeFilters[action.payload]
    },
    clearFilters: (state) => {
      state.activeFilters = {}
    },
    
    // Network status
    setOnlineStatus: (state, action: PayloadAction<boolean>) => {
      state.isOnline = action.payload
    },
    
    // Performance monitoring
    updatePerformanceMetrics: (state, action: PayloadAction<Partial<UIState['performanceMetrics']>>) => {
      state.performanceMetrics = { ...state.performanceMetrics, ...action.payload }
    },
    addApiResponseTime: (state, action: PayloadAction<{ endpoint: string; time: number }>) => {
      state.performanceMetrics.apiResponseTimes[action.payload.endpoint] = action.payload.time
    },
  },
})

// Action creators
export const {
  setTheme,
  toggleSidebar,
  setSidebarCollapsed,
  toggleMobileMenu,
  setMobileMenuOpen,
  setGlobalLoading,
  setPageLoading,
  addToast,
  removeToast,
  clearToasts,
  openModal,
  closeModal,
  closeAllModals,
  setSearchQuery,
  setFilter,
  removeFilter,
  clearFilters,
  setOnlineStatus,
  updatePerformanceMetrics,
  addApiResponseTime,
} = uiSlice.actions

// Selectors
export const selectUI = (state: RootState) => state.ui
export const selectTheme = (state: RootState) => state.ui.theme
export const selectSidebarCollapsed = (state: RootState) => state.ui.sidebarCollapsed
export const selectMobileMenuOpen = (state: RootState) => state.ui.mobileMenuOpen
export const selectGlobalLoading = (state: RootState) => state.ui.globalLoading
export const selectPageLoading = (state: RootState) => state.ui.pageLoading
export const selectToasts = (state: RootState) => state.ui.toasts
export const selectModals = (state: RootState) => state.ui.modals
export const selectSearchQuery = (state: RootState) => state.ui.searchQuery
export const selectActiveFilters = (state: RootState) => state.ui.activeFilters
export const selectIsOnline = (state: RootState) => state.ui.isOnline
export const selectPerformanceMetrics = (state: RootState) => state.ui.performanceMetrics

export default uiSlice.reducer
