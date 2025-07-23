# AMP Code Prompt: Testing & Validation for Manufacturing Product Catalog

## TASK OVERVIEW
You are responsible for comprehensive testing and validation of the SuiteCRM Enterprise Legacy Modernization project's manufacturing product catalog. Your focus is ensuring the system works flawlessly across devices, pricing calculations are accurate, performance meets enterprise standards, and accessibility compliance is verified.

## PRIMARY OBJECTIVES
1. **Cross-Platform Testing**: Validate functionality across mobile devices and platforms
2. **Business Logic Validation**: Ensure pricing calculations and workflows are accurate
3. **Performance Verification**: Confirm system meets speed and efficiency requirements
4. **Accessibility Compliance**: Verify the system is usable by all users regardless of ability
5. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### 1. **Mobile Device Testing (iOS/Android)**
- **iOS Testing**:
  - iPhone SE (small screen testing)
  - iPhone 12/13/14 (standard testing)
  - iPhone 14 Pro Max (large screen testing)
  - iPad Air/Pro (tablet testing)
  - Safari browser compatibility
  - Touch gesture functionality
  - Portrait/landscape orientation testing
- **Android Testing**:
  - Samsung Galaxy S21/S22 (flagship devices)
  - Google Pixel 6/7 (pure Android experience)
  - Budget Android devices (performance under constraints)
  - Chrome browser primary, Firefox/Edge secondary
  - Various screen sizes (5"-7" phones, 8"-12" tablets)
  - Hardware acceleration compatibility
- **Cross-Platform Features**:
  - Touch interactions (tap, long press, swipe, pinch-to-zoom)
  - Scroll performance and momentum
  - Virtual keyboard interactions
  - Device rotation handling
  - Battery optimization and background behavior

### 2. **Pricing Calculation Verification for Client Tiers**
- **Pricing Tier Testing**:
  - **Retail Pricing**: Base price validation for retail customers
  - **Wholesale Pricing**: Volume discount application (10-25% off)
  - **OEM Pricing**: Bulk pricing for original equipment manufacturers (25-40% off)
  - **Contract Pricing**: Custom negotiated pricing overrides
  - **Volume Breaks**: Quantity-based pricing tiers (50+, 100+, 500+ units)
- **Calculation Scenarios**:
  - Single product pricing across all tiers
  - Mixed cart with multiple pricing tiers
  - Volume discount calculations
  - Contract pricing override validation
  - Currency conversion accuracy (if applicable)
  - Tax calculation integration
  - Seasonal/promotional pricing
- **Edge Cases**:
  - Zero quantity handling
  - Negative pricing scenarios
  - Expired contract pricing fallback
  - Invalid client tier assignments
  - Database pricing inconsistencies

### 3. **Performance Testing: <2 Second Page Load Times**
- **Core Performance Metrics**:
  - **First Contentful Paint (FCP)**: <1.2 seconds
  - **Largest Contentful Paint (LCP)**: <2.0 seconds
  - **First Input Delay (FID)**: <100 milliseconds
  - **Cumulative Layout Shift (CLS)**: <0.1
  - **Time to Interactive (TTI)**: <2.5 seconds
- **Test Scenarios**:
  - Cold cache load (first visit)
  - Warm cache load (returning user)
  - 3G network simulation
  - 4G network simulation
  - WiFi network testing
  - Slow CPU simulation (4x throttling)
- **Load Testing**:
  - 100 concurrent users browsing catalog
  - 50 concurrent searches with filtering
  - 25 concurrent quote generations
  - Database query performance under load
  - Redis cache performance testing
  - API response time validation
- **Performance Tools**:
  - Lighthouse CI for automated testing
  - WebPageTest for real-world conditions
  - Chrome DevTools Performance tab
  - Network throttling simulation
  - Bundle analyzer for optimization

### 4. **Accessibility Testing for Mobile Interface**
- **WCAG 2.1 AA Compliance**:
  - **Perceivable**: Text alternatives, captions, color contrast
  - **Operable**: Keyboard access, no seizures, navigation
  - **Understandable**: Readable text, predictable functionality
  - **Robust**: Compatible with assistive technologies
- **Screen Reader Testing**:
  - VoiceOver (iOS) compatibility
  - TalkBack (Android) compatibility
  - NVDA (Windows) desktop testing
  - JAWS (Windows) enterprise testing
