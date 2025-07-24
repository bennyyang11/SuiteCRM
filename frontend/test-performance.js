#!/usr/bin/env node

/**
 * Performance Test Script for SuiteCRM Frontend
 * Tests bundle size, lazy loading, caching strategies, and Core Web Vitals
 */

const fs = require('fs')
const path = require('path')
const { execSync } = require('child_process')

class PerformanceTester {
  constructor() {
    this.results = {
      bundleSize: {},
      lazyLoading: {},
      caching: {},
      coreWebVitals: {},
      timestamp: new Date().toISOString()
    }
    this.targetBundleSize = 500 * 1024 // 500KB
  }

  log(message, type = 'info') {
    const colors = {
      info: '\x1b[36m',
      success: '\x1b[32m',
      warning: '\x1b[33m',
      error: '\x1b[31m',
      reset: '\x1b[0m'
    }
    console.log(`${colors[type]}${message}${colors.reset}`)
  }

  async testBundleSize() {
    this.log('🔍 Testing Bundle Size...', 'info')
    
    try {
      // Build the project
      this.log('Building project...', 'info')
      execSync('npm run build', { 
        cwd: path.resolve(__dirname),
        stdio: 'pipe'
      })

      // Analyze bundle sizes
      const distPath = path.join(__dirname, 'dist')
      if (!fs.existsSync(distPath)) {
        throw new Error('Build directory not found')
      }

      const getFileSize = (filePath) => {
        try {
          return fs.statSync(filePath).size
        } catch (e) {
          return 0
        }
      }

      const getBundleSizes = (dir) => {
        const files = fs.readdirSync(dir, { withFileTypes: true })
        let sizes = {}
        
        for (const file of files) {
          if (file.isDirectory()) {
            const subSizes = getBundleSizes(path.join(dir, file.name))
            sizes = { ...sizes, ...subSizes }
          } else if (file.name.endsWith('.js') || file.name.endsWith('.css')) {
            const filePath = path.join(dir, file.name)
            const size = getFileSize(filePath)
            sizes[file.name] = {
              size,
              sizeKB: Math.round(size / 1024 * 100) / 100,
              type: file.name.endsWith('.js') ? 'javascript' : 'css'
            }
          }
        }
        
        return sizes
      }

      const bundleSizes = getBundleSizes(distPath)
      const totalSize = Object.values(bundleSizes).reduce((sum, file) => sum + file.size, 0)
      const jsSize = Object.values(bundleSizes)
        .filter(file => file.type === 'javascript')
        .reduce((sum, file) => sum + file.size, 0)
      const cssSize = Object.values(bundleSizes)
        .filter(file => file.type === 'css')
        .reduce((sum, file) => sum + file.size, 0)

      this.results.bundleSize = {
        total: {
          size: totalSize,
          sizeKB: Math.round(totalSize / 1024 * 100) / 100,
          underTarget: totalSize < this.targetBundleSize
        },
        javascript: {
          size: jsSize,
          sizeKB: Math.round(jsSize / 1024 * 100) / 100
        },
        css: {
          size: cssSize,
          sizeKB: Math.round(cssSize / 1024 * 100) / 100
        },
        files: bundleSizes,
        targetKB: this.targetBundleSize / 1024
      }

      if (totalSize < this.targetBundleSize) {
        this.log(`✅ Bundle size: ${this.results.bundleSize.total.sizeKB}KB (under ${this.targetBundleSize/1024}KB target)`, 'success')
      } else {
        this.log(`❌ Bundle size: ${this.results.bundleSize.total.sizeKB}KB (exceeds ${this.targetBundleSize/1024}KB target)`, 'error')
      }

      // Check for proper code splitting
      const hasVendorChunk = Object.keys(bundleSizes).some(file => file.includes('vendor'))
      const hasRouteChunks = Object.keys(bundleSizes).some(file => file.includes('pages') || file.includes('chunks'))
      
      this.results.bundleSize.codeSplitting = {
        hasVendorChunk,
        hasRouteChunks,
        chunkCount: Object.keys(bundleSizes).length
      }

      if (hasVendorChunk && hasRouteChunks) {
        this.log('✅ Code splitting is properly configured', 'success')
      } else {
        this.log('⚠️  Code splitting could be improved', 'warning')
      }

    } catch (error) {
      this.log(`❌ Bundle size test failed: ${error.message}`, 'error')
      this.results.bundleSize.error = error.message
    }
  }

