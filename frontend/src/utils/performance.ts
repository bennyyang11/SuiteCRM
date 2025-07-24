// Performance monitoring utilities
import { store } from '@/store'
import { addApiResponseTime, updatePerformanceMetrics } from '@/store/slices/uiSlice'

interface PerformanceMetrics {
  bundleSize: number
  loadTime: number
  firstContentfulPaint: number
  largestContentfulPaint: number
  cumulativeLayoutShift: number
  firstInputDelay: number
}

class PerformanceMonitor {
  private metrics: Partial<PerformanceMetrics> = {}
  private observer: PerformanceObserver | null = null

  constructor() {
    this.initializeMonitoring()
  }

  private initializeMonitoring() {
    // Monitor Core Web Vitals
    if ('PerformanceObserver' in window) {
      this.observer = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          this.handlePerformanceEntry(entry)
        }
      })

      // Observe different performance entry types
      try {
        this.observer.observe({ entryTypes: ['navigation', 'paint', 'largest-contentful-paint', 'first-input', 'layout-shift'] })
      } catch (error) {
        console.warn('Performance observer not fully supported:', error)
      }
    }

    // Monitor bundle size
    this.measureBundleSize()

    // Monitor page load time
    window.addEventListener('load', () => {
      this.measureLoadTime()
    })
  }

  private handlePerformanceEntry(entry: PerformanceEntry) {
    switch (entry.entryType) {
      case 'paint':
        if (entry.name === 'first-contentful-paint') {
          this.metrics.firstContentfulPaint = entry.startTime
        }
        break

      case 'largest-contentful-paint':
        this.metrics.largestContentfulPaint = entry.startTime
        break

      case 'first-input':
        this.metrics.firstInputDelay = (entry as any).processingStart - entry.startTime
        break

      case 'layout-shift':
        if (!(entry as any).hadRecentInput) {
          this.metrics.cumulativeLayoutShift = (this.metrics.cumulativeLayoutShift || 0) + (entry as any).value
        }
        break
    }

    this.updateStore()
  }

  private measureBundleSize() {
    // Estimate bundle size from network entries
    if ('performance' in window && 'getEntriesByType' in performance) {
      const resourceEntries = performance.getEntriesByType('resource') as PerformanceResourceTiming[]
      const jsEntries = resourceEntries.filter(entry => 
        entry.name.includes('.js') && 
        (entry.name.includes('assets') || entry.name.includes('static'))
      )

      const totalSize = jsEntries.reduce((total, entry) => {
        return total + (entry.transferSize || 0)
      }, 0)

      this.metrics.bundleSize = Math.round(totalSize / 1024) // Convert to KB
    }
  }

  private measureLoadTime() {
    if ('performance' in window && 'timing' in performance) {
      const timing = performance.timing
      this.metrics.loadTime = timing.loadEventEnd - timing.navigationStart
    }
  }

  private updateStore() {
    store.dispatch(updatePerformanceMetrics(this.metrics))
  }

  // Public method to track API response times
  trackApiCall<T>(apiCall: Promise<T>, endpoint: string): Promise<T> {
    const startTime = performance.now()
    
    return apiCall
      .then((result) => {
        const endTime = performance.now()
        const duration = Math.round(endTime - startTime)
        
        store.dispatch(addApiResponseTime({ endpoint, time: duration }))
        return result
      })
      .catch((error) => {
        const endTime = performance.now()
        const duration = Math.round(endTime - startTime)
        
        store.dispatch(addApiResponseTime({ endpoint: `${endpoint}_error`, time: duration }))
        throw error
      })
  }

  // Get current metrics
  getMetrics(): Partial<PerformanceMetrics> {
    return { ...this.metrics }
  }

  // Check if performance targets are met
  checkTargets(): { bundleSize: boolean; loadTime: boolean; lcp: boolean; fid: boolean; cls: boolean } {
    return {
      bundleSize: (this.metrics.bundleSize || 0) < 500, // Target: <500KB
      loadTime: (this.metrics.loadTime || 0) < 3000, // Target: <3s
      lcp: (this.metrics.largestContentfulPaint || 0) < 2500, // Target: <2.5s
      fid: (this.metrics.firstInputDelay || 0) < 100, // Target: <100ms
      cls: (this.metrics.cumulativeLayoutShift || 0) < 0.1, // Target: <0.1
    }
  }

  // Disconnect observer when component unmounts
  disconnect() {
    if (this.observer) {
      this.observer.disconnect()
    }
  }
}

// Singleton instance
export const performanceMonitor = new PerformanceMonitor()

// Hook for React components
export function usePerformanceMonitor() {
  return {
    trackApiCall: performanceMonitor.trackApiCall.bind(performanceMonitor),
    getMetrics: performanceMonitor.getMetrics.bind(performanceMonitor),
    checkTargets: performanceMonitor.checkTargets.bind(performanceMonitor),
  }
}

// Bundle analyzer utility
export function analyzeBundleSize() {
  if (process.env.NODE_ENV === 'development') {
    // Dynamic import for bundle analyzer to avoid including in production
    import('webpack-bundle-analyzer').then(({ BundleAnalyzerPlugin }) => {
      console.log('Bundle analysis available in development mode')
    }).catch(() => {
      console.log('Bundle analyzer not available')
    })
  }

  // Report current bundle size
  const metrics = performanceMonitor.getMetrics()
  console.log('Current bundle size:', metrics.bundleSize, 'KB')
  
  return metrics.bundleSize
}

// Image optimization utility
export function optimizeImage(src: string, options: { width?: number; height?: number; quality?: number } = {}) {
  const { width, height, quality = 80 } = options
  
  // For production, this would integrate with a CDN or image optimization service
  if (process.env.NODE_ENV === 'production') {
    const params = new URLSearchParams()
    if (width) params.set('w', width.toString())
    if (height) params.set('h', height.toString())
    params.set('q', quality.toString())
    
    return `${src}?${params.toString()}`
  }
  
  return src
}

// Lazy loading utility for images
export function createLazyImageObserver() {
  if ('IntersectionObserver' in window) {
    return new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target as HTMLImageElement
          if (img.dataset.src) {
            img.src = img.dataset.src
            img.classList.remove('lazy')
            img.classList.add('loaded')
          }
        }
      })
    }, {
      rootMargin: '50px 0px',
      threshold: 0.01
    })
  }
  
  return null
}
