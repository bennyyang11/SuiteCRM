/**
 * TypeScript Type Definitions for Pipeline Management System
 */

// Main pipeline interfaces
export interface OrderPipelineItem {
  id: string;
  pipelineNumber: string;
  opportunityId?: string;
  accountId: string;
  accountName: string;
  assignedUserId: string;
  assignedUser: User;
  currentStage: PipelineStageId;
  stageEnteredDate: string;
  expectedCompletionDate?: string;
  actualCompletionDate?: string;
  totalValue: number;
  currencyId: string;
  priority: Priority;
  status: PipelineStatus;
  clientPoNumber?: string;
  clientContactId?: string;
  notes?: string;
  internalNotes?: string;
  shippingAddress?: string;
  shippingMethod?: string;
  trackingNumber?: string;
  dateEntered: string;
  dateModified: string;
  createdBy: string;
  modifiedUserId?: string;
  deleted: boolean;
  
  // Calculated fields
  daysInStage: number;
  isOverdue: boolean;
  hasNotes: boolean;
  hasAttachments: boolean;
  lastActivity?: string;
  clientApprovalDate?: string;
  manufacturingComplete?: boolean;
  quoteSentDate?: string;
}

export interface PipelineStage {
  id: PipelineStageId;
  name: string;
  order: number;
  color: string;
  description?: string;
  count?: number;
  totalValue?: number;
  urgentCount?: number;
  overdueCount?: number;
  avgDuration?: number;
}

export interface TimelineEvent {
  id: string;
  pipelineId: string;
  timestamp: Date;
  eventType: TimelineEventType;
  fromStage?: string;
  toStage?: string;
  user: User;
  description: string;
  notes?: string;
  duration?: number; // Hours spent in previous stage
  automaticTransition: boolean;
  workflowRuleId?: string;
  approvalRequired: boolean;
  approvedBy?: string;
  approvalDate?: Date;
}

export interface StatusBadge {
  type: BadgeType;
  color: string;
  icon?: string;
  text: string;
  tooltip: string;
}

export interface User {
  id: string;
  userName: string;
  firstName: string;
  lastName: string;
  fullName: string;
  email: string;
  phone?: string;
  department?: string;
  title?: string;
  avatarUrl?: string;
  isActive: boolean;
}

export interface FilterState {
  assignedUser?: string;
  priority?: Priority | null;
  stage?: PipelineStageId | null;
  searchQuery: string;
  dateRange?: {
    start: Date;
    end: Date;
  } | null;
  showOverdueOnly: boolean;
  accountId?: string;
  valueRange?: {
    min: number;
    max: number;
  };
}

export interface NotificationPreference {
  id: string;
  userId: string;
  notificationType: NotificationType;
  deliveryMethod: DeliveryMethod;
  enabled: boolean;
  scheduleTime?: string;
  scheduleDay?: string;
  thresholdHours?: number;
  thresholdAmount?: number;
  emailTemplateId?: string;
  messageFormat: MessageFormat;
}

export interface NotificationQueue {
  id: string;
  pipelineId?: string;
  recipientUserId: string;
  notificationType: string;
  deliveryMethod: DeliveryMethod;
  priority: Priority;
  subject?: string;
  message: string;
  messageHtml?: string;
  actionUrl?: string;
  status: NotificationStatus;
  scheduledTime: Date;
  sentTime?: Date;
  readTime?: Date;
  attempts: number;
  maxAttempts: number;
  errorMessage?: string;
  externalId?: string;
  batchId?: string;
}

export interface StageMetrics {
  stageName: string;
  displayName: string;
  avgDurationHours: number;
  minDurationHours: number;
  maxDurationHours: number;
  successRate: number;
  totalEntries: number;
  currentCount: number;
  lastCalculated: Date;
  automationPercentage: number;
  recentAvgHours: number;
}

export interface PipelineAnalytics {
  pipelineDate: string;
  currentStage: PipelineStageId;
  assignedUserId: string;
  assignedUserName: string;
  stageCount: number;
  urgentCount: number;
  highPriorityCount: number;
  onHoldCount: number;
  avgValue: number;
  totalValue: number;
  minValue: number;
  maxValue: number;
  avgDaysInStage: number;
  minDaysInStage: number;
  maxDaysInStage: number;
  avgTotalPipelineDays: number;
  completedCount: number;
  overdueCount: number;
}

// Enums and union types
export type PipelineStageId = 
  | 'quote_requested'
  | 'quote_prepared'
  | 'quote_sent'
  | 'quote_approved'
  | 'order_processing'
  | 'ready_to_ship'
  | 'invoiced_delivered';

export type Priority = 'low' | 'medium' | 'high' | 'urgent';

export type PipelineStatus = 'active' | 'on_hold' | 'cancelled' | 'completed';

export type TimelineEventType = 
  | 'stage_change'
  | 'note_added'
  | 'email_sent'
  | 'call_logged'
  | 'document_uploaded'
  | 'payment_received'
  | 'approval_requested'
  | 'approval_granted'
  | 'approval_denied'
  | 'task_created'
  | 'task_completed'
  | 'meeting_scheduled'
  | 'quote_generated'
  | 'invoice_sent'
  | 'shipment_created';

