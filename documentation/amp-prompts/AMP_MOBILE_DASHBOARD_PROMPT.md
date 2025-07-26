# AMP Code Prompt: Mobile Dashboard for Order Tracking Pipeline

## TASK OVERVIEW
You are developing the mobile-optimized dashboard for a SuiteCRM Enterprise Legacy Modernization project's Order Tracking Pipeline. Your focus is creating an intuitive, touch-friendly mobile interface that allows sales teams to manage orders on-the-go with swipe gestures, push notifications, and a comprehensive manager overview dashboard.

## PRIMARY OBJECTIVES
1. **Mobile Pipeline View**: Create touch-optimized pipeline interface for smartphones
2. **Gesture Interactions**: Implement swipe gestures for stage updates and navigation
3. **Push Notifications**: Build real-time mobile notifications for status changes
4. **Manager Dashboard**: Develop comprehensive overview dashboard for team management
5. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### 1. **Create mobile-optimized pipeline view**
- **Mobile Layout Design**:
  - **Vertical Stack Layout**: Stack stages vertically for mobile scrolling
  - **Card-based Interface**: Touch-friendly cards for each order
  - **Collapsible Stages**: Expandable/collapsible stage sections to save space
  - **Sticky Headers**: Stage headers that stick during scroll
  - **Quick Actions**: Prominent action buttons for common tasks

- **Mobile Component Structure**:
```typescript
interface MobilePipelineView {
  stages: MobileStage[];
  selectedOrder?: OrderPipelineItem;
  viewMode: 'compact' | 'detailed' | 'list';
  searchQuery: string;
  filters: MobileFilters;
}

interface MobileStage {
  id: string;
  name: string;
  shortName: string; // Abbreviated for mobile
  count: number;
  totalValue: number;
  isExpanded: boolean;
  orders: OrderPipelineItem[];
}

interface MobileFilters {
  priority: string[];
  assignedUser: string;
  dateRange: DateRange;
  searchText: string;
  showOverdue: boolean;
}
```

- **Touch-Optimized Features**:
  - **Large Touch Targets**: Minimum 44px touch targets for buttons and links
  - **Thumb Navigation**: Bottom navigation optimized for thumb reach
  - **Pull-to-Refresh**: Refresh pipeline data with pull gesture
  - **Smooth Scrolling**: Momentum scrolling with proper bounce effects
  - **Haptic Feedback**: Vibration feedback for interactions (iOS/Android)

- **Mobile View Modes**:
  - **Compact View**: Condensed cards showing essential info only
  - **Detailed View**: Expanded cards with more order details
  - **List View**: Traditional list format for power users
  - **Search Mode**: Optimized layout for search results

### 2. **Add swipe gestures for stage updates**
- **Swipe Gesture Implementation**:
```typescript
// Touch gesture handlers
interface SwipeGesture {
  direction: 'left' | 'right' | 'up' | 'down';
  distance: number;
  velocity: number;
  target: OrderPipelineItem;
}

const handleOrderSwipe = (gesture: SwipeGesture) => {
  const { direction, target } = gesture;
  
  if (direction === 'right') {
    // Advance to next stage
    advanceOrderStage(target.id);
  } else if (direction === 'left') {
    // Move back to previous stage (if allowed)
    revertOrderStage(target.id);
  } else if (direction === 'up') {
    // Open order details
    openOrderDetails(target.id);
  } else if (direction === 'down') {
    // Show quick actions menu
    showQuickActions(target.id);
  }
};
```

- **Gesture Types & Actions**:
  - **Swipe Right**: Advance order to next pipeline stage
  - **Swipe Left**: Move order back to previous stage (with validation)
  - **Swipe Up**: Open detailed order view
  - **Swipe Down**: Show quick actions menu (call, email, notes)
  - **Long Press**: Select multiple orders for batch operations
  - **Pinch**: Switch between view modes (compact/detailed)

