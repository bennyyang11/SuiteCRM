# AMP Code Prompt: Pipeline Management System for Order Tracking Dashboard

## TASK OVERVIEW
You are developing the pipeline management interface for a SuiteCRM Enterprise Legacy Modernization project's Order Tracking Dashboard. Your focus is creating an intuitive, interactive Kanban-style dashboard that allows sales teams and managers to track and manage orders through their entire lifecycle with visual indicators and seamless stage transitions.

## PRIMARY OBJECTIVES
1. **Kanban Interface**: Build drag-and-drop visual pipeline dashboard
2. **Stage Management**: Implement smooth stage transitions with business logic
3. **Timeline Visualization**: Create comprehensive order history timeline
4. **Notification System**: Build real-time status change notifications
5. **Progress Tracking**: Develop visual progress indicators and status badges
6. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### 1. **Build Kanban-style dashboard interface**
- **Kanban Board Structure**:
  - 7 vertical columns representing pipeline stages
  - Horizontal cards representing individual orders/quotes
  - Clear visual separation between stages
  - Responsive design for desktop and tablet views
  - Infinite scroll for large datasets

- **Component Architecture** (React/Vue):
```typescript
// Main Dashboard Component
interface PipelineBoard {
  stages: PipelineStage[];
  orders: OrderPipelineItem[];
  filters: FilterState;
  user: User;
}

interface PipelineStage {
  id: string;
  name: string;
  order: number;
  color: string;
  count: number;
  totalValue: number;
}

interface OrderPipelineItem {
  id: string;
  pipelineNumber: string;
  accountName: string;
  currentStage: string;
  totalValue: number;
  priority: 'low' | 'medium' | 'high' | 'urgent';
  daysInStage: number;
  assignedUser: string;
  isOverdue: boolean;
}
```

- **Visual Design Elements**:
  - **Stage Headers**: Clear stage names with order counts and total values
  - **Order Cards**: Compact cards showing key information
  - **Color Coding**: Priority-based color indicators (red=urgent, orange=high, blue=medium, gray=low)
  - **Progress Bars**: Visual indicator of time spent in current stage
  - **Avatars**: Assigned user photos for quick identification
  - **Status Icons**: Visual indicators for overdue, priority, and special conditions

### 2. **Implement drag-and-drop stage progression**
- **Drag & Drop Functionality**:
  - HTML5 drag and drop API or React DnD/Vue Draggable
  - Visual feedback during drag operations
  - Drop zone highlighting
  - Snap-to-position animations
  - Touch support for tablet devices

- **Business Logic Validation**:
```typescript
const validateStageTransition = (
  fromStage: string, 
  toStage: string, 
  orderData: OrderPipelineItem
): ValidationResult => {
  // Stage progression rules
  const validTransitions = {
    'quote_requested': ['quote_prepared'],
    'quote_prepared': ['quote_sent', 'quote_requested'], // Can go back for revisions
    'quote_sent': ['quote_approved', 'quote_prepared'], // Can return for changes
    'quote_approved': ['order_processing'],
    'order_processing': ['ready_to_ship'],
    'ready_to_ship': ['invoiced_delivered'],
    'invoiced_delivered': [] // Final stage
  };
  
  // Additional validation logic
  // - Check user permissions
  // - Verify required fields completed
  // - Confirm client approval if moving to processing
  // - Validate inventory availability
};
```

- **Transition Animations**:
  - Smooth card movement between columns
  - Loading states during API calls
  - Success/error feedback animations
  - Optimistic UI updates with rollback capability

### 3. **Create timeline view for order history**
- **Timeline Component Structure**:
```typescript
interface TimelineEvent {
  id: string;
  timestamp: Date;
  eventType: 'stage_change' | 'note_added' | 'email_sent' | 'call_logged';
  fromStage?: string;
  toStage?: string;
  user: User;
  description: string;
  duration?: number; // Time spent in previous stage
  automaticTransition: boolean;
}
```

- **Timeline Features**:
  - **Chronological Order**: Events displayed in reverse chronological order
  - **Event Types**: Different icons and colors for different event types
  - **Duration Indicators**: Time spent in each stage with visual bars
  - **User Actions**: Clear indication of who performed each action
  - **Expandable Details**: Click to expand full event details
  - **Filter Options**: Filter by event type, date range, or user
  - **Export Capability**: Export timeline as PDF for client communication

- **Visual Timeline Design**:
  - Vertical timeline with branching for parallel events
  - Milestone markers for stage completions
  - Progress indicators showing overall completion percentage
  - Time duration badges showing efficiency metrics
  - Hover tooltips with additional context

### 4. **Add status change notifications (email/SMS)**
- **Notification Trigger System**:
```php
class PipelineNotificationService {
    public function triggerStageChangeNotification(
        string $pipelineId,
        string $fromStage,
        string $toStage,
        string $userId
    ): void {
        // Determine notification recipients
        // Check notification preferences
        // Queue notifications for delivery
        // Log notification attempts
    }
}
```

- **Notification Types**:
  - **Stage Transitions**: Automatic notifications when orders move between stages
  - **Overdue Alerts**: Notifications when orders exceed expected stage duration
  - **Priority Escalations**: Urgent notifications for high-priority orders
  - **Client Updates**: External notifications to clients on status changes
  - **Daily Summaries**: Digest emails for managers with pipeline overview