  async testLazyLoading() {
    this.log('🔄 Testing Lazy Loading...', 'info')
    
    try {
      // Check if components are using React.lazy
      const appFile = fs.readFileSync(path.join(__dirname, 'src/App.tsx'), 'utf8')
      const hasLazyComponents = appFile.includes('React.lazy')
      const hasSuspense = appFile.includes('Suspense')
      
      this.results.lazyLoading = {
        hasLazyComponents,
        hasSuspense,
        implemented: hasLazyComponents && hasSuspense
      }

      if (hasLazyComponents && hasSuspense) {
        this.log('✅ Lazy loading is properly implemented', 'success')
      } else {
        this.log('❌ Lazy loading is not properly configured', 'error')
      }

      // Check for lazy image loading
      const lazyImageExists = fs.existsSync(path.join(__dirname, 'src/components/ui/LazyImage.tsx'))
      this.results.lazyLoading.hasLazyImages = lazyImageExists

      if (lazyImageExists) {
        this.log('✅ Lazy image loading component exists', 'success')
      } else {
        this.log('⚠️  Lazy image loading component not found', 'warning')
      }

    } catch (error) {
      this.log(`❌ Lazy loading test failed: ${error.message}`, 'error')
      this.results.lazyLoading.error = error.message
    }
  }

  async testCaching() {
    this.log('🗄️  Testing Caching Strategies...', 'info')
    
    try {
      // Check service worker configuration
      const viteConfig = fs.readFileSync(path.join(__dirname, 'vite.config.ts'), 'utf8')
      
      const hasServiceWorker = viteConfig.includes('VitePWA')
      const hasRuntimeCaching = viteConfig.includes('runtimeCaching')
      const hasApiCaching = viteConfig.includes('api-cache')
      const hasImageCaching = viteConfig.includes('images-cache')
      
      this.results.caching = {
        serviceWorker: hasServiceWorker,
        runtimeCaching: hasRuntimeCaching,
        apiCaching: hasApiCaching,
        imageCaching: hasImageCaching,
        properlyConfigured: hasServiceWorker && hasRuntimeCaching && hasApiCaching
      }

      if (this.results.caching.properlyConfigured) {
        this.log('✅ Caching strategies are properly configured', 'success')
      } else {
        this.log('❌ Caching strategies need improvement', 'error')
      }

      // Check React Query caching
      const mainFile = fs.readFileSync(path.join(__dirname, 'src/main.tsx'), 'utf8')
      const hasQueryCaching = mainFile.includes('staleTime') && mainFile.includes('cacheTime')
      
      this.results.caching.reactQueryCaching = hasQueryCaching

      if (hasQueryCaching) {
        this.log('✅ React Query caching is configured', 'success')
      } else {
        this.log('⚠️  React Query caching could be improved', 'warning')
      }

    } catch (error) {
      this.log(`❌ Caching test failed: ${error.message}`, 'error')
      this.results.caching.error = error.message
    }
  }

  async testPerformanceMonitoring() {
    this.log('📊 Testing Performance Monitoring...', 'info')
    
    try {
      // Check if performance monitoring utilities exist
      const performanceUtilExists = fs.existsSync(path.join(__dirname, 'src/utils/performance.ts'))
      const performanceHookExists = fs.existsSync(path.join(__dirname, 'src/hooks/usePerformance.ts'))
      const performanceDashboardExists = fs.existsSync(path.join(__dirname, 'src/components/PerformanceDashboard.tsx'))
      
      this.results.coreWebVitals = {
        monitoringImplemented: performanceUtilExists,
        hooksAvailable: performanceHookExists,
        dashboardAvailable: performanceDashboardExists,
        fullyImplemented: performanceUtilExists && performanceHookExists && performanceDashboardExists
      }

      if (this.results.coreWebVitals.fullyImplemented) {
        this.log('✅ Performance monitoring is fully implemented', 'success')
      } else {
        this.log('❌ Performance monitoring is incomplete', 'error')
      }

      // Check for Web Vitals tracking
      if (performanceUtilExists) {
        const perfFile = fs.readFileSync(path.join(__dirname, 'src/utils/performance.ts'), 'utf8')
        const tracksLCP = perfFile.includes('largest-contentful-paint')
        const tracksFID = perfFile.includes('first-input')
        const tracksCLS = perfFile.includes('layout-shift')
        
        this.results.coreWebVitals.webVitalsTracking = {
          lcp: tracksLCP,
          fid: tracksFID,
          cls: tracksCLS,
          complete: tracksLCP && tracksFID && tracksCLS
        }

        if (this.results.coreWebVitals.webVitalsTracking.complete) {
          this.log('✅ Core Web Vitals tracking is complete', 'success')
        } else {
          this.log('⚠️  Core Web Vitals tracking is incomplete', 'warning')
        }
      }

    } catch (error) {
      this.log(`❌ Performance monitoring test failed: ${error.message}`, 'error')
      this.results.coreWebVitals.error = error.message
    }
  }