- **Visual Feedback**:
  - **Swipe Indicators**: Arrow overlays showing swipe direction and action
  - **Progress Animation**: Real-time feedback during swipe gesture
  - **Snap Animation**: Smooth animation to final position
  - **Undo Toast**: Quick undo option for accidental swipes
  - **Loading States**: Visual feedback during API calls

- **Swipe Constraints**:
  - **Business Logic**: Prevent invalid stage transitions
  - **Permission Checks**: Only allow swipes if user has permission
  - **Threshold Distance**: Minimum swipe distance to trigger action
  - **Velocity Sensitivity**: Fast swipes vs. slow deliberate swipes

### 3. **Implement push notifications for status changes**
- **Push Notification Setup**:
```typescript
// Service Worker for push notifications
interface PushNotificationData {
  type: 'stage_change' | 'overdue_alert' | 'urgent_order' | 'daily_summary';
  orderId: string;
  orderNumber: string;
  fromStage?: string;
  toStage?: string;
  priority: string;
  accountName: string;
  assignedUser: string;
  actionUrl: string;
}

// Register service worker and request permission
const initializePushNotifications = async () => {
  if ('serviceWorker' in navigator && 'PushManager' in window) {
    const registration = await navigator.serviceWorker.register('/sw.js');
    const permission = await Notification.requestPermission();
    
    if (permission === 'granted') {
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: 'YOUR_VAPID_PUBLIC_KEY'
      });
      
      // Send subscription to server
      await sendSubscriptionToServer(subscription);
    }
  }
};
```

- **Notification Types**:
  - **Stage Changes**: "Order #12345 moved to Order Processing"
  - **Overdue Alerts**: "Order #12345 overdue in Quote Preparation (3 days)"
  - **Urgent Orders**: "URGENT: Order #12345 requires immediate attention"
  - **Assignment Notifications**: "New order #12345 assigned to you"
  - **Client Updates**: "Client approved quote for order #12345"
  - **Daily Summary**: "You have 5 active orders, 2 overdue"

- **Notification Features**:
  - **Rich Notifications**: Include order details, client name, value
  - **Action Buttons**: "View Order", "Call Client", "Mark Complete"
  - **Grouped Notifications**: Bundle related notifications together
  - **Custom Sounds**: Different notification sounds for different priorities
  - **Badge Updates**: Update app icon badge with pending count
  - **Do Not Disturb**: Respect user's notification schedule preferences

- **Deep Linking**:
  - **Direct Navigation**: Tap notification opens specific order
  - **Context Preservation**: Maintain app state when returning from notification
  - **Universal Links**: Support for iOS Universal Links and Android App Links

### 4. **Build manager overview dashboard**
- **Manager Dashboard Components**:
```typescript
interface ManagerDashboard {
  teamPerformance: TeamMetrics;
  pipelineOverview: PipelineMetrics;
  alerts: AlertItem[];
  topPerformers: UserPerformance[];
  bottlenecks: BottleneckAnalysis;
  recentActivity: ActivityFeed[];
}

interface TeamMetrics {
  totalActiveOrders: number;
  totalPipelineValue: number;
  averageStageTime: number;
  conversionRate: number;
  overduteCount: number;
  urgentCount: number;
}

interface PipelineMetrics {
  stageDistribution: StageCount[];
  velocityTrends: VelocityData[];
  forecastedCompletions: ForecastData[];
  topBottlenecks: string[];
}
```

- **Key Performance Indicators (KPIs)**:
  - **Pipeline Velocity**: Average time through each stage
  - **Conversion Rates**: Quote-to-order conversion by stage
  - **Team Performance**: Individual and team metrics
  - **Revenue Forecast**: Projected revenue based on pipeline
  - **Bottleneck Analysis**: Stages causing delays
  - **Client Satisfaction**: Order completion times and quality metrics

- **Visual Dashboard Elements**:
  - **KPI Cards**: Large, easy-to-read metric cards
  - **Progress Rings**: Circular progress indicators for goals
  - **Trend Charts**: Line/bar charts showing performance over time
  - **Heat Maps**: Visual representation of performance by user/stage
  - **Alert Badges**: Red badges for items requiring attention
  - **Quick Actions**: Fast access to common management tasks

