import React, { useState, useRef, useEffect } from 'react'
import { optimizeImage, createLazyImageObserver } from '@/utils/performance'

interface LazyImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {
  src: string
  alt: string
  width?: number
  height?: number
  quality?: number
  className?: string
  fallback?: string
  onLoad?: () => void
  onError?: () => void
}

export const LazyImage: React.FC<LazyImageProps> = ({
  src,
  alt,
  width,
  height,
  quality = 80,
  className = '',
  fallback = '/placeholder-image.svg',
  onLoad,
  onError,
  ...props
}) => {
  const [isLoaded, setIsLoaded] = useState(false)
  const [hasError, setHasError] = useState(false)
  const [isInView, setIsInView] = useState(false)
  const imgRef = useRef<HTMLImageElement>(null)

  useEffect(() => {
    const observer = createLazyImageObserver()
    const currentImg = imgRef.current

    if (observer && currentImg) {
      const handleIntersection = (entries: IntersectionObserverEntry[]) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setIsInView(true)
            observer.unobserve(entry.target)
          }
        })
      }

      observer.disconnect()
      const customObserver = new IntersectionObserver(handleIntersection, {
        rootMargin: '50px 0px',
        threshold: 0.01
      })

      customObserver.observe(currentImg)

      return () => {
        customObserver.disconnect()
      }
    } else {
      // Fallback if IntersectionObserver is not supported
      setIsInView(true)
    }
  }, [])

  const handleLoad = () => {
    setIsLoaded(true)
    onLoad?.()
  }

  const handleError = () => {
    setHasError(true)
    onError?.()
  }

  const optimizedSrc = optimizeImage(src, { width, height, quality })
  const displaySrc = hasError ? fallback : optimizedSrc

  return (
    <div className={`relative overflow-hidden ${className}`}>
      {/* Placeholder while loading */}
      {!isLoaded && !hasError && (
        <div 
          className="absolute inset-0 bg-manufacturing-100 animate-pulse flex items-center justify-center"
          style={{ width, height }}
        >
          <div className="w-8 h-8 text-manufacturing-400">
            <svg fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clipRule="evenodd" />
            </svg>
          </div>
        </div>
      )}

      {/* Actual image */}
      <img
        ref={imgRef}
        src={isInView ? displaySrc : undefined}
        alt={alt}
        width={width}
        height={height}
        className={`
          transition-opacity duration-300 
          ${isLoaded ? 'opacity-100' : 'opacity-0'}
          ${hasError ? 'object-contain' : 'object-cover'}
        `}
        onLoad={handleLoad}
        onError={handleError}
        loading="lazy"
        decoding="async"
        {...props}
      />

      {/* Error state */}
      {hasError && (
        <div className="absolute inset-0 bg-manufacturing-50 flex items-center justify-center">
          <div className="text-center text-manufacturing-500">
            <div className="w-8 h-8 mx-auto mb-2">
              <svg fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
            </div>
            <p className="text-xs">Failed to load</p>
          </div>
        </div>
      )}
    </div>
  )
}

// Higher-order component for lazy loading any component
export function withLazyLoading<T extends object>(
  Component: React.ComponentType<T>,
  fallback: React.ReactNode = <div className="skeleton h-32 w-full" />
) {
  const LazyComponent = React.lazy(() => Promise.resolve({ default: Component }))
  
  return (props: T) => (
    <React.Suspense fallback={fallback}>
      <LazyComponent {...props} />
    </React.Suspense>
  )
}

// Hook for lazy loading data
export function useLazyLoad<T>(
  loadFunction: () => Promise<T>,
  dependencies: React.DependencyList = []
) {
  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<Error | null>(null)

  const load = React.useCallback(async () => {
    try {
      setLoading(true)
      setError(null)
      const result = await loadFunction()
      setData(result)
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Unknown error'))
    } finally {
      setLoading(false)
    }
  }, dependencies)

  return { data, loading, error, load }
}
