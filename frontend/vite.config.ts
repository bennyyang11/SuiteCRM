import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'
import { VitePWA } from 'vite-plugin-pwa'
import { BundleAnalyzerPlugin } from 'rollup-plugin-visualizer'

export default defineConfig(({ command }) => ({
  plugins: [
    react({
      // Enable React Fast Refresh for development
      fastRefresh: true,
    }),
    VitePWA({
      registerType: 'autoUpdate',
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
        maximumFileSizeToCacheInBytes: 4000000,
        runtimeCaching: [
          // API responses cache with network-first strategy
          {
            urlPattern: /^https?:.*\/api\/.*/,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 60 * 5, // 5 minutes
              },
              cacheableResponse: {
                statuses: [0, 200],
              },
            },
          },
          // Images cache with cache-first strategy
          {
            urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'images-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
              },
            },
          },
          // Fonts cache with cache-first strategy
          {
            urlPattern: /\.(?:woff|woff2|ttf|eot)$/,
            handler: 'CacheFirst',
            options: {
              cacheName: 'fonts-cache',
              expiration: {
                maxEntries: 30,
                maxAgeSeconds: 60 * 60 * 24 * 365, // 1 year
              },
            },
          },
        ],
      },
      includeAssets: ['favicon.ico', 'apple-touch-icon.png', 'masked-icon.svg'],
      manifest: {
        name: 'SuiteCRM Manufacturing',
        short_name: 'CRM Mfg',
        description: 'Modern manufacturing CRM solution',
        theme_color: '#1f2937',
        background_color: '#ffffff',
        display: 'standalone',
        scope: '/',
        start_url: '/',
        icons: [
          {
            src: 'pwa-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: 'pwa-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      }
    }),
    // Bundle analyzer for production builds
    ...(command === 'build' ? [
      BundleAnalyzerPlugin({
        filename: 'bundle-analysis.html',
        open: false,
        gzipSize: true,
      })
    ] : [])
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },
  build: {
    target: 'es2020',
    minify: 'terser',
    sourcemap: false,
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
    rollupOptions: {
      output: {
        manualChunks: (id) => {
          // Vendor chunk for React ecosystem
          if (id.includes('react') || id.includes('react-dom')) {
            return 'react-vendor'
          }
          
          // Router chunk
          if (id.includes('react-router')) {
            return 'router'
          }
          
          // State management chunk
          if (id.includes('@reduxjs/toolkit') || id.includes('react-redux') || id.includes('@tanstack/react-query')) {
            return 'state-management'
          }
          
          // UI library chunk
          if (id.includes('framer-motion') || id.includes('lucide-react') || id.includes('react-hook-form')) {
            return 'ui-libraries'
          }
          
          // Utility libraries
          if (id.includes('date-fns') || id.includes('zod') || id.includes('axios')) {
            return 'utilities'
          }
          
          // Large third-party libraries get their own chunks
          if (id.includes('node_modules')) {
            return 'vendor'
          }
        },
        chunkFileNames: (chunkInfo) => {
          const facadeModuleId = chunkInfo.facadeModuleId
          if (facadeModuleId && facadeModuleId.includes('pages/')) {
            return 'pages/[name].[hash].js'
          }
          return 'chunks/[name].[hash].js'
        },
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return 'assets/[name].[hash].css'
          }
          return 'assets/[name].[hash].[ext]'
        },
      },
      external: (id) => {
        // Don't bundle development dependencies
        return false
      }
    },
    chunkSizeWarningLimit: 500,
    assetsInlineLimit: 4096, // Inline assets smaller than 4KB
    cssCodeSplit: true,
  },
  server: {
    port: 3001,
    proxy: {
      '/api': {
        target: 'http://localhost:3000',
        changeOrigin: true,
        configure: (proxy, _options) => {
          proxy.on('proxyRes', (proxyRes, req, res) => {
            // Add caching headers for API responses
            if (req.url?.includes('/api/')) {
              res.setHeader('Cache-Control', 'public, max-age=300') // 5 minutes
            }
          })
        }
      }
    }
  },
  optimizeDeps: {
    include: [
      'react',
      'react-dom',
      'react-router-dom',
      '@reduxjs/toolkit',
      'react-redux',
      '@tanstack/react-query'
    ],
    exclude: ['@vite/client', '@vite/env']
  }
}))
