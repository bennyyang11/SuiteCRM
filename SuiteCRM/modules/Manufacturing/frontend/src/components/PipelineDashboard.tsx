/**
 * Pipeline Dashboard - Kanban-style Order Tracking Interface
 * Manufacturing Order Pipeline Management System
 */

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { DndContext, DragEndEvent, DragOverlay, DragStartEvent, closestCenter } from '@dnd-kit/core';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { OrderPipelineItem, PipelineStage, FilterState, User, StatusBadge } from '../types/Pipeline';
import { usePipelineData } from '../hooks/usePipelineData';
import { usePipelineNotifications } from '../hooks/usePipelineNotifications';
import PipelineColumn from './PipelineColumn';
import PipelineCard from './PipelineCard';
import PipelineFilters from './PipelineFilters';
import TimelineModal from './TimelineModal';
import LoadingSpinner from './LoadingSpinner';
import ErrorBoundary from './ErrorBoundary';

interface PipelineDashboardProps {
  userId?: string;
  isManager?: boolean;
  className?: string;
}

const PIPELINE_STAGES: PipelineStage[] = [
  {
    id: 'quote_requested',
    name: 'Quote Requested',
    order: 1,
    color: '#6B7280',
    description: 'Initial quote request from client'
  },
  {
    id: 'quote_prepared',
    name: 'Quote Prepared',
    order: 2,
    color: '#3B82F6',
    description: 'Sales team preparing detailed quote'
  },
  {
    id: 'quote_sent',
    name: 'Quote Sent',
    order: 3,
    color: '#8B5CF6',
    description: 'Quote delivered to client for review'
  },
  {
    id: 'quote_approved',
    name: 'Quote Approved',
    order: 4,
    color: '#F59E0B',
    description: 'Client approves quote terms'
  },
  {
    id: 'order_processing',
    name: 'Order Processing',
    order: 5,
    color: '#EF4444',
    description: 'Manufacturing/procurement begins'
  },
  {
    id: 'ready_to_ship',
    name: 'Ready to Ship',
    order: 6,
    color: '#10B981',
    description: 'Order completed, awaiting shipment'
  },
  {
    id: 'invoiced_delivered',
    name: 'Invoiced & Delivered',
    order: 7,
    color: '#059669',
    description: 'Final delivery and billing complete'
  }
];

