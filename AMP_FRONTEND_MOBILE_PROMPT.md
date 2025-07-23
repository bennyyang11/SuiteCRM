# AMP Code Prompt: Frontend Mobile Interface for Manufacturing Product Catalog

## TASK OVERVIEW
You are developing the mobile-first frontend interface for a SuiteCRM Enterprise Legacy Modernization project. Your focus is creating a responsive, touch-optimized product catalog that provides an exceptional mobile experience for manufacturing sales teams and clients.

## PRIMARY OBJECTIVES
1. **Mobile-First Design**: Create responsive components optimized for mobile devices
2. **Modern Framework Integration**: Implement React/Vue.js with TypeScript for maintainability
3. **Performance Optimization**: Ensure fast loading and smooth interactions on mobile networks
4. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### 1. **React/Vue Component for Product Browsing**
- **Framework Choice**: React 18+ with TypeScript or Vue 3+ with Composition API
- **Component Structure**: 
  - `ProductCatalog.tsx/vue` (main container)
  - `ProductGrid.tsx/vue` (product listing)
  - `ProductCard.tsx/vue` (individual product display)
  - `SearchBar.tsx/vue` (search interface)
- **State Management**: Redux Toolkit (React) or Pinia (Vue) for complex state
- **API Integration**: Axios/Fetch with proper error handling and loading states
- **TypeScript**: Full type safety for props, API responses, and state

### 2. **Responsive Grid Layout (Mobile/Tablet/Desktop)**
- **Breakpoints**: 
  - Mobile: 320px-768px (1 column grid)
  - Tablet: 768px-1024px (2-3 columns)
  - Desktop: 1024px+ (4-6 columns)
- **CSS Framework**: Tailwind CSS or CSS Modules with flexbox/grid
- **Dynamic Layout**: Auto-adjusting columns based on screen size
- **Touch Targets**: Minimum 44px touch targets for mobile accessibility
- **Spacing**: Consistent spacing using design tokens

### 3. **Touch-Optimized Product Cards with Images**
- **Card Design**: Clean, modern cards with product images, name, SKU, price
- **Touch Interactions**: 
  - Tap to view details
  - Long press for quick actions
  - Swipe gestures for additional options
- **Image Optimization**: 
  - WebP format with fallbacks
  - Lazy loading for performance
  - Responsive images with srcset
  - Placeholder/skeleton loading states
- **Micro-interactions**: Smooth animations for tap feedback and state changes
- **Accessibility**: ARIA labels, keyboard navigation, screen reader support

### 4. **Advanced Filtering Sidebar (Category, Material, Price)**
- **Filter Categories**:
  - Product Category (dropdown/multi-select)
  - Material Type (checkboxes)
  - Price Range (dual-handle slider)
  - Stock Status (radio buttons)
  - Brand/Manufacturer (searchable dropdown)
- **Mobile UX**: 
  - Collapsible sidebar that slides in from side
  - Bottom sheet modal for filters on mobile
  - Clear visual feedback for active filters
  - Quick filter reset functionality
- **Real-time Filtering**: Debounced search with loading indicators
- **Filter Persistence**: Save user preferences in localStorage
- **URL State**: Reflect filters in URL for bookmarking/sharing

### 5. **"Add to Quote" Functionality**
- **Quote Builder Integration**:
  - Floating action button for quote management
  - Quantity selector with +/- buttons
  - Batch selection mode for multiple products
  - Quick add with single tap
- **Visual Feedback**:
  - Success animations when items added
  - Quote counter badge in header
  - Undo functionality for accidental additions
- **Offline Support**: Queue actions when offline, sync when online
- **Validation**: Stock level checks, minimum order quantities
- **Quote Preview**: Sliding panel to review selected items

### 6. **Offline Capability with Cached Data**
- **Service Worker**: Register service worker for offline functionality
- **Caching Strategy**:
  - Cache frequently viewed products
  - Cache search results for common queries
  - Cache product images and assets
  - Offline fallback pages
- **Data Sync**: 
  - Background sync when connection restored
  - Conflict resolution for offline changes
  - Progressive enhancement approach
