import React from 'react'
import ReactDOM from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import { Provider } from 'react-redux'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'react-hot-toast'
import { store } from '@/store'
import { performanceMonitor } from '@/utils/performance'
import App from './App'
import './styles/index.css'

// Initialize performance monitoring
performanceMonitor.recordBundleSize(performance.getEntriesByType('navigation')[0]?.transferSize || 0)

// React Query client with optimized defaults
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      cacheTime: 1000 * 60 * 30, // 30 minutes
      retry: (failureCount, error: any) => {
        // Don't retry on 4xx errors
        if (error?.response?.status >= 400 && error?.response?.status < 500) {
          return false
        }
        return failureCount < 3
      },
    },
  },
})

// Service Worker registration with performance tracking
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    const swRegistrationStart = performance.now()
    
    navigator.serviceWorker.register('/sw.js')
      .then((registration) => {
        const swRegistrationEnd = performance.now()
        console.log(`SW registered in ${(swRegistrationEnd - swRegistrationStart).toFixed(2)}ms`)
        
        // Track when SW updates
        registration.addEventListener('updatefound', () => {
          console.log('Service worker update found')
        })
      })
      .catch((registrationError) => {
        console.log('SW registration failed: ', registrationError)
      })
  })
}

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <Provider store={store}>
        <BrowserRouter>
          <App />
          <Toaster 
            position="top-right"
            toastOptions={{
              duration: 4000,
              style: {
                background: '#1f2937',
                color: '#f9fafb',
              },
            }}
          />
        </BrowserRouter>
      </Provider>
    </QueryClientProvider>
  </React.StrictMode>,
)