const PipelineDashboard: React.FC<PipelineDashboardProps> = ({
  userId,
  isManager = false,
  className = '',
}) => {
  // State management
  const [activeCard, setActiveCard] = useState<OrderPipelineItem | null>(null);
  const [selectedOrderId, setSelectedOrderId] = useState<string | null>(null);
  const [isTimelineOpen, setIsTimelineOpen] = useState(false);
  const [filters, setFilters] = useState<FilterState>({
    assignedUser: userId,
    priority: null,
    stage: null,
    searchQuery: '',
    dateRange: null,
    showOverdueOnly: false
  });

  // Custom hooks
  const {
    orders,
    stageMetrics,
    loading,
    error,
    refetch,
    updateOrderStage,
    isUpdating
  } = usePipelineData(filters);

  const {
    notifications,
    markAsRead,
    sendNotification
  } = usePipelineNotifications(userId);

  // Memoized calculations
  const stagesWithMetrics = useMemo(() => {
    return PIPELINE_STAGES.map(stage => {
      const stageOrders = orders.filter(order => order.currentStage === stage.id);
      const metrics = stageMetrics?.[stage.id];
      
      return {
        ...stage,
        count: stageOrders.length,
        totalValue: stageOrders.reduce((sum, order) => sum + order.totalValue, 0),
        urgentCount: stageOrders.filter(order => order.priority === 'urgent').length,
        overdueCount: stageOrders.filter(order => order.isOverdue).length,
        avgDuration: metrics?.avgDurationHours || 0
      };
    });
  }, [orders, stageMetrics]);

  const ordersByStage = useMemo(() => {
    const grouped = orders.reduce((acc, order) => {
      if (!acc[order.currentStage]) {
        acc[order.currentStage] = [];
      }
      acc[order.currentStage].push(order);
      return acc;
    }, {} as Record<string, OrderPipelineItem[]>);

    // Sort orders within each stage by priority and creation date
    Object.keys(grouped).forEach(stageId => {
      grouped[stageId].sort((a, b) => {
        const priorityOrder = { urgent: 4, high: 3, medium: 2, low: 1 };
        if (priorityOrder[a.priority] !== priorityOrder[b.priority]) {
          return priorityOrder[b.priority] - priorityOrder[a.priority];
        }
        return new Date(b.dateEntered).getTime() - new Date(a.dateEntered).getTime();
      });
    });

    return grouped;
  }, [orders]);

  // Drag and drop handlers
  const handleDragStart = useCallback((event: DragStartEvent) => {
    const { active } = event;
    const order = orders.find(o => o.id === active.id);
    setActiveCard(order || null);
  }, [orders]);

  const handleDragEnd = useCallback(async (event: DragEndEvent) => {
    const { active, over } = event;
    setActiveCard(null);

    if (!over || active.id === over.id) {
      return;
    }

    const orderId = active.id as string;
    const newStage = over.id as string;
    const order = orders.find(o => o.id === orderId);

    if (!order || order.currentStage === newStage) {
      return;
    }

    // Validate stage transition
    const isValidTransition = validateStageTransition(order.currentStage, newStage, order);
    if (!isValidTransition.valid) {
      // Show error notification
      console.error('Invalid stage transition:', isValidTransition.error);
      return;
    }

    try {
      // Optimistic update
      await updateOrderStage(orderId, newStage, {
        reason: 'Moved via drag and drop',
        automated: false
      });

      // Send notifications
      await sendNotification({
        type: 'stage_change',
        pipelineId: orderId,
        fromStage: order.currentStage,
        toStage: newStage,
        userId: order.assignedUserId
      });

    } catch (error) {
      console.error('Failed to update order stage:', error);
      // Revert optimistic update would happen automatically via error handling
    }
  }, [orders, updateOrderStage, sendNotification]);

  // Stage transition validation
  const validateStageTransition = useCallback((
    fromStage: string,
    toStage: string,
    order: OrderPipelineItem
  ): { valid: boolean; error?: string } => {
    const validTransitions: Record<string, string[]> = {
      'quote_requested': ['quote_prepared'],
      'quote_prepared': ['quote_sent', 'quote_requested'],
      'quote_sent': ['quote_approved', 'quote_prepared'],
      'quote_approved': ['order_processing'],
      'order_processing': ['ready_to_ship'],
      'ready_to_ship': ['invoiced_delivered'],
      'invoiced_delivered': []
    };

    const allowedStages = validTransitions[fromStage] || [];
    if (!allowedStages.includes(toStage)) {
      return {
        valid: false,
        error: `Cannot move from ${fromStage} to ${toStage}. Valid transitions: ${allowedStages.join(', ')}`
      };
    }

    // Additional business logic validation
    if (toStage === 'order_processing' && !order.clientApprovalDate) {
      return {
        valid: false,
        error: 'Client approval required before moving to order processing'
      };
    }

    if (toStage === 'ready_to_ship' && !order.manufacturingComplete) {
      return {
        valid: false,
        error: 'Manufacturing must be completed before ready to ship'
      };
    }

    return { valid: true };
  }, []);

  // Event handlers
  const handleOrderClick = useCallback((orderId: string) => {
    setSelectedOrderId(orderId);
    setIsTimelineOpen(true);
  }, []);

  const handleFilterChange = useCallback((newFilters: Partial<FilterState>) => {
    setFilters(prev => ({ ...prev, ...newFilters }));
  }, []);

  const handleRefresh = useCallback(() => {
    refetch();
  }, [refetch]);

  // Real-time updates via WebSocket
  useEffect(() => {
    const ws = new WebSocket(`ws://localhost:3000/pipeline-updates?userId=${userId}`);
    
    ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.type === 'pipeline_update') {
        refetch();
      }
    };

    return () => ws.close();
  }, [userId, refetch]);

  // Loading and error states
  if (loading && orders.length === 0) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="large" />
      </div>
    );
  }

  if (error) {
    return (
      <ErrorBoundary
        error={error}
        onRetry={handleRefresh}
        message="Failed to load pipeline data"
      />
    );
  }

  return (
    <ErrorBoundary>
      <div className={`pipeline-dashboard ${className} min-h-screen bg-gray-50`}>
        {/* Dashboard Header */}
        <div className="bg-white shadow-sm border-b sticky top-0 z-20">
          <div className="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center justify-between h-16">
              <div className="flex items-center">
                <h1 className="text-2xl font-bold text-gray-900">
                  Pipeline Dashboard
                </h1>
                <span className="ml-3 text-sm text-gray-500">
                  {orders.length} active orders
                </span>
              </div>

              <div className="flex items-center space-x-4">
                {/* Quick Stats */}
                <div className="hidden md:flex items-center space-x-6 text-sm text-gray-600">
                  <div className="flex items-center">
                    <div className="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                    <span>{orders.filter(o => o.priority === 'urgent').length} Urgent</span>
                  </div>
                  <div className="flex items-center">
                    <div className="w-3 h-3 bg-orange-500 rounded-full mr-2"></div>
                    <span>{orders.filter(o => o.isOverdue).length} Overdue</span>
                  </div>
                  <div className="flex items-center">
                    <div className="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                    <span>${orders.reduce((sum, o) => sum + o.totalValue, 0).toLocaleString()}</span>
                  </div>
                </div>

                {/* Refresh Button */}
                <button
                  onClick={handleRefresh}
                  disabled={loading}
                  className="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                  title="Refresh pipeline data"
                >
                  <svg className={`w-5 h-5 ${loading ? 'animate-spin' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Filters */}
        <PipelineFilters
          filters={filters}
          onFiltersChange={handleFilterChange}
          isManager={isManager}
          className="px-4 sm:px-6 lg:px-8 py-4"
        />

        {/* Kanban Board */}
        <div className="px-4 sm:px-6 lg:px-8 pb-8">
          <DndContext
            sensors={[]}
            collisionDetection={closestCenter}
            onDragStart={handleDragStart}
            onDragEnd={handleDragEnd}
          >
            <div className="flex space-x-6 overflow-x-auto pb-4">
              {stagesWithMetrics.map((stage) => (
                <SortableContext
                  key={stage.id}
                  id={stage.id}
                  items={ordersByStage[stage.id]?.map(order => order.id) || []}
                  strategy={verticalListSortingStrategy}
                >
                  <PipelineColumn
                    stage={stage}
                    orders={ordersByStage[stage.id] || []}
                    onOrderClick={handleOrderClick}
                    isLoading={isUpdating}
                  />
                </SortableContext>
              ))}
            </div>

            {/* Drag Overlay */}
            <DragOverlay>
              {activeCard ? (
                <PipelineCard
                  order={activeCard}
                  onClick={() => {}}
                  isDragging={true}
                />
              ) : null}
            </DragOverlay>
          </DndContext>
        </div>

        {/* Timeline Modal */}
        {isTimelineOpen && selectedOrderId && (
          <TimelineModal
            orderId={selectedOrderId}
            isOpen={isTimelineOpen}
            onClose={() => {
              setIsTimelineOpen(false);
              setSelectedOrderId(null);
            }}
          />
        )}

        {/* Real-time Notifications */}
        {notifications.length > 0 && (
          <div className="fixed bottom-4 right-4 z-50 space-y-2">
            {notifications.slice(0, 3).map((notification) => (
              <div
                key={notification.id}
                className="bg-white rounded-lg shadow-lg border p-4 max-w-sm animate-slide-up"
                onClick={() => markAsRead(notification.id)}
              >
                <div className="flex items-start">
                  <div className="flex-shrink-0">
                    <div className={`w-2 h-2 rounded-full ${
                      notification.priority === 'urgent' ? 'bg-red-500' : 'bg-blue-500'
                    }`}></div>
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-900">
                      {notification.subject}
                    </p>
                    <p className="text-sm text-gray-500">
                      {notification.message}
                    </p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </ErrorBoundary>
  );
};

export default PipelineDashboard;