- **Visual Accessibility**:
  - Color contrast ratio >4.5:1 for normal text
  - Color contrast ratio >3:1 for large text
  - Focus indicators clearly visible
  - Text resizing up to 200% without horizontal scrolling
  - No information conveyed by color alone
- **Motor Accessibility**:
  - Touch targets minimum 44x44px
  - Gesture alternatives available
  - No time-based interactions required
  - Drag and drop has keyboard alternatives
- **Cognitive Accessibility**:
  - Clear navigation structure
  - Consistent UI patterns
  - Error messages are descriptive
  - Help text available where needed

## TESTING TOOLS AND FRAMEWORKS

### Automated Testing:
```bash
# Performance testing
npm run lighthouse:mobile
npm run webpagetest

# Accessibility testing  
npm run axe-core
npm run pa11y

# Cross-browser testing
npm run playwright:mobile
npm run cypress:mobile
```

### Manual Testing Checklist:
- [ ] Device rotation handling
- [ ] Touch gesture responsiveness
- [ ] Offline functionality
- [ ] Form input validation
- [ ] Error state handling
- [ ] Loading state indicators
- [ ] Image loading and fallbacks
- [ ] Search functionality accuracy
- [ ] Filter combination testing
- [ ] Add to quote workflow
- [ ] Quote generation and PDF export

## PERFORMANCE BENCHMARKS

### Target Metrics:
- **Page Load Time**: <2 seconds (3G network)
- **Search Response**: <500ms
- **Filter Application**: <300ms
- **Image Loading**: <1 second per image
- **Quote Generation**: <3 seconds
- **Database Queries**: <100ms average
- **API Response**: <200ms average
- **Redis Cache Hit**: <10ms

### Load Testing Scenarios:
```javascript
// Example load testing script
const scenarios = [
  { users: 100, duration: '5m', action: 'browse_catalog' },
  { users: 50, duration: '3m', action: 'search_products' },
  { users: 25, duration: '2m', action: 'generate_quote' },
  { users: 200, duration: '1m', action: 'view_product_details' }
];
```

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   - [ ] **Testing & Validation**
     - [ ] Test on mobile devices (iOS/Android)
     - [ ] Verify pricing calculations for different client tiers
     - [ ] Performance test: <2 second page load times
     - [ ] Accessibility testing for mobile interface
   ```

2. **Document test results**:
   - Create test execution reports
   - Record performance metrics
   - Document accessibility audit findings
   - Log any bugs or issues discovered

3. **Create test evidence**:
   - Screenshots of successful tests
   - Performance test results
   - Accessibility audit reports
   - Video recordings of mobile interactions

## SUCCESS CRITERIA
- ✅ All 4 testing & validation tasks marked complete with ❌ in checklist
- ✅ Mobile testing passed on iOS and Android devices across screen sizes
- ✅ Pricing calculations verified accurate for all client tiers and scenarios
- ✅ Performance targets achieved: <2s page loads, >90 Lighthouse scores
- ✅ WCAG 2.1 AA accessibility compliance verified
- ✅ Cross-browser compatibility confirmed
- ✅ Load testing demonstrates system stability under enterprise load
- ✅ All critical user journeys working flawlessly
- ✅ Zero blocking bugs in production-ready code

## TEST SCENARIOS TO VALIDATE

### Critical User Journeys:
1. **Sales Rep Mobile Workflow**:
   - Login → Browse catalog → Search products → Apply filters → View pricing → Add to quote → Generate PDF
2. **Manager Dashboard Review**:
   - Login → View team performance → Review quote pipeline → Check inventory levels
3. **Client Self-Service**:
   - Login → View order history → Reorder products → Track shipments → Download invoices

### Edge Case Testing:
- Network connectivity loss/restoration
- Large product catalogs (1000+ items)
- Complex filtering combinations
- Simultaneous user sessions
- Database connection failures
- Cache invalidation scenarios

## CONTEXT AWARENESS
- **Enterprise Environment**: Testing must reflect real-world enterprise usage patterns
- **Manufacturing Industry**: Products have complex specifications and pricing structures
- **Mobile-First**: Primary focus on mobile device performance and usability
- **B2B Context**: Professional quality standards for business users
- **Demo Requirements**: System must perform flawlessly during client demonstrations

Begin with setting up testing environments, execute mobile device testing across platforms, validate pricing calculation accuracy, perform comprehensive performance testing, and conclude with thorough accessibility validation to ensure enterprise-ready quality. 