- **Delivery Channels**:
  - **Email**: HTML formatted emails with pipeline status
  - **SMS**: Concise text messages for urgent notifications
  - **Push Notifications**: Browser push for real-time updates
  - **In-App**: Toast notifications and dashboard alerts

- **Email Template Examples**:
```html
<!-- Stage Change Notification -->
<div class="pipeline-notification">
  <h2>Order Status Update</h2>
  <p>Order #{{pipelineNumber}} has moved from 
     <strong>{{fromStage}}</strong> to <strong>{{toStage}}</strong></p>
  <div class="order-details">
    <p>Client: {{accountName}}</p>
    <p>Value: ${{totalValue}}</p>
    <p>Expected Completion: {{expectedDate}}</p>
  </div>
  <a href="{{dashboardLink}}" class="btn">View Dashboard</a>
</div>
```

### 5. **Build progress indicators and status badges**
- **Progress Indicator Types**:
  - **Stage Progress Bar**: 7-segment progress bar showing current position
  - **Time-based Progress**: Circular progress showing time vs. expected duration
  - **Value Progress**: Progress based on order value milestones
  - **Custom Milestones**: User-defined checkpoints within stages

- **Status Badge System**:
```typescript
interface StatusBadge {
  type: 'priority' | 'overdue' | 'at_risk' | 'on_track' | 'completed';
  color: string;
  icon: string;
  text: string;
  tooltip: string;
}

const generateStatusBadges = (order: OrderPipelineItem): StatusBadge[] => {
  const badges: StatusBadge[] = [];
  
  // Priority badge
  if (order.priority === 'urgent') {
    badges.push({
      type: 'priority',
      color: 'red',
      icon: 'exclamation-triangle',
      text: 'URGENT',
      tooltip: 'High priority order requiring immediate attention'
    });
  }
  
  // Overdue badge
  if (order.isOverdue) {
    badges.push({
      type: 'overdue',
      color: 'orange',
      icon: 'clock',
      text: 'OVERDUE',
      tooltip: `${order.daysInStage} days in current stage`
    });
  }
  
  return badges;
};
```

- **Visual Progress Elements**:
  - **Mini Timeline**: Condensed timeline showing stage progression
  - **Percentage Complete**: Overall completion percentage calculation
  - **ETA Indicators**: Expected completion dates with confidence levels
  - **Risk Indicators**: Visual warnings for at-risk orders
  - **Velocity Metrics**: Speed indicators compared to historical averages

## TECHNICAL REQUIREMENTS

### Frontend Framework:
- **React 18+ with TypeScript** or **Vue 3+ with Composition API**
- **State Management**: Redux Toolkit (React) or Pinia (Vue)
- **Drag & Drop**: @dnd-kit/core (React) or Vue.Draggable
- **UI Components**: Chakra UI, Ant Design, or custom component library
- **Animations**: Framer Motion (React) or Vue Transition

### Performance Requirements:
- **Real-time Updates**: WebSocket connection for live pipeline updates
- **Lazy Loading**: Virtualized scrolling for large datasets (100+ orders)
- **Caching**: Aggressive caching of pipeline data with smart invalidation
- **Optimistic Updates**: Immediate UI feedback with server confirmation
- **Background Sync**: Offline capability with queue-based sync

### API Integration:
- **Pipeline API**: RESTful endpoints for pipeline CRUD operations
- **Notification API**: Webhook system for real-time notifications
- **Analytics API**: Real-time metrics and KPI calculations
- **User Management**: Integration with SuiteCRM user permissions

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   - [ ] **Pipeline Management System**
     - [ ] Build Kanban-style dashboard interface
     - [ ] Implement drag-and-drop stage progression
     - [ ] Create timeline view for order history
     - [ ] Add status change notifications (email/SMS)
     - [ ] Build progress indicators and status badges
   ```

2. **Test the interface**:
   - Drag and drop functionality across all stages
   - Timeline view with historical data
   - Notification delivery testing
   - Progress indicator accuracy
   - Cross-browser compatibility

3. **Validate business logic**:
   - Stage transition rules enforcement
   - Permission-based access control
   - Data consistency after operations
   - Performance with large datasets

## SUCCESS CRITERIA
- ✅ All 5 pipeline management tasks marked complete with ❌ in checklist
- ✅ Kanban dashboard fully functional with smooth drag-and-drop
- ✅ Stage transitions working with proper business logic validation
- ✅ Timeline view showing comprehensive order history
- ✅ Email/SMS notifications delivering reliably
- ✅ Progress indicators and status badges providing clear visual feedback
- ✅ Real-time updates working across multiple users
- ✅ Performance optimized for enterprise-scale usage
- ✅ Mobile tablet compatibility verified

## CONTEXT AWARENESS
- **Manufacturing Focus**: Pipeline optimized for manufacturing quote-to-delivery workflow
- **Sales Team Usability**: Interface designed for daily use by sales representatives
- **Manager Oversight**: Dashboard provides management visibility into team performance
- **Client Communication**: System supports transparent client status updates
- **Enterprise Scale**: Built to handle hundreds of concurrent pipeline items

Begin with the Kanban board structure, implement drag-and-drop functionality, create timeline visualization, build notification system, add progress indicators, and finally update the checklist with ❌ marks for completed tasks. 