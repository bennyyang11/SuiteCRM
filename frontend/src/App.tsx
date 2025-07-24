import React, { Suspense } from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import { useAppSelector } from '@/hooks/redux'
import { selectIsAuthenticated } from '@/store/slices/authSlice'
import { Layout } from '@/components/Layout'
import { LoadingSpinner } from '@/components/ui/LoadingSpinner'
import { ErrorBoundary } from '@/components/ui/ErrorBoundary'

// Lazy load components for better performance
const Dashboard = React.lazy(() => import('@/pages/Dashboard'))
const ProductCatalog = React.lazy(() => import('@/pages/ProductCatalog'))
const OrderPipeline = React.lazy(() => import('@/pages/OrderPipeline'))
const QuoteBuilder = React.lazy(() => import('@/pages/QuoteBuilder'))
const Login = React.lazy(() => import('@/pages/Login'))

// Protected Route wrapper
const ProtectedRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const isAuthenticated = useAppSelector(selectIsAuthenticated)
  
  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }
  
  return <>{children}</>
}

function App() {
  return (
    <ErrorBoundary>
      <div className="min-h-screen bg-gray-50">
        <Suspense fallback={<LoadingSpinner />}>
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route
              path="/*"
              element={
                <ProtectedRoute>
                  <Layout>
                    <Routes>
                      <Route path="/" element={<Navigate to="/dashboard" replace />} />
                      <Route path="/dashboard" element={<Dashboard />} />
                      <Route path="/catalog" element={<ProductCatalog />} />
                      <Route path="/pipeline" element={<OrderPipeline />} />
                      <Route path="/quotes/*" element={<QuoteBuilder />} />
                    </Routes>
                  </Layout>
                </ProtectedRoute>
              }
            />
          </Routes>
        </Suspense>
      </div>
    </ErrorBoundary>
  )
}

export default App