- **Manager Mobile Features**:
  - **Team Overview**: See all team members' pipeline status
  - **Drill Down**: Tap KPI cards to see detailed breakdowns
  - **Alerts Dashboard**: Centralized view of all system alerts
  - **Performance Trends**: Historical performance data and trends
  - **Export Reports**: Generate and share team performance reports
  - **Resource Allocation**: See workload distribution across team

- **Real-time Features**:
  - **Live Updates**: Real-time KPI updates without refresh
  - **Activity Feed**: Live stream of team activities and stage changes
  - **Alert System**: Immediate notifications for issues requiring attention
  - **Dashboard Refresh**: Pull-to-refresh for latest data
  - **Offline Sync**: Cache dashboard data for offline viewing

## TECHNICAL REQUIREMENTS

### Mobile Framework:
- **Progressive Web App (PWA)**: Installable mobile app experience
- **Service Worker**: Offline functionality and push notification support
- **Touch Gestures**: Hammer.js or native touch event handling
- **Mobile UI**: Tailwind CSS with mobile-first responsive design
- **App Shell**: Fast-loading app shell architecture

### Performance Optimization:
- **Lazy Loading**: Virtualized scrolling for large order lists
- **Image Optimization**: WebP images with lazy loading
- **Caching Strategy**: Aggressive caching with cache-first approach
- **Bundle Splitting**: Code splitting for faster initial load
- **Battery Optimization**: Efficient background sync and notifications

### Device Integration:
- **Camera Access**: Scan QR codes for quick order access
- **Geolocation**: Location-based features for field sales
- **Contacts Integration**: Quick access to client contact information
- **Calendar Integration**: Schedule follow-ups and meetings
- **Voice Commands**: Voice input for hands-free operation

### Offline Capabilities:
- **Data Sync**: Queue actions when offline, sync when online
- **Cached Data**: Store critical pipeline data locally
- **Offline Indicators**: Clear indication of offline status
- **Conflict Resolution**: Handle data conflicts when coming back online

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   - [ ] **Mobile Dashboard**
     - [ ] Create mobile-optimized pipeline view
     - [ ] Add swipe gestures for stage updates
     - [ ] Implement push notifications for status changes
     - [ ] Build manager overview dashboard
   ```

2. **Test on mobile devices**:
   - iOS Safari (iPhone/iPad various sizes)
   - Android Chrome (various manufacturers and screen sizes)
   - Touch gesture responsiveness and accuracy
   - Push notification delivery and interaction
   - Performance on slower devices and networks

3. **Validate mobile UX**:
   - One-handed usability testing
   - Touch target accessibility
   - Gesture conflict resolution
   - Battery usage optimization
   - Network efficiency

## SUCCESS CRITERIA
- ✅ All 4 mobile dashboard tasks marked complete with ❌ in checklist
- ✅ Mobile pipeline view optimized for touch interaction
- ✅ Swipe gestures working smoothly for stage updates
- ✅ Push notifications delivering reliably across platforms
- ✅ Manager dashboard providing comprehensive team overview
- ✅ PWA installation and offline functionality working
- ✅ Performance optimized for mobile devices and networks
- ✅ Responsive design working across all mobile screen sizes
- ✅ Accessibility compliance for mobile users

## CONTEXT AWARENESS
- **Field Sales Focus**: Designed for sales reps working away from desk
- **Touch-First Design**: Optimized for finger navigation and gestures
- **Real-time Updates**: Critical for mobile users who need current information
- **Offline Reliability**: Must work in areas with poor connectivity
- **Manager Efficiency**: Quick access to team performance and alerts

Begin with the mobile pipeline view layout, implement touch gestures, set up push notifications, build the manager dashboard, test across devices, and finally update the checklist with ❌ marks for completed tasks. 