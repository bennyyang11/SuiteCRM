import React from 'react'
import { usePerformance } from '@/hooks/usePerformance'
import { performanceMonitor } from '@/utils/performance'

export const PerformanceDashboard: React.FC = () => {
  const { coreWebVitals, apiMetrics, bundleInfo, isLoading } = usePerformance()

  const formatTime = (time?: number) => {
    if (!time) return 'N/A'
    return `${time.toFixed(0)}ms`
  }

  const formatSize = (bytes: number) => {
    if (bytes === 0) return 'N/A'
    const kb = bytes / 1024
    return `${kb.toFixed(1)} KB`
  }

  const getScoreColor = (value: number, thresholds: { good: number; poor: number }) => {
    if (value <= thresholds.good) return 'text-green-600'
    if (value <= thresholds.poor) return 'text-yellow-600'
    return 'text-red-600'
  }

  const exportMetrics = () => {
    const data = performanceMonitor.exportMetrics()
    const blob = new Blob([data], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `performance-metrics-${new Date().toISOString().split('T')[0]}.json`
    a.click()
    URL.revokeObjectURL(url)
  }

  if (isLoading) {
    return (
      <div className="p-6 bg-white rounded-lg shadow">
        <div className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div className="space-y-3">
            <div className="h-3 bg-gray-200 rounded"></div>
            <div className="h-3 bg-gray-200 rounded w-5/6"></div>
            <div className="h-3 bg-gray-200 rounded w-4/6"></div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="p-6 bg-white rounded-lg shadow space-y-6">
      <div className="flex justify-between items-center">
        <h3 className="text-lg font-semibold text-gray-900">Performance Dashboard</h3>
        <button
          onClick={exportMetrics}
          className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors"
        >
          Export Metrics
        </button>
      </div>

      {/* Core Web Vitals */}
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">FCP</div>
          <div className={`text-xl font-semibold ${getScoreColor(coreWebVitals.fcp || 0, { good: 1800, poor: 3000 })}`}>
            {formatTime(coreWebVitals.fcp)}
          </div>
          <div className="text-xs text-gray-400">First Contentful Paint</div>
        </div>

        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">LCP</div>
          <div className={`text-xl font-semibold ${getScoreColor(coreWebVitals.lcp || 0, { good: 2500, poor: 4000 })}`}>
            {formatTime(coreWebVitals.lcp)}
          </div>
          <div className="text-xs text-gray-400">Largest Contentful Paint</div>
        </div>

        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">FID</div>
          <div className={`text-xl font-semibold ${getScoreColor(coreWebVitals.fid || 0, { good: 100, poor: 300 })}`}>
            {formatTime(coreWebVitals.fid)}
          </div>
          <div className="text-xs text-gray-400">First Input Delay</div>
        </div>

        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">CLS</div>
          <div className={`text-xl font-semibold ${getScoreColor(coreWebVitals.cls || 0, { good: 0.1, poor: 0.25 })}`}>
            {coreWebVitals.cls?.toFixed(3) || 'N/A'}
          </div>
          <div className="text-xs text-gray-400">Cumulative Layout Shift</div>
        </div>

        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">TTFB</div>
          <div className={`text-xl font-semibold ${getScoreColor(coreWebVitals.ttfb || 0, { good: 800, poor: 1800 })}`}>
            {formatTime(coreWebVitals.ttfb)}
          </div>
          <div className="text-xs text-gray-400">Time to First Byte</div>
        </div>
      </div>

      {/* Bundle Info */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">Bundle Size</div>
          <div className={`text-xl font-semibold ${bundleInfo.size > 500 * 1024 ? 'text-red-600' : 'text-green-600'}`}>
            {formatSize(bundleInfo.size)}
          </div>
          <div className="text-xs text-gray-400">Total bundle size (target: &lt;500KB)</div>
          <div className="mt-2 w-full bg-gray-200 rounded-full h-2">
            <div
              className={`h-2 rounded-full ${bundleInfo.size > 500 * 1024 ? 'bg-red-500' : 'bg-green-500'}`}
              style={{ width: `${Math.min((bundleInfo.size / (500 * 1024)) * 100, 100)}%` }}
            ></div>
          </div>
        </div>

        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">Gzipped Size</div>
          <div className="text-xl font-semibold text-blue-600">
            {formatSize(bundleInfo.gzipSize)}
          </div>
          <div className="text-xs text-gray-400">After gzip compression</div>
          <div className="text-xs text-green-600 mt-1">
            {bundleInfo.size > 0 ? `${((bundleInfo.gzipSize / bundleInfo.size) * 100).toFixed(1)}% of original` : ''}
          </div>
        </div>
      </div>

      {/* API Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">API Calls</div>
          <div className="text-xl font-semibold text-blue-600">
            {apiMetrics.totalCalls}
          </div>
          <div className="text-xs text-gray-400">Total API requests</div>
        </div>

        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">Avg Response Time</div>
          <div className={`text-xl font-semibold ${apiMetrics.averageResponseTime > 1000 ? 'text-red-600' : 'text-green-600'}`}>
            {formatTime(apiMetrics.averageResponseTime)}
          </div>
          <div className="text-xs text-gray-400">Average API response time</div>
        </div>

        <div className="p-4 border rounded-lg">
          <div className="text-sm text-gray-500">Slow Calls</div>
          <div className={`text-xl font-semibold ${apiMetrics.slowCalls > 0 ? 'text-yellow-600' : 'text-green-600'}`}>
            {apiMetrics.slowCalls}
          </div>
          <div className="text-xs text-gray-400">Responses &gt;2s</div>
        </div>
      </div>

      {/* Performance Tips */}
      <div className="p-4 bg-gray-50 rounded-lg">
        <h4 className="font-medium text-gray-900 mb-2">Performance Recommendations</h4>
        <ul className="text-sm text-gray-600 space-y-1">
          {coreWebVitals.lcp && coreWebVitals.lcp > 2500 && (
            <li>• Consider optimizing images and reducing bundle size to improve LCP</li>
          )}
          {coreWebVitals.fid && coreWebVitals.fid > 100 && (
            <li>• Reduce JavaScript execution time to improve FID</li>
          )}
          {coreWebVitals.cls && coreWebVitals.cls > 0.1 && (
            <li>• Fix layout shifts by reserving space for dynamic content</li>
          )}
          {bundleInfo.size > 500 * 1024 && (
            <li>• Bundle size exceeds target - consider code splitting and tree shaking</li>
          )}
          {apiMetrics.averageResponseTime > 1000 && (
            <li>• API response times are slow - consider caching or optimization</li>
          )}
          {apiMetrics.slowCalls === 0 && bundleInfo.size <= 500 * 1024 && (
            <li className="text-green-600">• All performance targets are being met! 🎉</li>
          )}
        </ul>
      </div>
    </div>
  )
}