export type BadgeType = 
  | 'priority'
  | 'overdue'
  | 'at_risk'
  | 'on_track'
  | 'completed'
  | 'high_value'
  | 'follow_up'
  | 'approval_needed'
  | 'client_waiting';

export type NotificationType = 
  | 'stage_change'
  | 'overdue_alert'
  | 'daily_summary'
  | 'weekly_report'
  | 'client_update'
  | 'urgent_priority'
  | 'completion_milestone'
  | 'bottleneck_alert'
  | 'approval_required'
  | 'quote_expiring';

export type DeliveryMethod = 'email' | 'sms' | 'push' | 'in_app';

export type MessageFormat = 'full' | 'summary' | 'minimal';

export type NotificationStatus = 'pending' | 'sent' | 'failed' | 'cancelled' | 'read';

// API Response types
export interface ApiResponse<T> {
  status: 'success' | 'error';
  message?: string;
  data?: T;
  error?: string;
  pagination?: {
    page: number;
    limit: number;
    total_count: number;
    has_next: boolean;
    has_prev: boolean;
  };
}

export interface PipelineSearchParams {
  q?: string;
  assigned_user?: string;
  stage?: PipelineStageId;
  priority?: Priority;
  status?: PipelineStatus;
  account_id?: string;
  date_from?: string;
  date_to?: string;
  value_min?: number;
  value_max?: number;
  overdue_only?: boolean;
  page?: number;
  limit?: number;
  sort_by?: 'date_entered' | 'date_modified' | 'total_value' | 'pipeline_number' | 'account_name';
  sort_order?: 'ASC' | 'DESC';
}

export interface StageTransitionRequest {
  pipelineId: string;
  fromStage: PipelineStageId;
  toStage: PipelineStageId;
  reason?: string;
  notes?: string;
  automated?: boolean;
  approvalRequired?: boolean;
  approvedBy?: string;
}

export interface NotificationRequest {
  type: NotificationType;
  pipelineId?: string;
  fromStage?: PipelineStageId;
  toStage?: PipelineStageId;
  userId: string;
  priority?: Priority;
  customMessage?: string;
  scheduleTime?: Date;
}

// Validation result types
export interface ValidationResult {
  valid: boolean;
  error?: string;
  warnings?: string[];
}

// Dashboard state types
export interface DashboardState {
  orders: OrderPipelineItem[];
  stageMetrics: Record<PipelineStageId, StageMetrics>;
  analytics: PipelineAnalytics[];
  filters: FilterState;
  loading: boolean;
  error: string | null;
  selectedOrderId: string | null;
  isTimelineOpen: boolean;
  notifications: NotificationQueue[];
  lastUpdated: Date | null;
}

// Hook return types
export interface UsePipelineDataReturn {
  orders: OrderPipelineItem[];
  stageMetrics: Record<PipelineStageId, StageMetrics> | null;
  loading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
  updateOrderStage: (orderId: string, newStage: PipelineStageId, transition: Partial<StageTransitionRequest>) => Promise<void>;
  isUpdating: boolean;
}

export interface UseTimelineDataReturn {
  order: OrderPipelineItem | null;
  timeline: TimelineEvent[] | null;
  loading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
  addNote: (note: string) => Promise<void>;
  exportTimeline: (format: 'pdf' | 'excel') => Promise<void>;
}

export interface UsePipelineNotificationsReturn {
  notifications: NotificationQueue[];
  unreadCount: number;
  markAsRead: (notificationId: string) => Promise<void>;
  markAllAsRead: () => Promise<void>;
  sendNotification: (request: NotificationRequest) => Promise<void>;
  preferences: NotificationPreference[];
  updatePreferences: (preferences: NotificationPreference[]) => Promise<void>;
}

// Component prop types
export interface PipelineBoardProps {
  stages: PipelineStage[];
  orders: OrderPipelineItem[];
  filters: FilterState;
  user: User;
  isManager?: boolean;
  onOrderClick?: (orderId: string) => void;
  onStageChange?: (orderId: string, newStage: PipelineStageId) => void;
  onFilterChange?: (filters: Partial<FilterState>) => void;
}

export interface PipelineColumnProps {
  stage: PipelineStage;
  orders: OrderPipelineItem[];
  onOrderClick: (orderId: string) => void;
  onDrop?: (orderId: string, newStage: PipelineStageId) => void;
  isLoading?: boolean;
  className?: string;
}

export interface PipelineCardProps {
  order: OrderPipelineItem;
  onClick: (orderId: string) => void;
  isDragging?: boolean;
  showDetails?: boolean;
  className?: string;
}

export interface TimelineModalProps {
  orderId: string;
  isOpen: boolean;
  onClose: () => void;
  className?: string;
}

export interface ProgressIndicatorProps {
  percentage: number;
  daysInStage: number;
  isOverdue?: boolean;
  showDays?: boolean;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'linear' | 'circular' | 'mini';
  className?: string;
}

export interface StatusBadgesProps {
  badges: StatusBadge[];
  maxVisible?: number;
  size?: 'xs' | 'sm' | 'md';
  className?: string;
}