- **User Feedback**: Clear indicators for offline mode
- **Storage Management**: IndexedDB for large datasets, localStorage for preferences

## TECHNICAL REQUIREMENTS

### Modern Frontend Stack:
- **Framework**: React 18+ with TypeScript or Vue 3+ with TypeScript
- **Build Tool**: Vite or Create React App with optimizations
- **CSS**: Tailwind CSS or Styled Components for responsive design
- **State Management**: Context API + useReducer or Redux Toolkit/Pinia
- **HTTP Client**: Axios with interceptors for error handling

### Performance Optimization:
- **Bundle Size**: <500KB initial load, code splitting for routes
- **Lazy Loading**: Dynamic imports for components and routes
- **Image Optimization**: WebP format, responsive images, lazy loading
- **Caching**: Aggressive caching with cache busting for updates
- **Critical CSS**: Inline critical styles for faster first paint

### Mobile-First Design:
- **Touch Interfaces**: Gestures, haptic feedback, touch-friendly controls
- **Performance**: <2 second load time on 3G networks
- **Accessibility**: WCAG 2.1 AA compliance, screen reader support
- **PWA Features**: Installable, offline-capable, native app feel
- **Responsive Images**: Optimized for different screen densities

### Integration Requirements:
- **API Integration**: Connect to ProductCatalogAPI endpoints
- **Authentication**: JWT token handling with automatic refresh
- **Error Handling**: Graceful error states with retry mechanisms
- **Loading States**: Skeleton screens and progressive loading
- **Real-time Updates**: WebSocket integration for inventory updates

## COMPONENT STRUCTURE EXAMPLE

```typescript
// React TypeScript example
interface Product {
  id: string;
  sku: string;
  name: string;
  description: string;
  price: number;
  image: string;
  category: string;
  inStock: boolean;
}

const ProductCatalog: React.FC = () => {
  const [products, setProducts] = useState<Product[]>([]);
  const [filters, setFilters] = useState<FilterState>({});
  const [loading, setLoading] = useState(false);
  
  // Component implementation
};
```

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   - [ ] **Frontend Mobile Interface**
     - [ ] Create React/Vue component for product browsing
     - [ ] Implement responsive grid layout (mobile/tablet/desktop)
     - [ ] Add touch-optimized product cards with images
     - [ ] Build advanced filtering sidebar (category, material, price)
     - [ ] Add "Add to Quote" functionality
     - [ ] Implement offline capability with cached data
   ```

2. **Test across devices**:
   - iOS Safari (iPhone/iPad)
   - Android Chrome (various screen sizes)
   - Desktop browsers (Chrome, Firefox, Safari)
   - Tablet devices in both orientations

3. **Performance validation**:
   - Lighthouse mobile score >90
   - First Contentful Paint <1.5s
   - Largest Contentful Paint <2.5s
   - Cumulative Layout Shift <0.1

## SUCCESS CRITERIA
- ✅ All 6 frontend mobile tasks marked complete with ❌ in checklist
- ✅ React/Vue components fully functional with TypeScript
- ✅ Responsive design working perfectly across all devices
- ✅ Touch-optimized interactions smooth and intuitive
- ✅ Advanced filtering providing instant search results
- ✅ Add to Quote functionality integrated and working
- ✅ Offline capability maintaining core functionality without internet
- ✅ Performance metrics meeting mobile-first standards
- ✅ Accessibility compliance verified

## CONTEXT AWARENESS
- **Sales Team Focus**: Designed for field sales representatives using mobile devices
- **Manufacturing Context**: Product catalog for industrial/manufacturing products
- **Enterprise Quality**: Professional UI suitable for B2B interactions
- **Demo Requirements**: Impressive visual experience for client demonstrations
- **Legacy Integration**: Seamless integration with existing SuiteCRM backend

Begin with the component architecture setup, implement responsive grid layout, create touch-optimized product cards, add filtering capabilities, integrate quote functionality, and finally implement offline capabilities for a complete mobile-first experience. 