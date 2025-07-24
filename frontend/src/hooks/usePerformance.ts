import { useEffect, useState } from 'react'
import { performanceMonitor } from '@/utils/performance'

interface UsePerformanceResult {
  coreWebVitals: {
    fcp?: number
    lcp?: number
    fid?: number
    cls?: number
    ttfb?: number
  }
  apiMetrics: {
    averageResponseTime: number
    totalCalls: number
    slowCalls: number
  }
  bundleInfo: {
    size: number
    gzipSize: number
  }
  isLoading: boolean
}

export const usePerformance = (): UsePerformanceResult => {
  const [data, setData] = useState<UsePerformanceResult>({
    coreWebVitals: {},
    apiMetrics: {
      averageResponseTime: 0,
      totalCalls: 0,
      slowCalls: 0
    },
    bundleInfo: {
      size: 0,
      gzipSize: 0
    },
    isLoading: true
  })

  useEffect(() => {
    const updateMetrics = async () => {
      const coreWebVitals = performanceMonitor.getCoreWebVitals()
      const apiMetrics = performanceMonitor.getAPIMetrics()
      const bundleInfo = await performanceMonitor.getBundleInfo()

      const slowCalls = apiMetrics.filter(metric => metric.responseTime > 2000).length

      setData({
        coreWebVitals,
        apiMetrics: {
          averageResponseTime: performanceMonitor.getAverageAPIResponseTime(),
          totalCalls: apiMetrics.length,
          slowCalls
        },
        bundleInfo,
        isLoading: false
      })
    }

    updateMetrics()

    // Update metrics every 5 seconds
    const interval = setInterval(updateMetrics, 5000)

    return () => clearInterval(interval)
  }, [])

  return data
}

export const useAPIPerformance = (endpoint?: string) => {
  const [metrics, setMetrics] = useState({
    calls: 0,
    averageTime: 0,
    lastCall: null as Date | null,
    errors: 0
  })

  useEffect(() => {
    const updateMetrics = () => {
      const apiMetrics = performanceMonitor.getAPIMetrics()
      const filteredMetrics = endpoint 
        ? apiMetrics.filter(m => m.endpoint.includes(endpoint))
        : apiMetrics

      if (filteredMetrics.length > 0) {
        const totalTime = filteredMetrics.reduce((sum, m) => sum + m.responseTime, 0)
        const errors = filteredMetrics.filter(m => m.status >= 400).length
        const lastCall = new Date(Math.max(...filteredMetrics.map(m => m.timestamp)))

        setMetrics({
          calls: filteredMetrics.length,
          averageTime: totalTime / filteredMetrics.length,
          lastCall,
          errors
        })
      }
    }

    updateMetrics()
    const interval = setInterval(updateMetrics, 2000)

    return () => clearInterval(interval)
  }, [endpoint])

  return metrics
}

export const useBundleSize = () => {
  const [bundleSize, setBundleSize] = useState({
    size: 0,
    gzipSize: 0,
    isUnderLimit: true,
    limit: 500 * 1024 // 500KB
  })

  useEffect(() => {
    const checkBundleSize = async () => {
      const info = await performanceMonitor.getBundleInfo()
      setBundleSize({
        ...info,
        isUnderLimit: info.size < 500 * 1024,
        limit: 500 * 1024
      })
    }

    checkBundleSize()
  }, [])

  return bundleSize
}