  generateReport() {
    this.log('📄 Generating Performance Report...', 'info')
    
    const report = {
      summary: {
        timestamp: this.results.timestamp,
        bundleSizeTarget: this.results.bundleSize.total?.underTarget || false,
        lazyLoadingImplemented: this.results.lazyLoading.implemented || false,
        cachingConfigured: this.results.caching.properlyConfigured || false,
        monitoringImplemented: this.results.coreWebVitals.fullyImplemented || false
      },
      details: this.results
    }

    // Calculate overall score
    const scores = [
      report.summary.bundleSizeTarget ? 1 : 0,
      report.summary.lazyLoadingImplemented ? 1 : 0,
      report.summary.cachingConfigured ? 1 : 0,
      report.summary.monitoringImplemented ? 1 : 0
    ]
    report.summary.overallScore = (scores.reduce((a, b) => a + b, 0) / scores.length * 100).toFixed(0)

    // Save report
    const reportPath = path.join(__dirname, 'performance-test-report.json')
    fs.writeFileSync(reportPath, JSON.stringify(report, null, 2))

    // Display summary
    this.log('\n📋 Performance Test Summary', 'info')
    this.log('='.repeat(50), 'info')
    this.log(`Overall Score: ${report.summary.overallScore}%`, 
      report.summary.overallScore >= 80 ? 'success' : 
      report.summary.overallScore >= 60 ? 'warning' : 'error')
    
    this.log(`Bundle Size Target: ${report.summary.bundleSizeTarget ? '✅ Met' : '❌ Not Met'}`,
      report.summary.bundleSizeTarget ? 'success' : 'error')
    
    this.log(`Lazy Loading: ${report.summary.lazyLoadingImplemented ? '✅ Implemented' : '❌ Not Implemented'}`,
      report.summary.lazyLoadingImplemented ? 'success' : 'error')
    
    this.log(`Caching Strategies: ${report.summary.cachingConfigured ? '✅ Configured' : '❌ Not Configured'}`,
      report.summary.cachingConfigured ? 'success' : 'error')
    
    this.log(`Performance Monitoring: ${report.summary.monitoringImplemented ? '✅ Implemented' : '❌ Not Implemented'}`,
      report.summary.monitoringImplemented ? 'success' : 'error')

    if (this.results.bundleSize.total) {
      this.log(`\nBundle Details:`, 'info')
      this.log(`- Total Size: ${this.results.bundleSize.total.sizeKB}KB`, 'info')
      this.log(`- JavaScript: ${this.results.bundleSize.javascript.sizeKB}KB`, 'info')
      this.log(`- CSS: ${this.results.bundleSize.css.sizeKB}KB`, 'info')
      this.log(`- Chunks: ${this.results.bundleSize.codeSplitting?.chunkCount || 0}`, 'info')
    }

    this.log(`\nDetailed report saved to: ${reportPath}`, 'info')
    
    return report
  }

  async runAllTests() {
    this.log('🚀 Starting Performance Tests...', 'info')
    
    await this.testBundleSize()
    await this.testLazyLoading()
    await this.testCaching()
    await this.testPerformanceMonitoring()
    
    const report = this.generateReport()
    
    this.log('\n🎉 Performance testing completed!', 'success')
    
    // Exit with error code if overall score is too low
    if (parseInt(report.summary.overallScore) < 80) {
      process.exit(1)
    }
  }
}

// Run tests if called directly
if (require.main === module) {
  const tester = new PerformanceTester()
  tester.runAllTests().catch(error => {
    console.error('Performance test failed:', error)
    process.exit(1)
  })
}

module.exports = PerformanceTester
